<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();
error_reporting(E_ALL);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Index</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="inc/jQuery/css/ui-lightness/jquery-ui.custom.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
	.ui-autocomplete {
        font-size: 0.9em;  /* smaller size */
		max-height: 200px;
		overflow-y: auto;
		/* prevent horizontal scrollbar */
		overflow-x: hidden;
		/* add padding to account for vertical scrollbar */
		padding-right: 20px;
	}
	/* IE 6 doesn't support max-height
	 * we use height instead, but this forces the menu to always be this tall
	 */
	* html .ui-autocomplete {
		height: 200px;
	}
  </style>
  <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">

  
var taxwin;
var citwin;
var geowin;

function selectTaxon() {
	//taxonID=document.f.taxonIndex;
	taxwin = window.open("listTaxCommonName.php", "selectTaxon", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	taxwin.focus();
}
function UpdateTaxon(taxonID) {
//alert(taxonID);
	a=$('#ajax_taxon');
	b='<'+taxonID+'>';

	a.bind( 'autocompleteopen', function(event, ui) {
		c=$(this).data('autocomplete').menu;
		c._trigger('selected',event,{item:$(c.element[0].firstChild)});
	}).bind( "autocompleteselect", function(event, ui) {
		a.unbind( "autocompleteopen");
	});
	a.autocomplete( "search",b);
	
}
function selectCitation() {
	citationID=document.f.citationIndex;
	citwin = window.open("listLitCommonName.php", "selectCitation", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	citwin.focus();
}
function UpdateCitation(citationID) {
	a=$('#ajax_citation');
	b='<'+citationID+'>';
	a.bind( 'autocompleteopen', function(event, ui) {
		c=$(this).data('autocomplete').menu;
		c._trigger('selected',event,{item:$(c.element[0].firstChild)});
	}).bind( "autocompleteselect", function(event, ui) {
		a.unbind( "autocompleteopen");
	});
	a.autocomplete( "search",b);
}
function selectGeoname() {
	if(!geowin || geowin.closed){
		geowin = window.open("selectGeoname.php", "SelectGeoname", "width="+screen.width+", height="+screen.height+", top=0, left=0, scrollbars=yes, resizable=yes");
	}
	geowin.focus();
}
function UpdateGeoname(geonameID) {
	a=$('#ajax_geoname');
	b='<'+geonameID+'>';
	a.bind( 'autocompleteopen', function(event, ui) {
		c=$(this).data('autocomplete').menu;
		c._trigger('selected',event,{item:$(c.element[0].firstChild)});
	}).bind( "autocompleteselect", function(event, ui) {
		a.unbind( "autocompleteopen");
	});
	a.autocomplete( "search",b);
	
}

function p(objarray){
	return alert(pr(objarray));
}

function p(objarray,tiefe){
	return alert(pr(objarray,tiefe));
}

function pr(objarray){
	return pr(objarray,4);
}

function pr(objarray,tiefe){
	return print_r1(objarray,'','',0,tiefe);
}

function print_r1(objarray,string,ebene,tiefe,maxtiefe){
	for(i in objarray){
		try{
			if(typeof(objarray[i])=='object' && tiefe<maxtiefe){

				string=print_r1(objarray[i],string,ebene+'['+i+']',tiefe+1,maxtiefe);

			}else{
				if(typeof(objarray[i])!='function'){
					string+=ebene+'['+i+']='+objarray[i]+"\n" ;
				}
			}
		}catch(e){}
	}
	return string;
}


  </script>
</head>

<body>

<?php
/*
ALTER TABLE `tbl_name_languages` CHANGE `iso639-6` `iso639_6` VARCHAR( 4 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
ALTER TABLE `tbl_geonames_cache` CHANGE `geonameId` `geoname_id` INT( 11 ) NOT NULL

ALTER TABLE `names`.`tbl_geonames_cache` DROP INDEX `geonameId_UNIQUE` ,
ADD UNIQUE `geonameId_UNIQUE` ( `name` ( 10 ) )

geonames.org
ALTER TABLE `tbl_name_languages` ADD `parent_id` VARCHAR( 4 ) NULL
ALTER TABLE `tbl_name_languages` ADD `name` VARCHAR( 50 ) NULL 
ALTER TABLE `tbl_name_entities` CHANGE `entity_id` `entity_id` INT( 11 ) NOT NULL AUTO_INCREMENT
*/

$common_name_dB='names.';

$p_taxonIndex	='';
$p_taxon='';	
$p_citationIndex	='';
$p_citation='';	
	
$p_geoname	='';
$p_geonameIndex	='';
$p_language	='';
$p_languageIndex	='';
$p_languageIso	='';
	
$p_period	='';
$p_periodIndex	='';
$p_common_name	='';
$p_common_nameIndex	='';

$p_timestamp		=	'';
$p_userID			=	'';


if(isset($_POST['submitUpdate']) || isset($_POST['submitSearch'])){
	$p_taxon			=	$_POST['taxon'];
	$p_citation			=	$_POST['citation'];
	$p_language			=	$_POST['language'];
	$p_common_nameIndex	=	$_POST['common_nameIndex'];
	
	$p_taxonIndex		=	$_POST['taxonIndex'];
	$p_citationIndex	=	$_POST['citationIndex'];
	$p_geonameIndex		=	$_POST['geonameIndex'];
	$p_geoname			=	$_POST['geoname'];
	$p_languageIndex	=	$_POST['languageIndex'];
	$p_languageIso		=	$_POST['languageIso'];
	$p_period			=	$_POST['period'];
	$p_periodIndex		=	$_POST['periodIndex'];
	$p_common_name		=	$_POST['common_name'];
	
	$p_userID			=	$_POST['userID'];
	$p_timestamp		= 	$_POST['timestamp'];

	$sql="
SELECT
 a.tbl_name_entities_entity_id as 'entity_id',
 taxon.taxonID as 'taxonID',
 
 a.tbl_name_names_name_id as 'name_id',
 com.common_name as 'common_name',
 
 a.tbl_name_languages_language_id as 'language_id',
 lan.`iso639-6` as 'iso639_6',
 lan.name as 'language',
 
 a.tbl_geonames_cache_geonameId as 'geoname_id',
 geo.name as 'geoname',
 
 a.tbl_name_periods_period_id as 'period_id',
 per.period as 'period',
 
 a.tbl_name_references_reference_id as 'reference_id',
 lit.citationID as 'citationID'
 
FROM
 tbl_name_applies_to a
 LEFT JOIN tbl_name_entities ent ON ent.entity_id = a.tbl_name_entities_entity_id
 LEFT JOIN tbl_name_taxon taxon ON taxon.taxon_id = ent.entity_id
 
 LEFT JOIN tbl_name_names nam ON  nam.name_id = a.tbl_name_names_name_id
 LEFT JOIN tbl_name_commons com ON  com.common_id = nam.name_id
 
 LEFT JOIN tbl_geonames_cache geo ON geo.geonameId = a.tbl_geonames_cache_geonameId
 LEFT JOIN tbl_name_languages lan ON  lan.language_id = a.tbl_name_languages_language_id
 LEFT JOIN tbl_name_periods per ON per.period_id= a.tbl_name_periods_period_id

 LEFT JOIN tbl_name_references ref ON ref.reference_id = a.tbl_name_references_reference_id
 LEFT JOIN tbl_name_literature lit ON  lit.literature_id = ref.reference_id
";

}

if (isset($_GET['new'])) {

	if(isset($_GET['taxonID'])){
		$p_taxonIndex	= extractID($_GET['taxonID']);
		$p_taxon		= getTaxon($p_taxonIndex);	
	}
	
	if(isset($_GET['citationID'])){
		$p_citationIndex	= extractID($_GET['citationID']);
		$p_citation			= getCitation($p_citationIndex);
	}

} elseif (isset($_GET['ID']) && extractID($_GET['ID']) !== "NULL") {

	
	$result = db_query($sql);
	$row = mysql_fetch_array($result);
	
	$p_taxonIndex		=	$row['taxon_id'];
	$p_taxon			=	getTaxon($p_taxonIndex);
	$p_citationIndex	=	$row['citation_id'];
	$p_citation			=	getCitation($p_citationIndex);
	
	$p_geoname			=	$row['geoname'];
	$p_geonameIndex		=	$row['geoname_id'];
	$p_language			=	$row['language'];
	$p_languageIndex	=	$row['language_id'];
	$p_languageIso	=	$row['iso639_6'];
	
	$p_period			=	$row['period'];
	$p_periodIndex		=	$row['period_id'];
	$p_common_name		=	$row['common_name'];
	$p_common_nameIndex	=	$row['name_id'];

	$p_timestamp		=	$row['timestamp'];
	$p_userID			=	$row['userID'];
	
} else if(isset($_POST['taxon'])){


	if (isset($_POST['submitUpdate']) && $_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x200) != 0)) {
	
		if ($p_taxonIndex>0) {
            
			//Cache geoname
			$sql="INSERT INTO {$common_name_dB}tbl_geonames_cache(geonameId, name) VALUES ('{$p_geonameIndex}','{$p_geoname}') ON DUPLICATE KEY UPDATE  name=VALUES(name)";
			$result = db_query($sql);
			
			// Language
			$sql="INSERT INTO {$common_name_dB}tbl_name_languages (iso639_6,name) VALUES ('{$p_languageIso}','".mysql_real_escape_string($p_language)."') ON DUPLICATE KEY UPDATE language_id=LAST_INSERT_ID(language_id)";
			$result = db_query($sql);
			$p_language_id=mysql_insert_id();
			
			//period: todo: think about update; delete old one??
			$sql="INSERT INTO {$common_name_dB}tbl_name_periods (period) VALUES ('{$p_period}') ON DUPLICATE KEY UPDATE period_id=LAST_INSERT_ID(period_id)";
			$result = db_query($sql);
			$p_periodIndex=mysql_insert_id();
			
			//NAMES
			//commonname
			$sql="SELECT common_id FROM {$common_name_dB}tbl_name_commons WHERE common_name='{$p_common_name}'";
			$result = db_query($sql);
			$p_nameIndex=0;
			if($result){
				$row=mysql_fetch_array($result);
				if(isset($row['common_id'])){
					$p_nameIndex=$row['common_id'];
				}
			}
			
			if($p_nameIndex==0){
				$sql="INSERT INTO {$common_name_dB}tbl_name_names (name_id) VALUES (NULL)";
				$result = db_query($sql);
				$p_nameIndex=mysql_insert_id();
				
				$sql="INSERT INTO {$common_name_dB}tbl_name_commons (common_id, common_name) VALUES ('{$p_nameIndex}','{$p_common_name}')";
				$result = db_query($sql);
			}
			//$p_common_nameIndex=$p_nameIndex;
			
			// ENTITY
			// taxon
			$sql="SELECT taxon_id FROM {$common_name_dB}tbl_name_taxon WHERE taxonID='{$p_taxonIndex}'";
			$result = db_query($sql);
			$p_entityIndex=0;
			if($result){
				$row=mysql_fetch_array($result);
				if(isset($row['taxon_id'])){
					$p_entityIndex=$row['taxon_id'];
				}
			}
			if($p_entityIndex==0){
				$sql="INSERT INTO {$common_name_dB}tbl_name_entities (entity_id) VALUES (NULL)";
				$result = db_query($sql);
				$p_entityIndex=mysql_insert_id();
				// todo: autoincrement entity_id
				$sql="INSERT INTO {$common_name_dB}tbl_name_taxon (taxon_id, taxonID) VALUES ('{$p_entityIndex}','{$p_taxonIndex}')";
				$result = db_query($sql);
			}
			
			// Literature
			// taxon
			$sql="SELECT literature_id FROM {$common_name_dB}tbl_name_literature WHERE CitationID='{$p_citationIndex}'";
			$result = db_query($sql);
			$p_referenceIndex=0;
			if($result){
				$row=mysql_fetch_array($result);
				if(isset($row['literature_id'])){
					$p_referenceIndex=$row['literature_id'];
				}
			}
			if($p_referenceIndex==0){
				$sql="INSERT INTO {$common_name_dB}tbl_name_references (reference_id) VALUES (NULL)";
				$result = db_query($sql);
				$p_referenceIndex=mysql_insert_id();
				// todo: autoincrement reference_id
				$sql="INSERT INTO {$common_name_dB}tbl_name_literature (literature_id,citationId) VALUES ('{$p_referenceIndex}','{$p_citationIndex}')";
				$result = db_query($sql);
			}
			
			// LINK IT
			$sql = "
INSERT INTO {$common_name_dB}tbl_name_applies_to SET
tbl_name_entities_entity_id =  " . makeInt( $p_entityIndex ) . ",
tbl_name_names_name_id =  " . makeInt( $p_nameIndex ) . ",
tbl_name_languages_language_id =  " . makeInt( $p_language_id ) . ",
tbl_geonames_cache_geonameId =  " . makeInt( $p_geonameIndex ) . ",
tbl_name_periods_period_id =  " . makeInt( $p_periodIndex ) . ",
tbl_name_references_reference_id =  " . makeInt( $p_referenceIndex ) . "";
			
			$result = db_query($sql);
			
			print_r($result);
			
/*
			echo mysql_errno($result) . ": " . mysql_error($result) . "\n";
            if (intval($p_taxindID)) {
                $sql = "UPDATE tbl_tax_index SET
                         $sql_data
                        WHERE taxindID = " . intval($p_taxindID);
                $updated = 1;
            } else {
                $sql = "INSERT INTO tbl_tax_index SET $sql_data";
                $updated = 0;
            }
            $result = db_query($sql);
            $id = (intval($p_taxindID)) ? intval($p_taxindID) : mysql_insert_id();
            
			logIndex($id, $updated);
            if ($result) {
                echo "<script language=\"JavaScript\">\n";
                echo "  window.opener.document.f.reload.click()\n";
                echo "  self.close()\n";
                echo "</script>\n";
            }
			*/
        } else {
			/*if($result==3){
				
				echo "<script language=\"JavaScript\">\n";
				echo "  alert('Bad formatted Taxon ID or Citation ID');\n";
				echo "</script>\n";
			}*/
		}
	}else if(isset($_POST['submitSearch'])){
		/*
WHERE
    a.tbl_name_entities_entity_id = 
and a.tbl_name_names_name_id = 
and a.tbl_name_languages_language_id = 
and a.tbl_geonames_cache_geonameId = 
and a.tbl_name_periods_period_id = 
and a.tbl_name_references_reference_id = 
*/

		$where=" WHERE 1=1";
		if(intval($p_taxonIndex)>0 && $p_taxon!='')$where.=" and ='".$p_taxonIndex."'";
		if(intval($p_common_nameIndex)>0 && $p_common_name!='')$where.=" and ='".$p_common_nameIndex."'";
		if(intval($p_languageIndex)>0 && $p_language!='')$where.=" and ='".$p_languageIndex."'";
		if(intval($p_geonameIndex)>0 && $p_geoname!='')$where.=" and ='".$p_geonameIndex."'";
		if(intval($p_periodIndex)>0 && $p_period!='')$where.=" and ='".$p_periodIndex."'";
		if(intval($p_citationIndex)>0 && $p_citation!='')$where.=" and ='".$p_citationIndex."'";
		
		$sql=$sql.$where;
		
		$result = db_query($sql);
		while($row = mysql_fetch_array($result)){
			print_r($row);
		}
		


	}
}
		
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
 $cf = new CSSF();
 echo "<input type=\"hidden\" name=\"timestamp\" value=\"$p_timestamp\">\n";
 echo "<input type=\"hidden\" name=\"userID\" value=\"$p_userID\">\n";

 $cf->label(10, 10, "Entity","javascript:selectTaxon()");
 $cf->inputJqAutocomplete(11, 10, 28, "taxon", $p_taxon, $p_taxonIndex, "index_jq_autocomplete.php?field=taxon_commonname", 520, 2);
 
 $cf->label(10, 13, "Common Name");
 $cf->inputJqAutocomplete(11, 13, 28, "common_name", $p_common_name, $p_common_nameIndex, "index_jq_autocomplete.php?field=cname_commonname", 520, 2);
 
 
 $cf->label(10, 16, "Geography","javascript:selectGeoname()");
 $cf->inputJqAutocomplete(11, 16, 28, "geoname", $p_geoname, $p_geonameIndex, "index_jq_autocomplete.php?field=cname_geoname", 520, 2);
/*
 $cf->label(10, 19, "Language");
 $cf->inputJqAutocomplete(11, 19, 28, "language", $p_language, $p_languageIndex, "index_jq_autocomplete.php?field=cname_language", 520, 2);
  
 $cf->label(10, 22, "Period");
 $cf->inputJqAutocomplete(11,22, 28, "period", $p_period, $p_periodIndex, "index_jq_autocomplete.php?field=cname_period", 520, 2);
*/
echo <<<EOF
<div class="cssflabel" style="position: absolute; left: 4.375em; top: 19.2em; width: 5.625em;">Language&nbsp;</div>
<div class="cssfinput" style="position: absolute; left: 11em; top: 19em;"><input class='cssftextAutocomplete' style='width: 28em;' type='text' name='language' id='ajax_language' value='{$p_language}' maxlength='520'></div>
<input type='hidden' name='languageIndex' id='languageIndex' value='{$p_languageIndex}'>
<input type='hidden' name='languageIso' id='languageIso' value='{$p_languageIso}'>
<script type='text/javascript' language='JavaScript'>
  $(function() {
    $('#ajax_language').autocomplete ({
      source: 'index_jq_autocomplete.php?field=cname_language',
      minLength: 2,
      delay: 500, 
      select: function(event, ui){ 
            a=ui.item.id.split(',');
			$('#languageIso').val(a[0]);
			$('#languageIndex').val(a[1]); 
	  }
    })
    .data('autocomplete')._renderItem = function( ul, item ) {
      return $('<li></li>')
        .data('item.autocomplete', item)
        .append('<a' + ((item.color) ? ' style="background-color:' + item.color + ';">' : '>') + item.label + '</a>')
        .appendTo(ul);
    };
  });
</script>
<div class="cssflabel" style="position: absolute; left: 5.625em; top: 22.2em;  width: 4.375em;">Period&nbsp;</div>
<div class="cssfinput" style="position: absolute; left: 11em; top: 22em;">
<textarea class="cssftextAutocomplete" style="width: 28em; height: 6em;" name='period' id='ajax_period' wrap="virtual">{$p_period}</textarea></div>
<input type='hidden' name='periodIndex' id='{$p_periodIndex}' value='4'>
<script type='text/javascript' language='JavaScript'>
  $(function() {
    $('#ajax_period').autocomplete ({
      source: 'index_jq_autocomplete.php?field=cname_period',
      minLength: 2,
      delay: 500, 
      select: function(event, ui) { $('#periodIndex').val(ui.item.id); }
    })
    .data('autocomplete')._renderItem = function( ul, item ) {
      return $('<li></li>')
        .data('item.autocomplete', item)
        .append('<a' + ((item.color) ? ' style="background-color:' + item.color + ';">' : '>') + item.label + '</a>')
        .appendTo(ul);
    };
  });
</script>
EOF;


 
 $cf->label(10, 30, "Reference","javascript:selectCitation()");
 $cf->inputJqAutocomplete(11, 30, 28, "citation", $p_citation, $p_citationIndex, "index_jq_autocomplete.php?field=cname_citation", 520, 2);

if (($_SESSION['editControl'] & 0x200) != 0) {
    $text = ($p_taxindID) ? " Update " : " Insert ";
    $cf->buttonSubmit(10, 33, "reload", " Reload ");
    $cf->buttonReset(18, 33, " Reset ");
    $cf->buttonSubmit(26, 33, "submitUpdate", $text);
}
$cf->buttonJavaScript(34, 33, " Cancel ", "self.close()");
$cf->buttonSubmit(38, 33, "submitSearch", "search");
?>
  <script type="text/javascript" language="JavaScript">

   $(document).ready(function() {
   UpdateTaxon('16083');
 });
 </script>
</form>
</body>
</html>

<?
function getTaxon($taxon_id){
	$sql = "
SELECT
 taxonID, tg.genus,
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
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
WHERE
 taxonID = '" . $taxon_id . "'";
	
	$result = db_query($sql);
	$row = mysql_fetch_array($result);
	return taxon($row);
}

function getCitation($citation_id){
	 $sql = "
SELECT
 citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
FROM
 tbl_lit l
 LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
 LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
 LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
WHERE
 citationID = '" . $citation_id . "'";
	$result = db_query($sql);
	return protolog(mysql_fetch_array($result));
}
?>