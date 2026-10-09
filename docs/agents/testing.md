# Testing

PHPUnit läuft in einer Moodle-Testumgebung (Moodle 5.1/5.2) und in CI über `.github/workflows/moodle-ci.yml` (MariaDB und PostgreSQL, dazu phpcs, phpdoc, savepoints, mustache, grunt).

- `tests/request_test.php` ruft die öffentlichen Endpunkte über HTTP auf und braucht einen laufenden Testwebserver: `KURSFILTER_WEB_URL=<url> vendor/bin/phpunit --testsuite block_kursfilter_testsuite`.
- Änderungen an `amd/src/` brauchen einen Grunt-Build (`amd/build/`), sonst schlägt der CI-Schritt `grunt` fehl.
- Nach Änderungen an `db/access.php`, `db/services.php`, `db/install.xml` oder `version.php` Moodle-Upgrade ausführen.
