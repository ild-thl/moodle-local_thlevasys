<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Builds EvaSys XML import files from evaluation requests.
 *
 * Lecture/Person content follows the THL import pattern; scheduling uses the
 * current EvaSys SurveyTaskList schema.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates XML documents for EvaSys import.
 */
class evasys_xml_exporter {

    /** @var string Default mail sender name. */
    private const SENDER_NAME = 'Evaluationsteam';

    /** @var string Default mail sender address. */
    private const SENDER_EMAIL = 'evaluation@th-luebeck.de';

    /** @var string Default mail subject. */
    private const MAIL_SUBJECT = 'Evaluation [SURVEY]';

    /**
     * Build an EvaSys XML document for all requests in the current period.
     *
     * @param array $options Export options from the admin form.
     * @return string XML document.
     */
    public static function generate(array $options): string {
        self::validate_options($options);

        $requests = request_repository::get_requests_in_period();
        if (empty($requests)) {
            throw new \moodle_exception('error_export_norequests', 'local_thlevasys');
        }

        $starttime = self::format_evasys_datetime($options['evaluationstart']);
        $remindertime = self::format_evasys_datetime($options['evaluationreminder']);
        $endtime = self::format_evasys_datetime($options['evaluationend']);

        if ($remindertime <= $starttime) {
            throw new \moodle_exception('error_export_reminderbeforestart', 'local_thlevasys');
        }
        if ($endtime <= $remindertime) {
            throw new \moodle_exception('error_export_endbeforereminder', 'local_thlevasys');
        }

        $invitetime = self::add_minutes_to_evasys_datetime($starttime, 1);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('EvaSys');
        $dom->appendChild($root);

        $persons = [];
        $recipients = [];
        $tasklists = [];
        $exported = 0;

        foreach ($requests as $request) {
            $lecturedata = self::build_lecture_data($request, $options, $persons, $recipients);
            if ($lecturedata === null) {
                continue;
            }

            $requestid = (int) $request->id;
            $lecturekey = 'L' . $requestid;
            $surveykey = 'S' . $requestid;
            $tasklistkey = 'STL' . $requestid;

            $lecture = $dom->createElement('Lecture');
            $lecture->setAttribute('key', $lecturekey);
            $root->appendChild($lecture);

            $dozs = $dom->createElement('dozs');
            $lecture->appendChild($dozs);
            $doz = $dom->createElement('doz');
            $dozs->appendChild($doz);
            self::append_ref($dom, $doz, 'Person', $lecturedata['personkey']);

            self::append_text($dom, $lecture, 'name', $lecturedata['name']);
            self::append_text($dom, $lecture, 'orgroot', $lecturedata['orgroot']);
            self::append_text($dom, $lecture, 'short', $lecturedata['short']);
            self::append_text($dom, $lecture, 'period', $options['semester']);
            self::append_text($dom, $lecture, 'type', $lecturedata['type']);
            self::append_text($dom, $lecture, 'turnout', (string) $lecturedata['turnout']);
            self::append_text($dom, $lecture, 'p_o_study', $lecturedata['p_o_study']);
            self::append_text($dom, $lecture, 'coursefield1', $lecturedata['coursefield1']);
            self::append_text($dom, $lecture, 'coursefield2', $lecturedata['coursefield2']);
            self::append_text($dom, $lecture, 'coursefield3', $lecturedata['coursefield3']);
            self::append_text($dom, $lecture, 'coursefield4', $lecturedata['coursefield4']);

            $surveyrefwrapper = $dom->createElement('survey');
            $lecture->appendChild($surveyrefwrapper);
            self::append_ref($dom, $surveyrefwrapper, 'Survey', $surveykey);

            $survey = $dom->createElement('Survey');
            $survey->setAttribute('key', $surveykey);
            $root->appendChild($survey);

            self::append_text($dom, $survey, 'survey_form', $lecturedata['questionnaire']);
            self::append_text($dom, $survey, 'survey_type', 'online');
            self::append_text($dom, $survey, 'survey_period', $options['semester']);
            self::append_text($dom, $survey, 'survey_verify', '0');

            $surveytasks = $dom->createElement('survey_tasks');
            $survey->appendChild($surveytasks);
            $surveytask = $dom->createElement('survey_task');
            $surveytasks->appendChild($surveytask);
            self::append_ref($dom, $surveytask, 'SurveyTaskList', $tasklistkey);

            $tasklists[] = [
                'key' => $tasklistkey,
                'recipientkeys' => $lecturedata['recipientkeys'],
            ];
            $exported++;
        }

        if ($exported === 0) {
            throw new \moodle_exception('error_export_norequests', 'local_thlevasys');
        }

        foreach ($persons as $personkey => $persondata) {
            $person = $dom->createElement('Person');
            $person->setAttribute('key', $personkey);
            $root->appendChild($person);

            self::append_text($dom, $person, 'firstname', $persondata['firstname']);
            self::append_text($dom, $person, 'lastname', $persondata['lastname']);
            if (!empty($persondata['email'])) {
                self::append_text($dom, $person, 'email', $persondata['email']);
            }
        }

        foreach ($tasklists as $tasklist) {
            self::append_task_list(
                $dom,
                $root,
                $tasklist['key'],
                $starttime,
                $invitetime,
                $remindertime,
                $endtime,
                $tasklist['recipientkeys']
            );
        }

        foreach ($recipients as $recipientkey => $recipientdata) {
            $recipient = $dom->createElement('Recipient');
            $recipient->setAttribute('key', $recipientkey);
            $root->appendChild($recipient);
            self::append_text($dom, $recipient, 'email', $recipientdata['email']);
        }

        return $dom->saveXML();
    }

    /**
     * @param array $options Export options.
     */
    protected static function validate_options(array $options): void {
        if (trim((string) ($options['semester'] ?? '')) === '') {
            throw new \moodle_exception('error_export_semesterrequired', 'local_thlevasys');
        }
        if (trim((string) ($options['questionnairede'] ?? '')) === '') {
            throw new \moodle_exception('error_export_questionnaire_de_required', 'local_thlevasys');
        }
        if (trim((string) ($options['questionnaireen'] ?? '')) === '') {
            throw new \moodle_exception('error_export_questionnaire_en_required', 'local_thlevasys');
        }
        foreach (['evaluationstart', 'evaluationreminder', 'evaluationend'] as $field) {
            if (trim((string) ($options[$field] ?? '')) === '') {
                throw new \moodle_exception('error_export_daterequired', 'local_thlevasys');
            }
        }
    }

    /**
     * @param \stdClass $request Request record.
     * @param array $options Export options.
     * @param array $persons Collected person records (by ref).
     * @param array $recipients Collected recipient records (by ref).
     * @return array|null Lecture data or null if the request cannot be exported.
     */
    protected static function build_lecture_data(
        \stdClass $request,
        array $options,
        array &$persons,
        array &$recipients
    ): ?array {
        $course = get_course($request->courseid, false);
        if (!$course) {
            return null;
        }

        $teacher = \core_user::get_user($request->editingteacher, '*', IGNORE_MISSING);
        if (!$teacher) {
            return null;
        }

        $coursecontext = \context_course::instance($request->courseid);
        $groupid = (int) $request->groupid;
        $lecturename = format_string($course->fullname, true, ['context' => $coursecontext]);
        if ($groupid) {
            $group = groups_get_group($groupid, 'id, courseid, name', IGNORE_MISSING);
            if ($group && (int) $group->courseid === (int) $request->courseid) {
                $lecturename .= ' (' . format_string($group->name) . ')';
            }
        }

        $personkey = 'P' . (int) $teacher->id;
        if (!isset($persons[$personkey])) {
            $persons[$personkey] = [
                'firstname' => $teacher->firstname,
                'lastname' => $teacher->lastname,
                'email' => $teacher->email,
            ];
        }

        $recipientkeys = [];
        $enrolledusers = request_helper::get_student_participants($coursecontext, $groupid);
        foreach ($enrolledusers as $user) {
            if (empty($user->email)) {
                continue;
            }
            if ((int) $user->id === (int) $teacher->id) {
                continue;
            }

            $recipientkey = 'R' . (int) $user->id;
            if (!isset($recipients[$recipientkey])) {
                $recipients[$recipientkey] = [
                    'email' => $user->email,
                ];
            }
            $recipientkeys[] = $recipientkey;
        }

        $questionnaire = $request->lang === 'en'
            ? trim($options['questionnaireen'])
            : trim($options['questionnairede']);

        return [
            'personkey' => $personkey,
            'name' => $lecturename,
            'orgroot' => self::get_faculty_name((int) $course->category),
            'short' => self::get_course_short($course),
            'type' => get_string('export_lecture_type_default', 'local_thlevasys'),
            'turnout' => count($enrolledusers),
            'p_o_study' => request_helper::get_studiengang_idnumber((int) $course->category),
            'coursefield1' => $request->lang === 'en' ? 'englisch' : 'deutsch',
            'coursefield2' => 'Online',
            'coursefield3' => (string) (int) $request->courseid,
            'coursefield4' => (string) $groupid,
            'recipientkeys' => $recipientkeys,
            'questionnaire' => $questionnaire,
        ];
    }

    /**
     * Faculty (top-level category) name for orgroot.
     *
     * @param int $categoryid Course category id.
     * @return string
     */
    protected static function get_faculty_name(int $categoryid): string {
        $category = \core_course_category::get($categoryid, IGNORE_MISSING, true);
        if (!$category) {
            return '';
        }

        $pathids = array_values(array_filter(array_map('intval', explode('/', trim($category->path, '/')))));
        if (empty($pathids)) {
            return $category->get_formatted_name();
        }

        $faculty = \core_course_category::get($pathids[0], IGNORE_MISSING, true);
        return $faculty ? $faculty->get_formatted_name() : $category->get_formatted_name();
    }

    /**
     * @param \stdClass $course Course record.
     * @return string Short identifier for EvaSys.
     */
    protected static function get_course_short(\stdClass $course): string {
        $short = trim((string) $course->idnumber);
        if ($short === '') {
            $short = trim((string) $course->shortname);
        }
        if ($short === '') {
            $short = 'C' . (int) $course->id;
        }

        return $short;
    }

    /**
     * Convert a datetime-local value to EvaSys ISO datetime format.
     *
     * @param string $value HTML datetime-local value.
     * @return string Datetime string (Y-m-d\TH:i:s).
     */
    protected static function format_evasys_datetime(string $value): string {
        $value = trim($value);
        $datetime = \DateTime::createFromFormat(
            'Y-m-d\TH:i',
            $value,
            \core_date::get_user_timezone_object()
        );
        if (!$datetime) {
            throw new \moodle_exception('error_export_daterequired', 'local_thlevasys');
        }

        return $datetime->format('Y-m-d\TH:i:s');
    }

    /**
     * Add minutes to an EvaSys datetime string.
     *
     * @param string $value EvaSys datetime.
     * @param int $minutes Minutes to add.
     * @return string
     */
    protected static function add_minutes_to_evasys_datetime(string $value, int $minutes): string {
        $datetime = \DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
        if (!$datetime) {
            throw new \moodle_exception('error_export_daterequired', 'local_thlevasys');
        }
        $datetime->modify('+' . $minutes . ' minutes');

        return $datetime->format('Y-m-d\TH:i:s');
    }

    /**
     * Append a SurveyTaskList for one survey.
     *
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $parent Parent element.
     * @param string $tasklistkey Task list key.
     * @param string $starttime Open time.
     * @param string $invitetime Invite time.
     * @param string $remindertime Reminder time.
     * @param string $endtime Close time.
     * @param string[] $recipientkeys Recipient keys for this lecture.
     */
    protected static function append_task_list(
        \DOMDocument $dom,
        \DOMElement $parent,
        string $tasklistkey,
        string $starttime,
        string $invitetime,
        string $remindertime,
        string $endtime,
        array $recipientkeys
    ): void {
        $tasklist = $dom->createElement('SurveyTaskList');
        $tasklist->setAttribute('key', $tasklistkey);
        $parent->appendChild($tasklist);

        $opensurvey = $dom->createElement('OpenSurveyTask');
        $tasklist->appendChild($opensurvey);
        self::append_text($dom, $opensurvey, 'StartTime', $starttime);

        $invite = $dom->createElement('InviteParticipantsTask');
        $tasklist->appendChild($invite);
        self::append_text($dom, $invite, 'StartTime', $invitetime);
        self::append_text($dom, $invite, 'SendEmail', 'true');
        self::append_text($dom, $invite, 'CombineMail', 'true');
        self::append_text($dom, $invite, 'SenderName', self::SENDER_NAME);
        self::append_text($dom, $invite, 'SenderEmail', self::SENDER_EMAIL);
        self::append_text($dom, $invite, 'EmailSubject', self::MAIL_SUBJECT);
        self::append_text($dom, $invite, 'EmailText', self::get_dispatch_mail_text());

        $inviterecipients = $dom->createElement('Recipients');
        $invite->appendChild($inviterecipients);
        foreach ($recipientkeys as $recipientkey) {
            $recipient = $dom->createElement('recipient');
            $inviterecipients->appendChild($recipient);
            self::append_ref($dom, $recipient, 'Recipient', $recipientkey);
        }

        $remind = $dom->createElement('RemindParticipantsTask');
        $tasklist->appendChild($remind);
        self::append_text($dom, $remind, 'StartTime', $remindertime);
        self::append_text($dom, $remind, 'CombineMail', 'true');
        self::append_text($dom, $remind, 'SenderName', self::SENDER_NAME);
        self::append_text($dom, $remind, 'SenderEmail', self::SENDER_EMAIL);
        self::append_text($dom, $remind, 'EmailSubject', self::MAIL_SUBJECT);
        self::append_text($dom, $remind, 'EmailText', self::get_remind_mail_text());

        $close = $dom->createElement('CloseSurveyTask');
        $tasklist->appendChild($close);
        self::append_text($dom, $close, 'StartTime', $endtime);
    }

    /**
     * Invitation mail body (THL template).
     *
     * @return string
     */
    protected static function get_dispatch_mail_text(): string {
        return "Liebe Studierende,\\n\n" .
            "\\n\n" .
            "Sie sind hiermit zur Stimmabgabe bei einer Online-Befragung der Technischen Hochschule Lübeck berechtigt. " .
            "Ihre Meinung ist uns wichtig, wir freuen uns über Ihre Rückmeldung.\\n " .
            "Lehrveranstaltung: [SURVEY] \\n Lehrperson: [FIRSTNAME] [SURNAME]\\n\n" .
            "Bitte folgen Sie dem Link, um den Fragebogen zu öffnen.\\n\n" .
            "\\n\n" .
            "[DIRECT_ONLINE_LINK]\\n\n" .
            "\\n\n" .
            "\\n\n" .
            "Mit freundlichen Grüßen,\\n\n" .
            "\\n\n" .
            "Das Evaluationsteam der Technischen Hochschule Lübeck\n" .
            "\\n \n" .
            "---------------------- \n" .
            "\\n \n" .
            "HINWEIS: Diese E-Mail wurde automatisch generiert. Die in dieser E-Mail angegebene TAN ist nicht mit Ihrer " .
            "Person verbunden. Ihre Stimmabgabe erfolgt anonym.";
    }

    /**
     * Reminder mail body (THL template).
     *
     * @return string
     */
    protected static function get_remind_mail_text(): string {
        return "Liebe Studierende,\\n\n" .
            "\\n\n" .
            "wir möchten Sie daran erinnern, dass Sie noch eine Woche bei einer Online-Befragung der Technischen " .
            "Hochschule Lübeck teilnehmen können. Ihre Meinung ist uns wichtig, wir freuen uns über Ihre Rückmeldung.\\n " .
            "Lehrveranstaltung: [SURVEY] \\n Lehrperson: [FIRSTNAME] [SURNAME]\\n\n" .
            "Bitte folgen Sie dem Link, um den Fragebogen zu öffnen.\\n\n" .
            "\\n\n" .
            "[DIRECT_ONLINE_LINK]\\n\n" .
            "\\n\n" .
            "\\n\n" .
            "Mit freundlichen Grüßen,\\n\n" .
            "\\n\n" .
            "Das Evaluationsteam der Technischen Hochschule Lübeck\n" .
            "\\n \n" .
            "---------------------- \n" .
            "\\n \n" .
            "HINWEIS: Diese E-Mail wurde automatisch generiert. Die in dieser E-Mail angegebene TAN ist nicht mit Ihrer " .
            "Person verbunden. Ihre Stimmabgabe erfolgt anonym.";
    }

    /**
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $parent Parent element.
     * @param string $type Reference type.
     * @param string $key Reference key.
     */
    protected static function append_ref(\DOMDocument $dom, \DOMElement $parent, string $type, string $key): void {
        $ref = $dom->createElement('EvaSysRef');
        $ref->setAttribute('key', $key);
        $ref->setAttribute('type', $type);
        $parent->appendChild($ref);
    }

    /**
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $parent Parent element.
     * @param string $name Element name.
     * @param string $value Element value.
     */
    protected static function append_text(\DOMDocument $dom, \DOMElement $parent, string $name, string $value): void {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode($value));
        $parent->appendChild($element);
    }
}
