<?php

// Todo, 3.8.2011!
// ghomolka
require("../inc/variables.php");

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['INPUT']['host'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['user'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['pass'],
                     $_CONFIG['DATABASE']['INPUT']['name']);
if ($dbLink->connect_errno) {
    echo 'no database connection';
	exit();
}
$dbLink->set_charset('utf8');

$bpath='C:\Users\gunther\Desktop\sqls1\exiv2';
$pic='C:\Users\gunther\Desktop\sqls1\exiv2\wu_0038434.tif';

$a=$bpath.'\exiv2.exe -pa '.$pic;


exec($a,$t,$r);

$row=array();
$extractData=array(
 //'Exif.Image.BitsPerSample'=>	array('type'=>'Short','val'=>	''	),	//	8 8 8
 //'Exif.Image.Compression'=>	array('type'=>'Short','val'=>	''	),	//	Uncompressed
 //'Exif.Image.PhotometricInterpretation'=>	array('type'=>'Short','val'=>	''	),	//	RGB
 //'Exif.Image.SamplesPerPixel'=>	array('type'=>'Short','val'=>	''	),	//	3
 //'Exif.Image.RowsPerStrip'=>	array('type'=>'Short','val'=>	''	),	//	11351
 //'Exif.Image.StripByteCounts'=>	array('type'=>'Long','val'=>	''	),	//	269427336
 //'Exif.Image.PlanarConfiguration'=>	array('type'=>'Short','val'=>	$row['1']	),	//	1
 //'Exif.Image.ExifTag'=>	array('type'=>'Long','val'=>	$row['269462540']	),	//	269462540
 //'Iptc.Envelope.CharacterSet'=>	array('type'=>'String','val'=>	$row['?%G']	),	//	?%G
 //'Iptc.Application2.RecordVersion'=>	array('type'=>'Short','val'=>	$row['10664']	),	//	10664

 'Exif.Image.ImageWidth'=>	array('type'=>'Short','val'=>	''	),	//	7912
 'Exif.Image.ImageLength'=>	array('type'=>'Short','val'=>	''	),	//	11351
 'Iptc.Application2.DateCreated'=>	array('type'=>'Date','val'=>	''	),	//	40858
 'Iptc.Application2.TimeCreated'=>	array('type'=>'Time','val'=>	''	),	//	00:00:00+00:00
 'Exif.Image.Make'=>	array('type'=>'Ascii','val'=>	''	),	//	PENTACON
 'Exif.Image.Model'=>	array('type'=>'Ascii','val'=>	''	),	//	Scan5000
 'Exif.Image.XResolution'=>	array('type'=>'Rational','val'=>	''	),	//	600
 'Exif.Image.YResolution'=>	array('type'=>'Rational','val'=>	''	),	//	600
 'Exif.Image.ResolutionUnit'=>	array('type'=>'Short','val'=>	''	),	//	inch
 'Exif.Image.DateTime'=>	array('type'=>'Ascii','val'=>	''	),	//	2011:08:12 19:54:00
 'Exif.Photo.ColorSpace'=>	array('type'=>'Short','val'=>	''	),	//	Uncalibrated
 'Exif.Photo.PixelXDimension'=>	array('type'=>'Long','val'=>	''	),	//	7912
 'Exif.Photo.PixelYDimension'=>	array('type'=>'Long','val'=>	''	),	//	11351
 'Xmp.dc.format'=>	array('type'=>'XmpText','val'=>	''	),	//	image/tiff

);


//success
if($r==0){
	$res=array();
	foreach($t as $line){
		$keypos=strpos($line," ");
		$key=substr($line,0,$keypos);
		if(isset($extractData[$key])){
			$val=substr($line,60);
			$res[$key]=$val;

		}
	}
	echo "<pre>";
	print_r($res);
	//print_r($t);
	echo "</pre>";
// Error
}else{
	echo "error";
}
exit;
$row=array();


		$query="
SELECT
 sp.*,
 mgm.*,
 me.*,
 mdb.*,
 nat.*,
 prov.*,
 reg.*,
 spc.*,
 rank.*,
 col1.*,
 col2.*,

 tg.genus,
 te.epithet,ta.author,
 te1.epithet epithet1, ta1.author author1,
 te2.epithet epithet2, ta2.author author2,
 te3.epithet epithet3, ta3.author author3,
 te4.epithet epithet4, ta4.author author4,
 te5.epithet epithet5, ta5.author author5,

 lit.citationID, lit.suptitel, le.autor AS editor, la.autor, lp.periodicalID, lp.periodical

FROM
tbl_specimens sp
LEFT JOIN tbl_management_collections mgm ON mgm.collectionID=sp.collectionID
LEFT JOIN meta me ON me.source_id=mgm.source_id
LEFT JOIN metadb mdb ON mdb.source_id_fk=mgm.source_id
LEFT JOIN tbl_geo_nation nat ON nat.nationID=sp.NationID
LEFT JOIN tbl_geo_province prov ON prov.nationID=sp.nationID
LEFT JOIN tbl_geo_region reg ON reg.regionID= nat.regionID_fk

LEFT JOIN tbl_tax_species spc ON spc.taxonID=sp.taxonID
LEFT JOIN tbl_tax_genera tg ON tg.genID = spc.genID

LEFT JOIN tbl_tax_authors ta ON ta.authorID = spc.authorID
LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = spc.subspecies_authorID
LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = spc.variety_authorID
LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = spc.subvariety_authorID
LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = spc.forma_authorID
LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = spc.subforma_authorID
LEFT JOIN tbl_tax_epithets te ON te.epithetID = spc.speciesID
LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = spc.subspeciesID
LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = spc.varietyID
LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = spc.subvarietyID
LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = spc.formaID
LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = spc.subformaID

LEFT JOIN tbl_tax_index tbli ON tbli.taxindID=spc.taxonID
LEFT JOIN tbl_lit lit ON lit.citationID=tbli.citationID
LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = lit.periodicalID
LEFT JOIN tbl_lit_authors le ON le.autorID = lit.editorsID
LEFT JOIN tbl_lit_authors la ON la.autorID = lit.autorID
LEFT JOIN tbl_tax_rank rank ON rank.tax_rankID=spc.tax_rankID

LEFT JOIN tbl_collector col1 ON col1.SammlerID=sp.SammlerID
LEFT JOIN tbl_collector_2 col2 ON col2.Sammler_2ID=sp.Sammler_2ID

WHERE filename='wu_0020014';
";
	//echo $query;exit;
	$res = $dbLink->query($query);
	while($row = mysqli_fetch_array($res)){



# 'annotations ophyte aption'=>	array('type'=>'String','val'=>	$row['type']	),	//	type ??
#	//'Xmp.iptcExt.DigitalSourceType'=>	array('type'=>'XmpText','val'=>	 'http://cv.iptc.org/newscodes/digitalsourcetype/positiveFilm'	),	//	http://cv.iptc.org/newscodes/digitalsourcetype/positiveFilm

 /*
   	$row['specimen_ID']	$row['HerbNummer']	$row['collectionID']	$row['CollNummer']	$row['identstatusID']	$row['checked']	$row['accessible']
	$row['taxonID']	$row['SammlerID']	$row['Sammler_2ID']	$row['seriesID']	$row['series_number']	$row['Nummer']	$row['alt_number']	$row['Datum']
	$row['Datum2']	$row['det']	$row['typified']	$row['typusID']	$row['taxon_alt']	$row['NationID']	$row['provinceID']	$row['Bezirk']

		$row['quadrant']	$row['quadrant_sub']	$row['exactness']	$row['altitude_min']
 2009-11-09 16:50:24


 1866-02-17

		$row['habitat']	$row['habitus']	$row['Bemerkungen']	$row['aktualdatum']	$row['eingabedatum']	$row['digital_image']
	$row['garten']	$row['voucherID']	$row['ncbi_accession']	$row['foreign_db_ID']	$row['label']	$row['observation']	$row['digital_image_obs']	$row['filename']

	$row['source_id_fk']	$row['supplier_supplied_when']	$row['supplier_organisation']	$row['supplier_organisation_code']	$row['supplier_person']	$row['supplier_url']
	$row['supplier_adress']	$row['supplier_telephone']	$row['supplier_email']	$row['legal_owner_organisation']	$row['legal_owner_organisation_code']	$row['legal_owner_person']
	$row['legal_owner_adress']	$row['legal_owner_telephone']	$row['legal_owner_email']	$row['legal_owner_url']	$row['terms_of_use']	$row['acknowledgement']	$row['description']
	$row['disclaimer']	$row['restrictions']	$row['logo_url']	$row['statement_url']	$row['copyright']	$row['ipr']	$row['rights_url']
//date("c",mktime(hour,min,sec,month,days,year)

*/
		// todo: date can also be only a year...
		$date=explode("-",$row['Datum']);
		$date2=preg_split("/-|\s|\:/", $row['aktualdatum']);

		$newData=array();
		$newData=array_merge($newData,array(
 #'Xmp.dc.title'=>	array('type'=>'LangAlt lang="x-default','val'=>	$row['taxon (specimen_ID Institution HerbarNr. Collection)']	),	//	taxon (specimen_ID Institution HerbarNr. Collection)
 #'Xmp.dc.description'=>	array('type'=>'LangAlt lang="x-default','val'=>	$row['type']	),	//	type
 #'Exif.Image.ImageDescription'=>	array('type'=>'Ascii','val'=>	$row['type']	),	//	type
 #'Xmp.dc.subject'=>	array('type'=>'XmpBag','val'=>	$row['stichworte']	),	//	stichworte
 #'Iptc.Application2.TransmissionReference'=>	array('type'=>'String','val'=>	$row['Jobkennung']	),	//	Jobkennung
 #'Iptc.Application2.Keywords'=>	array('type'=>'String','val'=>	$row['stichworte']	),	//	stichworte


 'Exif.Image.Artist'=>	array('type'=>'Ascii','val'=>	$row['supplier_organisation']	),	//	supplier_organisation
 'Exif.Image.Copyright'=>	array('type'=>'Ascii','val'=>	$row['legal_owner_adress']	),	//	legal_owner_adress
 'Iptc.Application2.Writer'=>	array('type'=>'String','val'=>	$row['supplier_person']	),	//	supplier_person
 'Iptc.Application2.Headline'=>	array('type'=>'String','val'=>	$row['genus'].$row['species']	),	//	taxon (specimen_ID Institution HerbarNr. Collection)
 'Iptc.Application2.SpecialInstructions'=>	array('type'=>'String','val'=>	$row['acknowledgement']	),	//	acknowledgement
 'Iptc.Application2.Byline'=>	array('type'=>'String','val'=>	$row['supplier_organisation']	),	//	supplier_organisation
 'Iptc.Application2.Credit'=>	array('type'=>'String','val'=>	$row['legal_owner_organisation']	),	//	legal_owner_organisation
 'Iptc.Application2.Source'=>	array('type'=>'String','val'=>	$row['supplier_organisation']	),	//	legal_owner_organisation
 'Iptc.Application2.ObjectName'=>	array('type'=>'String','val'=>	$row['filename']	),	//	taxon (specimen_ID Institution HerbarNr. Collection)
 'Iptc.Application2.City'=>	array('type'=>'String','val'=>	$row['Fundort_engl'].'('.$row['Fundort_engl'].')'	),	//	Ort
 'Iptc.Application2.SubLocation'=>	array('type'=>'String','val'=>	$row['Bezirk']	),	//	Ortdetail
 'Iptc.Application2.ProvinceState'=>	array('type'=>'String','val'=>	$row['provinz']	),	//	Bundesland
 'Iptc.Application2.CountryName'=>	array('type'=>'String','val'=>	$row['nation_engl']	),	//	land
 'Iptc.Application2.CountryCode'=>	array('type'=>'String','val'=>	$row['iso_alpha_3_code']	),	//	ISO
 'Iptc.Application2.Copyright'=>	array('type'=>'String','val'=>	$row['legal_owner_organisation'].' '.$row['legal_owner_adress']	),	//	legal_owner_adress
 'Xmp.xmp.CreatorTool'=>	array('type'=>'XmpText','val'=>	'EXIV2'	),	//	Adobe Photoshop CS5 Windows mktime(
 'Xmp.xmp.ModifyDate'=>	array('type'=>'XmpText','val'=>	date("c",mktime($date2[3],$date2[4],$date2[5],$date2[1],$date2[2],$date2[0])) ),	//	2011-08-12T19:54:01+02:00
 'Xmp.xmp.CreateDate'=>	array('type'=>'XmpText','val'=>	date("c",mktime(12,0,0,$date[1],$date[2],$date[0])) ),	//	2011-08-12T17:15:33+02:00
 'Xmp.xmp.MetadataDate'=>	array('type'=>'XmpText','val'=>	date("c")	),	//	2011-08-12T19:54:01+02:00 c	ISO 8601 Datum (hinzugef�gt in PHP 5) 2004-02-12T15:19:21+00:00
 'Xmp.dc.rights'=>	array('type'=>'LangAlt lang="x-default','val'=>	$row['legal_owner_organisation'].' '.$row['legal_owner_adress']	),	//	legal_owner_adress
 'Xmp.dc.creator'=>	array('type'=>'XmpSeq','val'=>	$row['supplier_organisation']	),	//	supplier_organisation
 'Xmp.iptc.Location'=>	array('type'=>'XmpText','val'=>	$row['Bezirk']	),	//	Ortdetail
 'Xmp.iptc.CountryCode'=>	array('type'=>'XmpText','val'=>	$row['iso_alpha_3_code']	),	//	ISOCODE
 'Xmp.iptc.CreatorContactInfo'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//
 'Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiAdrExtadr'=>	array('type'=>'XmpText','val'=>	$row['supplier_adress']	),	//	supplier_adress
 'Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiTelWork'=>	array('type'=>'XmpText','val'=>	$row['supplier_telephone']	),	//	supplier_telephone
 'Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiUrlWork'=>	array('type'=>'XmpText','val'=>	$row['supplier_url']	),	//	supplier_url
 'Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiEmailWork'=>	array('type'=>'XmpText','val'=>	$row['supplier_email']	),	//	supplier_email
 'Xmp.iptc.CreatorContactInfo/Iptc4xmpCore:CiAdrCtry'=>	array('type'=>'XmpText','val'=>	'Austria'	),	//	�sterreich
 'Xmp.xmpRights.Marked'=>	array('type'=>'XmpText','val'=>	'TRUE'	),	//	TRUE
 'Xmp.xmpRights.WebStatement'=>	array('type'=>'XmpText','val'=>	$row['legal_owner_url']	),	//	legal_owner_url
 'Xmp.xmpRights.UsageTerms'=>	array('type'=>'LangAlt lang="x-default"','val'=>	$row['terms_of_use']	),	//	terms_of_use

 'Xmp.iptcExt.PersonInImage'=>	array('type'=>'XmpBag','val'=>	$row['genus'].' '.$row['epithet'].' '.$row['author'].' ' ),	//	taxon

 'Xmp.iptcExt.LocationCreated'=>	array('type'=>'XmpText type="Bag"','val'=>	 '0'	),	//	0
 'Xmp.iptcExt.LocationCreated[1]'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//	0
 'Xmp.iptcExt.LocationCreated[1]/Iptc4xmpExt:Sublocation'=>	array('type'=>'XmpText','val'=>	 '1'	),	//	1
 'Xmp.iptcExt.LocationCreated[1]/Iptc4xmpExt:City'=>	array('type'=>'XmpText','val'=>	 '2'	),	//	2
 'Xmp.iptcExt.LocationCreated[1]/Iptc4xmpExt:ProvinceState'=>	array('type'=>'XmpText','val'=>	 '3'	),	//	3
 'Xmp.iptcExt.LocationCreated[1]/Iptc4xmpExt:CountryName'=>	array('type'=>'XmpText','val'=>	 '4'	),	//	4
 'Xmp.iptcExt.LocationCreated[1]/Iptc4xmpExt:CountryCode'=>	array('type'=>'XmpText','val'=>	 '5'	),	//	5
 'Xmp.iptcExt.LocationCreated[1]/Iptc4xmpExt:WorldRegion'=>	array('type'=>'XmpText','val'=>	 '6'	),	//	6

 'Xmp.iptcExt.LocationShown'=>	array('type'=>'XmpText type="Bag"','val'=>	 '0'	),	//	0
 'Xmp.iptcExt.LocationShown[1]'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//	0
 'Xmp.iptcExt.LocationShown[1]/Iptc4xmpExt:Sublocation'=>	array('type'=>'XmpText','val'=>	$row['Bezirk']	),	//	Ortsdetaik
 'Xmp.iptcExt.LocationShown[1]/Iptc4xmpExt:City'=>	array('type'=>'XmpText','val'=> $row['Fundort_engl'].'('.$row['Fundort_engl'].')'	),	//	Ort
 'Xmp.iptcExt.LocationShown[1]/Iptc4xmpExt:ProvinceState'=>	array('type'=>'XmpText','val'=>	$row['provinz']	),	//	Bundesland
 'Xmp.iptcExt.LocationShown[1]/Iptc4xmpExt:CountryName'=>	array('type'=>'XmpText','val'=>	$row['nation_engl']	),	//	Landesname
 'Xmp.iptcExt.LocationShown[1]/Iptc4xmpExt:CountryCode'=>	array('type'=>'XmpText','val'=>	$row['iso_alpha_3_code']	),	//	ISOCODE
 'Xmp.iptcExt.LocationShown[1]/Iptc4xmpExt:WorldRegion'=>	array('type'=>'XmpText','val'=>	$row['geo_region']	),	//	Weltregion

 'Xmp.iptcExt.RegistryId'=>	array('type'=>'XmpText type="Bag"','val'=>	 '0'	),	//	0
 'Xmp.iptcExt.RegistryId[1]'=>	array('type'=>'XmpText type="Struct" ','val'=>	 '0'	),	//	0
 'Xmp.iptcExt.RegistryId[1]/Iptc4xmpExt:RegOrgId'=>	array('type'=>'XmpText','val'=>	$row['source_code']	),	//	source_code
 'Xmp.iptcExt.RegistryId[1]/Iptc4xmpExt:RegItemId'=>	array('type'=>'XmpText','val'=>	$row['specimen_ID']	),	//	specimenID

 'Xmp.plus.ImageSupplierImageID'=>	array('type'=>'XmpText','val'=>	$row['specimen_ID']	),	//	specimenID
 'Xmp.plus.PropertyReleaseStatus'=>	array('type'=>'XmpText','val'=>	'Unlimited Property Releases'	),	//	Unlimited Property Releases
 'Xmp.plus.Version'=>	array('type'=>'XmpText','val'=>	'36557'	),	//	36557

 'Xmp.plus.ImageSupplier'=>	array('type'=>'XmpText type="Seq"','val'=>	 '0'	),	//	0
 'Xmp.plus.ImageSupplier[1]'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//	0
 'Xmp.plus.ImageSupplier[1]/plus:ImageSupplierName'=>	array('type'=>'XmpText','val'=>	$row['source_name']	),	//	source_name
 'Xmp.plus.ImageSupplier[1]/plus:ImageSupplierID'=>	array('type'=>'XmpText','val'=>	$row['source_code']	),	//	source_code


 'Xmp.plus.ImageCreator'=>	array('type'=>'XmpText type="Seq"','val'=>	 '0'	),	//	0
 'Xmp.plus.ImageCreator[1]'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//	0
 'Xmp.plus.ImageCreator[1]/plus:ImageCreatorName'=>	array('type'=>'XmpText','val'=>	$row['Sammler']	),	//	Wien
 'Xmp.plus.ImageCreator[1]/plus:ImageCreatorID'=>	array('type'=>'XmpText','val'=>	$row['Sammler_FN_short']	),	//	thomas 1

 'Xmp.plus.ImageCreator[2]'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//	0
 'Xmp.plus.ImageCreator[2]/plus:ImageCreatorName'=>	array('type'=>'XmpText','val'=>	$row['Sammler_2']	),	//	Wien
 'Xmp.plus.ImageCreator[2]/plus:ImageCreatorID'=>	array('type'=>'XmpText','val'=>	$row['Sammler_2_FN_list']	),	//	thomas 1


 'Xmp.plus.CopyrightOwner'=>	array('type'=>'XmpText type="Seq"','val'=>	 '0'	),	//	0
 'Xmp.plus.CopyrightOwner[1]'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//	0
 'Xmp.plus.CopyrightOwner[1]/plus:CopyrightOwnerName'=>	array('type'=>'XmpText','val'=>	$row['source_code']	),	//	copy1
 'Xmp.plus.CopyrightOwner[1]/plus:CopyrightOwnerID'=>	array('type'=>'XmpText','val'=>	$row['source_name']	),	//	copyval1


 'Xmp.plus.Licensor'=>	array('type'=>'XmpText type="Seq"','val'=>	 '0'	),	//	0
 'Xmp.plus.Licensor[1]'=>	array('type'=>'XmpText type="Struct"','val'=>	 '0'	),	//	0
 'Xmp.plus.Licensor[1]/plus:LicensorName'=>	array('type'=>'XmpText','val'=>	$row['legal_owner_person']	),	//	lizenz1
 'Xmp.plus.Licensor[1]/plus:LicensorID'=>	array('type'=>'XmpText','val'=>	$row['legal_owner_organisation']	),	//	nn
 'Xmp.plus.Licensor[1]/plus:LicensorTelephone1'=>	array('type'=>'XmpText','val'=>	$row['legal_owner_telephone']	),	//	tel1
 'Xmp.plus.Licensor[1]/plus:LicensorEmail'=>	array('type'=>'XmpText','val'=>	$row['legal_owner_email']	),	//	email1
 'Xmp.plus.Licensor[1]/plus:LicensorURL'=>	array('type'=>'XmpText','val'=>	$row['legal_owner_url']	),	//	url1
 ));

	/*
	 // Todo: GPS
	if($row['Coord_W']!='' || $row['Coord_E']!=''||$row['Coord_S']!='' || $row['Coord_N']!=''){
		$newData=array_merge($newData,array(
			'Exif.GPSInfo.GPSVersionID'=>	array('type'=>'Byte','val'=>	'02000000.H'	),	//	02000000.H
			'Exif.GPSInfo.GPSTimeStamp'=>	array('type'=>'Rational','val'=>	date("H:i:s",mktime(12,0,0,$date[1],$date[2],$date[0])) ),	//	14:00:47
			'Exif.GPSInfo.GPSAltitude'=>	array('type'=>'Rational','val'=>	$row['altitude_in']	),	//	RATIONAL value in meters
			'Exif.GPSInfo.GPSAltitudeRef'=>	array('type'=>'Byte','val'=>	'0'	),	//	above/under sea level: 0/1
		));
	}

	if($row['Coord_W']!='' || $row['Coord_E']!=''){

		if($row['Coord_W']!=''){
			$newData=array_merge($newData,array(
				'Exif.GPSInfo.GPSLongitudeRef'=>	array('type'=>'Ascii','val'=>	$row['W']	),	//	E or W
				'Exif.GPSInfo.GPSLongitude'=>	array('type'=>'Rational','val'=>sprintf ("%2d/1,%2d/1,%2d/1", $row['Coord_W'],$row['W_Min'],$row['W_Sec'] )	),	//	dd/1,mm/1,ss/1
			));

		 }else if($row['Coord_E']!=''){
			$newData=array_merge($newData,array(
				'Exif.GPSInfo.GPSLongitudeRef'=>	array('type'=>'Ascii','val'=>	$row['E']	),	//	E or W
				'Exif.GPSInfo.GPSLongitude'=>	array('type'=>'Rational','val'=>sprintf ("%2d/1,%2d/1,%2d/1", $row['Coord_E'],$row['E_Min'],$row['E_Sec'] )	),	//	dd/1,mm/1,ss/1
			));
		 }
	}

	 if($row['Coord_S']!='' || $row['Coord_N']!=''){

		if($row['Coord_S']!=''){
			$newData=array_merge($newData,array(
				'Exif.GPSInfo.GPSLongitudeRef'=>	array('type'=>'Ascii','val'=>	$row['S']	),	//	E or W
				'Exif.GPSInfo.GPSLongitude'=>	array('type'=>'Rational','val'=>sprintf ("%2d/1,%2d/1,%2d/1", $row['Coord_S'],$row['S_Min'],$row['S_Sec'] )	),	//	dd/1,mm/1,ss/1
			));

		 }else if($row['Coord_N']!=''){
			$newData=array_merge($newData,array(
				'Exif.GPSInfo.GPSLongitudeRef'=>	array('type'=>'Ascii','val'=>	$row['N']	),	//	E or W
				'Exif.GPSInfo.GPSLongitude'=>	array('type'=>'Rational','val'=>sprintf ("%2d/1,%2d/1,%2d/1", $row['Coord_N'],$row['N_Min'],$row['N_Sec'] )	),	//	dd/1,mm/1,ss/1
			));
		 }
	}*/

		echo "<pre>";
		print_r($row);
		print_r($newData);
		echo "</pre>";
	}



?>