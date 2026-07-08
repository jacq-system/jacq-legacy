# Dokumentation `updateSpecimens`

## Zweck

`input/updateSpecimens.php` ist ein 3-Run-Workflow zum Aktualisieren bestehender Specimen-Datensaetze auf Basis einer Importdatei im bekannten Importformat.

Die Seite ist nicht als generischer Tabellenbaustein umgesetzt, sondern als fachlicher Update-Prozess mit serverseitigem Parsing, Validierung, Vergleichsansicht, selektiver Felduebernahme, Logging und Abschluss-Archivierung.

## Eingaben

Unterstuetzte Eingabewege:

- Dateiupload ueber `userfile`
- Download ueber `download_url`

Dateitypen:

- `txt`
- `csv`

Die Datei wird mit demselben CSV-Grundformat verarbeitet wie in `importSpecimens.php`.

## Workflow

### Run 1

Run 1 zeigt nur das Eingabeformular.

Funktionen:

- Upload einer lokalen Datei
- optionaler Download ueber URL
- Start des Pruef- und Vergleichslaufs

### Run 2

Run 2 liest die Datei ein, validiert die Zeilen und matched jede Zeile serverseitig auf ein bestehendes Specimen.

Wichtige Schritte:

- Einlesen der Datei ueber `loadUploadedUpdateRows(...)`
- Parsing und Normalisierung pro Zeile ueber `buildParsedUpdateRow(...)`
- Specimen-Match ueber `collectionID + HerbNummer`
- Trennung in:
  - `readyRows` fuer vergleichbare Zeilen
  - `issueRows` fuer blockierte Zeilen
- Aufbau einer Vergleichsansicht mit Import- und DB-Zeile
- Speicherung der Ursprungsdatei im Session-Kontext fuer spaetere Archivierung

In Run 2 ist das Importformular nicht mehr sichtbar, solange ein Update-Prozess aktiv ist.

Stattdessen werden angezeigt:

- Vergleichstabelle
- Issues-Tabelle
- Button `Start new update process`

### Run 3

Run 3 uebernimmt die vom Benutzer ausgewaehlten Werte und fuehrt die DB-Updates aus.

Wichtige Schritte:

- Lesen des Hidden-JSON-Felds `update_payload`
- erneutes Laden des aktuellen DB-Zustands pro Specimen
- Rechtepruefung ueber `userCanUpdateSpecimen(...)`
- Erzeugung eines differenziellen `UPDATE`
- Logging des Vorzustands ueber `logSpecimen($specimenId, 1)`
- Speichern eines Abschluss-Reports im Session-Kontext

Im Abschlussbildschirm werden angezeigt:

- Zusammenfassung erfolgreich/fehlgeschlagen
- Ergebnistabelle pro Zeile
- Button `Archive`
- Button `Start new update process`

## Vergleichsansicht

Die Vergleichsansicht ist eine serverseitig gerenderte HTML-Tabelle mit JS-gesteuerter Auswahl.

Umgesetzte Eigenschaften:

- zwei Zeilen pro Datensatz: Import / Database
- Auswahl pro Zelle
- Auswahl pro Zeile
- Auswahl pro Spalte
- Sticky Header und Sticky erste Spalte
- Scrollbarer Tabellencontainer
- Umschalter `Show changed columns only`
- identische Felder grau und nicht klickbar
- Warnfelder gelb markiert
- nicht aufloesbare Importfelder defaulten auf DB
- Taxon-Vorschlaege als Dropdown bei `similar_taxa`

Die eigentliche Rueckgabe erfolgt nicht ueber ein Klassenobjekt wie in `compareDataTable.js`, sondern ueber ein Hidden-JSON-Feld `update_payload`.

## Validierung und Statuscodes

Blockierende Statuscodes:

- `no_collection`
- `no_specimen`
- `multiple_specimens`
- `duplicate_specimen`

Warnende Statuscodes:

- `no_identstatus`
- `no_taxa`
- `similar_taxa`
- `no_genus`
- `no_collector`
- `no_series`
- `no_type`
- `no_nation`
- `no_province`
- numerische Feldwarnungen

Wirkung:

- blockierende Zeilen landen in `Rows With Issues`
- Warnungen bleiben vergleichbar
- betroffene Felder werden auf DB vorbelegt

## Taxon-Sonderfaelle

Der aktuelle Stand unterstuetzt:

- Aufloesung normaler Taxa
- genus-only Normalisierung mit Autorenangabe
- Vorschlagsmechanismus fuer `no_taxa` ueber TaxaMatch
- Auswahl eines Vorschlags per Dropdown in Run 2

Nicht umgesetzt ist derzeit:

- `insertTaxon(...)`
- externer Taxon-Neuanlagepfad mit `externalID`
- der in der Zielstruktur beschriebene POST-Contract mit `similarTaxa_<index>`, `contentid_<index>`, `position_<index>`

## Update-Strategie

Es werden nur geaenderte Felder aktualisiert.

Ablauf:

- Vergleich `selected` gegen aktuellen DB-Zustand
- nur abweichende Felder gehen in das `UPDATE`
- bei keiner Aenderung: `No changes selected.`

Vor jedem echten Update:

- `begin_transaction()`
- `logSpecimen(..., 1)`
- `UPDATE tbl_specimens ...`
- `commit()` bzw. `rollback()`

## Archivierung und Reset

Nach Run 3 wird ein Report im Session-Kontext gehalten.

`Archive` erzeugt eine ZIP-Datei mit:

- der verarbeiteten Ursprungsdatei
- dem Report als Textdatei

Namensschema:

- `import_YYYYMMDD_HHMMSS.zip`

`Start new update process`:

- loescht den Session-Kontext
- loescht die temporaer gespeicherte Ursprungsdatei
- bringt die Seite in den Run-1-Zustand zurueck

## Soll/Ist gegen `compareDataTableDoc.txt`

### Erfuellt

- Es gibt eine Vergleichstabelle fuer Import- und Datenbankwerte.
- Es gibt eine Rueckgabe der getroffenen Auswahl an den Server.
- Die Datenbasis ist spaltenorientiert und feldkey-basiert konsistent.

### Nicht eingehalten

- Kein `new CompareDataTable(id)` im Browser.
- Keine API `setHeader(...)`, `setImportData(...)`, `setDatabaseData(...)`, `buildTable()`.
- Keine Rueckgabe ueber `compareData.returnChosenData()`.
- Die alte `compareDataTable.js` wird aktuell nicht verwendet.

### Bewertung

Die urspruengliche Idee aus `compareDataTableDoc.txt` wurde funktional teilweise umgesetzt, technisch aber bewusst verlassen.

Der aktuelle Stand folgt nicht der dort beschriebenen Komponenten-API, sondern einem hybriden serverseitig/JS-seitigen Seitenworkflow. Das ist fachlich brauchbar, aber keine 1:1-Umsetzung der fruehen Tabellen-Doku.

## Soll/Ist gegen `updateSpecimens_target_structure.md`

### Erfuellt

- eigenstaendiges Feature in `input/updateSpecimens.php`
- zentraler 3-Run-Workflow
- zentrale Run-Erkennung `detectUpdateRun()`
- Parsing ueber eigene Helferfunktionen
- serverseitiges Specimen-Match ueber `collectionID + HerbNummer`
- Hidden-JSON-Payload fuer Run 3
- serverseitige Wiederladung der DB-Daten vor Update
- nur geaenderte Felder werden aktualisiert
- Logging vor dem Update
- Rechtepruefung analog `editSpecimens.php`
- `specimen_ID` wird intern verwendet, aber nicht als frei waehlbare Datenspalte dargestellt
- URL-Download ist inzwischen vorhanden
- Vergleichs-UI nicht auf `document.body`, sondern in serverseitigem Container

### Teilweise erfuellt

- Taxon-Sonderfaelle:
  Vorschlaege bei `similar_taxa` sind vorhanden, aber keine vollstaendige Taxon-Neuanlage.
- Wiederverwendung aus `importSpecimens.php`:
  Parsing- und Aufloesungslogik ist fachlich angelehnt, aber nicht als Shared-Include extrahiert.
- Vergleichs-UI:
  Die technischen Ziele sind weitgehend erreicht, aber nicht ueber eine neue `compareDataTable.js` umgesetzt.

### Nicht erfuellt

- keine neue oder grundlegend ueberarbeitete `input/updateSpecimens/compareDataTable.js` als produktive UI-Schicht
- kein POST-Contract fuer `similarTaxa_<index>`, `externalID`, `contentid_<index>`, `position_<index>`
- keine Taxon-Neuanlage in Run 3
- keine Trennung in separate Shared-Includes fuer gemeinsam genutzte Importlogik

### Bewertung

Die Zielstruktur ist in den Kernpunkten weitgehend eingehalten. Der aktuelle Stand entspricht vor allem den Punkten:

- 3-Run-Seite
- serverseitiges Matching
- Hidden-JSON-Payload
- selektives Update
- Logging und Rechtepruefung

Die groessten offenen Punkte liegen bei den erweiterten Taxon-Faellen und bei der urspruenglich angedachten JS-Komponentenstruktur.

## Fazit

`updateSpecimens.php` ist inzwischen ein funktionierender fachlicher Update-Prozess und kein reiner Prototyp mehr.

Die fruehe Tabellen-Doku `compareDataTableDoc.txt` ist nur noch als Konzeptreferenz brauchbar. Die spaeter definierte Zielstruktur aus `updateSpecimens_target_structure.md` wird in den wichtigen Kernanforderungen ueberwiegend eingehalten, jedoch mit offenen Punkten bei:

- Taxon-Neuanlage
- formalisiertem Taxon-POST-Contract
- produktiver Wiederverwendung oder Erneuerung von `compareDataTable.js`

## Soll/Ist gegen `updateSpecimens_doc.txt`

### Erfuellt

- Der Prozess ist als Single-File-Anwendung `updateSpecimens.php` umgesetzt.
- Der Ablauf ist weiterhin ein 3-Run-Workflow.
- In Run 2 wird die Datei geparst und fuer weitere Aktionen angezeigt.
- Es gibt eine Vergleichstabelle mit zwei Unterzeilen pro Datensatz: Import oben, Datenbank unten.
- Zellen sind einzeln waehbar.
- Zeilen sind komplett waehbar.
- Spalten sind komplett waehbar.
- Die Benutzerauswahl wird fuer Run 3 an dasselbe Script zurueckgegeben.
- In Run 3 werden `tbl_specimens`-Datensaetze aktualisiert.
- Die in der Doku genannten Statuswerte sind im Kern vorhanden: `no_collection`, `no_identstatus`, `no_taxa`, `similar_taxa`, `no_genus`, `no_collector`, `no_series`, `no_type`, `no_nation`, `no_province`, `OK`.

### Teilweise erfuellt

- Die in der fruehen Doku genannten Datenstrukturen (`$update`, `$status`, `$data`, `$exists`, `$header`, `$namedColumns`) existieren nicht mehr unter diesen Namen, werden aber funktional durch `raw`, `statusCodes`, `importNormalized`, `databaseNormalized`, `display`, `getUpdateFieldDefinitions()` und `payloadSeed` ersetzt.
- Taxon-Sonderfaelle werden angezeigt, aber nicht als vollstaendiger Block-2/Block-3-Workflow wie in `importSpecimens.php`.
- Der 3rd-run aktualisiert Specimens, importiert aber derzeit nicht selbst neue Taxa in weitere Tabellen.

### Nicht erfuellt

- `$_POST['update_data']` ist nicht mehr das eigentliche Run-3-Kriterium. Der aktuelle Workflow nutzt primaer `update_payload`; `update_data` existiert nur noch als Submit-Buttonname.
- Das in der Doku genannte Flag `$updateableTaxaPresent` gibt es im aktuellen Code nicht als zentrales Boolean mehr.
- Ein echter Taxon-Insert-Pfad analog `importSpecimens.php / Block 2 oder 3` ist nicht implementiert.
- Der Satz "taxa will be imported into various tables" trifft auf den aktuellen Stand nicht zu.

### Bewertung

`updateSpecimens_doc.txt` wird in den grundlegenden Ablauf- und UI-Anforderungen weitgehend eingehalten.

Nicht mehr aktuell sind vor allem die alten Variablennamen und die Erwartung, dass Run 3 auch neue Taxa in weitere Tabellen importiert. Der aktuelle Stand ist fachlich bereits ein echter Specimen-Update-Prozess, aber noch kein vollstaendiger Taxon-Nachbearbeitungsprozess wie die fruehe Doku ihn andeutet.
