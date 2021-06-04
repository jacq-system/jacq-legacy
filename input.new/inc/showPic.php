<?php
header ("Content-type: image/jpeg");

$img = "/data/samba/images/specimens/".basename($_GET['name']);

$width = intval($_GET['width']);    // new image width
$height = intval($_GET['height']);  // new image height

$cw = intval($_GET['cw']); // crop width
$ch = intval($_GET['ch']); // crop height
$cx = intval($_GET['cx']); // crop x
$cy = intval($_GET['cy']); // crop y

$copyright = "-draw \"text 10 10 ".utf8_encode(chr(169))."copyright\"";

if ($cw && $ch && $cx && $cy)
  if ($width && $height)
    passthru("convert -crop ".$cw."x".$ch."+".$cx."+".$cy." $copyright -geometry ".$width."x".$height." $img JPG:-");
  else
    passthru("convert -crop ".$cw."x".$ch."+".$cx."+".$cy." $copyright $img JPG:-");
else
  passthru("convert -geometry ".$width."x".$height." $copyright $img JPG:-");

//passthru("/opt/x11/bin/convert -pen 0 -geometry $heightx$width -bordercolor white -border 0x0 $img JPG:-");
//passthru("convert -geometry ".$height."x".$width." $img JPG:-");
?>