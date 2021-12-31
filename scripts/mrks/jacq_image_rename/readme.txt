required packages (Debian) besides php and apache:
    - GraphicsMagick (because ImageMagick gives to many error messages)
    - zbar-tools

mariadb:
    - create table according to "jacq_image_rename.sql"

config-file:
    - rename "config.example.php" to "config.php"
    - fill in dbhost, dbuser, dbpasswd ...
    - fill in absolute folder paths for input, output and tmp directory (the last two should have write permissions)
    - the script only processes QR codes which have the pattern $url/$acronym0000000 (e.g. https://wu.jacq.org/WU0123456)
    - output format: "phaidra" will give e.g. WU0123456.tif and "traditional" wu_0123456.tif

adapt the absolute (!) path of the config file in "image_rename.php" 
    
create symlink to /usr/local/bin/:
    ln -s ./image_rename.php /usr/local/bin/jacq-image-rename
    
    
index.php
    - it also uses the config file (as relative path)
    - it and the coresponding files (config, css and inc) should be placed within a webserver directory
    
    
Usage:

e.g.: 
    - renames Files from Session 211231_01 ($input_base_dir/211231_01/Output/) into Output folder:
        jacq-image-rename -s 211231_01
        
    - if the sessions are stored in different subdirectories:
        
        jacq-image-rename -i +/regular/ -s 211231_01
        
        this will open the corresponding Session within the subdir "regular" ($input_base_dir/regular/211231_01/Output/)
    
    - if you use the parameter -i without "+" the given path will not use the var $input_base_dir
    
for further options see jacq-image-rename -h


    

    
    
