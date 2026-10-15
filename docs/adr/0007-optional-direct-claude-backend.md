# Optionales direktes Claude-Backend für die KI-Verschlagwortung

**Ergänzt [0006](0006-ai-tagging-data-flow.md).**

## Entscheidung

Die KI-Verschlagwortung kann statt über Moodles `core_ai` direkt über die Anthropic-API (`https://api.anthropic.com/v1/messages`) laufen. Der Verein hat nur ein Claude-Konto; solange Moodle-Core keinen nutzbaren KI-Anbieter für diesen Zugang bereitstellt, ist der direkte Weg die einzige Möglichkeit, es zu verwenden.

- Einstellung `ai_backend`: `coreai` (Standard) oder `claude`. Bei `claude` gelten `ai_claude_apikey` (Passwortfeld) und `ai_claude_model` (Standard `claude-haiku-4-5`).
- Prompt, Filter für personenbezogene Angaben, Kürzung, `personaldata`-Meldung und Antwortprüfung sind unverändert und für beide Backends **derselbe Code** in `tag_suggester` (ADR 0006, Punkte 1–3, 5, 6). Nur der Versand unterscheidet sich.
- Der Aufruf geht über Moodles `\core\http_client`, damit Proxy und gesperrte Hosts gelten. Gesendet wird ausschließlich der Prompt, kein Nutzerbezug.
- Ohne API-Schlüssel gilt das Backend als nicht verfügbar; der Task schlägt dann sichtbar fehl wie bei fehlendem `core_ai`-Anbieter.
- Die technische Freigabe aus ADR 0006 bleibt: `ai_enabled` und der geplante Task müssen beide aktiv sein.

## Konsequenzen

- Der Versand an Anthropic umgeht `core_ai` und dessen Protokollierung (Anfrage, Antwort, Anbieter). Die Verarbeitung ist im Metadata-Provider als externer Ort `anthropic` deklariert.
- Der API-Schlüssel liegt als Plugin-Einstellung in der Moodle-Konfiguration. Der Upgrade 2026101019 hatte die früheren Schlüsseleinstellungen entfernt; der Schlüssel muss nach dem Einbau neu eingetragen werden.
- Der ältere eigene Connector (`ai_connector`, Ollama und Claude, unfiltert) kehrt **nicht** zurück. Ollama bleibt über `core_ai` erreichbar.
- Sobald ein Moodle-KI-Anbieter für Claude genutzt werden kann, wird `ai_backend` auf `coreai` gestellt; das direkte Backend kann dann in einem eigenen Schritt entfernt werden.
