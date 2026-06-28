<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

/**
 * Cache-Definitionen für block_kursfilter.
 *
 * ratelimit: Zählt AJAX-Anfragen je Nutzer innerhalb eines Zeitfensters (F-01).
 * Verwendet application-Store mit kurzer TTL.
 */
$definitions = [
    'ratelimit' => [
        'mode'           => cache_store::MODE_APPLICATION,
        'simplekeys'     => true,
        'simpledata'     => true,
        'ttl'            => 60,   // Entspricht RATE_LIMIT_WINDOW in external.php.
        'invalidationevents' => [],
        'staticacceleration'     => false,
    ],
];
