# Offene Punkte `updateSpecimens`

## Prioritaet 1

- Taxon-Neuanlage in Run 3 ergaenzen.
  Aktuell gibt es nur Vorschlaege ueber TaxaMatch und die Auswahl bestehender Taxa. Ein echter Pfad fuer `insertTaxon(...)` fehlt noch.

- Taxon-POST-Contract fuer Sonderfaelle formalisieren.
  Die in der Zielstruktur genannten Felder `similarTaxa_<index>`, `externalID`, `contentid_<index>` und `position_<index>` sind noch nicht umgesetzt.

- Browser-Endtest fuer den Abschluss-Workflow machen.
  Geprueft werden sollten `Archive`, ZIP-Inhalt, Dateiname `import_YYYYMMDD_HHMMSS.zip` und `Start new update process`.

## Prioritaet 2

- Vergleichs-UI technisch konsolidieren.
  Der aktuelle Tabellen- und JS-Block funktioniert innerhalb von `updateSpecimens.php`, ist aber keine saubere produktive Nachfolge von `compareDataTable.js`.

- Gemeinsame Import-Helfer extrahieren.
  Parsing-, URL-Download- und Aufloesungslogik sind in `importSpecimens.php` und `updateSpecimens.php` parallel vorhanden und sollten mittelfristig in Shared-Includes zusammengefuehrt werden.

- Tests fuer Statuscodes und Feld-Mappings nachziehen.
  Besonders relevant sind `no_taxa`, Collector-Aufspaltung, nullable Bool-Felder, `observation` und optionales `notes_internal`.

## Prioritaet 3

- Prozessdoku mit konkreten Testfaellen ergaenzen.
  Sinnvoll waeren Beispiel-Dateien und erwartete Ergebnisse fuer Run 2 und Run 3.

- UI-Feinschliff der Vergleichsansicht.
  Dazu gehoeren visuelle Konsistenz, klare Fehlerhinweise und ggf. eine weitere Entkopplung von PHP-Markup und JS-Logik.
