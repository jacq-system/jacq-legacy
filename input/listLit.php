<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

$nrSel = (!empty($_GET['nr'])) ? intval($_GET['nr']) : 0;

if (!isset($_SESSION['litBestand'])) $_SESSION['litBestand'] = "everything";
if (!isset($_SESSION['litCategory'])) $_SESSION['litCategory'] = "everything";

if (isset($_POST['search'])) {
    $_SESSION['litType']      = 1;
    $_SESSION['litTitel']     = $_POST['titel'];
    $_SESSION['litAutor']     = $_POST['autor'];
    $_SESSION['litPeriod']    = $_POST['periodical'];
    $_SESSION['litJahr']      = $_POST['jahr'];
    $_SESSION['litVol']       = $_POST['vol'];
    $_SESSION['litPp']        = $_POST['pp'];
    $_SESSION['litBestand']   = $_POST['bestand'];
    $_SESSION['litKeywords']  = $_POST['keywords'];
    $_SESSION['litCategory']  = $_POST['category'];
    $_SESSION['litContainer'] = (!empty($_POST['showContainer'])) ? 1 : 0;
    $_SESSION['litOrder']     = "titel, autor, jahr";
    $_SESSION['litOrTyp']     = 1;
} else if (isset($_GET['ltitel'])) {
    $_SESSION['litType']      = 2;
    $_SESSION['litTitel']     = $_GET['ltitel'];
    $_SESSION['litAutor']     = "";
    $_SESSION['litPeriod']    = "";
    $_SESSION['litJahr']      = "";
    $_SESSION['litVol']       = "";
    $_SESSION['litPp']        = "";
    $_SESSION['litBestand']   = "everything";
    $_SESSION['litKeywords']  = "";
    $_SESSION['litCategory']  = "everything";
    $_SESSION['litContainer'] = 0;
    $_SESSION['litOrder']     = "titel, autor, jahr";
    $_SESSION['litOrTyp']     = 1;
} else if (isset($_GET['lautor'])) {
    $_SESSION['litType']      = 3;
    $_SESSION['litTitel']     = "";
    $_SESSION['litAutor']     = $_GET['lautor'];
    $_SESSION['litPeriod']    = "";
    $_SESSION['litJahr']      = "";
    $_SESSION['litVol']       = "";
    $_SESSION['litPp']        = "";
    $_SESSION['litBestand']   = "everything";
    $_SESSION['litKeywords']  = "";
    $_SESSION['litCategory']  = "everything";
    $_SESSION['litContainer'] = 0;
    $_SESSION['litOrder']     = "autor, titel, jahr";
    $_SESSION['litOrTyp']     = 2;
} else if (isset($_GET['lempty'])) {
    $_SESSION['litType']      = 4;
    $_SESSION['litTitel']     = "";
    $_SESSION['litAutor']     = "";
    $_SESSION['litPeriod']    = "";
    $_SESSION['litJahr']      = "";
    $_SESSION['litVol']       = "";
    $_SESSION['litPp']        = "";
    $_SESSION['litBestand']   = "everything";
    $_SESSION['litKeywords']  = "";
    $_SESSION['litCategory']  = "everything";
    $_SESSION['litContainer'] = 0;
    $_SESSION['litOrder']     = "autor, titel, jahr";
    $_SESSION['litOrTyp']     = 2;
} else if (isset($_GET['order'])) {
    $_SESSION['litContainer'] = 0;
    if ($_GET['order'] == "a") {
        $_SESSION['litOrder'] = "autor, jahr, periodical, vol, part, ppSort";
        if ($_SESSION['litOrTyp'] == 2) {
            $_SESSION['litOrTyp'] = -2;
        } else {
            $_SESSION['litOrTyp'] = 2;
        }
    }
    else if ($_GET['order'] == "y") {
        $_SESSION['litOrder'] = "jahr, autor, titel";
        if ($_SESSION['litOrTyp'] == 3) {
            $_SESSION['litOrTyp'] = -3;
        } else {
            $_SESSION['litOrTyp'] = 3;
        }
    }
    else if ($_GET['order'] == "p") {
        $_SESSION['litOrder'] = "periodical, vol, part, ppSort, autor, jahr, titel";
        if ($_SESSION['litOrTyp'] == 4) {
            $_SESSION['litOrTyp'] = -4;
        } else {
            $_SESSION['litOrTyp'] = 4;
        }
    }
    else {
        $_SESSION['litOrder'] = "titel, autor, jahr";
        if ($_SESSION['litOrTyp'] == 1) {
            $_SESSION['litOrTyp'] = -1;
        } else {
            $_SESSION['litOrTyp'] = 1;
        }
    }
    if ($_SESSION['litOrTyp'] < 0) $_SESSION['litOrder'] = implode(" DESC, ", explode(", ", $_SESSION['litOrder'])) . " DESC";
}


if (!empty($_POST['select']) && !empty($_POST['citation'])) {
    $location="Location: editLit.php?sel=<" . $_POST['citation'] . ">";
    if (SID != "") $location .= "?" . SID;
    Header($location);
}

function makeDropdown($name, $select, $value, $text)
{
    echo "<select name=\"$name\">\n";
    for ($i = 0; $i < count($value); $i++) {
        echo "  <option";
        if ($value[$i] != $text[$i]) echo " value=\"" . $value[$i] . "\"";
        if ($select == $value[$i]) print " selected";
        echo ">" . htmlspecialchars($text[$i]) . "</option>\n";
    }
    echo "</select>\n";
}


function makeLineFromID ($citationID, $preTitleText)
{
    global $nr, $nrSel, $linkList;

    $sql ="SELECT l.citationID, l.titel, l.jahr, la.autor, lp.periodical, l.vol, l.part, l.pp
           FROM tbl_lit l
            LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
            LEFT JOIN tbl_lit_authors la ON la.autorID=l.autorID
           WHERE citationID = '" . intval($citationID) . "'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $linkList[$nr] = $row['citationID'];

        echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\"><td class=\"out\">";
        if ($_SESSION['litType'] != 4) {
            echo $preTitleText . "<a href=\"editLit.php?sel=" . htmlentities("<" . $row['citationID'] . ">") . "&nr=$nr\">"
               . makeTextShorter($row['titel'], 60)
               . "</a></td><td class=\"out\">";
        }
        echo "<a href=\"editLit.php?sel=" . htmlentities("<" . $row['citationID'] . ">") . "&nr=$nr\">"
           . trim($row['periodical'] . " " . $row['vol'] . (($row['part']) ? " (" . $row['part'] . ")" : "") . ": " . $row['pp'] . ".")
           . "</a></td><td class=\"out\">"
           . "<a href=\"editLit.php?sel=" . htmlentities("<" . $row['citationID'] . ">") . "&nr=$nr\">"
           . $row['autor']
           . "</a></td><td class=\"out\">"
           . $row['jahr']
           . "</td></tr>\n";

        $nr++;
    }
}


function makeTextShorter($text, $limit, $tail = 10)
{
    if (strlen($text) > $limit - 3) {
        return substr($text, 0, $limit - $tail - 3) . '...' . substr($text, -$tail);
    } else {
        return $text;
    }
}


function showTree($tree, $layer = 0, $leftString = '')
{
    if ($layer == 0) {
        makeLineFromID($tree[0]['id'], '');
        showTree($tree[0]['children'], 1);
    } else {
        for ($i = 0; $i < count($tree); $i++) {
            if ($i < count($tree) - 1) {
                $sign   = "<img src=\"webimages/tree_line.gif\">";
                $branch = "<img src=\"webimages/tree_branchLine.gif\">";
            } else {
                $sign = "<img src=\"webimages/tree_empty.gif\">";
                $branch = "<img src=\"webimages/tree_branch.gif\">";
            }
            makeLineFromID($tree[$i]['id'], $leftString . $branch . ' ');
            showTree($tree[$i]['children'], $layer + 1, $leftString . $sign);
        }
    }
}


function getChildrenOfParent ($parentID)
{
    $sql = "SELECT citation_child_ID
            FROM tbl_lit_container lc
             LEFT JOIN tbl_lit l ON l.citationID = lc.citation_child_ID
            WHERE citation_parent_ID = '" . intval($parentID) . "'
            ORDER BY l.ppSort";
    $result = db_query($sql);
    $ret = array();
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $ret[] = array('id'       => $row['citation_child_ID'],
                           'children' => getChildrenOfParent($row['citation_child_ID']));
        }
    }
    return $ret;
}


function listContainer ($citationID)
{
    for ($i = 0; $i < 1000; $i++) {
        $sql = "SELECT citation_parent_ID
                FROM tbl_lit_container
                WHERE citation_child_ID = '$citationID'";
        $result = db_query($sql);
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $citationID = $row['citation_parent_ID'];
        } else {
            break;
        }
    }

    if ($i < 1000) {
        $tree = array();
        $tree[] = array('id'       => $citationID,
                        'children' => getChildrenOfParent($citationID));
        showTree($tree);
    }
}


unset($bestand);
$bestand[] = "everything";
$sql = "SELECT bestand FROM tbl_lit GROUP BY bestand ORDER BY bestand";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $bestand[] = $row['bestand'];
        }
    }
}

unset($category);
$category[] = "everything";
$sql = "SELECT category FROM tbl_lit GROUP BY category ORDER BY category";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $category[] = $row['category'];
        }
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Literature</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<input class="button" type="button" value=" close window " onclick="self.close()" id="close">

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">
<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right">&nbsp;<b>Title:</b></td>
    <td><input type="text" name="titel" value="<?php echoSpecial('litTitel', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Periodical:</b></td>
    <td><input type="text" name="periodical" value="<?php echoSpecial('litPeriod', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Volume:</b></td>
    <td><input type="text" name="vol" value="<?php echoSpecial('litVol', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Author:</b></td>
    <td><input type="text" name="autor" value="<?php echoSpecial('litAutor', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Year:</b></td>
    <td><input type="text" name="jahr" value="<?php echoSpecial('litJahr', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Page:</b></td>
    <td><input type="text" name="pp" value="<?php echoSpecial('litPp', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Listing:</b></td>
    <td><?php makeDropdown("bestand",$_SESSION['litBestand'],$bestand,$bestand); ?></td>
  <td align="right">&nbsp;<b>Keywords:</b></td>
    <td><input type="text" name="keywords" value="<?php echoSpecial('litKeywords', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Categories:</b></td>
    <td><?php makeDropdown("category",$_SESSION['litCategory'],$category,$category); ?></td>
</tr><tr>
  <td colspan="6">
    <input class="button" type="submit" name="search" value=" search ">
    &nbsp;
    <input type="checkbox" name="showContainer"<?php if ($_SESSION['litContainer']) echo " checked"; ?>><b>list as containers</b>
  </td>
</tr>
</table>
</form>

<table><tr>
<?php if (($_SESSION['editControl'] & 0x20) != 0): ?>
<td>
  <input class="button" type="button" value="new entry" onClick="self.location.href='editLit.php'">
</td><td style="width: 3em">&nbsp;</td>
<?php endif; ?>
<td>
  <form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">
    <b>CitationID:</b> <input type="text" name="citation" value="<?php echoSpecial('citation', 'POST'); ?>">
    <input class="button" type="submit" name="select" value=" Edit ">
  </form>
</td></tr></table>

<?php
/*  Buchstabenleisten 'Title' und 'Author' abgeschaltet
echo "<form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";
echo "<b>Title:</b> ";
echo "<input class=\"button\" type=\"button\" value=\"empty\" ".
     "onClick=\"self.location.href='".$_SERVER['PHP_SELF']."?lempty=1'\">\n";
for ($i=0,$a='A';$i<26;$i++,$a++)
  echo "<input class=\"button\" type=\"button\" value=\"$a\" style=\"width: 1.6em\" ".
       "onClick=\"self.location.href='".$_SERVER['PHP_SELF']."?ltitel=$a'\"\n>";
echo "</form>\n";

echo "<form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";
echo "<b>Author:</b> ";
for ($i=0,$a='A';$i<26;$i++,$a++)
  echo "<input class=\"button\" type=\"button\" value=\"$a\" style=\"width: 1.6em\" ".
       "onClick=\"self.location.href='".$_SERVER['PHP_SELF']."?lautor=$a'\"\n>";
echo "</form>\n";
*/

if ($_SESSION['litType']) {
    $sql = "SELECT tl.citationID, tl.titel, tl.jahr, ta.autor,
             tp.periodical, tl.vol, tl.part, tl.pp
            FROM tbl_lit tl
             LEFT JOIN tbl_lit_authors ta ON ta.autorID = tl.autorID
             LEFT JOIN tbl_lit_periodicals tp ON tp.periodicalID = tl.periodicalID ";
    if ($_SESSION['litType'] == 4) {
        $sql .= "WHERE titel IS NULL";
    } else if ($_SESSION['litType'] == 3) {
        $sql .= "WHERE autor LIKE '" . mysql_escape_string($_SESSION['litAutor']) . "%'";
    } else if ($_SESSION['litType'] == 2) {
        $sql .= "WHERE titel LIKE '" . mysql_escape_string($_SESSION['litTitel']) . "%'";
    } else {
        $sql .= "WHERE 1";
        if (trim($_SESSION['litTitel'])) {
            $sql .= " AND titel LIKE '%" . mysql_escape_string($_SESSION['litTitel']) . "%'";
        }
        if (trim($_SESSION['litAutor'])) {
            $sql .= " AND autor LIKE '%" . mysql_escape_string($_SESSION['litAutor']) . "%'";
        }
        if (trim($_SESSION['litPeriod'])) {
            $sql .= " AND periodical LIKE '%" . mysql_escape_string($_SESSION['litPeriod']) . "%'";
        }
        if (trim($_SESSION['litJahr'])) {
            $sql .= " AND jahr LIKE '" . mysql_escape_string($_SESSION['litJahr']) . "%'";
        }
        if (trim($_SESSION['litVol'])) {
            $sql .= " AND vol LIKE '" . mysql_escape_string($_SESSION['litVol']) . "'";
        }
        if (trim($_SESSION['litPp'])) {
            $sql .= " AND pp LIKE '" . mysql_escape_string($_SESSION['litPp']) . "'";
        }
        if (trim($_SESSION['litKeywords'])) {
            $sql .= " AND tl.keywords LIKE '%" . mysql_escape_string($_SESSION['litKeywords']) . "%'";
        }
        if ($_SESSION['litBestand'] != "everything" && $_SESSION['litBestand']) {
            $sql .= " AND tl.bestand='" . mysql_escape_string($_SESSION['litBestand']) . "'";
        }
        if ($_SESSION['litCategory'] != "everything" && $_SESSION['litCategory']) {
            $sql .= " AND tl.category='" . mysql_escape_string($_SESSION['litCategory']) . "'";
        }
    }
    $sql .= " ORDER BY " . $_SESSION['litOrder'];

    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        echo "<table class=\"out\" cellspacing=\"0\">\n";
        echo "<tr class=\"out\">";
        if ($_SESSION['litType'] != 4) {
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=t\">Title</a>" . sortItem($_SESSION['litOrTyp'], 1) . "</th>";
        }
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=p\">Periodical/Monograph</a>" . sortItem($_SESSION['litOrTyp'], 4) . "</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=a\">Author</a>" . sortItem($_SESSION['litOrTyp'], 2) . "</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=y\">&nbsp;Date&nbsp;</a>" . sortItem($_SESSION['litOrTyp'], 3) . "</th>";
        echo "</tr>\n";
        $nr = 1;
        if (!$_SESSION['litContainer']) {
            while ($row = mysql_fetch_array($result)) {
                $linkList[$nr] = $row['citationID'];
                echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\"><td class=\"out\">";
                if ($_SESSION['litType'] != 4) {
                    echo "<a href=\"editLit.php?sel=" . htmlentities("<" . $row['citationID'] . ">") . "&nr=$nr\">";
                    echo $row['titel'];
                    echo "</a></td><td class=\"out\">";
                }
                echo "<a href=\"editLit.php?sel=" . htmlentities("<" . $row['citationID'] . ">") . "&nr=$nr\">";
                echo trim($row['periodical'] . " " . $row['vol'] . (($row['part']) ? " (" . $row['part'] . ")" : "") . ": " . $row['pp'] . ".");
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"editLit.php?sel=" . htmlentities("<" . $row['citationID'] . ">") . "&nr=$nr\">";
                echo $row['autor'];
                echo "</a></td><td class=\"out\">";
                echo $row['jahr'];
                echo "</td></tr>\n";
                $nr++;
            }
        } else {
            while ($row = mysql_fetch_array($result)) {
                if (!in_array($row['citationID'], $linkList)) {
                    listContainer($row['citationID']);
                }
            }
        }
        $linkList[0] = $nr - 1;
        $_SESSION['liLinkList'] = $linkList;
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
}
?>

</body>
</html>