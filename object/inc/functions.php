<?php
require_once('variables.php');
require_once('AnnotationQuery.inc.php');
require_once('ImagePreview.inc.php');
require_once('StableIdentifier.php');

// Connect to output DB by default

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASES']['OUTPUT']['host'],
                     $_CONFIG['DATABASES']['OUTPUT']['readonly']['user'],
                     $_CONFIG['DATABASES']['OUTPUT']['readonly']['pass'],
                     $_CONFIG['DATABASES']['OUTPUT']['db']);
if ($dbLink->connect_errno) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n".
         "<html>\n".
         "<head><titel>Sorry, no connection ...</title></head>\n".
         "<body><p>Sorry, no connection to database ...</p></body>\n".
         "</html>\n";
    exit();
}
$dbLink->set_charset('utf8');


function collection ($Sammler, $Sammler_2, $series, $series_number, $Nummer, $alt_number, $Datum)
{
    $text = $Sammler;
    if (strstr($Sammler_2, "&") || strstr($Sammler_2, "et al.")) {
        $text .= " et al.";
    } else if ($Sammler_2) {
        $text .= " & " . $Sammler_2;
    }
    if ($series_number) {
        if ($Nummer) {
            $text .= " " . $Nummer;
        }
        if ($alt_number && $alt_number != "s.n.") {
            $text .= " " . $alt_number;
        }
        if ($series) {
            $text .= " " . $series;
        }
        $text .= " " . $series_number;
    } else {
        if ($series) {
            $text .= " " . $series;
        }
        if ($Nummer) {
            $text .= " " . $Nummer;
        }
        if ($alt_number) {
            $text .= " " . $alt_number;
        }
        if (strstr($alt_number, "s.n.")) {
            $text .= " [" . $Datum . "]";
        }
    }

    return $text;
}


// new triple id class
class MyTripleID extends TripleID
{
	public function __construct($id) {
        global $dbLink;
		// do some conversion stuff
		// ex.: database query for institution, source, object ...
		//      sql = "SELECT * FROM table WHERE id=" . $id

		// fill variables with data from database

		$query = "SELECT s.specimen_ID, mc.collection, mc.collectionID, mc.source_id, mc.coll_short, mc.coll_gbif_pilot, s.HerbNummer
                  FROM tbl_specimens s
                   LEFT JOIN tbl_management_collections mc ON mc.collectionID=s.collectionID
                  WHERE specimen_ID=" . ($id);
		$result = $dbLink->query($query);

		if ($dbLink->connect_errno) {
			echo $query . "<br>\n";
			echo $dbLink->error . "<br>\n";
		}
		$row = $result->fetch_array();

		if ($row['source_id'] == '29'){
            $unitid = $row['HerbNummer'];
            $source = 'Herbarium Berolinense';
            $institutionID = 'B';
        }

        if ($row['source_id'] == '6'){
            $unitid = $row['specimen_ID'];
            $source = 'Herbarium W';
            $institutionID = 'W';
        }

		$this->institutionID = $institutionID;
		$this->sourceID = $source;
		$this->objectID = $unitid;
	}
} // class MyTripleID


function generateAnnoTable($metadata)
{
	// table header
	$str = '<table width="100%"><tr><td align="left">'
	     . '<strong>' . count($metadata) . ' annotation(s)</strong></td></tr>';
	// add annotations
	foreach($metadata as $anno) {
		$str .= '<tr><td align="left">';
		$str .= "<strong>Annotator:</strong> " . $anno['annotator'] . "; ";
		$str .= "<strong>Type of annotation:</strong> " . $anno['motivation'] . "; ";
		$str .= "<strong>Date:</strong> " . date("d M Y", $anno['time']/1000) . "; ";
		$str .= "<a href=\"" . $anno['viewURI'] . '" target="_blank" class="leftnavi">View annotation</a><br/>';
		$str .= "<hr /></td></tr>";
	}
	// close table
	$str .= "</table>";

	return $str;
}


function collectionID ($row)
{
	if ($row['source_id'] == '29') {
        $text = ($row['HerbNummer']) ? $row['HerbNummer'] : ('B (JACQ-ID ' . $row['specimen_ID'] . ')');
    } elseif ($row['source_id'] == '50') {
        $text = ($row['HerbNummer']) ? $row['HerbNummer'] : ('Willing (JACQ-ID ' . $row['specimen_ID'] . ')');
    } else {
        $text = $row['collection'] . " " . $row['HerbNummer'];
	}

    return $text;
}

function taxon ($row)
{
    $text = $row['genus'];
    if ($row['epithet'])  { $text .= " "          . $row['epithet']  . " " . $row['author'];  }
    if ($row['epithet1']) { $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1']; }
    if ($row['epithet2']) { $text .= " var. "     . $row['epithet2'] . " " . $row['author2']; }
    if ($row['epithet3']) { $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3']; }
    if ($row['epithet4']) { $text .= " forma "    . $row['epithet4'] . " " . $row['author4']; }
    if ($row['epithet5']) { $text .= " subforma " . $row['epithet5'] . " " . $row['author5']; }

    return $text;
}

function taxonWithHybrids ($row)
{
    global $dbLink;

    if ($row['statusID'] == 1 && strlen($row['epithet']) == 0 && strlen($row['author']) == 0) {
        $rowHybrid = $dbLink->query("SELECT parent_1_ID, parent_2_ID
                                     FROM tbl_tax_hybrids
                                     WHERE taxon_ID_fk = '" . $row['taxonID'] . "'")->fetch_array();
        $row1 = $dbLink->query("SELECT tg.genus,
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
                                WHERE taxonID = '" . $rowHybrid['parent_1_ID'] . "'")->fetch_array();
        $row2 = $dbLink->query("SELECT tg.genus,
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
                                WHERE taxonID = '" . $rowHybrid['parent_2_ID'] . "'")->fetch_array();

        return taxon($row1) . " x " . taxon($row2);
    } else {
        return taxon($row);
    }
}

/*************************************************************************
php easy :: pagination scripts set - Version Three
==========================================================================
Author:      php easy code, www.phpeasycode.com
Web Site:    http://www.phpeasycode.com
Contact:     webmaster@phpeasycode.com
*************************************************************************/
function paginate_three ($reload, $page, $tpages, $adjacents)
{
	$prevlabel = "&lsaquo; Prev";
	$nextlabel = "Next &rsaquo;";

	$out = "<div class=\"pagin\">\n";

	// previous
	if($page == 1) {
		$out.= "<span>$prevlabel</span>\n";
	} else if ($page == 2) {
		$out.= "<a href=\"" . htmlspecialchars($reload) . "\">$prevlabel</a>\n";
	} else {
		$out.= "<a href=\"" . htmlspecialchars($reload) . "&amp;page=" . htmlspecialchars(($page - 1)) . "\">$prevlabel</a>\n";
	}

	if ($tpages < 4 + $adjacents * 2 + 2) {
		$pmin = 1;
		$pmax = $tpages;
	} else {
		$prev = 0;
		$post = 0;
		// first
		if($page > ($adjacents + 2)) {
			$prev++;
			$out.= "<a href=\"" . htmlspecialchars($reload) . "\">1</a>\n";
		}

		// interval
		if ($page > ($adjacents + 3)) {
			$prev++;
			$out.= "<span class=\"pot\">...</span>";
		}

		// interval
		if ($page < ($tpages - $adjacents - 2)) {
            $post++;
        }

		// last
		if ($page < ($tpages - $adjacents - 2)) {
            $post++;
        }
		$pmin = $page - $adjacents - (2 - $prev);
		if ($pmin < 1) {
            $pmin = 1;
        }
		$diff = $adjacents - ($page - $pmin);
		$pmax = $page + $adjacents + $diff + 2 - $prev + 2 - $post;
		if ($pmax > $tpages) {
			$pmax = $tpages;
			$pmin = $pmax - 2 * $adjacents - 2;
			if ($pmin < 1) {
                $pmin = 1;
            }
		}
	}

	for ($i = $pmin; $i <= $pmax; $i++) {
		if ($i == $page) {
			$out.= "<span class=\"current\">" . htmlspecialchars($i) . "</span>\n";
		} elseif ($i == 1) {
			$out.= "<a href=\"" . htmlspecialchars($reload) . "\">" . htmlspecialchars($i) . "</a>\n";
		} else {
			$out.= "<a href=\"" . htmlspecialchars($reload) . "&amp;page=" . htmlspecialchars($i) . "\">" . htmlspecialchars($i) . "</a>\n";
		}
	}
	if (!($tpages < 4 + $adjacents * 2 + 2)) {
		// interval
		if ($page < ($tpages - $adjacents - 2)) {
			$out.= "<span class=\"pot\">...</span>";
		}

		// last
		if ($page < ($tpages - $adjacents - 2)) {
			$out.= "<a href=\"" . htmlspecialchars($reload) . "&amp;page=" . htmlspecialchars($tpages) . "\">" . htmlspecialchars($tpages) . "</a>\n";
		}
	}

	// next
	if ($page < $tpages) {
		$out.= "<a class=\"nextlabel\" href=\"" . htmlspecialchars($reload) . "&amp;page=" . htmlspecialchars(($page+1)) . "\">$nextlabel</a>\n";
	} else {
		$out.= "<span class=\"nextlabel\">$nextlabel</span>\n";
	}

	$out.= "</div>";

	return $out;
}