<?php
/****************************************************************************
* Software: CSSF                                                            *
* Version:  0.1.8                                                           *
* Date:     2004/11/12                                                      *
* Author:   Johannes SCHACHNER                                              *
* License:  Freeware                                                        *
*                                                                           *
* You may use and modify this software as you wish.                         *
* Changes: (+ ... Bugfix, - ... new feature, o ... change)                  *
* changed since 0.1:                                                        *
*     + changed "$fonsize" in "$font" in constructor                        *
*     - button background color is changeable                               *
* changed since 0.1.1:                                                      *
*     - name of "buttonJavaScript" is changeable                            *
* changed since 0.1.2:                                                      *
*     o htmlentities changed to htmlspecialchars except in textarea         *
* changed since 0.1.3                                                       *
*     o htmlentities changes to htmlspecialchars in textarea                *
* changed since 0.1.4                                                       *
*     o editDropdown: selected index jumps to first element after selection *
* changed since 0.1.5                                                       *
*     - new function: inputPassword                                         *
* changed since 0.1.6                                                       *
*     - new function: labelMandatory                                        *
* changed since 0.1.7                                                       *
*     o editDropdown: new Parameter $w, parameter $minsize discarted        *
****************************************************************************/
define('CSSF_VERSION','0.1.8');

class CSSF
{
//Private properties
var $fontSize;               // default fontsize

/****************************************************************************
*                                                                           *
*                              Public methods                               *
*                                                                           *
****************************************************************************/
function CSSF($font=8) {
  //Check for PHP locale-related bug
  if(1.1==1)
    $this->error('Don\'t alter the locale before including class file');
  //Initialization of properties
  $this->fontSize = $font;
}

function error($msg) {
  //Fatal error
  die('<B>CSSF error: </B>'.$msg);
}

function label($x,$y,$label,$link="") {

  $width = (strlen($label) + 1) / 1.6;
  $xh = $x - $width;
  if ($width>0) {
    echo "<div class=\"cssflabel\" style=\"position: absolute; left: ".$xh."em; top: ".($y+0.2)."em; ".
         "width: ".$width."em;\">";
    if ($link) echo "<a href=\"$link\">";
    echo $label;
    if ($link) echo "</a>";
    echo "&nbsp;</div>\n";
  }
}

function labelMandatory($x,$y,$width,$label,$link="") {

  $xh = $x - $width;
  if ($width>0) {
    echo "<div class=\"cssflabelMandatory\" style=\"position: absolute; left: ".$xh."em; top: ".($y+0.2)."em; ".
         "width: ".$width."em;\">";
    if ($link) echo "<a href=\"$link\">";
    echo $label;
    if ($link) echo "</a>";
    echo "&nbsp;</div>\n";
  }
}

function buttonLink($x,$y,$text,$link,$newwindow,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "<a href=\"$link\"";
  if ($newwindow) echo " target=\"_blank\"";
  echo "><input class=\"cssfbutton\"";
  if ($bgcol) echo " style=\"background-color: $bgcol;\"";
  echo " type=\"button\" value=\"$text\"></a>";
  echo "</div>\n";
}

function buttonJavaScript($x,$y,$text,$js,$bgcol="",$name="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "<input class=\"cssfbutton\"";
  if ($bgcol) echo " style=\"background-color: $bgcol;\"";
  echo " type=\"button\" value=\"$text\" ";
  if ($name) echo " name=\"$name\"";
  echo "onClick=\"$js\">";
  echo "</div>\n";
}

function buttonSubmit($x,$y,$name,$text,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "<input class=\"cssfbutton\"";
  if ($bgcol) echo " style=\"background-color: $bgcol;\"";
  echo " type=\"submit\" name=\"$name\" value=\"$text\">";
  echo "</div>\n";
}

function buttonReset($x,$y,$text,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "<input class=\"cssfbutton\"";
  if ($bgcol) echo " style=\"background-color: $bgcol;\"";
  echo " type=\"reset\" value=\"$text\">";
  echo "</div>\n";
}

function checkbox($x,$y,$name,$ischecked) {

  $this->_divclass($x-0.2,$y+0.1,"cssfinput");
  echo "<input class=\"cssfcheckbox\" type=\"checkbox\" name=\"$name\"";
  if ($ischecked) echo " checked";
  echo "></div>\n";
}

function checkboxJavaScript($x,$y,$name,$ischecked,$js) {

  $this->_divclass($x-0.2,$y+0.1,"cssfinput");
  echo "<input class=\"cssfcheckbox\" type=\"checkbox\" name=\"$name\" onChange=\"$js\"";
  if ($ischecked) echo " checked";
  echo "></div>\n";
}

function dropdown($x,$y,$name,$select,$value,$text,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "<select class=\"cssf\"";
  if ($bgcol) echo " style=\"background-color: $bgcol;\"";
  echo " name=\"$name\">\n";
  for ($i=0; $i<count($value); $i++) {
    echo "  <option";
    if ($value[$i]!=$text[$i]) echo " value=\"".$value[$i]."\"";
    if ($select==$value[$i]) print " selected";
    echo ">".htmlspecialchars($text[$i])."</option>\n";
  }
  echo "</select></div>\n";
}

function editDropdown($x,$y,$w,$name,$value,$options,$maxsize=0,$jump=0,$bgcol="",$title="") {

  $yh = $y + 1.7;

  $this->_divclass($x,$y,"cssfinput");
  echo "<input class=\"cssftext\" style=\"width: ".$w."em;";
  if ($bgcol) echo " background-color: $bgcol;";
  echo "\" type=\"text\" name=\"$name\" value=\"".htmlspecialchars($value)."\"";
  if ($maxsize) echo " maxlength=\"$maxsize\"";
  if ($title) echo " title=\"$title\"";
  echo "></div>\n";

  echo "<div class=\"cssfinput\" style=\"position: absolute; left: ".$x."em; top: ".$yh."em;\">";
  echo "<select class=\"cssf\"";
  echo " onchange=\"form.$name.value=this.options[this.options.selectedIndex].text";
  if ($jump) echo "; this.options.selectedIndex = 0";
  echo "\">\n";
  foreach ($options as $wert) {
    echo "  <option";
    if ($value==$wert) print " selected";
    echo ">".htmlspecialchars($wert)."</option>\n";
  }
  echo "</select></div>\n";
}

function inputDate($x,$y,$name,$value,$us) {

  $tmp = explode("-",$value);
  $this->_divclass($x,$y,"cssfinput");
  if ($us) {
    echo "<input class=\"cssftext\" style=\"width: 2.7em;\" type=\"text\" name=\"".$name."_y\" value=\"".$tmp[0]."\" maxlength=\"4\">";
    echo "<b> &minus; </b>";
    echo "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_m\" value=\"".$tmp[1]."\" maxlength=\"2\">";
    echo "<b> &minus; </b>";
    echo "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_d\" value=\"".$tmp[2]."\" maxlength=\"2\">";
  }
  else {
    echo "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_d\" value=\"".$tmp[2]."\" maxlength=\"2\">";
    echo "<b>. </b>";
    echo "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_m\" value=\"".$tmp[1]."\" maxlength=\"2\">";
    echo "<b>. </b>";
    echo "<input class=\"cssftext\" style=\"width: 2.7em;\" type=\"text\" name=\"".$name."_y\" value=\"".$tmp[0]."\" maxlength=\"4\">";
  }
  echo "</div>\n";
}

function inputText($x,$y,$w,$name,$value,$maxsize=0,$bgcol="",$title="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "<input class=\"cssftext\" style=\"width: ".$w."em;";
  if ($bgcol) echo " background-color: $bgcol;";
  echo "\" type=\"text\" name=\"$name\" value=\"".htmlspecialchars($value)."\"";
  if ($maxsize) echo " maxlength=\"$maxsize\"";
  if ($title) echo " title=\"$title\"";
  echo "></div>\n";
}

function inputPassword($x,$y,$w,$name,$maxsize=0,$bgcol="",$title="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "<input class=\"cssftext\" style=\"width: ".$w."em;";
  if ($bgcol) echo " background-color: $bgcol;";
  echo "\" type=\"password\" name=\"$name\"";
  if ($maxsize) echo " maxlength=\"$maxsize\"";
  if ($title) echo " title=\"$title\"";
  echo "></div>\n";
}

function text($x,$y,$text) {

  $this->_divclass($x,$y+0.2,"cssftext");
  echo $text;
  echo "</div>\n";
}

function textarea($x,$y,$w,$h,$name,$value,$bgcol="",$title="") {

  $this->_divclass($x,$y,"cssfinput");
  echo "\n<textarea class=\"cssf\" style=\"width: ".$w."em; height: ".$h."em;";
  if ($bgcol) echo " background-color: $bgcol;";
  echo "\" name=\"$name\"";
  if ($title) echo " title=\"$title\"";
  echo " wrap=\"virtual\">";
  if ($value) echo htmlspecialchars($value);
  echo "</textarea>\n";
  echo "</div>\n";
}

/****************************************************************************
*                                                                           *
*                              Private methods                              *
*                                                                           *
****************************************************************************/
function _divclass($x,$y,$class) {

  echo "<div class=\"$class\" style=\"position: absolute; left: ".$x."em; top: ".$y."em;\">";
}
}
?>