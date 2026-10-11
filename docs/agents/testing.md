# Testing

PHPUnit läuft in einer Moodle-Testumgebung (Moodle 5.1/5.2) und in CI über `.github/workflows/moodle-ci.yml` (MariaDB und PostgreSQL, dazu phpcs, phpdoc, savepoints, mustache, grunt).

- `tests/request_test.php` ruft die öffentlichen Endpunkte über HTTP auf und braucht einen laufenden Testwebserver: `KURSFILTER_WEB_URL=<url> vendor/bin/phpunit --testsuite block_kursfilter_testsuite`.

## Testwebserver für die Request-Tests

`tests/request_test.php` sendet echte HTTP-Anfragen. Der Webserver muss dieselben Tabellen lesen wie PHPUnit (Präfix `phpunit_prefix`, Datenverzeichnis `phpunit_dataroot`), nicht die der Live-Installation. Dafür gibt es keine eigene Moodle-Funktion; die `config.php` der Testinstallation wird umgeleitet, gesteuert über die Umgebungsvariable `KURSFILTER_WEB_TESTS` (Wert: Basis-URL des Testwebservers).

1. In `config.php` vor `require_once(__DIR__ . '/lib/setup.php');` einfügen (CI: `kf-web-config.php`, per `sed` eingebunden):

   ```php
   if (getenv('KURSFILTER_WEB_TESTS')) {
       $CFG->prefix = $CFG->phpunit_prefix;
       $CFG->dataroot = $CFG->phpunit_dataroot;
       $CFG->wwwroot = getenv('KURSFILTER_WEB_TESTS');
       unset($CFG->phpunit_prefix, $CFG->phpunit_dataroot);
   }
   ```

   Das `unset` verhindert, dass Moodle den Webserver als PHPUnit-Umgebung erkennt.
2. Webserver mit gesetzter Variablen starten, im Moodle-Verzeichnis: `KURSFILTER_WEB_TESTS=http://127.0.0.1:8080 php -S 127.0.0.1:8080 -t public`.
3. PHPUnit initialisieren (`php admin/tool/phpunit/cli/init.php`) und **ohne** `KURSFILTER_WEB_TESTS` starten, mit der URL in `KURSFILTER_WEB_URL`:
   `KURSFILTER_WEB_URL=http://127.0.0.1:8080 vendor/bin/phpunit --testsuite block_kursfilter_testsuite`.
   Ist `KURSFILTER_WEB_TESTS` im PHPUnit-Prozess gesetzt, würde die Umleitung auch ihn betreffen; ohne `KURSFILTER_WEB_URL` werden die Request-Tests übersprungen.

Die Tests schließen vor dem Aufruf ihre Transaktion, damit der Webserver die Testdaten sieht, und setzen die Datenbank danach vollständig zurück (der Webserver schreibt an PHPUnit vorbei).

- Änderungen an `amd/src/` brauchen einen Grunt-Build (`amd/build/`), sonst schlägt der CI-Schritt `grunt` fehl.
- Nach Änderungen an `db/access.php`, `db/services.php`, `db/install.xml` oder `version.php` Moodle-Upgrade ausführen.
