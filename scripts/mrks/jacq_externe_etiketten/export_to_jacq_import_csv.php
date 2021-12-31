<?php
header("Content-type: text/csv; charset=UTF-8; X-Content-Type-Options: nosniff");
header("Content-Disposition: attachment; filename=jacq_import.csv");

require_once __DIR__ . '/../external_tools/composer/vendor/autoload.php'; # mpdf and phpoffice/phpspreadsheet

require_once ('../quadrant/quadrant_function.php'); # functions to transform and maipulate geographic coodinates and related stuff
require_once ('../functions/namensaufspaltung_functions_agg.php'); # functions to split and rebuild taxa names
require_once ('../functions/read_csv_xlsx_functions.php'); # function to read spreadsheets based on phpoffice/phpspreadsheet
require_once ('./etiketten_functions.php'); # function to read spreadsheets based on phpoffice/phpspreadsheet


 

###################### read input file #################################
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);
$csv_neu = csv_to_array($_FILES['file']['tmp_name'], ",",$extension);

########################################################################



  
  
$herb_arr = nomalize_input_data($csv_neu);
  
#$herb_arr = herb_ett_ionprot($where,'','',$preset,$ett_anz);
#var_dump($herb_arr);


##################################################################################################################

echo "\"HerbNummer\";\"collectionID\";\"CollNummer\";\"status\";\"taxon\";\"Sammler\";\"series\";\"series_number\";\"Nummer\";\"alt_number\";\"Datum\";\"Datum2\";\"det\";\"typified\";\"Typus\";\"taxon_alt\";\"nation_engl\";\"provinz\";\"Fundort\";\"Fundort_engl\";\"Habitat\";\"Habitus\";\"Bemerkungen\";\"coord_NS\";\"lat_degree\";\"lat_minute\";\"lat_second\";\"coord_WE\";\"long_degree\";\"long_minute\";\"long_second\";\"quadrant\";\"quadrant_sub\";\"exactness\";\"alt_min\";\"alt_max\";\"digital_image\";\"digital_image_obs\";\"observation\"\n";


foreach ($herb_arr as $herb_line)
    {
    if ($herb_line['coll_no2'] == '' && $herb_line['coll_no'] != '')
        {
        if (!is_int($herb_line['coll_no']))
            {
            $herb_line['coll_no2'] = $herb_line['coll_no'];
            $herb_line['coll_no'] = '';
            }
        }
    
    #HerbNummer
    echo "\"".$herb_line['herbarium_no']."\";";
    #collectionID
    echo "\"".$herb_line['institution_subcollection']."\";";
    #CollNummer
    echo "\"".$herb_line['subcollection_number']."\";";
    #status
    echo "\"".$herb_line['status']."\";";
    #taxon
    echo "\"".$herb_line['taxon']."\";";
    #Sammler
    echo "\"".$herb_line['collectors']."\";";
    #series
    echo "\"".$herb_line['series']."\";";
    #series_number
    echo "\"".$herb_line['series_number']."\";";
    #Nummer
    echo "\"".$herb_line['coll_no']."\";";
    #alt_number
    echo "\"".$herb_line['coll_no2']."\";";
    #Datum
    echo "\"".$herb_line['date1']."\";";
    #Datum2
    echo "\"".$herb_line['date2']."\";";
    #det
    echo "\"".$herb_line['det']."\";";
    #typified
    echo "\"".$herb_line['typified']."\";";
    #Typus
    echo "\"".$herb_line['type_information']."\";";
    #taxon_alt
    echo "\"".$herb_line['ident_history']."\";";
    #nation_engl
    echo "\"".$herb_line['country']."\";";
    #provinz
    echo "\"".$herb_line['province']."\";";
    #Fundort
    echo "\"".$herb_line['locality']."\";";
    #Fundort_engl
    echo "\"".$herb_line['locality_en']."\";";
    #Habitat
    echo "\"".$herb_line['habitat']."\";";
    #Habitus
    echo "\"".$herb_line['habitus']."\";";
    #Bemerkungen
    echo "\"".$herb_line['annotations']."\";";
    #coord_NS
    echo "\"".$herb_line['lat_card']."\";";
    #lat_degree
    echo "\"".$herb_line['lat_deg']."\";";
    #lat_minute
    echo "\"".$herb_line['lat_min']."\";";
    #lat_second
    echo "\"".$herb_line['lat_sec']."\";";
    #coord_WE
    echo "\"".$herb_line['long_card']."\";";
    #long_degree
    echo "\"".$herb_line['long_deg']."\";";
    #long_minute
    echo "\"".$herb_line['long_min']."\";";
    #long_second
    echo "\"".$herb_line['long_sec']."\";";
    #quadrant
    echo "\"".$herb_line['gf']."\";";
    #quadrant_sub
    echo "\"".$herb_line['qu']."\";";
    #exactness
    echo "\"".$herb_line['exactness']."\";";
    #alt_min
    echo "\"".$herb_line['alt_min']."\";";
    #alt_max
    echo "\"".$herb_line['alt_max']."\";";
    #digital_image
    echo "\"".$herb_line['digital_image']."\";";
    #digital_image_obs
    echo "\"".$herb_line['digital_image_obs']."\";";
    #observation
    echo "\"".$herb_line['observation']."\";";
    echo "\n";
    }

?>
