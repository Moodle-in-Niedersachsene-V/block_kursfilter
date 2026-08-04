<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_kursfilter_search_courses' => [
        'classname'     => 'block_kursfilter_external',
        'methodname'    => 'search_courses',
        'description'   => 'Sucht Kurse nach Filterkriterien',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => '',
    ],
];
