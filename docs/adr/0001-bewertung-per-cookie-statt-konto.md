# Bewertungen werden über ein dauerhaftes Cookie, nicht über Konten begrenzt

Eine Bewertung je Besucher und Kurs wird über einen anonymen Cookie-Hash (`kf_rater_id`, Laufzeit 1 Jahr) erzwungen, nicht über die Konto-ID. Besucher arbeiten im Kursfilter über geteilte Pool-Konten, die nacheinander verschiedene Personen bedienen; eine Konto-ID würde Besucher fälschlich blockieren und echte Konten gibt es nur für Verwaltende. Zweck ist grobe Fairness, kein Manipulationsschutz: anderer Browser oder gelöschtes Cookie erlaubt eine weitere Bewertung, das wird bewusst akzeptiert. Dafür bleibt das Plugin frei von personenbezogenen Daten (kein Privacy-Provider, kein Schema-Umbau).

## Considered Options

- **Nur eigenes Konto** (`require_login()`, Unique-Index auf Konto+Kurs): anonyme Besucher könnten nicht mehr bewerten.
- **Konto-ID für echte Konten, Cookie für Besucher**: Aufwand (Spalte, Pool-Konten ausnehmen, Privacy-Provider) steht in keinem Verhältnis zum Gewinn, da nur Verwaltende echte Konten haben.
