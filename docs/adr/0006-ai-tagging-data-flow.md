# Datenfluss der KI-Verschlagwortung

**Ergänzt um [0007](0007-optional-direct-claude-backend.md): optional direkter Versand an die Anthropic-API statt über `core_ai`.**

## Entscheidung

Die KI-Verschlagwortung nutzt ausschließlich Moodles `core_ai` mit der Aktion `generate_text`. Der Datenfluss ist:

1. `tag_courses` liest öffentliche Kurse ohne bisherigen Tag-Vorschlag: Kursname, Kurzname, Kursbereich und Beschreibung.
2. `tag_suggester` wandelt diese Texte in Klartext um und entfernt E-Mail-Adressen sowie erkennbare Namensmuster deterministisch. Die Beschreibung wird **danach** auf 800 Zeichen gekürzt.
3. Der Prompt enthält diese Kursdaten als JSON zwischen `<course>` und `</course>`, eine Anweisung zur Behandlung als Daten und die erlaubten Filterwerte für Schulform, Fach und Niveaustufe. Einschreibungen, Teilnehmerlisten, Kontoprofile und Dateien werden nicht gelesen oder angehängt.
4. `core_ai` erhält Prompt, Kurskontext-ID und die ID des Administratorkontos (`get_admin()`), unter dem der Task anfragt. Es protokolliert Anfrage, Antwort, Anbieter und Verarbeitung gemäß seinem Privacy-Provider. Der konfigurierte KI-Anbieter erhält den Prompt über seinen Moodle-Adapter; bei einem externen Anbieter verlassen diese Kursinhalte den Server. Aufbewahrung und weitere Verarbeitung beim Anbieter richten sich nach dessen Konfiguration und Vereinbarungen.
5. Die englische KI-Anweisung verbietet die Weiterverwendung personenbezogener Angaben, die der Filter nicht erkannt hat. Erkennt das Modell solche Angaben, soll es `personaldata: true` und leere Filterlisten zurückgeben. Code verwirft dann alle Vorschläge und meldet einen Fehler mit Aufforderung zur Bereinigung des Kurstexts. Der Task protokolliert diesen Fehler mit Kurs-ID und schlägt sichtbar fehl; ein späterer Lauf versucht den Kurs erneut. Diese zusätzliche Meldung ersetzt weder Filter noch technische Freigabe.
6. Nur Antwortwerte aus den konfigurierten Filterlisten werden übernommen. Das Plugin speichert Tag-Vorschlag und Status; bei `ai_autoapply` werden die Werte unmittelbar Kurs-Tags, andernfalls warten sie auf Prüfung.

## Technische Freigabe

`ai_enabled` ist standardmäßig aus. `tag_courses::execute()` kehrt ohne KI-Anfrage zurück, solange die Einstellung aus ist, auch bei manuellem Task-Aufruf. Zusätzlich ist der geplante Task in `db/tasks.php` standardmäßig deaktiviert. Für Cron müssen Administratoren sowohl die Einstellung als auch den Task aktivieren und einen `generate_text`-Anbieter einrichten. Fehlt bei aktivierter Einstellung ein Anbieter, schlägt der Task sichtbar fehl. `suggest()` ist eine interne Schnittstelle; sein einziger Produktionsaufrufer ist der freigabeprüfende Task.

Diese Freigabe ist eine bewusste administrative Entscheidung über den Datenfluss, keine automatische Einwilligung betroffener Personen. Vor der Aktivierung sind Kursinhalte, Filterlisten und Anbieter auf ihre Eignung für diesen Versand zu prüfen.

## Grenzen und Konsequenzen

Der Filter ersetzt E-Mail-Adressen, Folgen aus mindestens zwei großgeschriebenen Namensteilen (auch Initialen, Doppelnamen und Partikel wie „von“) sowie Namen mit Anrede oder Titel (`Herr`, `Herrn`, `Frau`, `Dr.`, `Prof.`). Er arbeitet auch auf Kursname, Kurzname und Kursbereich, damit Angaben dort nicht ungefiltert weitergegeben werden. Fachbegriffe mit gleichem Schreibmuster können ebenfalls entfernt werden; das ist für die Datenminimierung akzeptiert.

Einzelne Namen ohne Anrede, abweichende Schreibweisen, verschleierte Kontaktdaten und andere personenbezogene Angaben sind nicht zuverlässig erkennbar. Der Filter garantiert daher keine Anonymisierung. Kursverantwortliche müssen solche Inhalte vor einer administrativen Freigabe bereinigen; ohne Freigabe bleibt der Versand aus. Diese Grenze wird auch in den Privacy-Strings benannt. Die KI entscheidet weder über die Freigabe noch darüber, welche erkannten Angaben entfernt werden.

Der Metadata-Provider deklariert den Link zu `core_ai` einschließlich Prompt, Nutzer-ID und Kurskontext. Ein Verhaltenstest beobachtet den tatsächlichen HTTP-Request des KI-Anbieters durch den echten `core_ai`-Pfad; nur der HTTP-Transport ist ersetzt.
