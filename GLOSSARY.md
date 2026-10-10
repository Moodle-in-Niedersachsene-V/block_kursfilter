# Kursfilter

Ein Block, mit dem Lehrkräfte im Materialien-Hub öffentliche Kurse finden, ansehen, bewerten und als Kurssicherung herunterladen. Testfragen findet man im Materialien-Hub auf anderem Weg; sie sind nicht Teil des Kursfilters.

## Materialien-Hub

**Materialien-Hub**:
Das Moodle des Vereins Moodle in Niedersachsen e. V., in dem Lehrkräfte Kurse und Testfragen zur Nachnutzung finden.
_Avoid_: Aufgabenpool, Vereins-Moodle

**Kurs**:
Ein Moodle-Kurs im Materialien-Hub, den Lehrkräfte ansehen und für den eigenen Unterricht übernehmen können; Gegenstand des Kursfilters.
_Avoid_: Material, Kursvorlage
Code: `course`

**Testfrage**:
Eine Frage aus der Fragensammlung des Materialien-Hubs, die Lehrkräfte für eigene Tests übernehmen können; nicht Gegenstand des Kursfilters.
_Avoid_: Aufgabe (in Moodle eine Aktivität), Quizfrage
Code: `question`

## Suche

**Öffentlicher Kurs**:
Ein sichtbarer Kurs, der nicht die Startseite ist. Nur öffentliche Kurse erscheinen in der Suche und können angesehen, bewertet oder heruntergeladen werden.
_Avoid_: freier Kurs, sichtbarer Kurs
Code: `public course`

**Kursbereich**:
Ein Moodle-Kursbereich als Filter; er schließt seine Unterbereiche ein.
_Avoid_: Kategorie
Code: `category`

**Schulform**:
Filter nach der Schulform, für die ein Kurs gedacht ist (z. B. Gymnasium).
Code: `school type` (`schooltype`)

**Fach**:
Filter nach dem Unterrichtsfach eines Kurses.
Code: `subject`

**Niveaustufe**:
Filter nach Jahrgang oder Stufe, für die ein Kurs gedacht ist (z. B. Klasse 5-6).
_Avoid_: Level, Klassenstufe
Code: `level`

**Kurs-Tag**:
Ein Schlagwort am Kurs; Schulform, Fach und Niveaustufe sind Kurs-Tags, deren Wert in der Liste der Einstellungen steht.
_Avoid_: Präfix-Tag
Code: `tag`

**Suchbegriff**:
Freier Text, der in Kursbeschreibung, Kursname und Kurzname gesucht wird.
_Avoid_: Kursname
Code: `search term` (`searchterm`)

## Besuch

**Besucher**:
Eine Person, die den Kursfilter nutzt, mit oder ohne eigenes Moodle-Konto.
_Avoid_: Gast, Kursbesucher, Nutzer
Code: `visitor`

**Vorschau**:
Der Blick eines Besuchers in einen öffentlichen Kurs über ein Pool-Konto, ohne Bearbeitungsrechte.
_Avoid_: Gast-Login, Gastzugang, Kurs ansehen
Code: `preview`

**Pool-Konto**:
Ein vom Plugin angelegtes Moodle-Konto, das genau einem Besucher für eine Vorschau überlassen wird.
_Avoid_: Pool-Nutzer, Gastnutzer, Vorschaukonto, Gastkonto
Code: `pool account`

**Pool-Rolle**:
Die Rolle der Pool-Konten in öffentlichen Kursen: sieht den Kurs wie eine Lehrkraft ohne Bearbeitungsrecht, aber keine Teilnehmenden, Identitäten oder Noten.
_Avoid_: Trainer-Rolle, Gastrolle
Code: `pool role`

**Belegt / frei**:
Ein Pool-Konto ist belegt, solange ein Besucher es für eine Vorschau nutzt, sonst frei.
_Avoid_: aktiv, besetzt
Code: `occupied` / `free`

## Bewertung und Download

**Bewertung**:
Ein bis fünf Sterne, die ein Besucher einem öffentlichen Kurs gibt; höchstens eine je Besucher und Kurs.
_Avoid_: Rating, Kursbewertung, Stimme
Code: `rating`

**Kurssicherung**:
Die nächtlich erzeugte Sicherungsdatei (.mbz) eines öffentlichen Kurses ohne Nutzerdaten; je Kurs gibt es höchstens eine.
_Avoid_: Export, Backup-Datei, Kursexport
Code: `backup`

## KI-Verschlagwortung

**KI-Verschlagwortung**:
Das automatische Ermitteln passender Filterwerte (Schulform, Fach, Niveaustufe) für einen öffentlichen Kurs durch den in Moodle eingerichteten KI-Anbieter.
_Avoid_: KI-Tagging, Auto-Tagging
Code: `ai tagging`

**Tag-Vorschlag**:
Die von der KI-Verschlagwortung ermittelten Filterwerte eines Kurses, die vor dem Übernehmen als Kurs-Tags auf Prüfung warten.
_Avoid_: KI-Vorschlag, pending tags
Code: `tag suggestion`

**Sicherungskonto**:
Das Administratorkonto, unter dem die Kurssicherungen erzeugt werden.
_Avoid_: Backup-Admin
Code: `backup user`
