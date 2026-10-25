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

## Pool-Start für andere Plugins

Der Fragenfilter kann nach POST- und Sesskey-Prüfung denselben Pool ohne Kurseinschreibung nutzen:

```php
if (!data_submitted()) {
    throw new moodle_exception('error_preview_needs_post', 'block_kursfilter');
}
require_sesskey();
$outcome = \block_kursfilter\preview_starter::start(null, isloggedin() && !isguestuser());
```

`LOGGED_IN` bedeutet erfolgreiche Pool-Anmeldung,
`REDIRECT` erhält eine echte Anmeldung, `POOL_FULL` und `IP_IN_USE` lehnen die Belegung ab.
Die Konstanten liegen in `preview_starter`; bei einem Lock-Timeout wird eine Moodle-Exception
weitergegeben. Kursvorschauen übergeben statt `null` eine öffentliche Kurs-ID und können
zusätzlich `COURSE_NOT_FOUND` erhalten. Siehe [ADR 0002](docs/adr/0002-preview-via-pool-accounts-instead-of-guest-access.md).

## Einstellungen

Filterwerte (Schulformen, Fächer, Niveaustufen) je eine Zeile; ein Kurs passt, wenn er einen Kurs-Tag mit genau diesem Wert trägt. Dazu Ergebnislimit, Anzahl der Pool-Konten und das Sicherungskonto.

KI-Verschlagwortung (Abschnitt „KI-Verschlagwortung“ der Einstellungen):

| Einstellung | Name | Standard | Wirkung |
|---|---|---|---|
| KI-Verschlagwortung aktivieren | `ai_enabled` | aus | erster Schalter: ohne ihn tut die Aufgabe `tag_courses` nichts |
| KI-Backend | `ai_backend` | `coreai` | `coreai` (KI-Anbieter in Moodle) oder `claude` (direkt an die Anthropic-API, ADR 0007) |
| Claude-API-Schlüssel / -Modell | `ai_claude_apikey`, `ai_claude_model` | leer, `claude-haiku-4-5` | nur bei `ai_backend` = `claude` |
| Tags automatisch übernehmen | `ai_autoapply` | aus | passende Werte werden sofort Kurs-Tags, sonst bleiben sie Tag-Vorschläge |
| Kurse je Lauf | `ai_batch_size` | 20 | Anzahl Kurse je Lauf der Aufgabe |

Die KI-Verschlagwortung läuft nur, wenn beide Schalter an sind: die Einstellung `ai_enabled` und die geplante Aufgabe `tag_courses` (Websiteverwaltung > Server > Geplante Aufgaben; standardmäßig deaktiviert).

## Geplante Aufgaben

- 02:00 `backup_courses`: eine Kurssicherung ohne Nutzerdaten je öffentlichem Kurs; scheitert eine, schlägt die Aufgabe mit Ursache im Protokoll fehl.
- 03:00 `setup_pool`: legt fehlende Pool-Konten an und schreibt sie in öffentliche Kurse ein.
- 04:00 `tag_courses` (standardmäßig aus, zusätzlich Einstellung `ai_enabled`): KI-Verschlagwortung über den in Moodle eingerichteten KI-Anbieter (Aktion „Text generieren“) oder direkt über die Claude-API (`ai_backend`). Übernommen werden nur Werte aus den Filterlisten, je Kurs einmal; ohne „Tags automatisch übernehmen“ bleiben sie als Tag-Vorschläge stehen (Prüfseite: Issue #3).

## Entwicklung

Coding Standards: [CODING_STANDARDS.md](CODING_STANDARDS.md). Tests und Build: [docs/agents/testing.md](docs/agents/testing.md).
