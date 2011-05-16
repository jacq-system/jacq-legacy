<?php
/**
 * herbardb synonyms class - methods for the handling of synonyms
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsSynonyms
 * @subpackage classes
 */
class clsSynonyms
{
/*************\
|             |
|  variables  |
|             |
\*************/

/***************\
|               |
|  constructor  |
|               |
\***************/

/**
 * constructor
 */
public function __construct ()
{

}


/*******************\
|                   |
|  public functions |
|                   |
\*******************/

public function getCloud ($taxonID)
{
    $taxonID = intval($taxonID);

    $order = " ORDER BY genus, epithet, author, epithet1, author1, epithet2, author2, epithet3, author3";

    $result = db_query("SELECT ts.taxonID, ts.synID, tst.statusID,
                        FROM tbl_tax_species ts
                         LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                        WHERE ts.taxonID = '$taxonID'");
    if (mysql_num_rows($result) == 0) return array();  // nothing found
    $row = mysql_fetch_array($result);

    // 1 = x (hybrid name), 96 = acc (accepted name), 97 = prov. acc. (provisionally accepted name), 103 = appl. incert. (application unceratin)
    if ($row['statusID'] == 96 || $row['statusID'] == 97 || $row['statusID'] == 103 || $row['statusID'] == 1) {
        $id = $row['taxonID'];
    } else {
        $id = $row['synID'];
    }

    $ret = array();
    do {
        $result = db_query("SELECT ts.taxonID, ts.basID, ts.synID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status, tst.statusID,
                             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                             ta4.author author4, ta5.author author5,
                             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                             te4.epithet epithet4, te5.epithet epithet5
                            FROM tbl_tax_species ts
                             LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                             LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                             LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                            WHERE taxonID = '$id'");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);

            $repeat = false;
            if (!empty($row['synID']) && $repeatCtr > 0) {
                $repeatCtr--;
                $repeat = true;
            }

            if ($short) {
                echo "<b>" . taxonList($row) . "</b>" . protologList($row['taxonID'], true) . "<br>\n";
            } else {
                echo "<b>" . taxonList($row) . "</b><br>\n" . protologList($row['taxonID']) . "<br>\n";
            }
            if (empty($row['synID']) && empty($row['basID'])) {
                typusList($row['taxonID'], false);
            }

            $tableStart = "<table cellspacing=\"0\" cellpadding=\"2\">";
            $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status,
                     ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                     ta4.author author4, ta5.author author5,
                     te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                     te4.epithet epithet4, te5.epithet epithet5
                    FROM tbl_tax_species ts
                     LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                     LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                     LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                     LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                     LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                     LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                     LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                     LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                     LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                    WHERE synID = '" . mysql_escape_string($id) . "' ";
            if (empty($row['basID'])) {
                $result2 = db_query($sql . "AND basID='" . mysql_escape_string($id) . "'");
            } else {
                $result2 = db_query($sql . "AND (basID IS NULL OR basID='" . mysql_escape_string($id) . "') AND taxonID='" . $row['basID'] . "'");
            }

            while ($row2 = mysql_fetch_array($result2)) {
                echo $tableStart;
                echo item(20, $row2, $short, "&equiv;");
                typusList($row2['taxonID'], true);
                echo "</table>\n";
                $result3 = db_query($sql . "AND basID='" . $row2['taxonID'] . "'" . $order);
                while ($row3 = mysql_fetch_array($result3)) {
                    echo $tableStart;
                    echo item(40, $row3, $short, "&equiv;");
                    echo "</table>\n";
                }
            }
            if (empty($row['basID'])) {
                $result2 = db_query($sql . "AND basID IS NULL" . $order);
            } else {
                $result2 = db_query($sql . "AND (basID IS NULL OR basID='" . mysql_escape_string($id) . "') AND taxonID!='" . $row['basID'] . "'" . $order);
            }

            while ($row2 = mysql_fetch_array($result2)) {
                echo $tableStart;
                echo item(20, $row2, $short);
                typusList($row2['taxonID'], true);
                echo "</table>\n";
                $result3 = db_query($sql . "AND basID='" . $row2['taxonID'] . "'". $order);
                while ($row3 = mysql_fetch_array($result3)) {
                    echo $tableStart;
                    echo item(40, $row3, $short, "&equiv;");
                    echo "</table>\n";
                }
            }

            // repeat the loop if the synID is set to anything
            if (!empty($row['synID'])) {
                $id = $row['synID'];
                echo "\n";
            } else {
                $id = 0;
            }
        } else {
            $id = 0;
        }
    } while ($id);

}

/**********************\
|                      |
|  protected functions |
|                      |
\**********************/

/*********************\
|                     |
|  private functions  |
|                     |
\*********************/

}