# Kursfilter läuft nur im Materialien-Hub

Kursfilter läuft vorerst nur im Materialien-Hub des Vereins Moodle in Niedersachsen e. V., nicht auf den NLQ-Instanzen der Schulen und nicht über den Moodle-Marketplace. Daraus folgen zwei Abweichungen von den Coding Standards:

- **N3 (nur `lang/en/` ausliefern):** Das Plugin liefert `lang/de/` mit aus. Ohne Marketplace gibt es keine Übersetzung über AMOS, und der deutschsprachige Materialien-Hub sähe sonst nur englische Texte. Englisch bleibt die Basis: Code, Kommentare, Docblocks, Testtitel und die Schnittstelle sind englisch, und jeder String entsteht zuerst in `lang/en/`.
- **D2 (jede von Moodle unterstützte Datenbank):** Das Plugin muss nur mit der Datenbank des Materialien-Hubs laufen. Platzhalter und die DML-API bleiben Pflicht (Sicherheit); ein Nachweis für alle anderen Datenbanken entfällt.

## Consequences

Soll Kursfilter auf weitere Instanzen, etwa die NLQ-Instanzen, wird diese ADR überprüft. Für den Marketplace wird sie abgelöst: `lang/de/` wird dann wie bei Coursepilot (ADR 0024 dort) aus dem Release-Paket genommen, und D2 gilt wieder vollständig.
