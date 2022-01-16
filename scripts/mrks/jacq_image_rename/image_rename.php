#!/usr/bin/php
<?php

require ('/usr/local/jacq_image_rename/config/config.php');


# bash-parameter: ................. ####################################################################################################

# standard-werte für parameter ...
$input = "";
$output = "";
$tmp_dir_input = "";

$kurz_par  = "";
$kurz_par .= "a:"; # acronym
$kurz_par .= "i:"; # input folder
$kurz_par .= "s:"; # session_name of capture one software
$kurz_par .= "o:"; # output folder
$kurz_par .= "r"; # rename
$kurz_par .= "u:"; # url
$kurz_par .= "t:"; # tmp folder
$kurz_par .= "h"; # listet parameter auf


$lang_par  = array(
    "acronym:",  # acronym
    "input:",  # input 
    "session_name:", # session name of capture one software
    "output:",    # output
    "rename",    # rename
    "url:",    # url
    "tmpdir:",    # url
    "help",   # listet parameter auf
    );
$bash_options = getopt($kurz_par, $lang_par);
#$_REQUEST['valid_only'] = "XXXX";

if (isset($bash_options['a']))  $acronym = $bash_options['a'];
if (isset($bash_options['i']))  $input = $bash_options['i'];
if (isset($bash_options['s']))  $session = $bash_options['s'];
if (isset($bash_options['o']))  $output = $bash_options['o'];
if (isset($bash_options['u']))  $url = $bash_options['u'];
if (isset($bash_options['t']))  $tmp_dir_input = $bash_options['t'];

if (isset($bash_options['acronym']))  $acronym = $bash_options['acronym'];
if (isset($bash_options['input']))  $input = $bash_options['input'];
if (isset($bash_options['session']))  $session = $bash_options['session'];
if (isset($bash_options['output']))  $output = $bash_options['output'];
if (isset($bash_options['url']))  $url = $bash_options['url'];
if (isset($bash_options['tmpdir']))  $tmp_dir_input = $bash_options['tmpdir'];

if (isset($bash_options['h']) || isset($bash_options['help']))
    {
    echo "\n### mögliche Parameter:\n";
    echo "\t-a --acronym\therbarium acronym\n";
    echo "\t-i --input\tinput folder (subdir of standard input dir with leading '+' (e.g. +/20211123_01/)\n";
    echo "\t-o --output\toutput folder\n";
    echo "\t-s --session\tsession name from capture one software\n";
    echo "\t-t --tmpdir\ttmp folder\n";
    echo "\t-u --url\tURL prefix within QR-Code (e.g. https://wu.jacq.org/)\n";
    echo "\t-h --help\tpossible options\n";
    echo "\n";
    exit;
    }



function if_null_insertaufbereitung($post_content)
	{
	$post_content_return = '';
	$post_content = str_replace("'","\'",$post_content);
	$post_content = str_replace('"','&quot;',$post_content);

		if ($post_content == '')
					{
					$post_content_return = 'NULL';
					}
				else
					{
					$post_content_return = "'".$post_content."'";
					}
		return ($post_content_return);
	}



$dblink = new mysqli($dbhost, $dbuser, $dbpwd, $dbname, 3306);
if ($dblink->connect_errno) {
    echo "Failed to connect to MySQL: (" . $dblink->connect_errno . ") " . $dblink->connect_error;
}
#mysqli_set_charset("UTF8", $dblink);  
if (!$dblink->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $dblink->error);
} else {
    #printf("Current character set: %s\n", $dblink->character_set_name());
}

$GLOBALS['dblink']=$dblink;

############################################################################

if (isset($_SERVER["REMOTE_USER"]))
	{
	$user = $_SERVER["REMOTE_USER"]; #apache-benuztername auslesen
	}
elseif (get_current_user()) $user = get_current_user(); # linux user auslesen
else
	{
	$user = '';
	}

############################################################################
#echo "XXXXXXXXXXXXXXXXXX: ".$user."\n";


if ($session != '')
    {
    $input = "+".$session."/Output/";
    }




if (preg_match("/\/$/",$input_base_dir) != 1 && $input_base_dir != '') $input_base_dir .= "/"; # adds missing / at the end of input_base_dir
if (preg_match("/\/$/",$output_base_dir) != 1 && $output_base_dir != '') $output_base_dir .= "/"; # adds missing / at the end of output_base_dir

if (preg_match("/^\+/",$input) == 1) #if input subdir is given
    {
   # if ($output == '') $output = $input; #if no output subdir is given => input subdir'll be used 
    $input = $input_base_dir.trim($input,'+/');
    }

if (preg_match("/\/$/",$input) != 1 && $input != '') $input .= "/"; # adds missing / at the end of input

if ($input == '')
    {
    echo "\nInput directory missing ...\nUse '-h' for help.\n\n";
    exit;
    }

if (!file_exists($input) && !is_dir($input))
    {
    echo "\nNo such input directory: ".$input."\n\n";
    exit;
    } 
    
if (!file_exists($output_base_dir) && !is_dir($output_base_dir))
    {
    echo "\nNo such output base directory: ".$output_base_dir."\n\n";
    echo "\nPlease check config file ...\n\n";
    exit;
    } 
        
if (!is_writable($output_base_dir))
    {
    echo "\nNo writing permissions in output base directory: ".$output."\n\n";
    exit;
    } 

    

if ($output != '' && trim($output,'+./') != '' && preg_match("/^\+/",$output) == 1) # if output subdir is given
    {
    if (!is_writable($output_base_dir))
        {
        echo "\nNo writing permissions in output base directory for creating subdir: ".$output_base_dir."\n\n";
        exit;
        } 
    $output = $output_base_dir.trim($output,'+/');
    if (!file_exists($output) && !is_dir($output)) mkdir($output);
    }
    
    
if ($output != '' &&  !file_exists($output) && !is_dir($output))
    {
    echo "\nNo such output directory: ".$output."\n\n";
    exit;
    } 
    
if ($output != '' && !is_writable($output))
    {
    echo "\nNo writing permissions in output directory: ".$output."\n\n";
    exit;
    } 

if (preg_match("/\/$/",$output) != 1 && $output != '') $output .= "/"; # adds missing / at the end of output
    
 
if ($tmp_dir_input != '') $tmp_dir =  $tmp_dir_input;
 
if (!file_exists($tmp_dir) && !is_dir($tmp_dir))
    {
    echo "\nNo such tmp-directory: ".$tmp_dir."\n\n";
    exit;
    } 
    
if (!is_writable($tmp_dir))
    {
    echo "\nNo writing permissions in tmp-directory: ".$tmp_dir."\n\n";
    exit;
    } 

if (preg_match("/\/$/",$tmp_dir) != 1 && $tmp_dir != '') $tmp_dir .= "/"; # adds missing / at the end of output
 
 
    

$filelist = scandir($input);


#$img_file_pattern = "/\.(tif|tiff|jpg|jpeg|png)$/i"; # nur jpeg/tiff/png erlaubt
$img_file_pattern = "/\.(tif|tiff)$/i"; # nur tiff erlaubt

$qr_url_pattern = str_replace("/","\\/",$url);
$qr_url_pattern = str_replace(".","\\.",$qr_url_pattern);
$qr_url_pattern2 = "/".$qr_url_pattern.$acronym."/i"; # pattern zum extrahieren der herb-nr.
$qr_url_pattern = "/^".$qr_url_pattern.$acronym."[0-9]+$/i"; # pattern für vergleich


foreach ($filelist as $file)
    {
    echo "###########################################################################\n";
    if (preg_match($img_file_pattern,$file) == 1)
        {
        echo $input.$file."\n";
       
        $comment = '';
     
        #exif-daten auslesen
        $exif = exif_read_data($input.$file);

        $aufnahmedatum = $exif['DateTimeOriginal'];
        echo "Aufnahmedatum: ".$aufnahmedatum."\n";
        $year = substr($aufnahmedatum,0,4);
        $year2 = substr($aufnahmedatum,2,2);
        $month = substr($aufnahmedatum,5,2);
        $day = substr($aufnahmedatum,8,2);
        if ($year != '' && $year >= 2000 && $month != '' && preg_match("/^(0[1-9]|1[0-2])$/",$month) == 1 && $day != '')
            {
            $output = $output_base_dir.$year."/";
            if (!file_exists($output) && !is_dir($output)) mkdir($output);
            $output = $output_base_dir.$year."/".$year2.$month.$day."/";
            if (!file_exists($output) && !is_dir($output)) mkdir($output);
           }
        else if ($output == '')
            {
            echo "\nDate unknown - Please use '-o' to set output directory manually: ".$aufnahmedatum."\n\n";
            exit;            
            }

        if (!file_exists($output) && !is_dir($output))
            {
            echo "\nNo such output directory: ".$output."\n\n";
            exit;
            } 
    
            
        # neuer db-Eintrag
        $insert_query = "INSERT INTO `jacq_image_rename` (
                            `id`,                 
                            `orig_path`, 
                            `orig_filename`, 
                            `orig_time`, 
                            `user`,
                            `timestamp`) 
                        VALUES (
                            NULL, 
                            ".if_null_insertaufbereitung($input).", 
                            ".if_null_insertaufbereitung($file).", 
                            ".if_null_insertaufbereitung($aufnahmedatum).", 
                            ".if_null_insertaufbereitung($user).", 
                            CURRENT_TIMESTAMP)";

        $insertgetdata = mysqli_query($GLOBALS['dblink'], $insert_query);
        if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
        $new_db_id=mysqli_insert_id($GLOBALS['dblink']);
        echo "ID: ".$new_db_id." -- New Database entry --\n";
      
        
        $qr_output = shell_exec("zbarimg ".$input.$file." -q");
        # bei qr-error: https://stackoverflow.com/questions/69689869/improve-zbarimg-qrcode-recognition
        # https://stackoverflow.com/questions/64831824/qr-code-detection-with-zbar-from-console-fails-for-valid-qr-codes-zbarcam-from
        # https://github.com/mchehab/zbar/issues/65
        if ($qr_output == '') # if no qr code is found => try rescan with converted image
            {
            $tmp_file = $tmp_dir."qr_code_file_".time().".tif";
            echo shell_exec("gm -convert ".$input.$file."[0] +repage -threshold 50% ".$tmp_file);
            $qr_output = shell_exec("zbarimg ".$tmp_file." -q");
            echo "\n +++ QR RESCAN DONE +++ \n";
            echo shell_exec("rm ".$tmp_file);
            }
        
        
        
        echo $qr_output;
        if ($qr_output != '') 
            {
            $qr_output = rtrim($qr_output);
           # $qr_arr = explode ("QR-Code:",$qr_output);
            $qr_arr = preg_split ("/(QR-Code:|\R(((?!.*http).*)):)/",$qr_output);
            $qr_arr = array_filter($qr_arr); # entfernt leere arrays
            #var_dump($qr_arr);
            $ii = 0;
            foreach ($qr_arr AS $qr_txt)
                {
                $ii++;
                $comment = '';
                $qr_txt = rtrim($qr_txt);
                
                if ($ii >= 2) # if more than one qr-code exists, create additonal db entry for each qr-code
                    {
                    # neuer db-Eintrag
                    $insert_query = "INSERT INTO `jacq_image_rename` (
                                        `id`,                 
                                        `orig_path`, 
                                        `orig_filename`, 
                                        `orig_time`, 
                                        `qr_code`,
                                        `user`,
                                        `timestamp`) 
                                    VALUES (
                                        NULL, 
                                        ".if_null_insertaufbereitung($input).", 
                                        ".if_null_insertaufbereitung($file).", 
                                        ".if_null_insertaufbereitung($aufnahmedatum).", 
                                        ".if_null_insertaufbereitung($user).", 
                                        ".if_null_insertaufbereitung($user).", 
                                        CURRENT_TIMESTAMP)";

                    $insertgetdata = mysqli_query($GLOBALS['dblink'], $insert_query);
                    if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
                    $new_db_id=mysqli_insert_id($GLOBALS['dblink']);
                    echo "ID: ".$new_db_id." -- New Database entry --\n";
                    }


                if (preg_match($qr_url_pattern,$qr_txt) == 1) # überprüft ob qr-code dem schema ener stable-url des entsprechenden herbariums entspricht
                    {
                   # var_dump($qr_url_pattern2);
                    $herb_nr = preg_replace($qr_url_pattern2,"",$qr_txt);
                    echo "\e[0;32mHerb. No.: ".$herb_nr."\e[0m\n";
                    
                    #prüfen ob der neue Dateiname mit "_01", "_a" oder ähnlichem versehen sein soll (weil es der orig-Dateiname auch ist)
                    $second_img_pattern = "/_(0[0-9]{1}|[a-z]{1}).(tif|tiff)$/i";
                    if (preg_match($second_img_pattern,$file,$filename_match))
                        {
                        if (isset($filename_match[1])) $filename_erg = "_".$filename_match[1];
                        #replace usw.
                        }
                    else $filename_erg = '';
                    
                    # datei umbenennen/kopieren
                    if ($output_format == "phaidra") $img_new_filename1 =strtoupper($acronym).strtolower($herb_nr.$filename_erg.".tif"); #phaidra format e.g. WU0123456.tif
                    elseif ($output_format == "djatoka" || $output_format == "traditional") $img_new_filename1 = strtolower($acronym."_".$herb_nr.$filename_erg.".tif"); #traditional format e.g. wu_0123456.tif
                    else $img_new_filename1 =strtoupper($acronym).strtolower($herb_nr.$filename_erg.".tif"); # if unclear use phaidra format
                    
                    $img_new_filename = $output.$img_new_filename1;
                    if (file_exists($img_new_filename))
                        {
                        echo "\e[0;31m\n File already exists: ".$img_new_filename1."\e[0m\n\n";
                        $comment = "File already exists";
                        $img_new_filename1 = '';                        
                        }
                    else
                        {
                        $copy = copy($input.$file,$img_new_filename);
                        if ($copy != "1") 
                            {
                            echo "\e[0;31m\n File rename/copy ERROR: ".$copy."\e[0m\n\n";
                            $comment = "File rename/copy Error";
                            $img_new_filename1 = '';
                            }
                        }
                    #db-update-query
                    $update_query = "UPDATE `jacq_image_rename` 
                            SET 
                            `new_path` = ".if_null_insertaufbereitung($output).",
                            `new_filename` = ".if_null_insertaufbereitung($img_new_filename1).",
                            `qr_code` = ".if_null_insertaufbereitung($qr_txt).",
                            `base_url` = ".if_null_insertaufbereitung($url).",
                            `acronym` = ".if_null_insertaufbereitung($acronym).",
                            `number` = ".if_null_insertaufbereitung($herb_nr).",
                            `comment` = ".if_null_insertaufbereitung($comment)."
                             WHERE `id` = ".$new_db_id;
                    }
                else 
                    {
                    echo "\e[0;31m### Invalid qr code: ".$qr_txt."\e[0m\n";
                    $update_query = "UPDATE `jacq_image_rename` 
                            SET 
                            `qr_code` = ".if_null_insertaufbereitung($qr_txt).",
                            `comment` = ".if_null_insertaufbereitung('Invalid QR code')."
                             WHERE `id` = ".$new_db_id;
                    }
                
                $insertgetdata = mysqli_query($GLOBALS['dblink'], $update_query);
                if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
                $new_id=mysqli_insert_id($GLOBALS['dblink']);
                echo "DB: ".$new_db_id." -- Database update ---\n";

                }
            }
        else 
            {
            echo "\e[0;31m### No QR-Code found: ".$file."\e[0m\n";
            $update_query = "UPDATE `jacq_image_rename` 
                            SET 
                            `comment` = ".if_null_insertaufbereitung('No QR-Code found')."
                             WHERE `id` = ".$new_db_id;
            $insertgetdata = mysqli_query($GLOBALS['dblink'], $update_query);
            if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
            $new_id=mysqli_insert_id($GLOBALS['dblink']);
            echo "DB: ".$new_db_id." -- Database update ---\n";
            }
        }
    else echo "### Incorrect filetype: ".$file."\n";
    }




?>

