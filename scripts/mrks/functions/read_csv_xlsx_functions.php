<?php


##### Function zum Tabellen auslesen #############################################################
function csv_to_array($filename='', $delimiter=',', $endung = '') 
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();

    if ($endung == 'xlsx' || $endung == 'xls' || $endung == 'ods' || $endung == 'csv') # wenn Dateiendung Tabelle
		{
		$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filename); #ermittelt dateityp automatisch
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
		$spreadsheet = $reader->load($filename); # lädt datei

		$highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
		$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn();

		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

		$header = array();
		$cc = 0;
		for ($row = 1; $row <= $highestRow; $row++) 
			{
			 $arr_row = array();
			  for ($col = 0; $col <= $highestColumnIndex; ++$col) 
				{
					if($row == 1)
						{
						if ($spreadsheet->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue() == "Collection") $cc++; # weil im export-file 2x der gleiche splatenname collecton ...
						if ($spreadsheet->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue() == "Collection" && $cc == 2) $header[] = "Collector"; # wenn oberhalb das 2. mal. der wert erhöht wurde dann heißt splate collector anstatt collection ...
						else $header[] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue();
						}
					else
						{
						$arr_row[] = $spreadsheet->getActiveSheet()->getCellByColumnAndRow($col, $row)->getValue();
						}

				  }
        #   echo "<p>".$row."</p>";
        #   var_dump($arr_row);
			if ($row != 1) $data[] = array_combine($header, $arr_row);

			}
		}
    return $data;
}


####################### datum prüfen ##############################
function validate_date($datum)
  {
  if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$datum))
    {
        return $datum;
    }else{
        return 0;
    }
  }
##################################################################
     


?>
