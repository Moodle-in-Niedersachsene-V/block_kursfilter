# Coding Standards

Kurzfassung der fuer dieses Plugin relevanten Regeln aus dem Coursepilot-Standard
(`CODING_STANDARDS.md`, Teil 1 Core und Teil 2 Moodle-Plugins, Repo `moodle-coursepilot`,
Branch `dev`). Regelnummern sind identisch; Begruendung und Quellen stehen dort.
Ein Review zitiert die Regel mit Nummer (`C3`, `M4`). Was phpcs/CI prueft, steht hier nicht.

## Core

- **C2.** Begriffe der Domaene benutzen, ein Begriff pro Konzept (Bezeichner, Testtitel, Meldungen).
- **C3.** Verhalten ueber die oeffentliche Schnittstelle testen; Doubles nur fuer externe Systeme,
  die Datenbank laeuft echt.
- **C4.** Jeder Test endet mit mindestens einer fachlichen Assertion (nicht nur "keine Exception").
- **C5.** Fehler laut melden; Fallbacks nur per Entscheidung (ADR/Spec).
- **C6.** Kommentare sagen das Warum; Docblocks sagen, was bereitgestellt wird. Keine
  Tracker-Verweise (`#12`, `MDL-...`) im Code, hoechstens `TODO <volle Issue-URL>`.
- **C9.** Jede Unterdrueckung (Ignore, Baseline) nennt ihren Grund, temporaere auch das Issue.
- **C11.** Nur die Allgemeinheit bauen, die die aktuelle Aenderung braucht.
- **C12.** Doku wird in derselben Aenderung mitgezogen (oder PR begruendet, warum nicht).
- **C13.** Testtitel passen zum geprueften Szenario; kein unnoetiger Aufwand im Testcode.
- **C14.** Befehl und Abfrage trennen: Funktion aendert Zustand oder liefert Information.

## Moodle-Plugins

- **M1.** Bezeichner, Kommentare, Docblocks und Testtitel sind Englisch. Uebersetzungen in `lang/<code>/`.
- **M2.** Sichtbarer Text kommt aus Sprachstrings; Fehler per `moodle_exception` mit String-Key.
- **M3.** Seitenskripte verdrahten nur (Config, Zugriff, Parameter, eine Klasse, Ausgabe);
  Entscheidungslogik liegt in getesteten Klassen.
- **M4.** Seiten/Formulare: Capability pruefen, Eingaben nur ueber `required_param()`/
  `optional_param()` mit `PARAM_*`, schreibend nur per POST + Sesskey, Ausgabe escapen.
  Oeffentliche Endpunkte ohne `require_login()` sind begruendet zu kommentieren.
- **M7.** Datenbankzugriff ueber DML-API mit Platzhaltern, DB-neutral (`$DB->sql_*()`).
- **M8.** Einstellungen als `block_kursfilter/<name>` in der Plugin-Config.
- **M9.** Schreibende Aktionen loesen ein Moodle-Ereignis aus (`classes/event/`); lesende nicht.
- **M10.** Interne Funktionen mit expliziten, typisierten Parametern statt `$options`-Array.
- **M11.** Jeder Selektor in `styles.css` beginnt mit `.block_kursfilter` (oder Klasse mit Plugin-Praefix).
