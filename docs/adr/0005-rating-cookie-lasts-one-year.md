# Bewertungen werden über ein dauerhaftes Cookie, nicht über Konten begrenzt

Eine Bewertung je Besucher und Kurs wird über einen pseudonymen Cookie-Hash (`kf_rater_id`, Laufzeit 1 Jahr) erzwungen, nicht über die Konto-ID. Besucher arbeiten im Kursfilter über geteilte Pool-Konten, die nacheinander verschiedene Personen bedienen; eine Konto-ID würde Besucher fälschlich blockieren und echte Konten gibt es nur für Personen, die Inhalte verwalten. Zweck ist grobe Fairness, kein Manipulationsschutz: anderer Browser oder gelöschtes Cookie erlaubt eine weitere Bewertung, das wird bewusst akzeptiert. Die frühere Annahme, das Plugin sei damit frei von personenbezogenen Daten, ist durch die Präzisierung in [ADR 0003](0003-anonymous-rating-via-cookie.md) abgelöst: Cookie-Kennung und Ereignisverknüpfung werden im Metadata-Provider deklariert.

## Considered Options

- **Nur eigenes Konto** (`require_login()`, Unique-Index auf Konto+Kurs): anonyme Besucher könnten nicht mehr bewerten.
- **Konto-ID für echte Konten, Cookie für Besucher**: Aufwand (Spalte, Pool-Konten ausnehmen, Privacy-Provider) steht in keinem Verhältnis zum Gewinn, da nur verwaltende Personen echte Konten haben.
