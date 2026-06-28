<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname']                    = 'Course Filter';
$string['block_kursfilter:addinstance']  = 'Add a Course Filter block';
$string['block_kursfilter:myaddinstance']= 'Add a Course Filter block to My page';

$string['label_kursbereich']  = 'Course area';
$string['label_schulform']    = 'School type';
$string['label_fach']         = 'Subject';
$string['label_niveaustufe']  = 'Level';
$string['label_kursname']     = 'Course name';

$string['placeholder_kursname'] = 'Search course name …';
$string['filter_all']           = '– All –';
$string['hint_setfilter']       = 'Set a filter to search for courses.';

$string['btn_reset']      = 'Reset';
$string['btn_opencourse'] = 'Open course';
$string['btn_export']     = 'Export';

$string['settings_schulformen_heading'] = 'School types';
$string['settings_schulformen_desc']    = 'Values shown as chips. Courses must be tagged "schulform:Value".';
$string['settings_schulformen']         = 'School types';
$string['settings_schulformen_help']    = 'One per line. Course must carry tag "schulform:Gymnasium" etc.';

$string['settings_faecher_heading']     = 'Subjects';
$string['settings_faecher']             = 'Subjects';
$string['settings_faecher_help']        = 'One per line. Course must carry tag "fach:Mathematik" etc.';

$string['settings_niveaustufen_heading']= 'Levels';
$string['settings_niveaustufen_desc']   = 'One per line. Course must carry tag "niveaustufe:Klasse 10" etc.';
$string['settings_niveaustufen']        = 'Levels';
$string['settings_niveaustufen_help']   = 'One per line. Freely extensible.';

$string['settings_resultlimit']         = 'Max. results';
$string['settings_resultlimit_help']    = 'Maximum number of courses per search (default: 100).';

// Error messages (F-01)
$string['ratelimitexceeded'] = 'Too many requests. Please wait {$a->seconds} seconds.';
