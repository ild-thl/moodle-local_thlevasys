<?php
/**
 * Anonymisiert personenbezogene Felder in einer EvaSys-XML-Importdatei.
 *
 * Verwendung (PowerShell / CMD):
 *   php anonymize_evasys_xml.php "C:\Pfad\eingabe.xml"
 *   php anonymize_evasys_xml.php "C:\Pfad\eingabe.xml" "C:\Pfad\ausgabe.xml"
 *
 * Ohne zweiten Parameter wird neben der Eingabedatei eine Datei
 * mit Suffix "_anonymized.xml" erzeugt.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Dieses Skript bitte nur auf der Kommandozeile ausführen.\n");
    exit(1);
}

if ($argc < 2) {
    fwrite(STDERR, "Verwendung: php anonymize_evasys_xml.php <eingabe.xml> [ausgabe.xml]\n");
    exit(1);
}

$input = $argv[1];
if (!is_readable($input)) {
    fwrite(STDERR, "Datei nicht lesbar: {$input}\n");
    exit(1);
}

$output = $argv[2] ?? preg_replace('/(\.xml)?$/i', '_anonymized.xml', $input);

/** Textknoten, deren Inhalt anonymisiert wird. */
$sensitivetags = [
    'firstname',
    'lastname',
    'email',
    'username',
    'title',
    'address',
    'custom1',
    'custom2',
    'custom3',
    'custom4',
    'custom5',
    'ParticipantEmail',
    'SenderEmail',
    'SenderName',
    'EmailSubject',
    'EmailText',
];

/** @var array<string, string> Bereits gesehene Originalwerte => Ersatzwerte. */
$replacements = [];
$counters = [];

$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;

$previous = libxml_use_internal_errors(true);
if (!$dom->load($input)) {
    foreach (libxml_get_errors() as $error) {
        fwrite(STDERR, trim($error->message) . "\n");
    }
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    exit(1);
}
libxml_use_internal_errors($previous);

$xpath = new DOMXPath($dom);

foreach ($sensitivetags as $tag) {
    foreach ($xpath->query('//' . $tag) as $node) {
        if (!($node instanceof DOMElement)) {
            continue;
        }
        $original = trim($node->textContent);
        if ($original === '') {
            continue;
        }
        $node->nodeValue = '';
        $node->appendChild($dom->createTextNode(anonymize_value($tag, $original)));
    }
}

// EvaSysRef-Keys für Person/Participant/Recipient bleiben strukturell erhalten,
// aber ggf. in Attributen stehende Klartext-Keys werden nicht angefasst.

if ($dom->save($output) === false) {
    fwrite(STDERR, "Konnte Ausgabe nicht speichern: {$output}\n");
    exit(1);
}

fwrite(STDOUT, "Anonymisierte Datei gespeichert:\n{$output}\n");
exit(0);

/**
 * Liefert einen stabilen Ersatzwert für denselben Originalwert.
 */
function anonymize_value(string $tag, string $original): string {
    global $replacements, $counters;

    $mapkey = strtolower($tag) . '|' . mb_strtolower($original);
    if (isset($replacements[$mapkey])) {
        return $replacements[$mapkey];
    }

    $tagkey = strtolower($tag);
    $counters[$tagkey] = ($counters[$tagkey] ?? 0) + 1;
    $n = $counters[$tagkey];

    switch ($tagkey) {
        case 'firstname':
            $value = 'Vorname' . $n;
            break;
        case 'lastname':
            $value = 'Nachname' . $n;
            break;
        case 'username':
            $value = 'user' . $n;
            break;
        case 'email':
        case 'participantemail':
        case 'senderemail':
            $value = 'person' . $n . '@example.com';
            break;
        case 'title':
            $value = '';
            break;
        case 'address':
            $value = 'Musterstrasse ' . $n . ', 12345 Musterstadt';
            break;
        case 'sendername':
            $value = 'System';
            break;
        case 'emailsubject':
            $value = 'Betreff (anonymisiert)';
            break;
        case 'emailtext':
            $value = 'Text (anonymisiert).';
            break;
        case 'custom1':
        case 'custom2':
        case 'custom3':
        case 'custom4':
        case 'custom5':
            $value = 'custom' . $n;
            break;
        default:
            $value = 'anon' . $n;
            break;
    }

    $replacements[$mapkey] = $value;
    return $value;
}
