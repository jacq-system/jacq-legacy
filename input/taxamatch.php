<?php
session_start();
require("inc/connect.php");

function DamerauLevenshteinDistance($srcString, $destString)
{
    $d = array();
    $cost = 0;
    $str1 = str_split($srcString);
    $str1len = count($str1);
    $str2 = str_split($destString);
    $str2len = count($str2);


    for ($i = 0; $i <= $str1len; $i++){
        $d[$i][0] = $i;
    }
    for ($j = 0; $j <= $str2len; $j++){
        $d[0][$j] = $j;
    }
    for ($i = 1; $i <= $str1len; $i++){
        $str1test = $str1[($i-1)];
        for ($j = 1; $j <= $str2len; $j++){
            $str2test = $str2[($j - 1)];
            if ($str1test != $str2test) {
                $cost = 1;
            } else {
                $cost = 0;
            }

            $d[$i][$j] = min( $d[($i - 1)][$j] + 1,                 // Deletion
                              min( $d[$i][($j - 1)] + 1,            // Insertion
                                   $d[($i - 1)][($j - 1)] + $cost   // Substitution
                                 )
                            );

            if (($i > 1) && ($j > 1) && ($str1test == $str2[($j - 2)]) && ($str1[($i - 2)] == $str2test)) {
                $d[$i][$j] = min($d[$i][$j], $d[($i - 2)][($j - 2)] + $cost);
            }
        }
    }
    return $d[$str1len][$str2len];
}



$start = microtime(true);

// check the search-type
if (!empty($_POST['searchGenus'])) {
    $searchTextEnding = '';
    $searchText = $_POST['searchtext'];
    $sql = "SELECT g.genus AS name, f.family, a.author, g.genID
            FROM tbl_tax_genera g, tbl_tax_families f, tbl_tax_authors a
            WHERE g.familyID = f.familyID
             AND g.authorID = a.authorID";
    $res = mysql_query($sql);
    $type = 'genus';
} else if (!empty($_POST['searchFamily'])) {
    $searchTextEnding = $_POST['searchtextEnding'];
    $searchText = $_POST['searchtext'];
    $sql = "SELECT family AS name, familyID
            FROM tbl_tax_families";
    $res = mysql_query($sql);
    $type = 'family';
} else if (!empty($_POST['searchSpecies'])) {
    $searchTextEnding = '';
    $searchText = $_POST['searchtext'];
    $sql = "SELECT g.genus AS name, f.family, genID, a.author
            FROM tbl_tax_genera g, tbl_tax_families f, tbl_tax_authors a
            WHERE g.familyID = f.familyID
             AND g.authorID = a.authorID";
    $res = mysql_query($sql);
    $type = 'species';
} else {
    $searchTextEnding = '';
    $searchText = '';
    $type = '';
}

// do the actual calculation of the distances
if ($type) {
    $lev = array();
    $parts = explode(' ', $searchText);
    $limit = ceil(strlen($parts[0] . $searchTextEnding) / 2);
    if ($limit < 2) $limit = 2;
    $lenSearchText = strlen($parts[0] . $searchTextEnding);
    $ctr = 0;
    while ($row = mysql_fetch_array($res)) {
        $distance = DamerauLevenshteinDistance($parts[0] . $searchTextEnding, $row['name']);
        if ($distance < $limit) {
            $ratio = 1 - $distance / max(strlen($row['name']), $lenSearchText);
            switch ($type) {
                case 'genus':
                    $lev[] = array('name'     => $row['name'],
                                   'distance' => $distance,
                                   'ratio'    => $ratio,
                                   'text'     => $row['name'] . ' ' . $row['author'] . ' (' . $row['family'] . ') <' . $row['genID'] . '>');
                    break;
                case 'family':
                    $lev[] = array('name'     => $row['name'],
                                   'distance' => $distance,
                                   'ratio'    => $ratio,
                                   'text'     => $row['name'] . ' <' . $row['familyID'] . '>');
                    break;
                case 'species':
                    $lev[] = array('name'     => $row['name'],
                                   'distance' => $distance,
                                   'ratio'    => $ratio,
                                   'text'     => $row['name'] . ' ' . $row['author'] . ' (' . $row['family'] . ') <' . $row['genID'] . '>',
                                   'genID'    => $row['genID']);
                    break;
            }
        }
        $ctr++;
    }
    if (count($lev) > 0) {
        foreach ($lev as $key => $row) {
            $sort1[$key] = $row['distance'];
            $sort2[$key] = $row['ratio'];
            $sort3[$key] = $row['name'];
        }
        array_multisort($sort1, SORT_NUMERIC, $sort2, SORT_DESC, SORT_NUMERIC, $sort3, $lev);
    }
}

if ($type == 'species') {
    $parts = explode(' ', $searchText);
    if (!empty($parts[1])) {
        $limit = ceil(strlen($parts[1]) / 2);
        $lenSearchText = strlen($parts[1]);
        foreach ($lev as $key => $val) {
            $lev2[$key] = array();
            $sql = "SELECT ts.synID, ts.taxonID,
                     te.epithet epithet0,  te1.epithet epithet1, te2.epithet epithet2,
                     te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                     ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                     ta4.author author4, ta5.author author5
                    FROM tbl_tax_species ts
                     LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                     LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
                     LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
                     LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
                     LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
                     LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
                     LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
                    WHERE ts.genID = '" . $val['genID'] . "'";
            $res = mysql_query($sql);
            while ($row = mysql_fetch_array($res)) {
                $distance = -1;
                for ($i = 0; $i <= 5; $i++) {
                    if ($row['epithet' . $i]) {
                        $distance = DamerauLevenshteinDistance($parts[1], $row['epithet' . $i]);
                        if ($distance < $limit) {
                            $ratio = 1 - $distance / max(strlen($row['epithet' . $i]), $lenSearchText);

                            if ($row['synID']) {
                                $sql = "SELECT ts.taxonID, tg.genus,
                                         ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                                         ta4.author author4, ta5.author author5,
                                         te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                                         te4.epithet epithet4, te5.epithet epithet5
                                        FROM tbl_tax_species ts
                                         LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
                                         LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
                                         LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
                                         LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
                                         LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
                                         LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
                                         LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
                                         LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
                                         LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
                                         LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
                                         LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
                                         LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
                                         LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                                        WHERE ts.taxonID='".mysql_escape_string($row['synID'])."'";
                                $result2 = db_query($sql);
                                $row2 = mysql_fetch_array($result2);
                                $syn = $row2['genus'];
                                if ($row2['epithet'])  $syn .= " ".$row2['epithet']." ".$row2['author'];
                                if ($row2['epithet1']) $syn .= " subsp. ".$row2['epithet1']." ".$row2['author1'];
                                if ($row2['epithet2']) $syn .= " var. ".$row2['epithet2']." ".$row2['author2'];
                                if ($row2['epithet3']) $syn .= " subvar. ".$row2['epithet3']." ".$row2['author3'];
                                if ($row2['epithet4']) $syn .= " forma ".$row2['epithet4']." ".$row2['author4'];
                                if ($row2['epithet5']) $syn .= " subforma ".$row2['epithet5']." ".$row2['author5'];
                                $syn .= ' <' . $row2['taxonID'] . '>';
                            } else {
                                $syn = '';
                            }

                            $text = $val['name'];
                            if ($row['epithet0']) $text .= " ".$row['epithet0']." ".$row['author'];
                            if ($row['epithet1']) $text .= " subsp. ".$row['epithet1']." ".$row['author1'];
                            if ($row['epithet2']) $text .= " var. ".$row['epithet2']." ".$row['author2'];
                            if ($row['epithet3']) $text .= " subvar. ".$row['epithet3']." ".$row['author3'];
                            if ($row['epithet4']) $text .= " forma ".$row['epithet4']." ".$row['author4'];
                            if ($row['epithet5']) $text .= " subforma ".$row['epithet5']." ".$row['author5'];
                            $text .= ' <' . $row['taxonID'] . '>';

                            $lev2[$key][] = array('name'     => $row['epithet' . $i],
                                                  'distance' => $distance,
                                                  'ratio'    => $ratio,
                                                  'text'     => $text,
                                                  'syn'      => $syn);
                            break;
                        }
                    }
                }
                $ctr++;
            }
            if (count($lev2[$key]) > 0) {
                $sort1 = array();
                $sort2 = array();
                $sort3 = array();
                foreach ($lev2[$key] as $key2 => $row2) {
                    $sort1[$key2] = $row2['distance'];
                    $sort2[$key2] = $row2['ratio'];
                    $sort3[$key2] = $row2['name'];
                }
                array_multisort($sort1, SORT_NUMERIC, $sort2, SORT_DESC, SORT_NUMERIC, $sort3, $lev2[$key]);
            }
        }
    }
}

$stop = microtime(true);

// make dropdown for text endings
$sql = "SELECT bot_rank_suffix, zoo_rank_suffix
        FROM tbl_tax_rank
        WHERE rank = 'family'";
$res = db_query($sql);
$dropdownEnding = "<select name='searchtextEnding'>\n"
                . "<option value=''" . (($searchTextEnding == '') ? ' selected' : '') . ">-</option>\n";
while ($row = mysql_fetch_array($res)) {
    $dropdownEnding .= "<option value='"
                     . substr($row['bot_rank_suffix'], 1)
                     . "'" . (($searchTextEnding == substr($row['bot_rank_suffix'], 1)) ? ' selected' : '')
                     . ">"
                     . $row['bot_rank_suffix'] . "</option>\n";
    $dropdownEnding .= "<option value='"
                     . substr($row['zoo_rank_suffix'], 1)
                     . "'" . (($searchTextEnding == substr($row['zoo_rank_suffix'], 1)) ? ' selected' : '')
                     . ">"
                     . $row['zoo_rank_suffix'] . "</option>\n";
}
$dropdownEnding .= "</select>\n";

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - taxamatch</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style>
    body { background-color:lightgreen; }
  </style>
</head>

<body>
<p>
  <form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">
    <table>
      <tr><td></td><td>family suffix</td></tr>
      <tr>
        <td><input type="text" name="searchtext" value="<?php echo $searchText; ?>" style="width:30em;"></td>
        <td><?php echo $dropdownEnding; ?></td>
      </tr><tr>
        <td colspan="2">
          <input type="submit" value="search species" name="searchSpecies">
          <input type="submit" value="search genus" name="searchGenus">
          <input type="submit" value="search family" name="searchFamily">
        </td>
      </tr>
    </table>
  </form>
</p>

<p>
<?php
if ($type){
    echo "<big><b>Search for '" . $searchText . "': (" . count($lev) . " matches found)</b></big><br>\n"
       . $ctr . " rows checked, " . number_format(($stop - $start), 2) . " seconds needed<br>\n";

    echo "<table>\n"
       . "<tr><th></th><th>Dist.</th><th>Ratio</th><th></th><th></th><th></th></tr>\n";
    foreach ($lev as $key => $row) {
        if ($type != 'species' || count($lev2[$key]) > 0) {
            echo "<tr valign='baseline'>"
               . '<td><b>' . $row['text'] . '</b></td>'
               . '<td>&nbsp;' . $row['distance'] . '</td>'
               . '<td align="right">&nbsp;' . number_format($row['ratio'] * 100, 1) . '%</td>';
            if ($type == 'species') {
                $first = true;
                foreach ($lev2[$key] as $key2 => $row2) {
                    if (!$first) {
                        echo "<td colspan='3'></td>";
                    } else {
                        $first = false;
                    }
                    echo '<td>&nbsp;&nbsp;<b>' . $row2['text'] . '</b></td>'
                       . '<td>&nbsp;' . $row2['distance'] . '&nbsp;</td>'
                       . '<td align="right">&nbsp;' . number_format($row2['ratio'] * 100, 1) . "%</td><tr>\n";
                    if ($row2['syn']) {
                        echo "<tr><td colspan='3'></td><td>&nbsp;&nbsp;&rarr;&nbsp;" . $row2['syn'] . "</td><td colspan='2'></td></tr>\n";
                    }
                }
            } else {
                echo "<td></td><td></td><td></td></tr>\n";
            }
        }
    }
    echo "</table>\n";
}
?>
</p>

</body>
</html>