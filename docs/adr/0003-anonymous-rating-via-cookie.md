# Anonyme Bewertung per Cookie

**Teilweise abgelöst durch [0005](0005-rating-cookie-lasts-one-year.md):** Das Cookie ist kein Sitzungs-Cookie mehr, sondern gilt ein Jahr.

Besucher bewerten öffentliche Kurse **ohne Konto**. `rate.php` nimmt einen `POST` mit Sesskey an und erkennt den Besucher an einem Cookie `kf_rater_id` mit einem zufälligen SHA-256-Wert. Je Cookie und Kurs wird höchstens eine **Bewertung** (1–5 Sterne) gespeichert; eine zweite wird nicht übernommen.

Gespeichert wird nur der Hash, keine Nutzer-ID, IP-Adresse oder Browserkennung. Das Ereignis `course_rated` enthält den Hash nicht. Das Cookie ist ein Sitzungs-Cookie (`secure`, `httponly`, `samesite=Lax`), damit kein dauerhaftes Wiedererkennen entsteht.

## Consequences

Die Bewertung ist kein Missbrauchsschutz: Wer das Cookie löscht oder die Sitzung beendet, kann erneut bewerten. Das ist bewusst hingenommen. Ein belastbareres Bewertungsmodell (Nachweis je Besucher, Zeitraum, Umgang mit Wiederholung) ist Gegenstand von Issue #2. Wird es dort geändert, wird diese ADR abgelöst.
