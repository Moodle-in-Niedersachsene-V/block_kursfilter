# Deutsches Sprachpaket wird mit dem Plugin ausgeliefert

Kursfilter wird für Moodle-Instanzen in Niedersachsen entwickelt und derzeit nicht im Moodle-Marketplace veröffentlicht. Deshalb gibt es keine Übersetzung über AMOS, und ohne `lang/de/` sähen deutsche Instanzen nur englische Texte. Abweichend von Coding Standard N3 liefert das Plugin `lang/de/` mit aus. Englisch bleibt die Basis: Code, Kommentare, Docblocks, Testtitel und die Schnittstelle sind englisch, und jeder String entsteht zuerst in `lang/en/`.

## Consequences

Soll Kursfilter später in den Marketplace, wird diese ADR abgelöst und `lang/de/` wie bei Coursepilot (ADR 0024 dort) aus dem Release-Paket genommen.
