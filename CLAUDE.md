# Kursfilter – CLAUDE.md

Moodle-Block `block_kursfilter` von Moodle in Niedersachsen e. V.: Kurssuche nach Kursbereich, Schulform, Fach, Niveaustufe und Suchbegriff, dazu Bewertung, Kurssicherung zum Download und Vorschau über Pool-Konten.

- **Stack:** PHP-Plugin für Moodle 5.1+ (`version.php`), AMD-Modul in `amd/src/`, Mustache-Template.
- **GitHub:** `Moodle-in-Niedersachsene-V/block_kursfilter`
- **Ziel:** vorerst nur der Materialien-Hub des Vereins Moodle in Niedersachsen e. V.; weder die NLQ-Instanzen der Schulen noch der Moodle-Marketplace (siehe ADR 0001).

## Wichtige Dateien

| Datei/Ordner | Zweck |
|---|---|
| `GLOSSARY.md` | Domain-Glossar mit fester Code-Schreibweise je Begriff |
| `docs/adr/` | Architekturentscheidungen |
| `CODING_STANDARDS.md` | Verweis auf die Vereinsstandards und projektbezogene Abweichungen |
| `docs/agents/` | Arbeitsdoku für Agenten (Issues, Labels, Domain, Testing) |

## Aufgabenhandling

- **Coding Standards:** vor dem Schreiben oder Reviewen von Code oder Tests [`CODING_STANDARDS.md`](CODING_STANDARDS.md) lesen.
- Vor Funktionsänderungen alle Aufrufer suchen.
- Testen: siehe [`docs/agents/testing.md`](docs/agents/testing.md).

## Git/gh-Workflow

`main` = veröffentlichter Stand. Änderungen über Branch und PR. Force-Push, History-Rewrite und Branch-Löschung nur nach Rückfrage.

## Agent skills

### Issue tracker

GitHub Issues in `Moodle-in-Niedersachsene-V/block_kursfilter`, via `gh` CLI. Siehe `docs/agents/issue-tracker.md`.

### Triage labels

Standard-Vokabular: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix` (1:1-Mapping). Siehe `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `GLOSSARY.md` + `docs/adr/` im Root. Siehe `docs/agents/domain.md`.
