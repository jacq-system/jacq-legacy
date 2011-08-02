<?php
/****************************************************************************
* Software: CSSF                                                            *
* Version:  0.2.0                                                          *
* Date:     2007/9/30                                                       *
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
* changed since 0.1.8                                                       *
*     o changed all "echo" in "print" due to intermittent memory errors     *
* changed since 0.1.9                                                       *
*     o new option $nameIsID, default false. When set id is set to $name    *
*     o new option to editDropdown: $js for javascript-onchange             *
* changed since 0.1.10                                                      *
*     o new option $cssfImages, holds the path to all used images           *
*     o new function 'comboBox' for showing a comboBox using xajax          *
****************************************************************************/
define('CSSF_VERSION','0.2.0');

class CSSF
{
//Private properties
var $fontSize;               // default fontsize
var $nameIsID;               // when set to true, the param $name is used to set the id
var $cssfImages;             // path to the used images
var $tabindex=1;
/****************************************************************************
*                                                                           *
*                              Public methods                               *
*                                                                           *
****************************************************************************/
function CSSF($font=8) {
  //Check for PHP locale-related bug
  //if(1.1==1)
  //  $this->error('Don\'t alter the locale before including class file');
  //Initialization of properties
  $this->fontSize = $font;
  $this->nameIsID = false;
  $this->cssfImages = array('downarrow' => 'webimages/downarrow.gif');
}

/**
 * prints any necessary javascript code
 *
 */
/*
function javascriptCode() {

  print "<script type=\"text/javascript\" language=\"JavaScript\">\n".
        "  function cssfActivateKeyPress(Ereignis) {\n".
        "    if (!Ereignis)\n".
        "      Ereignis = window.event;\n".
        "    if (Ereignis.which) {\n".
        "      Tastencode = Ereignis.which;\n".
        "    } else if (Ereignis.keyCode) {\n".
        "      Tastencode = Ereignis.keyCode;\n".
        "    }\n".
        "    if (Tastencode==13 || Tastencode==32)\n".
        "      return true;\n".
        "    else\n".
        "      return false;\n".
        "  }\n".
        "  function cssfComboBoxHelper(name) {\n".
        "    var ajaxname = 'ajax_' + name;\n".
        "    var ajaxdivname = 'ajax_div_' + name;\n".
        "    var ajaxselectname = 'ajax_select_' + name;\n".
        "\n".
        "    if (xajax.$(ajaxselectname).options.selectedIndex>=0) {\n".
        "      xajax.$(ajaxname).value=xajax.$(ajaxselectname).options[xajax.$(ajaxselectname).options.selectedIndex].text;\n".
        "      xajax.$(ajaxdivname).style.display='none';\n".
        "      xajax.$(ajaxname).focus();\n".
        "    }\n".
        "  }\n".
        "</script>\n";
}
 * deprecated
 */

/*function error($msg) {
  //Fatal error
  die('<B>CSSF error: </B>'.$msg);
}*/

function label($x,$y,$label,$link="",$id="") {

  $width = (strlen($label) + 1) / 1.6;
  $xh = $x - $width;
  if ($width>0) {
    print "<div class=\"cssflabel\" ";
    if ($id) print "id=\"$id\" ";
    print "style=\"position: absolute; left: ".$xh."em; top: ".($y+0.2)."em; width: ".$width."em;\">";
    if ($link) print "<a href=\"$link\">";
    print $label;
    if ($link) print "</a>";
    print "&nbsp;</div>\n";
  }
}

function labelMandatory($x,$y,$width,$label,$link="") {

  $xh = $x - $width;
  if ($width>0) {
    print "<div class=\"cssflabelMandatory\" style=\"position: absolute; left: ".$xh."em; top: ".($y+0.2)."em; ".
         "width: ".$width."em;\">";
    if ($link) print "<a href=\"$link\">";
    print $label;
    if ($link) print "</a>";
    print "&nbsp;</div>\n";
  }
}

function buttonLink($x,$y,$text,$link,$newwindow,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  print "<a href=\"$link\"";
  if ($newwindow) print " target=\"_blank\"";
  print "><input tabindex=\"{$this->tabindex}\" class=\"cssfbutton\"";
  if ($bgcol) print " style=\"background-color: $bgcol;\"";
  print " type=\"button\" value=\"$text\"></a>";
  print "</div>\n";
  $this->tabindex++;
}

function buttonJavaScript($x,$y,$text,$js,$bgcol="",$name="") {

  $this->_divclass($x,$y,"cssfinput");
  print "<input tabindex=\"{$this->tabindex}\" class=\"cssfbutton\"";
  if ($bgcol) print " style=\"background-color: $bgcol;\"";
  print " type=\"button\" value=\"$text\" ";
  if ($name) print " name=\"$name\"";
  print "onClick=\"$js\">";
  print "</div>\n";
  $this->tabindex++;
}

function buttonSubmit($x,$y,$name,$text,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  print "<input tabindex=\"{$this->tabindex}\" class=\"cssfbutton\"";
  if ($bgcol) print " style=\"background-color: $bgcol;\"";
  print " type=\"submit\" name=\"$name\"";
  if ($this->nameIsID) print " id=\"$name\"";
  print " value=\"$text\">";
  print "</div>\n";
  $this->tabindex++;
}

function buttonReset($x,$y,$text,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  print "<input tabindex=\"{$this->tabindex}\" tabindex=\"{$this->tabindex}\" class=\"cssfbutton\"";
  if ($bgcol) print " style=\"background-color: $bgcol;\"";
  print " type=\"reset\" value=\"$text\">";
  print "</div>\n";
  $this->tabindex++;
}

function checkbox($x,$y,$name,$ischecked) {

  $this->_divclass($x-0.2,$y+0.1,"cssfinput");
  print "<input class=\"cssfcheckbox\" type=\"checkbox\" name=\"$name\"";
  if ($this->nameIsID) print " id=\"$name\"";
  if ($ischecked) print " checked";
  print "></div>\n";
}

function checkboxJavaScript($x,$y,$name,$ischecked,$js) {

  $this->_divclass($x-0.2,$y+0.1,"cssfinput");
  print "<input class=\"cssfcheckbox\" type=\"checkbox\" name=\"$name\" onChange=\"$js\"";
  if ($this->nameIsID) print " id=\"$name\"";
  if ($ischecked) print " checked";
  print "></div>\n";
}

function dropdown($x,$y,$name,$select,$value,$text,$bgcol="") {

  $this->_divclass($x,$y,"cssfinput");
  print "<select class=\"cssf\"";
  if ($bgcol) print " style=\"background-color: $bgcol;\"";
  if ($this->nameIsID) print " id=\"$name\"";
  print " name=\"$name\">\n";
  for ($i=0; $i<count($value); $i++) {
    print "  <option";
    if ($value[$i]!=$text[$i]) print " value=\"".$value[$i]."\"";
    if ($select==$value[$i]) print " selected";
    print ">".htmlspecialchars($text[$i])."</option>\n";
  }
  print "</select></div>\n";
}

function editDropdown($x,$y,$w,$name,$value,$options,$maxsize=0,$jump=0,$bgcol="",$title="",$js="") {

  $yh = $y + 1.7;

  $this->_divclass($x,$y,"cssfinput");
  print "<input class=\"cssftext\" style=\"width: ".$w."em;";
  if ($bgcol) print " background-color: $bgcol;";
  print "\" type=\"text\" name=\"$name\" value=\"".htmlspecialchars($value)."\"";
  if ($js) print " onchange=\"$js\"";
  if ($this->nameIsID) print " id=\"$name\"";
  if ($maxsize) print " maxlength=\"$maxsize\"";
  if ($title) print " title=\"$title\"";
  print "></div>\n";

  print "<div class=\"cssfinput\" style=\"position: absolute; left: ".$x."em; top: ".$yh."em;\">";
  print "<select class=\"cssf\"";
  print " onchange=\"form.$name.value=this.options[this.options.selectedIndex].text";
  if ($jump) print "; this.options.selectedIndex = 0";
  if ($js) print "; $js";
  print "\">\n";
  foreach ($options as $wert) {
    print "  <option";
    if (substr($wert,0,1) == '-') {
        $wert = substr($wert,1);
        print " style=\"background-color:red;\"";
    }
    if ($value==$wert) print " selected";
    print ">".htmlspecialchars($wert)."</option>\n";
  }
  print "</select></div>\n";
}

/**
 * show a combobox at the given coordinates
 *
 * Shows a combobox at the given coordinates. A xajax-function
 * with the name "xajax_cssfComboBox" is called and has therefore
 * be provided by the main program! As parameters this function gets:
 * - the parameter $name
 * - the current value of the input-box
 * - the display-state of the div-box ("none" or "block")
 * The used IDs are:
 * ajax_$name .......... text input field
 * ajax_div_$name ...... div-block for taking the select-block
 * ajax_select_$name ... select-block for the results
 *
 * @param float $x x-coordinate
 * @param float $y y-coordinate
 * @param float $w width
 * @param string $name name of input-box
 * @param string $value value in input-box (if any)
 * @param integer[optional] $maxsize maxsize-parameter of input-box
 * @param string[optional] $bgcol background-color
 * @param string[optional] $title title
 */
/*
function comboBox($x,$y,$w,$name,$value,$maxsize=0,$bgcol="",$title="") {

  $this->_divclass($x,$y,"cssfinput");
  print "<input class=\"cssftext\" style=\"width: {$w}em;";
  if ($bgcol) print " background-color: $bgcol;";
  print "\" type=\"text\" name=\"$name\" id=\"ajax_$name\" value=\"".htmlspecialchars($value)."\"";
  if ($maxsize) print " maxlength=\"$maxsize\"";
  if ($title) print " title=\"$title\"";
  print " onkeydown=\"xajax.$('ajax_div_$name').style.display='none';\">";
  print "<button type=\"button\" class=\"cssf\" style=\"height:1.2em; width:1em;\" ".
          "onclick=\"xajax.$('ajax_$name').disabled=true; xajax_cssfComboBox('$name', xajax.$('ajax_$name').value, xajax.$('ajax_div_$name').style.display)\">".
         "<img src=\"{$this->cssfImages['downarrow']}\">".
        "</button>";
  print "<div id=\"ajax_div_$name\" style=\"position:absolute; z-index:1000; border:1px solid black; display:none;\"></div>";
  print "</div>\n";
}
 * deprecated
 */

/* deprecated
function inputAutocomplete($x,$y,$w,$name,$value,$index,$serverScript,$maxsize=0,$options="",$bgcol="",$title="") {

  $this->_divclass($x,$y,"cssfinputAutocomplete");
  print "<input class=\"cssftextAutocomplete\" style=\"width: {$w}em;";
  if ($bgcol) print " background-color: $bgcol;";
  print "\" type=\"text\" name=\"$name\" id=\"ajax_$name\" value=\"".htmlspecialchars($value)."\"";
  if ($maxsize) print " maxlength=\"$maxsize\"";
  if ($title) print " title=\"$title\"";
  print "><div class=\"cssf_input_autocomplete\" id=\"ajax_div_$name\"></div>";
  print "</div>\n";
  print "<input type=\"hidden\" name=\"{$name}Index\" id=\"{$name}Index\" value=\"$index\">\n";
  print "<script type=\"text/javascript\" language=\"JavaScript\">\n".
        "  new Ajax.Autocompleter(\"ajax_$name\", \"ajax_div_$name\", \"$serverScript\"".(($options) ? ", {".$options."}" : "").");\n".
        "</script>\n";
}
 * deprecated
 */

function inputJqAutocomplete($x, $y, $w, $name, $value, $index, $serverScript, $maxsize = 0, $minLength = 1, $bgcol = "", $title = "",$autoFocus=false) {
  $this->_divclass($x, $y, "cssfinput");
  print "<input class='cssftextAutocomplete' style='width: {$w}em;";
  if ($bgcol) print " background-color: $bgcol;";
  print "' type='text' name='{$name}' id='ajax_{$name}' value='" . htmlspecialchars($value, ENT_QUOTES) . "'";
  if ($maxsize) print " maxlength='{$maxsize}'";
  if ($title) print " title='{$title}'";
  print ">"
      . "</div>\n"
      . "<input type='hidden' name='{$name}Index' id='{$name}Index' value='{$index}'>\n"
      . "<script type='text/javascript' language='JavaScript'>\n"
      . "  $(function() {\n"
	  . "    $('#ajax_{$name}').autocomplete ({\n"
	  . "      source: '{$serverScript}',\n"
	  . "      minLength: {$minLength},\n"
      . "      delay: 500, \n";

  if ($autoFocus)print " autoFocus: true,\n";  
  print "      select: function(event, ui) { $('#{$name}Index').val(ui.item.id); }\n"
	  . "    })\n"
      . "    .data('autocomplete')._renderItem = function( ul, item ) {\n"
      . "      return $('<li></li>')\n"
      . "        .data('item.autocomplete', item)\n"
      . "        .append('<a' + ((item.color) ? ' style=\"background-color:' + item.color + ';\">' : '>') + item.label + '</a>')\n"
      . "        .appendTo(ul);\n"
      . "    };\n"
	  . "  });\n"
      . "</script>\n";
}


function inputJqAutocomplete2($x, $y, $w, $name, $value, $index, $serverScript, $maxsize = 0, $minLength = 1, $bgcol = "", $title = "",$autoFocus=false,$textarea=false,$rows=0) {

	$this->_divclass($x, $y, "cssfinput");
	$val=htmlspecialchars($value, ENT_QUOTES);
	
	if($textarea){
		echo<<<EOF
<input type="hidden" name="{$name}Index" id="{$name}Index"  value="{$index}"/>
<textarea  tabindex=\"{$this->tabindex}\" class='cssftextAutocomplete' style='width: {$w}em;background-color: rgb(255, 255, 153);' rows="{$rows} type="text" type="text" name="{$name}" id="ajax_{$name}" maxlength="{$maxsize}" title="{$title}">{$value}</textarea>
</div>

EOF;
		
	}else{
		echo<<<EOF
<input type="hidden" name="{$name}Index" id="{$name}Index"  value="{$index}"/>
<input tabindex=\"{$this->tabindex}\" class='cssftextAutocomplete' style='width: {$w}em;' type="text" style="width: 200px;"  type="text" value="{$value}" name="{$name}" id="ajax_{$name}" maxlength="{$maxsize}" title="{$title}" />
</div>

EOF;
	}
	
	$this->tabindex++;
}



function inputDate($x,$y,$name,$value,$us) {

  $tmp = explode("-",$value);
  $this->_divclass($x,$y,"cssfinput");
  if ($us) {
    print "<input class=\"cssftext\" style=\"width: 2.7em;\" type=\"text\" name=\"".$name."_y\"";
    if ($this->nameIsID) print " id=\"".$name."_y\"";
    print " value=\"".$tmp[0]."\" maxlength=\"4\">";
    print "<b> &minus; </b>";
    print "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_m\"";
    if ($this->nameIsID) print " id=\"".$name."_m\"";
    print " value=\"".$tmp[1]."\" maxlength=\"2\">";
    print "<b> &minus; </b>";
    print "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_d\"";
    if ($this->nameIsID) print " id=\"".$name."_d\"";
    print " value=\"".$tmp[2]."\" maxlength=\"2\">";
  }
  else {
    print "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_d\"";
    if ($this->nameIsID) print " id=\"".$name."_y\"";
    print " value=\"".$tmp[2]."\" maxlength=\"2\">";
    print "<b>. </b>";
    print "<input class=\"cssftext\" style=\"width: 1.8em;\" type=\"text\" name=\"".$name."_m\"";
    if ($this->nameIsID) print " id=\"".$name."_m\"";
    print " value=\"".$tmp[1]."\" maxlength=\"2\">";
    print "<b>. </b>";
    print "<input class=\"cssftext\" style=\"width: 2.7em;\" type=\"text\" name=\"".$name."_y\"";
    if ($this->nameIsID) print " id=\"".$name."_d\"";
    print " value=\"".$tmp[0]."\" maxlength=\"4\">";
  }
  print "</div>\n";
}

function inputText($x,$y,$w,$name,$value,$maxsize=0,$bgcol="",$title="",$readonly='') {

  $this->_divclass($x,$y,"cssfinput");
  print "<input class=\"cssftext\" style=\"width: ".$w."em;";
  if ($bgcol) print " background-color: $bgcol;";
  print "\" type=\"text\" name=\"$name\" value=\"".htmlspecialchars($value)."\"";
  if ($this->nameIsID) print " id=\"$name\"";
  if ($maxsize) print " maxlength=\"$maxsize\"";
  if ($title) print " title=\"$title\"";
  if ($readonly) print " readonly";
  print "></div>\n";
}

function inputPassword($x,$y,$w,$name,$maxsize=0,$bgcol="",$title="") {

  $this->_divclass($x,$y,"cssfinput");
  print "<input class=\"cssftext\" style=\"width: ".$w."em;";
  if ($bgcol) print " background-color: $bgcol;";
  print "\" type=\"password\" name=\"$name\"";
  if ($this->nameIsID) print " id=\"$name\"";
  if ($maxsize) print " maxlength=\"$maxsize\"";
  if ($title) print " title=\"$title\"";
  print "></div>\n";
}

function text($x,$y,$text) {

  $this->_divclass($x,$y+0.2,"cssftext");
  print $text;
  print "</div>\n";
}

function textarea($x,$y,$w,$h,$name,$value,$bgcol="",$title="",$readonly='') {

  $this->_divclass($x,$y,"cssfinput");
  print "\n<textarea class=\"cssf\" style=\"width: ".$w."em; height: ".$h."em;";
  if ($bgcol) print " background-color: $bgcol;";
  print "\" name=\"$name\"";
  if ($this->nameIsID) print " id=\"$name\"";
  if ($title) print " title=\"$title\"";
  if ($readonly) print " readonly";
  print " wrap=\"virtual\">";
  if ($value) print htmlspecialchars($value);
  print "</textarea>\n";
  print "</div>\n";
}

/****************************************************************************
*                                                                           *
*                              Private methods                              *
*                                                                           *
****************************************************************************/
function _divclass($x,$y,$class) {

  print "<div class=\"$class\" style=\"position: absolute; left: ".$x."em; top: ".$y."em;\">";
}
}