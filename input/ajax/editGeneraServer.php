<?php
/**
 * the complete file is no longer needed
 * kept just for the records :-)
 */
session_start();
require_once ("../inc/xajax/xajax_core/xajax.inc.php");
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
//no_magic();   das funktioniert bei ajax NICHT!!!!!  Vorsicht bei Datenbankupdates!!

function make_family($value) {
  $results = array();
  if ($value && strlen($value)>1) {
    $pieces = explode(" ",$value);
    $sql = "SELECT family, familyID, category ".
           "FROM tbl_tax_families tf ".
            "LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID=tf.categoryID ".
           "WHERE family LIKE '".mysql_escape_string($pieces[0])."%' ".
           "ORDER BY family";
    if ($result = db_query($sql)) {
      if (mysql_num_rows($result)>0)
        while ($row=mysql_fetch_array($result))
          $results[] = $row['family']." ".$row['category']." <".$row['familyID'].">";
    }
  }
  if (!count($results)) $results[] = "";
  $results[] = "";

  return $results;
}

function make_author($value) {
  $results = array();
  if ($value && strlen($value)>1) {
    $pieces = explode(" <",$value);
    $sql = "SELECT author, authorID, Brummit_Powell_full ".
           "FROM tbl_tax_authors ".
           "WHERE author LIKE '".mysql_escape_string($pieces[0])."%' ".
           "ORDER BY author";
    if ($result = db_query($sql)) {
      if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result)) {
          $res = $row['author']." <".$row['authorID'].">";
          if ($row['Brummit_Powell_full']) $res .= " [".replaceNewline($row['Brummit_Powell_full'])."]";
          $results[] = $res;
        }
      }
    }
  }
  if (!count($results)) $results[] = "";
  $results[] = "";

  return $results;
}

function make_taxon($value) {
  $results = array();
  if ($value && strlen($value)>1) {
    $pieces = explode(chr(194).chr(183),$value);
    $pieces = explode(" ",$pieces[0]);
    $sql = "SELECT taxonID, tg.genus, ".
            "ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ".
            "ta4.author author4, ta5.author author5, ".
            "te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, ".
            "te4.epithet epithet4, te5.epithet epithet5 ".
           "FROM tbl_tax_species ts ".
            "LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID ".
            "LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID ".
            "LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID ".
            "LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID ".
            "LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID ".
            "LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID ".
            "LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID ".
            "LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID ".
            "LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID ".
            "LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID ".
            "LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID ".
            "LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID ".
            "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
           "WHERE tg.genus LIKE '".mysql_escape_string($pieces[0])."%' ";
    if ($pieces[1])
      $sql .= "AND te.epithet LIKE '".mysql_escape_string($pieces[1])."%' ";
    $sql .= "ORDER BY tg.genus, te.epithet, epithet1, epithet2, epithet3";
    if ($result = db_query($sql)) {
      if (mysql_num_rows($result)>0)
        while ($row=mysql_fetch_array($result))
          $results[] = taxon($row);
    }
  }
  if (!count($results)) $results[] = "";
  $results[] = "";

  return $results;
}

/**
 * xajax-function for displaying a select-block of a combobox
 *
 * @param string $name name of the input-block and part of the function to call
 * @param string $value current contents of the input-block
 * @param string $display current display state of the div-block ("none" or "block")
 * @return xajaxResponse
 */
function cssfComboBox($name, $value, $display) {
  $func = 'make_'.$name;
  $results = $func($value);
  $numresults = count($results);

  $data = "<select id=\"ajax_select_$name\" class=\"cssf\" size=\"".(($numresults>10) ? 10 : $numresults)."\" ".
          "style=\"min-width:".strlen($value)."ex;\" ".
          "onclick=\"form.$name.value=this.options[this.options.selectedIndex].text; xajax.$('ajax_div_$name').style.display='none';\"".
          "onkeypress=\"if (cssfActivateKeyPress(event)) {cssfComboBoxHelper('$name');}\"".
          ">\n";
  foreach ($results as $result) {
  	$data .= "<option";
  	if ($result==$value) $data .= " selected";
  	$data .= ">".htmlspecialchars($result)."</option>\n";
  }
  $data .= "</select>\n";

  $objResponse = new xajaxResponse();

  if ($display=="none") {
    $objResponse->assign("ajax_div_$name", 'style.display', 'block');
    $objResponse->assign("ajax_div_$name", 'innerHTML', $data);
    $objResponse->call("xajax.$('ajax_select_$name').focus","");
  }
  else {
    $objResponse->assign("ajax_div_$name", 'style.display', 'none');
    $objResponse->call("xajax.$('ajax_$name').focus","");
  }
  $objResponse->assign("ajax_$name", 'disabled', '');

  return $objResponse;
}

/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("cssfComboBox");
$xajax->processRequest();