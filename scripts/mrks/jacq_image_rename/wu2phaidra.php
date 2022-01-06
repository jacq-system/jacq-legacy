#!/usr/bin/php
<?php

require ('/usr/local/lib/jacq_img_rename/config/config.php');


# bash-parameter: ................. ####################################################################################################

# standard-werte für parameter ...
$input = "";
$output = "";
$tmp_dir_input = "";
$session = "";

$kurz_par  = "";
#$kurz_par .= "a:"; # acronym
#$kurz_par .= "i:"; # input folder
$kurz_par .= "s:"; # session_name of capture one software
#$kurz_par .= "o:"; # output folder
#$kurz_par .= "r"; # rename
#$kurz_par .= "u:"; # url
#$kurz_par .= "t:"; # tmp folder
$kurz_par .= "h"; # listet parameter auf


$lang_par  = array(
    "session_name:", # session name of capture one software
    "help",   # listet parameter auf
    );
$bash_options = getopt($kurz_par, $lang_par);
#$_REQUEST['valid_only'] = "XXXX";

#if (isset($bash_options['a']))  $acronym = $bash_options['a'];
#if (isset($bash_options['i']))  $input = $bash_options['i'];
if (isset($bash_options['s']))  $session = $bash_options['s'];
#if (isset($bash_options['o']))  $output = $bash_options['o'];
#if (isset($bash_options['u']))  $url = $bash_options['u'];
#if (isset($bash_options['t']))  $tmp_dir_input = $bash_options['t'];

#if (isset($bash_options['acronym']))  $acronym = $bash_options['acronym'];
#if (isset($bash_options['input']))  $input = $bash_options['input'];
if (isset($bash_options['session']))  $session = $bash_options['session'];
#if (isset($bash_options['output']))  $output = $bash_options['output'];
#if (isset($bash_options['url']))  $url = $bash_options['url'];
#if (isset($bash_options['tmpdir']))  $tmp_dir_input = $bash_options['tmpdir'];

#var_dump($_REQUEST);
#if (isset($bash_options['r']) || isset($bash_options['rename']))  $rename == 'yes';

if (isset($bash_options['h']) || isset($bash_options['help']))
    {
    echo "\n### possible options:\n";
 #   echo "\t-a --acronym\therbarium acronym\n";
#    echo "\t-i --input\tinput folder (subdir of standard input dir with leading '+' (e.g. +/20211123_01/)\n";
 #   echo "\t-o --output\toutput folder\n";
    echo "\t-s --session\tsession name from capture one software\n";
 #   echo "\t-t --tmpdir\ttmp folder\n";
  #  echo "\t-r --rename\trename original file (not implemented yet!)\n";
 #   echo "\t-u --url\tURL prefix within QR-Code (e.g. https://wu.jacq.org/)\n";
    echo "\t-h --help\tpossible options\n";
    echo "\n";
    exit;
    }
#var_dump($bash_options);
# bash-param-ende .............. ####################################################################################################
#var_dump($input_base_dir);



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


$session = str_replace("/","",$session);

if ($session == '')
    {
    echo "\nNo valid session defind ...\nUse '-h' for help.\n\n";
    exit;
    }



    
if (!file_exists($output_dir2) && !is_dir($output_dir2))
    {
    echo "\nNo such output directory: ".$output_dir2."\n\n";
    echo "\nPlease check config file ...\n\n";
    exit;
    } 
        
if (!is_writable($output_dir2))
    {
    echo "\nNo writing permissions in output base directory: ".$output_dir2."\n\n";
    exit;
    } 

    
$copystatus = 0;
$copycount = 0;

$irt_query = "SELECT *,
                    YEAR(orig_time) AS year 
                FROM jacq_image_rename 
                WHERE new_filename IS NOT NULL
                    AND orig_path LIKE '%/".$session.$session_subfolder."%'
                    AND comment is NULL";
   # var_dump($irt_query);
$irt_result = mysqli_query($GLOBALS['dblink'], $irt_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
while ($irt_line = mysqli_fetch_array($irt_result, MYSQLI_ASSOC)) 
	{
	$outputdir = $output_dir2;
	$copystatus = 1;
    $path = $irt_line['new_path'];
    $filename = $irt_line['new_filename'];
    
            if ($irt_line['year'] != '' && $irt_line['year'] >= 2000 && $session != '')
            {
            $outputdir = $outputdir.$irt_line['year']."/";
            if (!file_exists($outputdir) && !is_dir($outputdir)) mkdir($outputdir);
            $outputdir = $outputdir.$session."/";
            if (!file_exists($outputdir) && !is_dir($outputdir)) mkdir($outputdir);
           }
        else if ($outputdir == '')
            {
            echo "\nOutput error.\n\n";
            exit;            
            }

        if (!file_exists($outputdir) && !is_dir($outputdir))
            {
            echo "\nNo such output directory: ".$outputdir."\n\n";
            exit;
            } 
            
        # datei umbenennen/kopieren
                    
        if (file_exists($outputdir.$filename))
            {
            echo "\e[0;31m\n File already exists: ".$outputdir.$filename."\e[0m\n\n";
            }
        else
            {
            $copy = copy($path.$filename,$outputdir.$filename);
            if ($copy != "1") 
                {
                echo "\e[0;31m\n File rename/copy ERROR: ".$copy."\e[0m\n\n";
                }
            else $copycount++;
            }
   

    
	}
mysqli_free_result($irt_result);


if ($copystatus == 0) echo "\e[0;31m\n No immages found for session ".$session."\e[0m\nPlease check session name or run jacq-image-rename first!\n\n";
else echo $copycount." files copied.";


?>

