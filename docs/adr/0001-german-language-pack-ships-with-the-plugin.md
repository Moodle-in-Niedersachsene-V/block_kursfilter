# Deutsches Sprachpaket wird mit dem Plugin ausgeliefert

Kursfilter läuft vorerst nur im Aufgabenpool des Vereins Moodle in Niedersachsen e. V., nicht auf den NLQ-Instanzen der Schulen und nicht über den Moodle-Marketplace. Ohne Marketplace gibt es keine Übersetzung über AMOS, und ohne `lang/de/` sähe der deutschsprachige Aufgabenpool nur englische Texte. Abweichend von Coding Standard N3 liefert das Plugin `lang/de/` mit aus. Englisch bleibt die Basis: Code, Kommentare, Docblocks, Testtitel und die Schnittstelle sind englisch, und jeder String entsteht zuerst in `lang/en/`.

## Consequences

Soll Kursfilter später in den Marketplace, wird diese ADR abgelöst und `lang/de/` wie bei Coursepilot (ADR 0024 dort) aus dem Release-Paket genommen.
