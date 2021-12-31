<?php

### MySQL-Server ###
$dbhost = "";
$dbuser = "";
$dbpwd = "";
$dbname = "jacq_image_rename";


### Directorys (absolute path) ###
# location where all original images are usually stored (e.g. the folder where all capture one sessions are stored, or if you have different forders (for eg. type image and nomal specimens) use the main directory of this folders
$input_base_dir = "";

# locatioan where all new images should be stored (it should have write access)
$output_base_dir = "";

# tmp folder should have write access
$tmp_dir = "";


# qr code parameters
$url = "https://w.jacq.org/"; # base url of qr-codes e.g. "https://wu.jacq.org/"
$acronym = "W"; # herbarium acronym of qr-codes and output_images; e.g. "WU"


#output_format - possible options:
#   $output_format = "phaidra";  # eg. WU0000000.tif
#   $output_format = "traditional"; # eg. wu_0000000.tif
$output_format = "traditional";

?>

