# Öffentlicher Download der Kurssicherung

Besucher laden die **Kurssicherung** (.mbz) eines öffentlichen Kurses **ohne Konto** über `backup.php?courseid=…` herunter. Die Datei wird nicht beim Aufruf erzeugt, sondern nächtlich per geplanter Aufgabe unter dem **Sicherungskonto** (Einstellung `backup_userid`) und liegt in der Dateiablage des Plugins; je Kurs gibt es höchstens eine.

Damit ein öffentlicher Download unbedenklich ist:

- Die Sicherung enthält keine Nutzerdaten, Rollenzuweisungen, Logs oder Notenhistorie.
- `backup.php` liefert nur Sicherungen **öffentlicher Kurse** (sichtbar, nicht die Startseite); sonst „nicht gefunden“.
- Eine neue Sicherung ersetzt die alte erst, wenn sie vollständig vorliegt.
- Der Download löst keine Sicherung aus und belastet den Server daher nicht je Aufruf.

## Consequences

Was in einem öffentlichen Kurs steht, ist über den Download vollständig kopierbar, auch Inhalte, die in der Vorschau nur lesbar sind. Wer einen Kurs nicht herausgeben will, muss ihn verbergen. Die Sicherung ist so aktuell wie der letzte Nachtlauf. Ein Sicherungskonto mit Backup-Berechtigung ist Voraussetzung.
