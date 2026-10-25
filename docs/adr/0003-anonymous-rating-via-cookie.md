# Pseudonyme Bewertung per Cookie

**Präzisiert mit Issue #8; Cookie-Laufzeit gemäß [0005](0005-rating-cookie-lasts-one-year.md).**

Besucher bewerten öffentliche Kurse **ohne eigenes Konto**. `rate.php` nimmt einen `POST` mit Sesskey an und erkennt den Browser am Cookie `kf_rater_id` mit einer zufälligen SHA-256-Kennung. Je Cookie und Kurs wird höchstens eine **Bewertung** (1–5 Sterne) gespeichert; eine zweite wird nicht übernommen.

Die Tabelle `block_kursfilter_ratings` speichert Kurs-ID, Cookie-Kennung, Sterne und Zeitpunkt. Das Cookie gilt ein Jahr ab Erstellung beziehungsweise Verlängerung bei Wiederkehr (`secure`, `httponly`, `samesite=Lax`). Es ist eine pseudonyme Online-Kennung, keine garantierte Anonymisierung.

## Entscheidung zum Ereignis

`course_rated` bleibt als Moodle-Ereignis erhalten. Es enthält zwar nicht die Cookie-Kennung, aber seine `objectid` verweist auf die Bewertungszeile. Der konfigurierte Logstore kann Nutzer-ID und IP-Adresse speichern. Damit lässt sich eine Bewertung eines angemeldeten Besuchers dessen Konto zuordnen; bei Pool-Konten identifiziert die Konto-ID allein nicht die Person. Auch Gastbewertungen können über IP und Zeitpunkt zuordenbar sein.

Wir dokumentieren und deklarieren diesen Datenfluss statt das Ereignis zu entkoppeln. Der Metadata-Provider beschreibt Tabelle, Browser-Cookie und Logstores; die bisherige Aussage „keine personenbezogenen Daten“ entfällt. Protokollierung und Aufbewahrung der Ereignisse richten sich nach der Moodle-Logstore-Konfiguration. Diese Änderung löscht oder anonymisiert keine bestehenden Bewertungen oder Protokolle und ergänzt keinen Export-/Löschablauf für Bewertungen.

## Konsequenzen

Die Bewertung ist kein belastbarer Missbrauchsschutz: Wer das Cookie löscht oder einen anderen Browser nutzt, kann erneut bewerten. Das ist bewusst hingenommen. Ein belastbareres Bewertungsmodell (Nachweis je Besucher, Zeitraum, Umgang mit Wiederholung) ist Gegenstand von Issue #2. Wird es dort geändert, wird diese ADR abgelöst.
