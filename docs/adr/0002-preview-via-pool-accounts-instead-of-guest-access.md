# Vorschau über Pool-Konten statt Gastzugang

Besucher sehen einen öffentlichen Kurs über ein **Pool-Konto**, nicht über den Moodle-Gastzugang. Das Plugin legt eine feste Zahl Pool-Konten an (Einstellung `poolsize`, Standard 10, Obergrenze 50). `preview.php` überlässt dem Besucher genau eines davon, solange die Vorschau läuft (**belegt**), und meldet ihn damit an.

Der Gastzugang genügt nicht, weil er pro Kurs eingeschaltet werden müsste, keine eigene Rolle trägt und Besucher nicht voneinander trennt. Pool-Konten tragen dagegen die **Pool-Rolle**: Sie sieht den Kurs wie eine Lehrkraft ohne Bearbeitungsrecht, aber ohne `moodle/course:viewparticipants`, `moodle/site:viewuseridentity`, `moodle/grade:viewall` und `moodle/user:viewdetails`. Teilnehmende, Identitäten, Noten und fremde Profile bleiben verborgen. Das eigene Profil erlaubt Moodle jedem Nutzer ohne Capability-Prüfung; deshalb leitet ein Hook (`before_http_headers`) Pool-Konten von Profil-, Einstellungs- und Kennwortseiten auf die Startseite um. Bearbeiten (`moodle/user:editownprofile`) und Kennwortwechsel (`moodle/user:changeownpassword`) sind zusätzlich gesperrt.

- Eine echte Anmeldung wird nie durch ein Pool-Konto ersetzt.
- Ist kein Konto frei, zeigt `preview.php` einen Hinweis; es entstehen keine Konten auf Vorrat.
- Nur `POST` mit gültigem Sesskey startet eine Vorschau.
- Pro IP-Adresse läuft höchstens eine Vorschau. Die IP-Ermittlung folgt Moodles Proxy-Konfiguration (`getremoteaddr()`).
- Ein Moodle-Lock schützt Auswahl und Anmeldung bis zum Anlegen der Sitzung. Aktive Moodle-Sitzungen bestimmen die Belegung; Logout und Moodles Inaktivitätslimit geben das Konto frei. Ein zusätzlicher Cache mit eigener Laufzeit entfällt.

## Consequences

Die Zahl gleichzeitiger Vorschauen ist durch die Poolgröße begrenzt. Der Verein muss die Größe an die Last anpassen. Die Pool-Rolle ist Teil des Plugins; neue Moodle-Capabilities mit Personenbezug müssen dort geprüft und gegebenenfalls gesperrt werden.
