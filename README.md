# Kursfilter (`block_kursfilter`)

Moodle-Block von Moodle in Niedersachsen e. V. für den Materialien-Hub: Lehrkräfte finden öffentliche Kurse nach Kursbereich, Schulform, Fach, Niveaustufe und Suchbegriff, sehen sie in der Vorschau an, bewerten sie und laden die Kurssicherung herunter. Begriffe: [GLOSSARY.md](GLOSSARY.md).

## Voraussetzungen

Moodle 5.1 oder neuer.

## Funktionen und Endpunkte

| Funktion | Weg | Zugriff |
|---|---|---|
| Kurssuche | Web-Service `block_kursfilter_search_courses` | Moodle-Sitzung und Capability `block/kursfilter:search` (Standard: Gast, angemeldete Nutzer) |
| Vorschau | `preview.php`, nur POST mit Sesskey | öffentlich; meldet ein freies Pool-Konto an |
| Bewertung | `rate.php`, nur POST mit Sesskey | öffentlich; eine Bewertung je Browser-Sitzung und Kurs (siehe Issue #2) |
| Kurssicherung | `backup.php?courseid=…` | öffentlich, nur für öffentliche Kurse |

## Einstellungen

Filterwerte (Schulformen, Fächer, Niveaustufen) je eine Zeile; ein Kurs passt, wenn er einen Kurs-Tag mit genau diesem Wert trägt. Dazu Ergebnislimit, Anzahl der Pool-Konten und das Sicherungskonto.

## Geplante Aufgaben

- 02:00 `backup_courses`: eine Kurssicherung ohne Nutzerdaten je öffentlichem Kurs; scheitert eine, schlägt die Aufgabe mit Ursache im Protokoll fehl.
- 03:00 `setup_pool`: legt fehlende Pool-Konten an und schreibt sie in öffentliche Kurse ein.

## Entwicklung

Coding Standards: [CODING_STANDARDS.md](CODING_STANDARDS.md). Tests und Build: [docs/agents/testing.md](docs/agents/testing.md).
