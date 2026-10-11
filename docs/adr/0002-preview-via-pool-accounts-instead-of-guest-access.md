# Vorschau über Pool-Konten statt Gastzugang

Besucher sehen einen öffentlichen Kurs über ein **Pool-Konto**, nicht über den Moodle-Gastzugang. Das Plugin legt eine feste Zahl Pool-Konten an (Einstellung `poolsize`, Standard 10, Obergrenze 50). `preview.php` überlässt dem Besucher genau eines davon, solange die Vorschau läuft (**belegt**), und meldet ihn damit an.

Der Gastzugang genügt nicht, weil er pro Kurs eingeschaltet werden müsste, keine eigene Rolle trägt und Besucher nicht voneinander trennt. Pool-Konten tragen dagegen die **Pool-Rolle**: Sie sieht den Kurs wie eine Lehrkraft ohne Bearbeitungsrecht, aber ohne `moodle/course:viewparticipants`, `moodle/site:viewuseridentity` und `moodle/grade:viewall`. Teilnehmende, Identitäten und Noten bleiben verborgen.

- Eine echte Anmeldung wird nie durch ein Pool-Konto ersetzt.
- Ist kein Konto frei, zeigt `preview.php` einen Hinweis; es entstehen keine Konten auf Vorrat.
- Nur `POST` mit gültigem Sesskey startet eine Vorschau.

## Consequences

Die Zahl gleichzeitiger Vorschauen ist durch die Poolgröße begrenzt. Der Verein muss die Größe an die Last anpassen. Die Pool-Rolle ist Teil des Plugins; neue Moodle-Capabilities mit Personenbezug müssen dort geprüft und gegebenenfalls gesperrt werden.
