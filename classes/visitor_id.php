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

namespace block_kursfilter;

/**
 * Visitor ID (Besucher-Kennung): the anonymous cookie that identifies a browser.
 *
 * Single place for cookie name, format, lifetime and attributes (ADR 0003, 0005).
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class visitor_id {
    /** Cookie name stored in the browser. Must not change (ADR 0003). */
    const COOKIE_NAME = 'kf_rater_id';

    /**
     * Cookie options with a lifetime of one year, counted from now (ADR 0005).
     *
     * @return array Options for setcookie().
     */
    public static function get_cookie_options(): array {
        return [
            'expires'  => time() + YEARSECS,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    /**
     * Visitor ID of a returning visitor, extended by one year. Call it once per request.
     *
     * @return string|null The visitor ID, or null without a valid cookie (nothing is set then).
     */
    public static function renew(): ?string {
        $hash = self::read();
        if ($hash !== null && !headers_sent()) {
            setcookie(self::COOKIE_NAME, $hash, self::get_cookie_options());
        }
        return $hash;
    }

    /**
     * Visitor ID of this browser; a new one is created (with a one-year cookie) if none is valid.
     *
     * @return string SHA-256 hash identifying this browser.
     */
    public static function get_or_create(): string {
        $hash = self::renew();
        if ($hash === null) {
            $hash = hash('sha256', bin2hex(random_bytes(32)));
            if (!headers_sent()) {
                setcookie(self::COOKIE_NAME, $hash, self::get_cookie_options());
            }
            // Makes the new ID visible to later calls of this request.
            // phpcs:ignore moodle.Commenting.InlineComment.NotCapital,moodle.Commenting.InlineComment.InvalidEndChar -- Semgrep marker syntax.
            $_COOKIE[self::COOKIE_NAME] = $hash; // nosemgrep: moodle-superglobal-direkt
        }
        return $hash;
    }

    /**
     * Valid visitor ID sent by the browser, if any.
     *
     * @return string|null The valid visitor ID from the cookie, or null.
     */
    private static function read(): ?string {
        // Moodle offers no *_param() for cookies; only 64 hex characters pass.
        // phpcs:ignore moodle.Commenting.InlineComment.NotCapital,moodle.Commenting.InlineComment.InvalidEndChar -- Semgrep marker syntax.
        $raw = $_COOKIE[self::COOKIE_NAME] ?? ''; // nosemgrep: moodle-superglobal-direkt
        return is_string($raw) && preg_match('/^[0-9a-f]{64}$/', $raw) ? $raw : null;
    }
}
