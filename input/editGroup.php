<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

if (isset($_GET['sel'])) {
    $sql = "SELECT *
            FROM herbarinput_log.tbl_herbardb_groups
            WHERE groupID = '" . intval($_GET['sel']) . "'";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $p_rights = array();
        foreach ($row as $key => $val) {
            switch ($key) {
    	        case 'groupID':
                    $p_groupID = $val;
                    break;
                case 'group_name':
                    $p_group_name = $val;
                    break;
                case 'group_description':
                    $p_group_description = $val;
                    break;
    	        default:
    	            $p_rights[$key] = $val;
    	    }
        }
    } else {
        $result = dbi_query("SHOW COLUMNS FROM herbarinput_log.tbl_herbardb_groups");
        $p_rights = array();
        while ($row = mysqli_fetch_array($result)) {
            switch ($row['Field']) {
                case 'groupID':
                    $p_groupID = 0;
                    break;
                case 'group_name':
                    $p_group_name = "";
                    break;
                case 'group_description':
                    $p_group_description = "";
                    break;
                default:
                    $p_rights[$row['Field']] = 0;
            }
        }
    }
} else {
    $result = dbi_query("SHOW COLUMNS FROM herbarinput_log.tbl_herbardb_groups");
    $p_rights = array();
    while ($row = mysqli_fetch_array($result)) {
        switch ($row['Field']) {
            case 'groupID':
                $p_groupID = $_POST['groupID'];
                break;
            case 'group_name':
                $p_group_name = $_POST['group_name'];
                break;
            case 'group_description':
                $p_group_description = $_POST['group_description'];
                break;
            default:
                $p_rights[$row['Field']] = $_POST[$row['Field']];
        }
    }

    if ($_POST['submitUpdate'] && checkRight('admin')) {
        $result = dbi_query("SHOW COLUMNS FROM herbarinput_log.tbl_herbardb_groups");
        $sqldata = array();
        while ($row = mysqli_fetch_array($result)) {
            switch ($row['Field']) {
                case 'groupID':
                    break;
                case 'group_name':
                    $sqldata[] = "group_name = " . quoteString($p_group_name);
                    break;
                case 'group_description':
                    $sqldata[] = "group_description = " . quoteString($p_group_description);
                    break;
                default:
                    $sqldata[] = "`" . $row['Field'] . "` = '" . (($p_rights[$row['Field']]) ? 1 : 0) . "'";
            }
        }
        if (intval($p_groupID)) {
            $sql = "UPDATE herbarinput_log.tbl_herbardb_groups SET " . implode(", ", $sqldata) . " WHERE groupID='" . intval($p_groupID) . "'";
        } else {
            $sql = "INSERT INTO herbarinput_log.tbl_herbardb_groups SET " . implode(", ", $sqldata);
        }
        dbi_query($sql);

        //$location = "Location: listGroups.php";
        //if (SID) $location = $location . "?" . SID;
        //Header($location);
    }
}

$result = dbi_query("SELECT `column`, description FROM tbl_descriptions WHERE `table` = 'tbl_herbardb_groups'");
$rightDescription = array();
while ($row = mysqli_fetch_array($result)) {
    $rightDescription[$row['column']] = $row['description'];
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Groups</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script type="text/javascript" language="JavaScript">
    function openGroupUnlock(sel) {
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 20) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 20);
      else
        options += "710";
      options += ", top=20,left=20,scrollbars=yes,resizable=yes";

      newWindow = window.open("listGroupUnlock.php?sel="+sel,"editGroupUnlock",options);
      newWindow.focus();
    }
  </script>
</head>

<body>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">
<?php
$cf = new CSSF();

echo "<input type=\"hidden\" name=\"groupID\" value=\"$p_groupID\">\n";
$cf->label(9, 0.5, "groupID");
$cf->text(9, 0.5, "&nbsp;" . (($p_groupID) ? $p_groupID : "<span style=\"background-color: red\">&nbsp;<b>new</b>&nbsp;</span>"));
$cf->labelMandatory(9, 2, 7, "Group name");
$cf->inputText(9, 2, 40, "group_name", $p_group_name, 40);
$cf->labelMandatory(9, 4, 7, "Description");
$cf->textarea(9, 4, 40, 2.5, "group_description", $p_group_description);
$line = 7;
foreach ($p_rights as $key => $val) {
    $cf->label(9, $line, $key);
    $cf->checkbox(9, $line, $key, $val);
    if (strlen($rightDescription[$key]) > 0) {
        $cf->text(10.5, $line, $rightDescription[$key]);
    }
    $line += 1.5;
}
$cf->label(9, $line, "Table unlock", "javascript:openGroupUnlock('$p_groupID')");
$line += 2;

if (checkRight('admin')) {
    if ($p_groupID) {
        $cf->buttonJavaScript(12, $line, " Reload ", "self.location.href='editGroup.php?sel=" . $p_groupID . "'");
        $cf->buttonSubmit(20, $line, "submitUpdate", " Update ");
    }
    else {
        $cf->buttonReset(12, $line, " Reset ");
        $cf->buttonSubmit(20, $line, "submitUpdate", " Insert ");
    }
}
$cf->buttonJavaScript(2, $line, " < List ", "self.location.href='listGroups.php'");
?>
</form>

</body>
</html>