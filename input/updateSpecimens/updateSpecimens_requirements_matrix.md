# Soll-Ist-Matrix `updateSpecimens`

| Themenblock | `updateSpecimens_doc.txt` | `compareDataTableDoc.txt` | `updateSpecimens_target_structure.md` | Aktueller Stand |
|---|---|---|---|---|
| 3-Run-Workflow | gefordert | nicht relevant | gefordert | erfuellt |
| Single-File-Seite `updateSpecimens.php` | gefordert | nicht relevant | gefordert | erfuellt |
| Upload ueber Datei | gefordert | nicht relevant | gefordert | erfuellt |
| Upload ueber URL | nicht urspruenglich gefordert | nicht relevant | optional/spaeter empfohlen | erfuellt |
| Parsing des bestehenden Importformats | gefordert | indirekt vorausgesetzt | gefordert | erfuellt |
| Specimen-Match serverseitig | implizit gefordert | nicht relevant | explizit gefordert | erfuellt |
| Match ueber `collectionID + HerbNummer` | nicht explizit benannt | nicht relevant | explizit gefordert | erfuellt |
| Zwei Unterzeilen pro Datensatz (Import / DB) | gefordert | implizit gefordert | gefordert | erfuellt |
| Zellweise Auswahl | gefordert | implizit gefordert | gefordert | erfuellt |
| Zeilenweise Auswahl | gefordert | implizit gefordert | gefordert | erfuellt |
| Spaltenweise Auswahl | gefordert | implizit gefordert | gefordert | erfuellt |
| Hidden-JSON / Rueckgabe der Auswahl | gefordert | Rueckgabe gefordert | explizit gefordert | erfuellt |
| Nur geaenderte Felder updaten | nicht klar gefordert | nicht relevant | explizit gefordert | erfuellt |
| Logging vor Update | nicht explizit gefordert | nicht relevant | explizit gefordert | erfuellt |
| Rechtepruefung vor Update | nicht explizit gefordert | nicht relevant | explizit gefordert | erfuellt |
| `Rows With Issues` / Trennung blocker vs warning | implizit aus Statuslogik | nicht relevant | gefordert | erfuellt |
| Warnfelder auf DB vorbelegen | nicht explizit gefordert | nicht relevant | implizit sinnvoll | erfuellt |
| Identische Felder grau / nicht klickbar | nicht gefordert | nicht gefordert | nicht gefordert | zusaetzlich umgesetzt |
| `Show changed columns only` | nicht gefordert | nicht gefordert | nicht gefordert | zusaetzlich umgesetzt |
| Sticky Header / erste Spalte / Scrollcontainer | nicht gefordert | nicht gefordert | nicht gefordert | zusaetzlich umgesetzt |
| Archivierung nach Abschluss | nicht gefordert | nicht gefordert | nicht gefordert | zusaetzlich umgesetzt |
| Reset / neuer Prozess | nicht gefordert | nicht gefordert | nicht gefordert | zusaetzlich umgesetzt |
| Alte `CompareDataTable`-API (`setHeader`, `buildTable`, `returnChosenData`) | nicht relevant | gefordert | nicht als Pflicht empfohlen | nicht erfuellt |
| Produktive Nutzung von `compareDataTable.js` | nicht relevant | implizit gefordert | eher als UI-Idee genannt | nicht erfuellt |
| Tabelle als eigenstaendige JS-Komponente | nicht relevant | gefordert | empfohlen | teilweise erfuellt |
| Vergleichs-UI schreibt nicht direkt in `document.body` | nicht thematisiert | alte Doku anders | explizit gefordert | erfuellt |
| `textContent` statt `innerHTML` in eigener JS-Komponente | nicht thematisiert | nicht thematisiert | explizit empfohlen | teilweise erfuellt |
| Taxon-Vorschlaege bei `no_taxa` / `similar_taxa` | gefordert | nicht relevant | gefordert | erfuellt |
| genus-only Normalisierung mit Autorenangabe | nicht gefordert | nicht relevant | nicht explizit gefordert | zusaetzlich umgesetzt |
| Vollstaendiger Taxon-Insert-Pfad in Run 3 | implizit gefordert | nicht relevant | gefordert | nicht erfuellt |
| POST-Contract `similarTaxa_<index>`, `externalID`, `contentid_<index>`, `position_<index>` | nicht direkt benannt | nicht relevant | explizit gefordert | nicht erfuellt |
| `compareDataTable.js` stark ueberarbeiten oder neu schreiben | nicht relevant | implizit Altstand | explizit empfohlen | nicht erfuellt |
| Shared-Include fuer gemeinsame Importhelfer | nicht relevant | nicht relevant | empfohlen | nicht erfuellt |

## Kurzfazit

- Stark erfuellt sind die Kernanforderungen aus `updateSpecimens_doc.txt` und `updateSpecimens_target_structure.md`: 3-Run-Workflow, Parsing, serverseitiges Specimen-Match, Vergleichsansicht, differenzielles Update, Logging und Rechtepruefung.
- Nicht erfuellt ist vor allem die fruehe Komponentenidee aus `compareDataTableDoc.txt`: Die alte `CompareDataTable`-API existiert produktiv nicht mehr.
- Der groesste fachliche Restpunkt ueber alle drei Quellen ist der fehlende vollstaendige Taxon-Nachbearbeitungspfad in Run 3.
