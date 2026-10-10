# block_kursfilter — Kursfilter-Block

**Paket:** `block_kursfilter`  
**Maintainer:** Moodle in Niedersachsen e. V.  
**Lizenz:** GNU GPL v3 oder später  
**Moodle:** 5.1+  
**PHP:** 8.2+  
**Datenbanken:** MariaDB, MySQL, PostgreSQL

---

## Beschreibung

Der Kursfilter-Block ermöglicht Gästen und nicht eingeschriebenen Nutzerinnen und Nutzern das gezielte Durchsuchen und Entdecken öffentlicher Kurse auf einer Moodle-Materialplattform. Kurse werden nach Schulform, Fach und Niveaustufe gefiltert; zusätzlich steht eine Freitextsuche zur Verfügung.

Besuchern wird ein anonymer Gast-Pool-Zugang zugewiesen, sodass sie Kursinhalte einsehen können, ohne ein eigenes Konto anlegen zu müssen. Kurse können mit einem Cookie-basierten Sternebewertungssystem bewertet werden. Optional stellt das Plugin täglich erzeugte Kurssicherungen (`.mbz`) zum Download bereit und kann Kurse automatisch per KI verschlagworten.

---

## Features

### Kurssuche und Filterung
- Filterung nach **Schulform**, **Fach** und **Niveaustufe** über konfigurierbare Tag-Listen
- **Freitextsuche** über Kursname, Kurzname und Beschreibung
- Filterung nach **Kursbereich** (inkl. aller Unterkategorien)
- Server-seitiges **Rate-Limiting** (max. 30 Anfragen/60 s je Nutzer)
- Konfigurierbares Ergebnislimit (Standard: 100, max. 200)

### Gast-Pool-Nutzer
- Anonyme Pool-Accounts (`kursfilter_pool_XX`) ermöglichen Kurszugang ohne eigene Anmeldung
- Pool-Größe konfigurierbar (Standard: 10, max. 50)
- Pool-Nutzer werden lazy angelegt und automatisch in alle sichtbaren Kurse eingeschrieben
- Logout-Ereignisse geben den Pool-Platz sofort wieder frei

### Kursbewertungen
- Cookie-basiertes Sternebewertungssystem (1–5 Sterne)
- Jede anonyme Besucherin / jeder anonyme Besucher kann pro Kurs genau einmal bewerten
- Durchschnittsbewertung und Anzahl werden in der Suchergebnisliste angezeigt
- **Keine personenbezogenen Daten** werden gespeichert

### Kurssicherungen (Gäste-Download)
- Nächtlicher Scheduled Task erzeugt eine `.mbz`-Sicherung pro Kurs (täglich 02:00 Uhr)
- Gäste können die jeweils aktuellste Sicherung über einen Download-Button herunterladen
- Pro Kurs wird immer nur eine Datei gespeichert (alte Sicherung wird überschrieben)

### KI-Verschlagwortung (optional)
- Automatische Tag-Vorschläge über **Ollama** (lokal) oder die **Anthropic Claude API**
- Kurse werden mit den konfigurierten Schulformen, Fächern und Niveaustufen verschlagwortet
- Scheduled Task verarbeitet ausstehende Kurse in konfigurierbarer Batch-Größe
- **Datenschutz:** Es werden ausschließlich Kursname, Kurzname, Kategorie und ein gekürzter Beschreibungstext (max. 800 Zeichen) übermittelt. Keine Nutzerdaten, keine Lehrkräftenamen, keine Einschreibedaten.
- Tags können automatisch gesetzt oder zur manuellen Überprüfung gespeichert werden

---

## Installation

1. ZIP-Datei in Moodle unter **Website-Administration → Plugins → Plugin installieren** hochladen.
2. Datenbank-Upgrade bestätigen.
3. Block auf der gewünschten Seite (Startseite oder Dashboard) hinzufügen.
4. Einstellungen unter **Website-Administration → Plugins → Blöcke → Kursfilter** konfigurieren.

---

## Konfiguration

### Schulformen, Fächer, Niveaustufen
Je eine Liste (ein Wert pro Zeile). An den Kursen müssen entsprechende Tags in der Form `schulform:Gymnasium`, `fach:Mathematik`, `niveaustufe:Klasse 5-6` gesetzt sein.

### Gast-Pool-Nutzer
| Einstellung | Beschreibung |
|---|---|
| Anzahl Pool-Nutzer | Anzahl anonymer Besucherkonten (Standard: 10, max. 50) |

### Kurssicherungen
| Einstellung | Beschreibung |
|---|---|
| Nutzer-ID für Kurssicherungen | Administrator-Konto für den Backup-Task; leer = erster Site-Admin |

### KI-Verschlagwortung
| Einstellung | Beschreibung |
|---|---|
| KI-Verschlagwortung aktivieren | Scheduled Task ein-/ausschalten |
| KI-Backend | Ollama (lokal) oder Anthropic Claude API |
| Ollama URL | Basis-URL des lokalen Ollama-Servers (Standard: `http://localhost:11434`) |
| Ollama Modell | z. B. `gemma3:4b` |
| Claude API-Schlüssel | API-Schlüssel für die Anthropic Claude API |
| Claude Modell | z. B. `claude-haiku-4-5-20251001` |
| Batch-Größe | Kurse pro Cron-Lauf (Standard: 20) |
| Tags automatisch anwenden | Direkt setzen oder zur Überprüfung speichern |

### Suchergebnisse
| Einstellung | Beschreibung |
|---|---|
| Max. Ergebnisse | Maximale Anzahl Kurse pro Suchanfrage (Standard: 100, max. 200) |

---

## Scheduled Tasks

| Task | Zeitplan | Beschreibung |
|---|---|---|
| Kursfilter-Pool-Nutzer einrichten | täglich 03:00 | Pool-Nutzer anlegen und in neue Kurse einschreiben |
| Kurssicherungen erzeugen | täglich 02:00 | `.mbz`-Sicherung pro Kurs für Gäste-Download |
| Kurse per KI verschlagworten | täglich 04:00 | Ausstehende Kurse automatisch mit Tags versehen |

---

## Datenschutz

Dieses Plugin speichert keine personenbezogenen Daten. Kursbewertungen werden ausschließlich über einen anonymen Cookie-Hash gespeichert, der keiner Person zugeordnet werden kann. Bei der KI-Verschlagwortung werden keine Nutzerdaten an externe Dienste übermittelt.

Das Plugin implementiert die Moodle Privacy API als `null_provider`.

---

## Technische Anforderungen

- Moodle 5.1 oder neuer (`2025041400`)
- PHP 8.2+
- MariaDB, MySQL oder PostgreSQL
- Für KI-Verschlagwortung: lokaler Ollama-Server **oder** Anthropic Claude API-Schlüssel

---

## Lizenz

GNU General Public License v3 oder später — https://www.gnu.org/licenses/gpl-3.0.html

Copyright 2026 Moodle in Niedersachsen e. V.
