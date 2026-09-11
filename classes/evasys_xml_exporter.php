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

    /** @var string Shared survey task list key for one export file. */
    private const TASKLIST_KEY = 'STL001';

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
        $participants = [];
        $surveys = [];

        foreach ($requests as $request) {
            $lecturedata = self::build_lecture_data($request, $options, $persons, $participants);
            if ($lecturedata === null) {
                continue;
            }

            $lecture = $dom->createElement('Lecture');
            $lecture->setAttribute('key', $lecturedata['key']);
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

            if (!empty($lecturedata['participantkeys'])) {
                $participantwrapper = $dom->createElement('participants');
                $lecture->appendChild($participantwrapper);
                foreach ($lecturedata['participantkeys'] as $participantkey) {
                    $participantnode = $dom->createElement('participant');
                    $participantwrapper->appendChild($participantnode);
                    self::append_ref($dom, $participantnode, 'Participant', $participantkey);
                }
            }

            $surveykey = 'Survey' . (int) $request->id;
            $surveys[$surveykey] = [
                'form' => $lecturedata['questionnaire'],
                'period' => $options['semester'],
            ];

            $surveyrefwrapper = $dom->createElement('survey');
            $lecture->appendChild($surveyrefwrapper);
            self::append_ref($dom, $surveyrefwrapper, 'Survey', $surveykey);
        }

        if (empty($surveys)) {
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
            self::append_text($dom, $person, 'username', $persondata['username']);
        }

        foreach ($surveys as $surveykey => $surveydata) {
            $survey = $dom->createElement('Survey');
            $survey->setAttribute('key', $surveykey);
            $root->appendChild($survey);

            self::append_text($dom, $survey, 'survey_form', $surveydata['form']);
            self::append_text($dom, $survey, 'survey_period', $surveydata['period']);
            self::append_text($dom, $survey, 'survey_type', 'online');
            self::append_text($dom, $survey, 'survey_verify', '0');

            $surveytasks = $dom->createElement('survey_tasks');
            $survey->appendChild($surveytasks);
            $surveytask = $dom->createElement('survey_task');
            $surveytasks->appendChild($surveytask);
            self::append_ref($dom, $surveytask, 'SurveyTaskList', self::TASKLIST_KEY);
        }

        self::append_task_list($dom, $root, $starttime, $invitetime, $remindertime, $endtime);

        foreach ($participants as $participantkey => $participantdata) {
            $participant = $dom->createElement('Participant');
            $participant->setAttribute('key', $participantkey);
            $root->appendChild($participant);

            self::append_text($dom, $participant, 'email', $participantdata['email']);
            self::append_text($dom, $participant, 'firstname', $participantdata['firstname']);
            self::append_text($dom, $participant, 'lastname', $participantdata['lastname']);
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
     * @param array $participants Collected participant records (by ref).
     * @return array|null Lecture data or null if the request cannot be exported.
     */
    protected static function build_lecture_data(
        \stdClass $request,
        array $options,
        array &$persons,
        array &$participants
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
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
        $orgroot = $category ? $category->get_formatted_name() : '';

        $groupid = (int) $request->groupid;
        $lecturename = format_string($course->fullname, true, ['context' => $coursecontext]);
        if ($groupid) {
            $group = groups_get_group($groupid, 'id, courseid, name', IGNORE_MISSING);
            if ($group && (int) $group->courseid === (int) $request->courseid) {
                $lecturename .= ' (' . format_string($group->name) . ')';
            }
        }

        $short = self::get_course_short($course);
        if ($groupid) {
            $short .= '_G' . $groupid;
        }

        $personkey = 'User' . (int) $teacher->id;
        if (!isset($persons[$personkey])) {
            $persons[$personkey] = [
                'firstname' => $teacher->firstname,
                'lastname' => $teacher->lastname,
                'email' => $teacher->email,
                'username' => $teacher->username,
            ];
        }

        $participantkeys = [];
        $enrolledusers = request_helper::get_student_participants($coursecontext, $groupid);
        foreach ($enrolledusers as $user) {
            if (empty($user->email)) {
                continue;
            }
            if ((int) $user->id === (int) $teacher->id) {
                continue;
            }

            $participantkey = 'P' . (int) $user->id;
            if (!isset($participants[$participantkey])) {
                $participants[$participantkey] = [
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                ];
            }
            $participantkeys[] = $participantkey;
        }

        $questionnaire = $request->lang === 'en'
            ? trim($options['questionnaireen'])
            : trim($options['questionnairede']);

        return [
            'key' => 'Request' . (int) $request->id,
            'personkey' => $personkey,
            'name' => $lecturename,
            'orgroot' => $orgroot,
            'short' => $short,
            'type' => get_string('export_lecture_type_default', 'local_thlevasys'),
            'turnout' => count($enrolledusers),
            'participantkeys' => $participantkeys,
            'questionnaire' => $questionnaire,
        ];
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
     * Convert a datetime-local value to EvaSys format in the user's timezone.
     *
     * @param string $value HTML datetime-local value.
     * @return string EvaSys datetime string.
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
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $parent Parent element.
     * @param string $starttime EvaSys open time.
     * @param string $invitetime EvaSys invite time.
     * @param string $remindertime EvaSys reminder time.
     * @param string $endtime EvaSys end time.
     */
    protected static function append_task_list(
        \DOMDocument $dom,
        \DOMElement $parent,
        string $starttime,
        string $invitetime,
        string $remindertime,
        string $endtime
    ): void {
        $tasklist = $dom->createElement('SurveyTaskList');
        $tasklist->setAttribute('key', self::TASKLIST_KEY);
        $parent->appendChild($tasklist);

        $opensurvey = $dom->createElement('OpenSurveyTask');
        $tasklist->appendChild($opensurvey);
        self::append_text($dom, $opensurvey, 'StartTime', $starttime);

        $invite = $dom->createElement('InviteParticipantsTask');
        $tasklist->appendChild($invite);
        self::append_text($dom, $invite, 'StartTime', $invitetime);
        self::append_text($dom, $invite, 'SendEmail', 'false');
        self::append_text($dom, $invite, 'CombineMail', 'true');

        $remind = $dom->createElement('RemindParticipantsTask');
        $tasklist->appendChild($remind);
        self::append_text($dom, $remind, 'StartTime', $remindertime);
        self::append_text($dom, $remind, 'CombineMail', 'true');

        $close = $dom->createElement('CloseSurveyTask');
        $tasklist->appendChild($close);
        self::append_text($dom, $close, 'StartTime', $endtime);
    }

    /**
     * @param \DOMDocument $dom Document.
     * @param \DOMElement $parent Parent element.
     * @param string $type Reference type.
     * @param string $key Reference key.
     */
    protected static function append_ref(\DOMDocument $dom, \DOMElement $parent, string $type, string $key): void {
        $ref = $dom->createElement('EvaSysRef');
        $ref->setAttribute('type', $type);
        $ref->setAttribute('key', $key);
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
