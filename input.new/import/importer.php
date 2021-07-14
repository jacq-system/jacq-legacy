<?php

if (isset($_POST['import'])) {
    session_start();

    $summary = array(); //[] is not working with php<5.4
    foreach ($_POST['checkList'] as $key => $value) {
        foreach ($_SESSION['tableData'] as $row) {
            switch ($value) {
                case "import":
                    if ($row['importSet']['data'][0] == $key) {
                       array_push($summary, $row['importSet']['data']);
                    }
                    break;
                case "db":
                    if (!isset($row['dbSet'])) {
                        break;
                    }
                    if ($row['dbSet']['data'][0] == $key) {
                       array_push($summary, $row['dbSet']['data']);
                    }
                    break;
            }
        }
    }
    $_SESSION['importData'] = $summary;
    #print_r($summary);
    header("Location: " . $_POST['redirect']);
}

class ValidateImporter {
    function __construct($importData, $dbData, $columnNames, $redirect)
    {
        if (!$this->checkGivenData($importData, $dbData, $columnNames)) {
            throw new Exception("Can't build table. Given data has another count of columns than column names given!");
        }
        $this->redirect = $redirect;
        $tableData = $this->buildTableData($importData, $dbData);
        $_SESSION['tableData'] = $tableData;
        $this->buildTable($tableData, $columnNames);
    }

    function checkGivenData($importData, $dbData, $columnNames) {
        $counter = count($columnNames);

        foreach ($importData as $row) {
            if (count($row) != $counter) {
                return False;
            }
        }
        foreach ($dbData as $row) {
            if (count($row) != $counter) {
                return False;
            }
        }
        return True;
    }

    function buildTableData($importData, $dbData) {
        $tableData = array(); //[] is not working with php<5.4
        $i = 0;

        foreach ($importData as $importRow) {
            $tableData[$i]['importSet']['data'] = $importRow;
            $tableData[$i]['importSet']['preselected'] = "checked";

            foreach ($dbData as $dbRow) {
                if ($importRow[0] == $dbRow[0]) { # found the dbRow with the same Id as the importRow
                    $tableData[$i]['dbSet']['data'] = $dbRow;
                    $tableData[$i]['dbSet']['preselected'] = "unchecked";
                    unset($dbRow);
                    break;
                }
            }
            $i++;
        }
        return $tableData;
    }

    function buildTable($tableData, $columnNames) {
        array_push($columnNames, "");

        echo("<form id='importForm' action='importer.php' method='post'>");
        echo("<table>");
        echo("<tr>");
        foreach ($columnNames as $header) {
            echo("<th align='left'>");
            echo($header);
            echo("</th>");
        }
        echo("</tr>");

        foreach ($tableData as $row) {
            // if db data exists build both rows and color cells with different content
            if (isset($row['dbSet']['data'])) {
                echo("<tr>");
                foreach ($row['dbSet']['data'] as $cellItem_db) {
                    echo("<td>");
                    echo($cellItem_db);
                    echo("</td>");
                }
                echo("<td>");
                echo("<input type='radio' class='dbSelect' name='checkList[". $row['dbSet']['data'][0] ."]' value='db' " . $row['dbSet']['preselected'] . ">");
                echo("</td>");
                echo("</tr>");

                // build import-data row with diffs
                echo("<tr class='hLine'>");
                for ($i = 0; $i < count($row['importSet']['data']); $i++) {
                    if ($row['dbSet']['data'][$i] != $row['importSet']['data'][$i]) {
                        echo("<td class='updatedDataCell'>");
                    } else {
                        echo("<td>");
                    }
                    echo($row['importSet']['data'][$i]);
                    echo("</td>");
                }
                echo("<td>");
                echo("<input type='radio' class='importSelect' name='checkList[". $row['importSet']['data'][0] ."]' value='import' " . $row['importSet']['preselected'] . ">");
                echo("</td>");
                echo("</tr>");
            } else {
                // build import row; no db data exists so color the row marked as new data
                echo("<tr class='hLine newDataRow'>");
                foreach ($row['importSet']['data'] as $cellItem_import) {
                    echo("<td>");
                    echo($cellItem_import);
                    echo("</td>");
                }
                echo("<td>");
                echo("<input type='radio' class='importSelect' name='checkList[". $row['importSet']['data'][0] ."]' value='import' " . $row['importSet']['preselected'] . ">");
                echo("</td>");
                echo("</tr>");
            }
        }
        echo("</table>");
        echo("<input type='hidden' name='redirect' value='" . $this->redirect . "'>");
        echo("<input type='submit' name='import' value='import'>");
        echo("</form>");
        echo("<input type='button' name='dbselect' value='select all database entries' onclick='selectData(\"db\")'>");
        echo("<input type='button' name='importselect' value='select all import entries' onclick='selectData(\"import\")'>");
    }
}
?>

<style>
    table {
        border: 1px solid black;
        border-collapse: collapse;
    }
    th, td {
        border-left: 1px solid black;
    }
    th, .hLine {
        border-bottom: 1px solid black;
    }
    .updatedDataCell {
        background-color: aqua;
    }
    .newDataRow {
        background-color: greenyellow;
    }
</style>

<script>
    function selectData (selector) {
        var _db = document.getElementsByClassName('dbSelect');
        var _import = document.getElementsByClassName('importSelect');

        if (selector == "db") {
            for (i = 0; i < _db.length; i++) {
                _db[i].checked = true;
            }
            for (i = 0; i < _import.length; i++) {
                _import[i].checked = false;
            }
        }
        if (selector == "import") {
            for (i = 0; i < _db.length; i++) {
                _db[i].checked = false;
            }
            for (i = 0; i < _import.length; i++) {
                _import[i].checked = true;
            }
        }

    }
</script>
