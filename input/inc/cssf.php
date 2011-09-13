<?php
/****************************************************************************
* Software: CSSF                                                            *
* Version:  0.2.0                                                           *
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

class CSSF{
	//Private properties
	var $fontSize;		 // default fontsize
	var $nameIsID;		 // when set to true, the param $name is used to set the id
	var $cssfImages;	 // path to the used images
	var $tabindex=1;
	var $relative=0;
	var $doEcho=1;
	var $xrel=0;
	var $yrel=0;
	
	/****************************************************************************
	*																			*
	*	Public methods															 *
	*																			*
	****************************************************************************/
	function CSSF($font=8) {
		//Check for PHP locale-related bug
		//if(1.1==1)
		//		$this->error('Don\'t alter the locale before including class file');
		//Initialization of properties
		$this->fontSize = $font;
		$this->nameIsID = false;
		$this->cssfImages = array('downarrow' => 'webimages/downarrow.gif');
	}
	
	function setRelative($relative){
		$this->relative=$relative;
	}
	
	function setEcho($doEcho){
		$this->doEcho=$doEcho;
	}
	/**
	 * prints any necessary javascript code
	 *
	 */
	/*
	function javascriptCode() {

		print "<script type=\"text/javascript\" language=\"JavaScript\">\n".
			"function cssfActivateKeyPress(Ereignis) {\n".
			"		if (!Ereignis)\n".
			"				Ereignis = window.event;\n".
			"		if (Ereignis.which) {\n".
			"				Tastencode = Ereignis.which;\n".
			"		} else if (Ereignis.keyCode) {\n".
			"				Tastencode = Ereignis.keyCode;\n".
			"		}\n".
			"		if (Tastencode==13 || Tastencode==32)\n".
			"				return true;\n".
			"		else\n".
			"				return false;\n".
			"}\n".
			"function cssfComboBoxHelper(name) {\n".
			"		var ajaxname = 'ajax_' + name;\n".
			"		var ajaxdivname = 'ajax_div_' + name;\n".
			"		var ajaxselectname = 'ajax_select_' + name;\n".
			"\n".
			"		if (xajax.$(ajaxselectname).options.selectedIndex>=0) {\n".
			"				xajax.$(ajaxname).value=xajax.$(ajaxselectname).options[xajax.$(ajaxselectname).options.selectedIndex].text;\n".
			"				xajax.$(ajaxdivname).style.display='none';\n".
			"				xajax.$(ajaxname).focus();\n".
			"		}\n".
			"}\n".
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
				print "		<option";
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
			print "		<option";
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
								"		new Ajax.Autocompleter(\"ajax_$name\", \"ajax_div_$name\", \"$serverScript\"".(($options) ? ", {".$options."}" : "").");\n".
								"</script>\n";
	}
	 * deprecated
	 */
	
	function inputJqAutocomplete($x, $y, $w, $name, $value, $index, $serverScript, $maxsize = 0, $minLength = 1, $bgcol = "", $title = "",$autoFocus=false, $zeroOnEmpty=false) {
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
			. "		$(function() {\n"
			. "				$('#ajax_{$name}').autocomplete ({\n"
			. "						source: '{$serverScript}',\n"
			. "						minLength: {$minLength},\n"
			. "						delay: 500, \n";

			if ($autoFocus) print " autoFocus: true,\n";		
		print "	select: function(event, ui) { $('#{$name}Index').val(ui.item.id); }\n"
			. "				})\n"
			. "				.data('autocomplete')._renderItem = function( ul, item ) {\n"
			. "						return $('<li></li>')\n"
			. "								.data('item.autocomplete', item)\n"
			. "								.append('<a' + ((item.color) ? ' style=\"background-color:' + item.color + ';\">' : '>') + item.label + '</a>')\n"
			. "								.appendTo(ul);\n"
			. "				};\n"
			. "		});\n";
		if( $zeroOnEmpty ) print "$('#ajax_{$name}').change( function() { if( $('#ajax_{$name}').val() == '' ) $('#{$name}Index').val(''); } );\n";
		print "</script>\n";
	}
	
	
	function inputJqAutocomplete2($x, $y, $w, $name, $value, $index, $serverScript, $maxsize = 0, $minLength = 1, $bgcol = "", $title = "",$autoFocus=false,$textarea=false,$rows=0) {

		$this->_divclass($x, $y, "cssfinput");
		
		$val=htmlspecialchars($value, ENT_QUOTES);
		$bgcol=($bgcol=='')?"":" background-color: {$bgcol};";
		$maxsize=($maxsize=='')?"":" maxlength='{$maxsize}'";
		$title=($title=='')?"":" title='{$title}'";
	
		if($textarea){
			$value=str_replace('&quot;','"',$value);
			echo<<<EOF
<input type="hidden" name="{$name}Index" id="{$name}Index"		value="{$index}"/>
<textarea		tabindex="{$this->tabindex}" class='cssftextAutocomplete' style='width: {$w}em;{$bgcol}' rows="{$rows}" name="{$name}" id="ajax_{$name}"{$maxsize}{$title}>{$value}</textarea>
</div>

EOF;
		
	}else{
		
		echo<<<EOF
<input type="hidden" name="{$name}Index" id="{$name}Index"		value="{$index}"/>
 <input tabindex="{$this->tabindex}" class='cssftextAutocomplete' style='width: {$w}em;{$bgcol}' type="text" type="text" value="{$value}" name="{$name}" id="ajax_{$name}"{$maxsize}{$title} />
</div>

EOF;
		}
		
		$this->tabindex++;
	}
	
	// mustmach: 0 => don't need to match, symbol: !, mustmatch=1: => must match, orange + !,
	// mustmatch=2 => only an insert, no Index, mustmatch=3: => must much + "0" allowed
	// textarea>0 => $rows of textarea, otherwise textinput
	function inputJqAutocomplete3($x, $y, $w, $name, $index, $serverScript, $maxsize = 0, $minLength=1, $bgcol = "", $title = "",$mustmatch=0, $autoFocus=false,$textarea=0) {
		
		$val='';
		$id=$index;
		
		$bgcol=($bgcol=='')?"":" background-color: {$bgcol};";
		$maxsize=($maxsize=='')?"":" maxlength='{$maxsize}'";
		$title=($title=='')?"":" title='{$title}'";
		
		if($this->doEcho){
			$pi=parse_url($serverScript);
			parse_str($pi['query'],$pv);
			$res=array();
			//static call: $res=call_user_func_array( array('clsAutocompleteCommonName', $pv['field']), array('--',$index));
			if(strpos($pi['path'],'common')!==false){
				require_once('clsAutocomplete.php');
				if(method_exists('clsAutocompleteCommonName',$pv['field'])){
					if(!isset($GLOBALS['ACFREUD2']))$GLOBALS['ACFREUD2']=clsAutocompleteCommonName::Load();
					$res=call_user_func_array( array($GLOBALS['ACFREUD2'], $pv['field']), array('--',$index));
				}
			}else/*if(strpos($pi['path'],'??')!==false)*/{
				if(method_exists('clsAutocomplete',$pv['field'])){
					if(!isset($GLOBALS['ACFREUD1']))$GLOBALS['ACFREUD1']=clsAutocomplete::Load();
					$res=call_user_func_array( array($GLOBALS['ACFREUD1'], $pv['field']), array('--',$index));
				}
			}
			if(isset($res[0]) && isset($res[0]['value']) && isset($res[0]['id']) ){
				$val=$res[0]['value'];
				$id=$res[0]['id'];
				$index=0;
			}
		}
		if($mustmatch==2){
			$val=$id;
		}else if($id==0 && $mustmatch!=3){
			//$val=$_POST['ajax_'.$name];// Todo!!! wichtig!
		}
		if($textarea>0){
			$val=str_replace('&quot;','"',$val);
			$ret=<<<EOF
<textarea		tabindex="{$this->tabindex}" class='cssftextAutocomplete' style='width: {$w}em;{$bgcol}' rows="{$textarea}" name="ajax_{$name}" id="ajax_{$name}"{$maxsize}{$title}>{$val}</textarea>
EOF;
		
		}else{
			$val=htmlspecialchars($val, ENT_QUOTES);
			$ret=<<<EOF
 <input tabindex="{$this->tabindex}" class='cssftextAutocomplete' style='width: {$w}em;{$bgcol}' type="text" value="{$val}" name="ajax_{$name}" id="ajax_{$name}"{$maxsize}{$title} />
EOF;
		}
		
		$ret.=<<<EOF
<input type="hidden" name="{$name}Index" id="{$name}Index" value="{$id}"/>
EOF;
		if($this->doEcho){
			$ret.=<<<EOF
</div>
<script>ACFreudConfig.push(['{$serverScript}','{$name}','{$index}','{$mustmatch}','{$autoFocus}','{$minLength}']);</script>
EOF;
			$this->_divclass($x, $y, "cssfinput");
			echo $ret;
			$this->tabindex++;
		
		}else{
			return $ret;
		}
	}
	

	function inputMapLines($x, $y, $asdialog, $label, $title, $serverACL,$serverACR,$callbackUrl,$serverParams,$searchjs,$searchhtml){
		$htmla=$htmlb='';
		/*
		$r=array(array("\n","'"),array("",'"'));
		$this->setEcho(false);
		$htmla=str_replace($r[0],$r[1],$this->inputJqAutocomplete3(0, 0, 20, '###name###', '', '',0,'1', '','',3, true,false,false));
		$this->setEcho(true);

		$htmlb=str_replace('###name###',"acmap_r_'+x+'",$htmla);
		$htmla=str_replace('###name###',"acmap_l_'+x+'",$htmla);
		*/
?>
<link rel="stylesheet" href="inc/jQuery/pagination.css" type="text/css" />
<script type="text/javascript" src="inc/jQuery/jquery.pagination.js"></script>
<script type="text/javascript" src="inc/jQuery/jquery.multi-open-accordion-1.5.3.min.js"></script>
<script type="text/javascript" src="inc/jQuery/freud_EditMapping.js"></script>

<script>

<?PHP
echo<<<EOF
		
var serverACL='{$serverACL}';
var serverACR='{$serverACR}';
var serverUrl='{$callbackUrl}';
var serverParams='{$serverParams}';

{$searchjs}
EOF;
?>

var newsearch=false;
var searchString='';
var x=0;

function getACTableCode(idtype,x){
	/*return '<tr id="acmap_tr_'+x+'"><td><?PHP echo $htmla; ?></td><td><?PHP echo $htmlb; ?></td><td><a href="javascript:'+((idtype==1)?'removeInputLine':'deleteSearchedLine')+'(\''+x+'\')"><img src="webimages/remove.png" title="delete entry" border="0"></a>';*/
	return '<tr id="acmap_tr_'+x+'"><td> <input tabindex="1" class="cssftextAutocomplete" style="width: 20em;" type="text" value="" name="ajax_acmap_l_'+x+'" id="ajax_acmap_l_'+x+'" title="1" /><input type="hidden" name="acmap_l_'+x+'Index" id="acmap_l_'+x+'Index" value=""/></td><td> <input tabindex="1" class="cssftextAutocomplete" style="width: 20em;" type="text" value="" name="ajax_acmap_r_'+x+'" id="ajax_acmap_r_'+x+'" title="1" /><input type="hidden" name="acmap_r_'+x+'Index" id="acmap_r_'+x+'Index" value=""/></td><td><a href="javascript:'+((idtype==1)?'removeInputLine':'deleteSearchedLine')+'(\''+x+'\')"><img src="webimages/remove.png" title="delete entry" border="0"></a>';
}

</script>
<div id="dialog-confirm" title="Delete Item?" style="display:none;">
<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><div sytle="float:left"><span id="dialog-confirm-t"></span>This Item will be deleted. Are you sure?</div></p>
</div>

	<?PHP
	if($asdialog){
		// do Label text
		$this->label($x,$y,$label,'#" onclick="editMapping();');
		echo<<<EOF
<div id="editMapping" style="display:none;" title="Taxon Synonymy<br>{$title}">
EOF;
	}else{
		$this->_divclass($x,$y,"editMapping1");
		echo<<<EOF
Taxon Synonymy<br>{$title}<br>
EOF;
	}
	?>

<div id="editAccordion">
<h3><a href="#section1">Insert New and save</a></h3>
<div>
<form name="insertLineForm" id="insertLineForm" onsubmit="return false;">
<table id="insertLineTable">
<tr><td colspan="2"></td></tr>
</table>
<input class="cssftext" type="submit" name="doSearch" value="Save" >
<div style="float:left;margin-left:10px;" id="PageInfo3"></div>
</form>
</div>
<h3><a href="#section2">Search and instant deletion</a></h3>
<div>
<form  id="searchLineForm" onsubmit="return false;">
<?PHP echo $searchhtml; ?>
<input class="cssftext" type="submit" name="doSearch" value="Search" >
<table id="searchLineTable">
<tr><td colspan="2"></td></tr>
</table>
<div id="PageInfo"  style="float:left;"></div><div style="float:left;margin-left:10px;" id="PageInfo2"></div><div style="clear:both;" id="Pagination"></div>
</form>
</div>
</div>
</div>

<?PHP
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
		
		$value=htmlspecialchars_decode($value);
		$this->_divclass($x,$y,"cssfinput");
		print "\n<textarea tabindex=\"{$this->tabindex}\" class=\"cssf\" style=\"width: ".$w."em; height: ".$h."em;";
		if ($bgcol) print " background-color: $bgcol;";
		print "\" name=\"$name\"";
		if ($this->nameIsID) print " id=\"$name\"";
		if ($title) print " title=\"$title\"";
		if ($readonly) print " readonly";
		print " wrap=\"virtual\">";
		if ($value) print htmlspecialchars($value);
		print "</textarea>\n";
		print "</div>\n";
		$this->tabindex++;
	}
	
	/****************************************************************************
	*																			*
	*	Private methods															*
	*																			*
	****************************************************************************/
	function _divclass($x,$y,$class) {
		if($this->relative){
			$x+=$this->xrel;
		}
		$this->xrel=$x;
		print "<div class=\"$class\" style=\"position: absolute; left: ".$x."em; top: ".$y."em;\">";
	}
}