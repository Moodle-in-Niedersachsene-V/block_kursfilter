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
 * AI connector for block_kursfilter.
 *
 * Supports Ollama (local) and Claude API as interchangeable backends.
 * Course data is anonymised before sending: only fullname, shortname,
 * category name and the first 800 characters of the plain-text summary
 * are transmitted. No user IDs, teacher names, enrolment data,
 * timestamps or Moodle-internal identifiers leave the server.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter;

/**
 * Sends anonymised course data to a configured AI backend and
 * returns a list of suggested tags (schulform, fach, niveaustufe).
 */
class ai_connector {
    /** @var string Active backend: 'ollama' or 'claude'. */
    private string $backend;

    /** @var array Plugin configuration values. */
    private array $config;

    /**
     * Constructor: loads plugin config once.
     */
    public function __construct() {
        $this->backend = get_config('block_kursfilter', 'ai_backend') ?: 'claude';
        $this->config  = (array) get_config('block_kursfilter');
    }

    /**
     * Generates tag suggestions for a course.
     *
     * Only fullname, shortname, category name and a truncated plain-text
     * summary are sent. No user data, teacher names, enrolment information
     * or internal IDs leave the server.
     *
     * @param string $fullname     Kursname.
     * @param string $shortname    Kurzname des Kurses.
     * @param string $summary      Kursbeschreibung (HTML wird zu Plain-Text).
     * @param string $categoryname Name der Kurskategorie.
     * @return string[]            Vorgeschlagene Tags, gegliedert nach Typ.
     */
    public function suggest_tags(
        string $fullname,
        string $shortname,
        string $summary,
        string $categoryname
    ): array {
        // Datensparsamkeit: HTML entfernen, auf 800 Zeichen kuerzen.
        $plaintext = strip_tags($summary);
        $plaintext = html_entity_decode($plaintext, ENT_QUOTES, 'UTF-8');
        $plaintext = preg_replace('/\s+/', ' ', trim($plaintext));
        if (mb_strlen($plaintext) > 800) {
            $plaintext = mb_substr($plaintext, 0, 800) . ' …';
        }

        $schulformen = $this->get_configured_list('schooltypes');
        $faecher = $this->get_configured_list('subjects');
        $niveaustufen = $this->get_configured_list('levels');

        $prompt = $this->build_prompt(
            $fullname,
            $shortname,
            $plaintext,
            $categoryname,
            $schulformen,
            $faecher,
            $niveaustufen
        );

        $raw = $this->backend === 'claude'
            ? $this->call_claude($prompt)
            : $this->call_ollama($prompt);

        return $this->parse_tags($raw);
    }

    /**
     * Returns a configured multi-line list as a trimmed string array.
     *
     * @param string $key Config key name.
     * @return string[]
     */
    private function get_configured_list(string $key): array {
        $raw = get_config('block_kursfilter', $key) ?: '';
        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }

    /**
     * Builds the prompt sent to the AI.
     *
     * @param string   $fullname     Kursname.
     * @param string   $shortname    Kurzname.
     * @param string   $summary      Bereinigter Beschreibungstext.
     * @param string   $category     Kategoriename.
     * @param string[] $schulformen  Konfigurierte Schulformen.
     * @param string[] $faecher      Konfigurierte Faecher.
     * @param string[] $niveaustufen Konfigurierte Niveaustufen.
     * @return string                Fertiger Prompt.
     */
    private function build_prompt(
        string $fullname,
        string $shortname,
        string $summary,
        string $category,
        array $schulformen,
        array $faecher,
        array $niveaustufen
    ): string {
        $sflist = !empty($schulformen) ? implode(', ', $schulformen) : '(keine Vorgabe)';
        $falist = !empty($faecher) ? implode(', ', $faecher) : '(keine Vorgabe)';
        $nvlist = !empty($niveaustufen) ? implode(', ', $niveaustufen) : '(keine Vorgabe)';

        return "Du bist ein Verschlagwortungs-Assistent fuer Moodle-Lernmaterialien an deutschen Schulen."
            . " Analysiere den folgenden Kurs und weise ihm passende Tags zu."
            . " Verwende NUR Werte aus den vorgegebenen Listen – erfinde keine neuen."
            . "\n\nKursname: {$fullname}"
            . "\nKurzname: {$shortname}"
            . "\nKategorie: {$category}"
            . "\nBeschreibung: {$summary}"
            . "\n\nMoegliche Schulformen (Tag-Praefix 'schulform:'): {$sflist}"
            . "\nMoegliche Faecher (Tag-Praefix 'fach:'): {$falist}"
            . "\nMoegliche Niveaustufen (Tag-Praefix 'niveaustufe:'): {$nvlist}"
            . "\n\nAntworte AUSSCHLIESSLICH mit einer kommaseparierten Liste im Format:"
            . " schulform:Wert, fach:Wert, niveaustufe:Wert"
            . "\nWaehle nur passende Tags. Lass Kategorien weg, wenn kein Wert passt."
            . "\nKeine Erklaerungen, keine Nummerierung, keine Anführungszeichen.";
    }

    /**
     * Calls the local Ollama API.
     *
     * @param string $prompt Prompt text.
     * @return string Raw model response.
     */
    private function call_ollama(string $prompt): string {
        $url = rtrim($this->config['ai_ollama_url'] ?? 'http://localhost:11434', '/');
        $model = $this->config['ai_ollama_model'] ?? 'gemma3:4b';

        $payload = json_encode([
            'model'   => $model,
            'prompt'  => $prompt,
            'stream'  => false,
            'options' => ['temperature' => 0.2, 'num_predict' => 120],
        ]);

        $response = $this->http_post(
            $url . '/api/generate',
            $payload,
            ['Content-Type: application/json'],
            30
        );

        if ($response === false) {
            return '';
        }

        $data = json_decode($response, true);
        return $data['response'] ?? '';
    }

    /**
     * Calls the Anthropic Claude API.
     *
     * Only anonymised course content is sent – no Moodle user data,
     * no teacher names, no enrolment information.
     *
     * @param string $prompt Prompt text.
     * @return string Raw model response.
     */
    private function call_claude(string $prompt): string {
        $apikey = get_config('block_kursfilter', 'ai_claude_apikey') ?: '';
        $model = $this->config['ai_claude_model'] ?? 'claude-haiku-4-5-20251001';

        if (empty($apikey)) {
            return '';
        }

        $payload = json_encode([
            'model'      => $model,
            'max_tokens' => 150,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $response = $this->http_post(
            'https://api.anthropic.com/v1/messages',
            $payload,
            [
                'Content-Type: application/json',
                'x-api-key: ' . $apikey,
                'anthropic-version: 2023-06-01',
            ],
            30
        );

        if ($response === false) {
            return '';
        }

        $data = json_decode($response, true);
        return $data['content'][0]['text'] ?? '';
    }

    /**
     * Parses the raw AI response into a validated tag list.
     *
     * Only tags matching the pattern 'prefix:value' with known prefixes
     * (schulform, fach, niveaustufe) are accepted.
     *
     * @param string $raw Raw model output.
     * @return string[]   Validated, deduplicated tag list.
     */
    private function parse_tags(string $raw): array {
        if (empty(trim($raw))) {
            return [];
        }

        $allowed = ['schulform', 'fach', 'niveaustufe'];
        $tags = [];

        foreach (array_map('trim', explode(',', $raw)) as $tag) {
            if (!preg_match('/^([a-z]+):(.+)$/u', $tag, $m)) {
                continue;
            }
            [, $prefix, $value] = $m;
            if (!in_array($prefix, $allowed, true)) {
                continue;
            }
            $value = trim($value);
            if (mb_strlen($value) < 2 || mb_strlen($value) > 50) {
                continue;
            }
            $tags[] = $tag;
        }

        return array_values(array_unique($tags));
    }

    /**
     * HTTP POST using Moodle's \curl class.
     *
     * Uses \curl instead of raw cURL so that Moodle's proxy settings
     * and SSRF-protection are respected.
     *
     * Note: \curl::setHeader() requires 'Name: value' strings, not an
     * associative array — the latter silently drops headers.
     *
     * @param string   $url     Target URL.
     * @param string   $payload JSON payload.
     * @param string[] $headers HTTP headers as 'Name: value' strings.
     * @param int      $timeout Timeout in seconds.
     * @return string|false Response body or false on error.
     */
    private function http_post(
        string $url,
        string $payload,
        array $headers,
        int $timeout
    ): string|false {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        // Moodle's \curl::setHeader() expects 'Name: value' strings, not an associative array.
        $curl = new \curl();
        $curl->setHeader($headers);

        $options = [
            'CURLOPT_TIMEOUT'        => $timeout,
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_POST'           => true,
            'CURLOPT_POSTFIELDS'     => $payload,
        ];

        $result = $curl->post($url, $payload, $options);

        if ($curl->get_errno() !== 0) {
            mtrace('block_kursfilter ai_connector: cURL error ' . $curl->get_errno() . ' – ' . $curl->error);
            return false;
        }

        // HTTP-Fehler erkennen (z. B. 401 Unauthorized, 400 Bad Request).
        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;
        if ($httpcode >= 400) {
            mtrace('block_kursfilter ai_connector: HTTP ' . $httpcode . ' von ' . $url . ' – ' . $result);
            return false;
        }

        return $result;
    }
}
