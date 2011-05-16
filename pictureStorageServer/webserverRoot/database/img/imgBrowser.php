<?php
session_start();

if (isset($_POST['picname']) && isset($_GET['name']) && $_POST['picname']!=$_GET['name'])
  $usedName = $_POST['picname'];
elseif (isset($_GET['name']))
  $usedName = $_GET['name'];
else
  $usedName = $_GET['ID'];

require_once("../../inc/connect.php");
require_once("inc/getPathPic.php");

if ($ID) {
  $select = "<select name=\"picname\" onchange=\"document.f.submit()\">";
  $row = getData($ID);

  $countPics = 0;
  for ($i=1;$i<=3;$i++) {
    switch ($i) {
      case 1:
        $searchpath = $row['img_directory']."/";
        $searchpic = getSpecimenPicName($row);
        break;
      case 2:
        $searchpath = $row['img_obs_directory']."/";
        $searchpic = "obs_".$row['specimen_ID'];
        break;
      case 3:
        $searchpath = $row['img_tab_directory']."/";
        $searchpic = "tab_".$row['specimen_ID'];
        break;
    }
    $filelist = shell_exec("find " . $searchpath . " -name '" . basename($searchpic) . "*'");
    $files = explode("\n", $filelist);
    for ($j=0;$j<count($files);$j++) {
      if (trim($files[$j])) {
        $select .= "<option";
        if (basename($files[$j])==$pic) $select .= " selected";
        $select .= ">".basename($files[$j])."</option>";
        $countPics++;
      }
    }
    //foreach(glob($searchpath.$searchpic."*") as $v) {
    //  $select .= "<option";
    //  if (basename($v)==$pic) $select .= " selected";
    //  $select .= ">".basename($v)."</option>";
    //  $countPics++;
    //}
  }
  $select .= "</select>\n";
}

if (file_exists($path.$pic)) {
  $data = getimagesize($path.$pic);
  $i_width = $data[0];
  $i_height = $data[1];
} else
  $i_width = $i_height = 1;

$width = array(0, 640, 800, 1024, 1280, 1600);
$height = array(0, 480, 600, 768, 960, 1200);

// calculate new Centerpoint
if (isset($_POST['imgSubmit_x']) ||
    isset($_POST['shiftNW_x']) || isset($_POST['shiftN_x']) || isset($_POST['shiftNE_x']) ||
    isset($_POST['shiftW_x']) || isset($_POST['shiftE_x']) ||
    isset($_POST['shiftSW_x']) || isset($_POST['shiftS_x']) || isset($_POST['shiftSE_x'])) {
  if (isset($_POST['imgSubmit_x'])) {
    $_SESSION['xCenter'] = round(intval($_POST['imgSubmit_x']) * $_SESSION['zoomFactor']) + $_SESSION['xOffset'];;
    $_SESSION['yCenter'] = round(intval($_POST['imgSubmit_y']) * $_SESSION['zoomFactor']) + $_SESSION['yOffset'];;
  } elseif (isset($_POST['shiftNW_x'])) {
    $_SESSION['xCenter'] -= 10 * $_SESSION['zoomFactor'];
    $_SESSION['yCenter'] -= 10 * $_SESSION['zoomFactor'];
  } elseif (isset($_POST['shiftN_x'])) {
    $_SESSION['yCenter'] -= 10 * $_SESSION['zoomFactor'];
  } elseif (isset($_POST['shiftNE_x'])) {
    $_SESSION['xCenter'] += 10 * $_SESSION['zoomFactor'];
    $_SESSION['yCenter'] -= 10 * $_SESSION['zoomFactor'];
  } elseif (isset($_POST['shiftW_x'])) {
    $_SESSION['xCenter'] -= 10 * $_SESSION['zoomFactor'];
  } elseif (isset($_POST['shiftE_x'])) {
    $_SESSION['xCenter'] += 10 * $_SESSION['zoomFactor'];
  } elseif (isset($_POST['shiftSW_x'])) {
    $_SESSION['xCenter'] -= 10 * $_SESSION['zoomFactor'];
    $_SESSION['yCenter'] += 10 * $_SESSION['zoomFactor'];
  } elseif (isset($_POST['shiftS_x'])) {
    $_SESSION['yCenter'] += 10 * $_SESSION['zoomFactor'];
  } elseif (isset($_POST['shiftSE_x'])) {
    $_SESSION['xCenter'] += 10 * $_SESSION['zoomFactor'];
    $_SESSION['yCenter'] += 10 * $_SESSION['zoomFactor'];
  }
  if ($_SESSION['xCenter']<0) $_SESSION['xCenter'] = 0;
  if ($_SESSION['xCenter']>$i_width) $_SESSION['xCenter'] = $i_width;
  if ($_SESSION['yCenter']<0) $_SESSION['yCenter'] = 0;
  if ($_SESSION['yCenter']>$i_height) $_SESSION['yCenter'] = $i_height;
  $newCenterpoint = true;
}
else
  $newCenterpoint = false;

// check if Button pressed or else it's the first time
if ($_POST['btnSubmit'] || $newCenterpoint) {
  $resolution = intval($_POST['resolution']);
  $zoom = intval($_POST['zoom']);
}
else {
  $resolution = 1;
  $zoom = 1;
  $_SESSION['xCenter'] = $i_width / 2;
  $_SESSION['yCenter'] = $i_height / 2;
  $_SESSION['xOffset'] = $_SESSION['yOffset'] = 0;
  $_SESSION['zoomFactor'] = $i_width_zoom / $width[$resolution];
}

$i_width_zoom = ($i_width*0.75<$i_height) ? $i_height / 0.75 : $i_width;
$maxZoom = $i_width_zoom / $width[$resolution];

switch ($zoom) {
  case 2:  $_SESSION['zoomFactor'] = 1 + ($maxZoom - 1) / 4 * 3;
           $commonBlock = true;
           break;
  case 3:  $_SESSION['zoomFactor'] = 1 + ($maxZoom - 1) / 4 * 2;
           $commonBlock = true;
           break;
  case 4:  $_SESSION['zoomFactor'] = 1 + ($maxZoom - 1) / 4;
           $commonBlock = true;
           break;
  case 5:  $_SESSION['zoomFactor'] = 1;
           $commonBlock = true;
           break;
  default: $_SESSION['zoomFactor'] = $maxZoom;
           $_SESSION['xOffset'] = $_SESSION['yOffset'] = $cw = $ch = 0;
           $commonBlock = false;
}
if ($commonBlock) {
  $cw = $width[$resolution] * $_SESSION['zoomFactor'];
  if ($cw>$i_width) $cw = $i_width;

  $ch = $height[$resolution] * $_SESSION['zoomFactor'];
  if ($ch>$i_height) $ch = $i_height;

  $_SESSION['xOffset'] = round($_SESSION['xCenter'] - $cw / 2);
  if ($_SESSION['xOffset']<0) $_SESSION['xOffset'] = 0;
  if ($_SESSION['xOffset']>$i_width-$cw) $_SESSION['xOffset'] = $i_width - $cw;

  $_SESSION['yOffset'] = round($_SESSION['yCenter'] - $ch / 2);
  if ($_SESSION['yOffset']<0) $_SESSION['yOffset'] = 0;
  if ($_SESSION['yOffset']>$i_height-$ch) $_SESSION['yOffset'] = $i_height - $ch;
}

$options = "name=$pic&p=".sprintf("%8X%1d",ip2long($_SERVER['REMOTE_ADDR']),$resolution).
           sprintf("%05d%05d%05d%05d",$cw,$ch,$_SESSION['xOffset'],$_SESSION['yOffset']);

for ($i=1;$i<=5;$i++) {
  $rbtnResol[$i] = ($resolution==$i) ? " checked" : "";
  $rbtnZoom[$i] = ($zoom==$i) ? " checked" : "";
}
$txtZoom4 = sprintf("%.1f%%",100.0/(1 + ($maxZoom - 1) / 4));
$txtZoom3 = sprintf("%.1f%%",100.0/(1 + ($maxZoom - 1) / 4 * 2));
$txtZoom2 = sprintf("%.1f%%",100.0/(1 + ($maxZoom - 1) / 4 * 3));
$txtZoom1 = sprintf("%.1f%%",100.0/$maxZoom);
?>
<html>
<head>
  <title>Virtual Herbaria / Image Browser</title>
  <style type="text/css">
    td.left { font-size: 12px; vertical-align: top; white-space: nowrap; }
    td.spacer { padding-left: 20px; }
    div.header { font-size: 16px; font-weight: bold; padding: 0px 0px 5px; }
  </style>
  <script type="text/javascript" language="JavaScript">
    var txtExplain = "Click to recenter Image";
  </script>
</head>
<body>

<script src="js/overlib/overlib.js" type="text/javascript">
</script>

<form Action="<?php echo $_SERVER['PHP_SELF']."?name=".$pic."&ID=".$ID ?>" Method="POST" name="f">

<table><tr>
<td class="left">
  <p>
    <div class="header">Filename</div>
<?php
  if ($ID && $countPics>1)
    echo $select;
  else
    echo $pic ;
?>
  </p>
  <p>
    <div class="header">Size</div>
    <input type="radio" name="resolution" value="1"<?php echo $rbtnResol[1] ?>> 640 x 480<br>
    <input type="radio" name="resolution" value="2"<?php echo $rbtnResol[2] ?>> 800 x 600<br>
    <input type="radio" name="resolution" value="3"<?php echo $rbtnResol[3] ?>> 1024 x 768<br>
    <input type="radio" name="resolution" value="4"<?php echo $rbtnResol[4] ?>> 1200 x 960<br>
    <input type="radio" name="resolution" value="5"<?php echo $rbtnResol[5] ?>> 1600 x 1200
  </p>
  <p>
    <div class="header">Zoom</div>
    <input type="radio" name="zoom" value="1"<?php echo $rbtnZoom[1] ?>> show all (<?php echo $txtZoom1 ?>)<br>
    <input type="radio" name="zoom" value="2"<?php echo $rbtnZoom[2] ?>> Zoom 1 (<?php echo $txtZoom2 ?>)<br>
    <input type="radio" name="zoom" value="3"<?php echo $rbtnZoom[3] ?>> Zoom 2 (<?php echo $txtZoom3 ?>)<br>
    <input type="radio" name="zoom" value="4"<?php echo $rbtnZoom[4] ?>> Zoom 3 (<?php echo $txtZoom4 ?>)<br>
    <input type="radio" name="zoom" value="5"<?php echo $rbtnZoom[5] ?>> max. Zoom (100%)
  </p>
  <p>
    <div class="header">Shift</div>
    <input type="image" name="shiftNW" src="pics/shiftnw.gif">
    <input type="image" name="shiftN" src="pics/shiftn.gif">
    <input type="image" name="shiftNE" src="pics/shiftne.gif"><br>
    <input type="image" name="shiftW" src="pics/shiftw.gif">
    <img border="0" height="24" src="pics/blind.png" width="24">
    <input type="image" name="shiftE" src="pics/shifte.gif"><br>
    <input type="image" name="shiftSW" src="pics/shiftsw.gif">
    <input type="image" name="shiftS" src="pics/shifts.gif">
    <input type="image" name="shiftSE" src="pics/shiftse.gif">
  </p>
  <p>
    <?php echo $_SESSION['zoomFactor'] ?><br>
    <?php echo $i_width ?> x <?php echo $i_height ?>
  </p>
  <p>
    <input type="submit" name="btnSubmit" value=" redraw ">
  </p>
</td><td class="spacer">
  &nbsp;
</td><td class="right">
  <input type="image" name="imgSubmit" src="showPic.php?<?php echo $options ?>" onmouseover="return overlib(txtExplain)" onmouseout="return nd()">
</td>
</tr></table>
</form>

</body>
</html>