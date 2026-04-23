# Zielstruktur fuer `updateSpecimens.php`

## Grundsatz

`updateSpecimens` sollte als neues, eigenstaendiges Feature aufgebaut werden. Die vorhandene Doku in `updateSpecimens_doc.txt` bleibt dabei die fachliche Spezifikation, aber nicht die technische Basis. Die vorhandene `compareDataTable.js` kann als UI-Idee dienen, sollte jedoch technisch ueberarbeitet oder neu geschrieben werden.

Empfohlene Struktur:

- `input/updateSpecimens.php`
  Single-File-Page fuer Upload, Vergleich, Rueckgabe der Auswahl und DB-Update
- `input/updateSpecimens/compareDataTable.js`
  neu oder stark ueberarbeitet; nur fuer die Vergleichsauswahl verantwortlich
- optionale spaetere Auslagerung:
  gemeinsame Helfer aus `importSpecimens.php` in ein Shared-Include

## Ablauf

Die Seite bleibt gemaess Spezifikation ein 3-Run-Workflow:

1. Run 1
   Formular anzeigen
   Uploadfeld `userfile`
   optional spaeter auch Download-URL wie in `importSpecimens.php`

2. Run 2
   CSV einlesen
   Importwerte parsen und validieren
   passenden existierenden Specimen-Datensatz laden
   Vergleichsansicht rendern
   Taxon-Sonderfaelle unterhalb der betroffenen Zeilen anzeigen

3. Run 3
   Nutzerentscheidung aus Hidden-JSON oder Hidden-Inputs lesen
   ggf. neue Taxa anlegen oder aehnliche Taxa aufloesen
   `tbl_specimens` aktualisieren
   Ergebnis- und Fehlerliste ausgeben

## Verantwortlichkeiten in `updateSpecimens.php`

Die Datei sollte intern klar in Funktionsbloecke getrennt werden:

### 1. Bootstrapping

- `session_start()`
- `require("../inc/connect.php")`
- `require("../inc/log_functions.php")`
- `require_once("../inc/herbardb_input_functions.php")`
- `require_once("../inc/jsonRPCClient.php")`
- `require_once("../inc/clsTaxonTokenizer.php")`

### 2. Run-Erkennung

Empfohlene zentrale Funktion:

```php
function detectUpdateRun(): int
```

Rueckgabe:

- `1` Formular
- `2` Datei wurde geliefert
- `3` Rueckgabe der Auswahl liegt vor

### 3. Parsing und Validierung

Diese Logik sollte nicht frei im Hauptprogramm stehen, sondern in klar benannten Funktionen:

```php
function parseUpdateLine($handle, $minNumOfParts = 6, $delimiter = ';', $enclosure = '"')
function loadUploadedUpdateRows(string $tmpPath): array
function buildParsedUpdateRow(array $rawRow, int $lineNumber): array
function validateParsedUpdateRow(array $parsedRow): array
```

Ziel:

- gleiche CSV-Grundannahmen wie `importSpecimens.php`
- gleiche Feldabbildung wie beim Specimen-Import
- gleiche Statuswerte, soweit sinnvoll:
  `no_collection`, `no_identstatus`, `no_taxa`, `similar_taxa`, `no_genus`, `no_collector`, `no_series`, `no_type`, `no_nation`, `no_province`, `OK`
- zusaetzlich fuer dieses Feature sinnvoll:
  `no_specimen`

## Datenmodell fuer Run 2

Empfohlen ist eine strukturierte Zwischenrepraesentation pro Zeile:

```php
$rows[$i] = array(
    'lineNumber' => 12,
    'raw' => array(...),
    'status' => 'OK',
    'specimen_ID' => 12345,
    'contentID' => 678,
    'position' => 0,
    'parsed' => array(...),
    'database' => array(...),
    'display' => array(
        'header' => array(...),
        'importData' => array(...),
        'databaseData' => array(...),
    ),
    'taxon' => array(
        'importTaxa' => '',
        'similarMatches' => array(),
        'needsExternalID' => false,
    ),
);
```

Wichtig:

- `parsed` verwendet moeglichst Feldnamen aus `tbl_specimens`
- `database` enthaelt dieselben Keys
- `display` ist nur eine View-Schicht fuer die Vergleichstabelle

## Identifikation des zu aktualisierenden Specimens

Die Identifikation darf nicht vom Browser abhaengen, sondern muss serverseitig fuer jede Importzeile aufgebaut werden.

Empfehlung:

- primaer Match ueber `collectionID + HerbNummer`
- gleiches Dublettenverhalten wie in `importSpecimens.php`
- zusaetzlich den gefundenen `specimen_ID` immer explizit mitfuehren

Begruendung:

- fachlich kompatibel zum bestehenden Import
- stabiler als Client-seitige freie IDs
- `specimen_ID` kann in Run 3 direkt fuer `UPDATE tbl_specimens` verwendet werden

## Vergleichs-UI

Die Vergleichstabelle sollte nicht direkt `document.body` beschreiben. Besser:

- PHP rendert einen Container `div`
- PHP gibt pro Block JSON fuer Header und Datensaetze aus
- JS baut die Tabelle innerhalb des Containers
- ein Hidden-Feld `update_payload` enthaelt die aktuelle Auswahl als JSON

Empfohlener Payload:

```json
{
  "12345": {
    "specimen_ID": 12345,
    "selected": {
      "HerbNummer": "B-123",
      "CollNummer": "77",
      "Fundort": "Berlin"
    }
  }
}
```

Die Vergleichs-UI sollte folgende technischen Regeln einhalten:

- `textContent` statt `innerHTML`
- keine Mutation der Originalarrays
- Container als Konstruktorparameter
- eindeutige Spalten-Keys statt nur numerischer Positionen
- ID-Spalte nicht global toggelbar

## Taxon-Sonderfaelle

Die Doku verlangt Bloecke analog zu `importSpecimens.php`.

Empfehlung:

- nur Zeilen mit `no_taxa` oder `similar_taxa` erhalten Zusatzblock
- der Zusatzblock bleibt serverseitig gerendert
- der Vergleich der restlichen Felder bleibt JS-seitig

POST-Contract fuer diese Faelle:

- `similarTaxa_<index>`
- `externalID`
- `contentid_<index>`
- `position_<index>`

Damit bleibt das Verhalten nah an `importSpecimens.php`, und bestehende Taxon-Logik kann weitgehend wiederverwendet werden.

## Run 3: Update-Strategie

Run 3 sollte nicht jede Zeile blind komplett ueberschreiben. Besser:

1. `update_payload` lesen und decodieren
2. serverseitig gegen erlaubte Feldliste validieren
3. pro Zeile aktuelle DB-Daten erneut laden
4. Rechtepruefung und Dublettenpruefung analog zu `editSpecimens.php`
5. nur geaenderte Felder in `UPDATE tbl_specimens SET ...` aufnehmen
6. vor dem Update `logSpecimen($specimen_ID, 1)` ausfuehren

Empfohlene Helfer:

```php
function getAllowedUpdateFields(): array
function decodeUpdatePayload(string $json): array
function buildSpecimenUpdateSql(array $selectedData, array $currentDbRow): string
function updateSpecimenRow(int $specimenId, array $selectedData): array
```

Rueckgabe von `updateSpecimenRow()`:

```php
array(
    'success' => true,
    'message' => '',
    'changedFields' => array('Fundort', 'Bemerkungen')
)
```

## Wiederverwendung aus bestehendem Code

Folgende Logik sollte direkt oder nach kleiner Extraktion wiederverwendet werden:

- CSV-Parsing aus `importSpecimens.php`
- Taxon-Pruefung und `insertTaxon(...)`
- Collector-, Series-, Type-, Nation- und Province-Aufloesung
- Dublettenpruefung fuer `HerbNummer`
- Rechte- und Source-Pruefung aus `editSpecimens.php`
- Logging ueber `logSpecimen(...)`

Nicht direkt uebernehmen:

- die alte `compareDataTable.js` in ihrer aktuellen Form
- lose `echo`-Bloecke ohne klare Datentrennung
- komplette Voll-Updates ohne Feldvergleich

## Empfohlene Feldlisten

Es gibt zwei Feldlisten:

1. fachliche Feldliste fuer Parsing und Validierung
2. sichtbare Feldliste fuer die Vergleichstabelle

Empfehlung:

- Parsing orientiert sich an der Import-Spezifikation
- Anzeige konzentriert sich auf wirklich aenderbare bzw. konfliktbehaftete Felder
- `specimen_ID` wird intern mitgefuehrt, aber nicht als normal waehlbare Datenspalte dargestellt

## Umsetzungsreihenfolge

1. `updateSpecimens.php` als neues Grundgeruest anlegen
2. Run 1 und Run 2 mit Parsing, Match auf existierende Specimens und Statusanzeige bauen
3. neue Vergleichs-UI anbinden
4. Taxon-Sonderfaelle integrieren
5. Run 3 mit validiertem Payload und echten Updates implementieren
6. Syntax- und Testlauf mit kleinen Beispiel-CSVs

## Minimalziel fuer die erste lauffaehige Version

Wenn die Implementierung in Stufen erfolgen soll, ist dieses Minimalziel sinnvoll:

- Upload eines CSV im bestehenden Importformat
- Match auf vorhandene Specimens
- Vergleichstabelle fuer `OK`-Zeilen
- Rueckgabe der Nutzerwahl
- Update einer begrenzten, sicheren Feldmenge
  `CollNummer`, `identstatusID`, `taxonID`, `seriesID`, `series_number`, `Nummer`, `alt_number`, `Datum`, `Datum2`, `det`, `typified`, `typusID`, `taxon_alt`, `NationID`, `provinceID`, `Fundort`, `Fundort_engl`, `Habitat`, `Habitus`, `Bemerkungen`, Koordinatenfelder, `quadrant`, `quadrant_sub`, `exactness`, `altitude_min`, `altitude_max`, `notes_internal`

Danach koennen weitere Randfaelle und Komfortfunktionen folgen.
