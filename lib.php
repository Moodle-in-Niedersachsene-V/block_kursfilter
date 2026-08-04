<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Pluginfile-Handler für block_kursfilter.
 * Aktuell keine eigenen Dateibereiche – Platzhalter für künftige Erweiterungen.
 */
function block_kursfilter_pluginfile(
    $course, $cm, $context, $filearea, $args, $forcedownload, array $options = []
): void {
    send_file_not_found();
}
