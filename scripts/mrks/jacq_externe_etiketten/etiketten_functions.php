<?php

# normalize diffent input file structures to one standardisized output
# parameter outputtype optional; standard = 'label', which means all standardisized columns,
# a second value would be 'jacq_import' with the columns of the jacq-import template
function nomalize_input_data($data_arr, $outputtype = 'label')
    {
    $output_arr = array();

    #define jacq-(sub)collection arrays:
    # die daten sollten eigentlich über eine db-abfrage aus dem jacq geholt werden ...
    $subcoll_val_arr = array();
    $subcoll_val_arr["188"] = "ADMONT-Busenlechner";
    $subcoll_val_arr["190"] = "ADMONT-Hayek";
    $subcoll_val_arr["186"] = "ADMONT-Herbarium Universale";
    $subcoll_val_arr["191"] = "ADMONT-Italien";
    $subcoll_val_arr["189"] = "ADMONT-Kerner";
    $subcoll_val_arr["187"] = "ADMONT-Obersteiermark";
    $subcoll_val_arr["154"] = "B-Algae";
    $subcoll_val_arr["153"] = "B-Bridel";
    $subcoll_val_arr["152"] = "B-Bryophyta";
    $subcoll_val_arr["149"] = "B-Carpotheca";
    $subcoll_val_arr["150"] = "B-DNA Bank";
    $subcoll_val_arr["159"] = "B-Exhibitio";
    $subcoll_val_arr["162"] = "B-externa";
    $subcoll_val_arr["157"] = "B-Fungi";
    $subcoll_val_arr["160"] = "B-Hortus botanicus";
    $subcoll_val_arr["156"] = "B-Lichenes";
    $subcoll_val_arr["155"] = "B-Mixta";
    $subcoll_val_arr["127"] = "B-Phanerogamae";
    $subcoll_val_arr["151"] = "B-Pteridophyta";
    $subcoll_val_arr["161"] = "B-texta";
    $subcoll_val_arr["158"] = "B-Udi";
    $subcoll_val_arr["133"] = "B-Willdenow";
    $subcoll_val_arr["246"] = "B-Willing";
    $subcoll_val_arr["148"] = "B-Xylotheca";
    $subcoll_val_arr["134"] = "BAK";
    $subcoll_val_arr["248"] = "BATU";
    $subcoll_val_arr["239"] = "BOZ";
    $subcoll_val_arr["241"] = "BOZ-Algen";
    $subcoll_val_arr["243"] = "BOZ-Flechten";
    $subcoll_val_arr["245"] = "BOZ-Gefäßpflanzen";
    $subcoll_val_arr["240"] = "BOZ-Huter";
    $subcoll_val_arr["244"] = "BOZ-Moose";
    $subcoll_val_arr["242"] = "BOZ-Pilze";
    $subcoll_val_arr["194"] = "BPWW-FM";
    $subcoll_val_arr["207"] = "BPWW-GLE";
    $subcoll_val_arr["206"] = "BPWW-MON";
    $subcoll_val_arr["208"] = "BPWW-OLE";
    $subcoll_val_arr["205"] = "BPWW-TdA05";
    $subcoll_val_arr["204"] = "BPWW-TdA07";
    $subcoll_val_arr["203"] = "BPWW-TdA08";
    $subcoll_val_arr["202"] = "BPWW-TdA09";
    $subcoll_val_arr["201"] = "BPWW-TdA10";
    $subcoll_val_arr["200"] = "BPWW-TdA11";
    $subcoll_val_arr["199"] = "BPWW-TdA12";
    $subcoll_val_arr["198"] = "BPWW-TdA13";
    $subcoll_val_arr["197"] = "BPWW-TdA14";
    $subcoll_val_arr["196"] = "BPWW-TdA15";
    $subcoll_val_arr["195"] = "BPWW-TdA16";
    $subcoll_val_arr["210"] = "BPWW-WBLNOE";
    $subcoll_val_arr["209"] = "BPWW-WBLW";
    $subcoll_val_arr["128"] = "BRNU";
    $subcoll_val_arr["236"] = "BRNU-alg";
    $subcoll_val_arr["168"] = "BRNU-bryo";
    $subcoll_val_arr["167"] = "BRNU-lich";
    $subcoll_val_arr["166"] = "BRNU-myc";
    $subcoll_val_arr["234"] = "CBH";
    $subcoll_val_arr["141"] = "CHER";
    $subcoll_val_arr["215"] = "DR-Algae";
    $subcoll_val_arr["212"] = "DR-Bryophyta";
    $subcoll_val_arr["216"] = "DR-Fruits &amp; Seeds";
    $subcoll_val_arr["214"] = "DR-Fungi";
    $subcoll_val_arr["218"] = "DR-Herbarium Wolf";
    $subcoll_val_arr["213"] = "DR-Lichenes";
    $subcoll_val_arr["211"] = "DR-Phanerogamae &amp; Pteridophyta";
    $subcoll_val_arr["217"] = "DR-Wood samples";
    $subcoll_val_arr["144"] = "ERE-armenian Collection";
    $subcoll_val_arr["143"] = "ERE-general collection";
    $subcoll_val_arr["169"] = "FT";
    $subcoll_val_arr["135"] = "GAT";
    $subcoll_val_arr["17"] = "GJO";
    $subcoll_val_arr["230"] = "GJO-carpology";
    $subcoll_val_arr["229"] = "GJO-wood";
    $subcoll_val_arr["18"] = "GZU";
    $subcoll_val_arr["122"] = "GZU ethnobotanische Kollektion";
    $subcoll_val_arr["121"] = "GZU Holz-Kollektion";
    $subcoll_val_arr["120"] = "GZU karpologische Kollektion";
    $subcoll_val_arr["119"] = "GZU StudienHerbar";
    $subcoll_val_arr["40"] = "GZU-Arbesser";
    $subcoll_val_arr["94"] = "GZU-Brunner";
    $subcoll_val_arr["22"] = "GZU-Conrath";
    $subcoll_val_arr["41"] = "GZU-Czegka";
    $subcoll_val_arr["42"] = "GZU-Degener";
    $subcoll_val_arr["43"] = "GZU-Dolenz";
    $subcoll_val_arr["44"] = "GZU-Eberstaller";
    $subcoll_val_arr["23"] = "GZU-Ecklon &amp; Zeyher";
    $subcoll_val_arr["45"] = "GZU-Eggler";
    $subcoll_val_arr["46"] = "GZU-Ettingshausen";
    $subcoll_val_arr["47"] = "GZU-Evers";
    $subcoll_val_arr["48"] = "GZU-Fritsch";
    $subcoll_val_arr["49"] = "GZU-Hachtmann";
    $subcoll_val_arr["50"] = "GZU-Heider";
    $subcoll_val_arr["52"] = "GZU-Heske";
    $subcoll_val_arr["53"] = "GZU-Höpflinger";
    $subcoll_val_arr["54"] = "GZU-Hoppe";
    $subcoll_val_arr["55"] = "GZU-Huber &amp; Dietl";
    $subcoll_val_arr["56"] = "GZU-Husak";
    $subcoll_val_arr["57"] = "GZU-Karl";
    $subcoll_val_arr["58"] = "GZU-Kerner";
    $subcoll_val_arr["59"] = "GZU-Krašan";
    $subcoll_val_arr["60"] = "GZU-Lemperg";
    $subcoll_val_arr["61"] = "GZU-Maurer";
    $subcoll_val_arr["62"] = "GZU-Melzer";
    $subcoll_val_arr["63"] = "GZU-Mulley";
    $subcoll_val_arr["27"] = "GZU-Nees ab Esenbeck";
    $subcoll_val_arr["24"] = "GZU-Nemetz";
    $subcoll_val_arr["64"] = "GZU-Nevole";
    $subcoll_val_arr["65"] = "GZU-Palla";
    $subcoll_val_arr["66"] = "GZU-Pernhoffer";
    $subcoll_val_arr["67"] = "GZU-Petrak";
    $subcoll_val_arr["68"] = "GZU-Pilhatsch";
    $subcoll_val_arr["69"] = "GZU-Pittoni";
    $subcoll_val_arr["70"] = "GZU-Poelt";
    $subcoll_val_arr["71"] = "GZU-Rechinger";
    $subcoll_val_arr["72"] = "GZU-Rössler";
    $subcoll_val_arr["25"] = "GZU-Rosthorn";
    $subcoll_val_arr["73"] = "GZU-Salzmann";
    $subcoll_val_arr["74"] = "GZU-Schaeftlein";
    $subcoll_val_arr["26"] = "GZU-Schmarda";
    $subcoll_val_arr["93"] = "GZU-Schulz";
    $subcoll_val_arr["75"] = "GZU-Schwimmer";
    $subcoll_val_arr["76"] = "GZU-Starmühler";
    $subcoll_val_arr["77"] = "GZU-Stippl";
    $subcoll_val_arr["78"] = "GZU-Stolba";
    $subcoll_val_arr["79"] = "GZU-Teppner";
    $subcoll_val_arr["80"] = "GZU-Thwaites &amp; Wallich";
    $subcoll_val_arr["81"] = "GZU-Troyer";
    $subcoll_val_arr["82"] = "GZU-Untchj";
    $subcoll_val_arr["83"] = "GZU-Vončina";
    $subcoll_val_arr["87"] = "GZU-Widder";
    $subcoll_val_arr["84"] = "GZU-Wight";
    $subcoll_val_arr["85"] = "GZU-Witasek";
    $subcoll_val_arr["88"] = "GZU-Woynar";
    $subcoll_val_arr["86"] = "GZU-Zenker";
    $subcoll_val_arr["99"] = "HAL";
    $subcoll_val_arr["97"] = "Herb DrogMus AT";
    $subcoll_val_arr["232"] = "Herb Gutermann Walter";
    $subcoll_val_arr["98"] = "Herb Pilsl Peter";
    $subcoll_val_arr["233"] = "Herb Sauberer Norbert";
    $subcoll_val_arr["174"] = "Herb Willing";
    $subcoll_val_arr["136"] = "HERZ";
    $subcoll_val_arr["129"] = "HTunc";
    $subcoll_val_arr["89"] = "JE";
    $subcoll_val_arr["125"] = "KFTA";
    $subcoll_val_arr["231"] = "KIEL";
    $subcoll_val_arr["108"] = "KUFS";
    $subcoll_val_arr["116"] = "LAGU";
    $subcoll_val_arr["137"] = "LECB";
    $subcoll_val_arr["101"] = "LW";
    $subcoll_val_arr["131"] = "LWKS";
    $subcoll_val_arr["164"] = "LWKS-B";
    $subcoll_val_arr["100"] = "LWS";
    $subcoll_val_arr["130"] = "LZ";
    $subcoll_val_arr["163"] = "MHES";
    $subcoll_val_arr["110"] = "MJG";
    $subcoll_val_arr["171"] = "MJG-Fruchtsammlung";
    $subcoll_val_arr["117"] = "MJG-Fungi";
    $subcoll_val_arr["118"] = "MJG-Herbarium Garganicum";
    $subcoll_val_arr["172"] = "MJG-Lichenes";
    $subcoll_val_arr["111"] = "MJG-Rheinland Pfalz";
    $subcoll_val_arr["228"] = "NBSI";
    $subcoll_val_arr["146"] = "NS";
    $subcoll_val_arr["147"] = "NSK";
    $subcoll_val_arr["132"] = "OLD";
    $subcoll_val_arr["219"] = "PI";
    $subcoll_val_arr["221"] = "PI-ARC";
    $subcoll_val_arr["223"] = "PI-ART";
    $subcoll_val_arr["224"] = "PI-BOTT";
    $subcoll_val_arr["225"] = "PI-CAR";
    $subcoll_val_arr["226"] = "PI-CITT";
    $subcoll_val_arr["220"] = "PI-GUAD";
    $subcoll_val_arr["222"] = "PI-PASS";
    $subcoll_val_arr["227"] = "PI-PELL";
    $subcoll_val_arr["238"] = "PIAGR";
    $subcoll_val_arr["180"] = "PRC-Algae";
    $subcoll_val_arr["182"] = "PRC-Bryophyta";
    $subcoll_val_arr["181"] = "PRC-Carpotheca";
    $subcoll_val_arr["183"] = "PRC-Fungi";
    $subcoll_val_arr["184"] = "PRC-Lichenes";
    $subcoll_val_arr["142"] = "PRC-Phanerogamae";
    $subcoll_val_arr["177"] = "SARAT";
    $subcoll_val_arr["170"] = "TBI";
    $subcoll_val_arr["112"] = "TEST";
    $subcoll_val_arr["102"] = "TGU";
    $subcoll_val_arr["173"] = "TMRC";
    $subcoll_val_arr["235"] = "TUB";
    $subcoll_val_arr["165"] = "UBT";
    $subcoll_val_arr["247"] = "UPA";
    $subcoll_val_arr["19"] = "W";
    $subcoll_val_arr["179"] = "W Carpotheca";
    $subcoll_val_arr["193"] = "W Cecidiologica";
    $subcoll_val_arr["90"] = "W Krypto";
    $subcoll_val_arr["92"] = "W Krypto-Grunow";
    $subcoll_val_arr["123"] = "W Krypto-Zahlbruckner";
    $subcoll_val_arr["96"] = "W-Bauer";
    $subcoll_val_arr["91"] = "W-Boos";
    $subcoll_val_arr["39"] = "W-Buchenau";
    $subcoll_val_arr["32"] = "W-E. Khek";
    $subcoll_val_arr["31"] = "W-Endlicher";
    $subcoll_val_arr["124"] = "W-Erzherzog Rainer";
    $subcoll_val_arr["95"] = "W-F. Wimmer";
    $subcoll_val_arr["37"] = "W-Fenzl";
    $subcoll_val_arr["30"] = "W-Hackel";
    $subcoll_val_arr["106"] = "W-Herb.bras.";
    $subcoll_val_arr["33"] = "W-Hirth";
    $subcoll_val_arr["34"] = "W-Host";
    $subcoll_val_arr["28"] = "W-Jacq.";
    $subcoll_val_arr["29"] = "W-Jacq. fil.";
    $subcoll_val_arr["139"] = "W-Kucera";
    $subcoll_val_arr["38"] = "W-Neilreich";
    $subcoll_val_arr["192"] = "W-Pittoni";
    $subcoll_val_arr["36"] = "W-Portenschlag";
    $subcoll_val_arr["109"] = "W-Putterlick";
    $subcoll_val_arr["20"] = "W-Rchb.";
    $subcoll_val_arr["21"] = "W-Rchb.Orch.";
    $subcoll_val_arr["103"] = "W-Ronniger";
    $subcoll_val_arr["138"] = "W-Stella mat.";
    $subcoll_val_arr["107"] = "W-Trattinnick";
    $subcoll_val_arr["126"] = "W-Wołoszczak";
    $subcoll_val_arr["35"] = "W-Wulfen";
    $subcoll_val_arr["104"] = "W-ZooBot";
    $subcoll_val_arr["1"] = "WU";
    $subcoll_val_arr["10"] = "WU-Algae";
    $subcoll_val_arr["9"] = "WU-Bryophyta-Hepaticae";
    $subcoll_val_arr["8"] = "WU-Bryophyta-Musci";
    $subcoll_val_arr["14"] = "WU-Carpotheca";
    $subcoll_val_arr["113"] = "WU-Dörfler";
    $subcoll_val_arr["11"] = "WU-Fungi-Generale";
    $subcoll_val_arr["237"] = "WU-Grabherr";
    $subcoll_val_arr["2"] = "WU-Halácsy-Europ.";
    $subcoll_val_arr["3"] = "WU-Halácsy-Graec.";
    $subcoll_val_arr["115"] = "WU-HBV";
    $subcoll_val_arr["4"] = "WU-Keck";
    $subcoll_val_arr["5"] = "WU-Kerner";
    $subcoll_val_arr["13"] = "WU-Lichenes";
    $subcoll_val_arr["15"] = "WU-Liquor";
    $subcoll_val_arr["6"] = "WU-Melk";
    $subcoll_val_arr["145"] = "WU-microscopic slides";
    $subcoll_val_arr["12"] = "WU-Mykologicum";
    $subcoll_val_arr["178"] = "WU-Mykologicum-Mappen";
    $subcoll_val_arr["7"] = "WU-Pteridophyta";
    $subcoll_val_arr["140"] = "WU-Schönbeck";
    $subcoll_val_arr["175"] = "WU-Vogel";
    $subcoll_val_arr["114"] = "WU-Wendelberger";
    $subcoll_val_arr["176"] = "WU-Witasek";
    $subcoll_val_arr["16"] = "WU-Xylotheca";
    $subcoll_val_arr["185"] = "WUP";
    
    $subcoll_id_arr = array_flip($subcoll_val_arr); # flips value and key of array
    
    
    if (is_array($data_arr))
        {
        foreach ($data_arr as $data_line)
            {
            # number of identical labels is defined in column 'quantity' or 'anzahl'
            if (isset($data_line['quantity']) && $data_line['quantity'] >= 1) $quantity = $data_line['quantity'];
            elseif (isset($data_line['anzahl']) && $data_line['anzahl'] >= 1) $quantity = $data_line['anzahl'];
            else $quantity = 1;
            
            
            # herbarium number / barcode or similar (as defined in columns 'herbarium_no', 'HerbNummer', 'HerbariumNr_BarCode' 'W acqu'
            if (isset($data_line['herbarium_no'])) $herbarium_no = $data_line['herbarium_no'];
            elseif (isset($data_line['HerbNummer'])) $herbarium_no = $data_line['HerbNummer'];           
            elseif (isset($data_line['HerbariumNr_BarCode'])) $herbarium_no = $data_line['HerbariumNr_BarCode'];           
            elseif (isset($data_line['W acqu'])) $herbarium_no = $data_line['W acqu'];           
            else $herbarium_no = '';
            

            # collection number as given isn columns 'coll_no' 'coll_no2' 'Nummer' 'First_collectors_number' 'alt_number' or 'Alt_number'
            if (isset($data_line['coll_no'])) $coll_no = $data_line['coll_no'];
            elseif (isset($data_line['Nummer'])) $coll_no = $data_line['Nummer'];
            elseif (isset($data_line['First_collectors_number'])) $coll_no = $data_line['First_collectors_number'];
            else $coll_no = '';
            
            if (isset($data_line['coll_no2'])) $coll_no2 = $data_line['coll_no2'];
            elseif (isset($data_line['alt_number'])) $coll_no2 = $data_line['alt_number'];
            elseif (isset($data_line['Alt_number'])) $coll_no2 = $data_line['Alt_number'];
            else $coll_no2 = '';
            
            if ($coll_no == '0') $coll_no = '';
            if ($coll_no2 == '0') $coll_no2 = '';
            
            if ($coll_no == '' && $coll_no2 != '')  # if there is only one collection number in both fields, it should get assigned to 'coll_no'
                {
                $coll_no = $coll_no2;
                $coll_no2 = '';
                }
            
        
            # number of (nearly) identical labels, but different collection number as definded in the columns 'coll_no_range1' and 'coll_no_range2' or 'nummernzusatz',
            # but only if no collection number (collum 'coll_no', 'Nummer', 'First_collectors_number', 'coll_no2', 'alt_number' 
            # and 'Alt_number') and no herbarium number/barcode or similar is given for this data line
            $coll_no_range1 = '';
            $coll_no_range2 = '';
            
            if (isset($data_line['coll_no_range1'])) $coll_no_range1 = $data_line['coll_no_range1'];
            elseif (isset($data_line['nummernzusatz'])) $coll_no_range1 = $data_line['nummernzusatz'];
            else $coll_no_range1 = '';
            
            if (isset($data_line['coll_no_range2'])) $coll_no_range2 = $data_line['coll_no_range2'];
            else $coll_no_range2 = '';
           
            if ($herbarium_no == '' && $coll_no == '')
                {
                if (isset($data_line['coll_no_range1']) && isset($data_line['coll_no_range2']))
                    {
                    $coll_no_range1 = $data_line['coll_no_range1'];
                    $coll_no_range2 = $data_line['coll_no_range2'];
                    }
                elseif (isset($coll_no_range1))
                    {
                    $coll_no_range1 = str_replace('–','-',$coll_no_range1);
                    $coll_no_range1 = str_replace(' ','',$coll_no_range1);
                    if (preg_match("/[0-9]+\-[0-9]+/",$coll_no_range1)) #if string is a range of numbers
                        {
                        $coll_no_range_arr = explode('-',$coll_no_range1);
                        $coll_no_range1 = $coll_no_range_arr[0];
                        $coll_no_range2 = $coll_no_range_arr[1];
                        }
                    elseif (preg_match("/[0-9]/",$coll_no_range1) && $coll_no_range1 != '0') $coll_no = $coll_no_range1; #if string is a single number then set it as collection number
                    }
                    
                if ($coll_no_range1 == $coll_no_range2 && $coll_no_range1 != 0) #if both numbers are identical then set it as collection number
                    {
                    $coll_no = $coll_no_range1; 
                    $coll_no_range1 = '';
                    $coll_no_range2 = '';
                    }
                
                if ($coll_no_range1 > $coll_no_range2 && $coll_no_range2 == 0) #if second number is 0 then set the first number as collection number
                    {
                    $coll_no = $coll_no_range1;
                    $coll_no_range1 = '';
                    $coll_no_range2 = '';
                    }
                    
                if ($coll_no_range1 > $coll_no_range2 && $coll_no_range2 != 0) # if first number is larger than second, the values should be exchanged
                    {
                    list ($coll_no_range1, $coll_no_range2) = array($coll_no_range2, $coll_no_range1); # switches values of both variables
                    }
                }           

                
            # main label language
            if (isset($data_line['language'])) $language = $data_line['language'];
            else $language = '';
            
            # institution_code
            if (isset($data_line['institution_code'])) $institution_code = $data_line['institution_code'];
            elseif (isset($data_line['Institution_Code'])) $institution_code = $data_line['Institution_Code'];
            else $institution_code = '';

            # subcollection_number
            if (isset($data_line['subcollection_number'])) $subcollection_number = $data_line['subcollection_number'];
            elseif (isset($data_line['CollNummer'])) $subcollection_number = $data_line['CollNummer'];
            elseif (isset($data_line['Collection_Number'])) $subcollection_number = $data_line['Collection_Number'];
            else $subcollection_number = '';

            
            # institution subcollection - eiher as nummeric ID or string value; will be transformed to both variants (nummeric ID and string value)
            if (isset($data_line['institution_subcollection'])) $institution_subcollection = $data_line['institution_subcollection'];
            elseif (isset($data_line['collectionID'])) $institution_subcollection = $data_line['collectionID'];
            else $institution_subcollection = '';

            $institution_subcollection_val = '';
            $institution_subcollection_id = '';
            $institution_subcollection_error = '';
            if (preg_match("/^[0-9]$/",$institution_subcollection)) #if subcollection has nummeric value
                {
                if (isset($subcoll_val_arr[$institution_subcollection])) $institution_subcollection_val = $subcoll_val_arr[$institution_subcollection];
                else 
                    {
                    $institution_subcollection_val = '###';
                    $institution_subcollection_error = "Error assigning Subcollection name for ID \"".$institution_subcollection."\"";
                    }
                $institution_subcollection_id = $institution_subcollection;
                }
            elseif ($institution_subcollection != '') #if subcollection has string value
                {
                $institution_subcollection_val = $institution_subcollection;
                if (isset($subcoll_id_arr[$institution_subcollection])) $institution_subcollection_id = $subcoll_id_arr[$institution_subcollection];
                else $institution_subcollection_error = "Error assigning Subcollection ID for \"".$institution_subcollection."\"";
                }
            

            if ($institution_code == '' && $institution_subcollection_val != '') # if instiution code is missing
                {
                $institution_code = preg_replace('/-.*/','',$institution_subcollection_val); # replaces the "-" and everything after with nothing
                }
            
            
            
            # taxonomic status ('status' column); normalizing different (typographic) variants
            $status = '';
            $status_jacq = ''; # status for jacq-import output
            if (isset($data_line['status'])) 
                {
                $status = ($data_line['status']);
                if ($status == 'cf') $status = 'cf.';
                if (str_replace(' ','_',str_replace('.','',$status)) == 'cf_spec.') $status = 'cf_spec';
                if (str_replace(' ','_',str_replace('.','',$status)) == 'cf_infra_spec') $status = 'cf_infra_spec';
                if ($status == 'agg') $status = 'agg.';
                if ($status == 'subagg') $status = 'agg.';
                if ($status == 'aff') $status = 'aff.';
                if ($status == 'fem') $status = '♀';
                if ($status == 'fem.') $status = '♀';
                if ($status == 'masc') $status = '♂';
                if ($status == 'masc.') $status = '♂';
                if ($status == 's. l.') $status = 's. lat.';
                if ($status == 's.l.') $status = 's. lat.';
                if ($status == 's.lat.') $status = 's. lat.';
                if ($status == 's.str.') $status = 's. str.';
                if ($status == 's.strictiss.') $status = 's. strictiss.';
                
                if ($status == 's. lat.') $status_jacq = 's.l.';
                elseif ($status == 's. str.') $status_jacq = 's.str.';
                elseif ($status == 's. strictiss.') $status_jacq = 's.strictiss.';
                elseif ($status == 'cf_spec') $status_jacq = 'cf.';
                elseif ($status == 'cf_infra_spec') $status_jacq = 'cf.';
                elseif ($status == '♀') $status_jacq = 'fem.';
                elseif ($status == '♂') $status_jacq = 'masc.';
                else $status_jacq = $status; 
               }
            
            /* jacq-status possibilities:
                <option selected></option>
                <option value="1">aff.</option>
                <option value="2">agg.</option>
                <option value="3">cf.</option>
                <option value="13">f. nov.</option>
                <option value="10">fem.</option>
                <option value="12">group</option>
                <option value="4">ined.</option>
                <option value="5">masc.</option>
                <option value="6">s.l.</option>
                <option value="7">s.str.</option>
                <option value="15">s.strictiss.</option>
                <option value="8">sp. nov.</option>
                <option value="14">subsp. nov.</option>
                <option value="11">var. nov.</option>
                <option value="9">x</option> */
                
                
            # taxon
            if (isset($data_line['taxon'])) $taxon = $data_line['taxon'];
            elseif(isset($data_line['Taxon'])) $taxon = $data_line['Taxon'];
            else $taxon = '';
            
            # family
            if (isset($data_line['family'])) $family = $data_line['family'];
            elseif (isset($data_line['Family'])) $family = $data_line['Family'];
            else $family = '';
            
            # genus
            if (isset($data_line['genus'])) $genus = $data_line['genus'];
            elseif (isset($data_line['Genus'])) $genus = $data_line['Genus'];
            else $genus = '';
            
            # species
            if (isset($data_line['species'])) $species = $data_line['species'];
            elseif (isset($data_line['Species'])) $species = $data_line['Species'];
            else $species = '';
            
            # author
            if (isset($data_line['author'])) $author = $data_line['author'];
            else $author = '';

            # rank
            if (isset($data_line['rank'])) $rank = $data_line['rank'];
            elseif (isset($data_line['ssp var f'])) $rank = $data_line['ssp var f'];
            else $rank = '';
            
            # infra_spec
            if (isset($data_line['infra_spec'])) $infra_spec = $data_line['infra_spec'];
            elseif (isset($data_line['Infra_spec'])) $infra_spec = $data_line['Infra_spec'];
            elseif (isset($data_line['subspecies'])) $infra_spec = $data_line['subspecies'];
            else $infra_spec = '';

            # infra_author
            if (isset($data_line['infra_author'])) $infra_author = $data_line['infra_author'];
            elseif (isset($data_line['Infra_author'])) $infra_author= $data_line['Infra_author'];
            else $infra_author = '';
            
            #prepearing taxon name for output
            $taxon_arr = '';
            $taxon_html = '';
            $taxon_jacq = $taxon;
            if ($taxon != '') 
                {
                if ($status == 'agg.' || $status == 'subagg.' || $status == 'group') $taxon .= " agg.";
                    
                if ($status == 'aff.' || $status == 'cf.') $taxon = $status." ".$taxon;
                
                if ($status == 'cf_spec') $taxon = preg_replace('/ /', ' cf. ', $taxon, 1); # replaces first blank with " cf. "
                
                if ($status == 'cf_infra_spec') # inserts cf. before subsp./var./f./...
                    {
                    $taxon = preg_replace('/ ssp. /', ' cf. subsp. ', $taxon, 1);
                    $taxon = preg_replace('/ subsp. /', ' cf. subsp. ', $taxon, 1);
                    $taxon = preg_replace('/ var. /', ' cf. var. ', $taxon, 1);
                    $taxon = preg_replace('/ subvar. /', ' cf. subvar. ', $taxon, 1);
                    $taxon = preg_replace('/ f. /', ' cf. f. ', $taxon, 1);
                    $taxon = preg_replace('/ subf. /', ' cf. subf. ', $taxon, 1);
                    }
                   
                $taxon_arr = namensaufspaltung_hybrid($taxon); # the taxon name ist now splitted into its parts and rebuilt (e.g. in hml format)
                $taxon_html = $taxon_arr['html']; # html output of the taxon name
                 
                #workarround for missing agg. in html output
                if (($status == 'agg.' || $status == 'subagg.' || $status == 'group') && !preg_match('/agg\./',$taxon_html))
                    {
                    #$taxon_html .= " <b>agg.</b>";
                    $taxon_html = "<b><i>".$taxon_arr['fam_gatt']." ".$taxon_arr['art']."</i></b> agg.";
                    }
                 
                $taxon_html = str_replace(' agg.',' <b>agg.</b>',$taxon_html); # agg. output shold be bold
                
                if ($status == 'group' || $status == 'subagg.') #the 'namensaufspaltung_hybrid' function cannot handle 'group' or 'subagg' yet, this is why 'agg.' is now replaced by 'group' or 'subagg.'
                    {
                    $taxon = str_replace("agg.", $status, $taxon);
                    $taxon_html = str_replace("agg.", $status, $taxon_html);
                    }
                
                # add status after taxon
                if ($status == 'f. nov.' ||
                    $status == 'ined.' ||
                    $status == 'sp. nov.' ||
                    $status == 'subsp. nov.' ||
                    $status == 'var. nov.' ||
                    $status == 's. strictiss.' || 
                    $status == 's. str.' || 
                    $status == 's. lat.' || 
                    $status == '♂' || 
                    $status == '♀') 
                        {
                        $taxon_html .= " <b>".$status."</b>";
                        $taxon .= " ".$status;
                        }
               }
            elseif ($genus != '') # if column 
                {
                $taxon = $genus;
                $taxon_jacq = $taxon;
                if ($status == 'aff.' || $status == 'cf.') $taxon = $status." ".$taxon;  
                if ($status == 'cf_spec') $taxon .= " ".' cf.';  
                
                if ($species != '')
                    {
                    $taxon .= " ".$species;
                    $taxon_jacq .= " ".$species;
                    if ($author != '') 
                        { 
                        $taxon .= " ".$author;
                        $taxon_jacq .= " ".$author;
                        }
                    if ($rank != '')
                        {
                        if ($status == "cf_infra_spec") $taxon .= " ".' cf.';  
                        $taxon .= " ".$rank." ".$infra_spec;
                        $taxon_jacq .= " ".$rank." ".$infra_spec;
                        if ($infra_author != '' && $infra_spec != $species)
                            {
                            $taxon .= " ".$infra_author;
                            $taxon_jacq .= " ".$infra_author;
                            }
                        }
                    }
                    
                $taxon_arr = namensaufspaltung_hybrid($taxon); # the taxon name ist now splitted into its parts and rebuilt (e.g. in hml format)
                $taxon_html = $taxon_arr['html']; # html output of the taxon name
            
                # add status after taxon
                if ($status == 'f. nov.' ||
                    $status == 'ined.' ||
                    $status == 'sp. nov.' ||
                    $status == 'subsp. nov.' ||
                    $status == 'var. nov.' ||
                    $status == 's. strictiss.' || 
                    $status == 's. str.' || 
                    $status == 's. lat.' || 
                    $status == '♂' || 
                    $status == '♀') 
                        {
                        $taxon_html .= " <b>".$status."</b>";
                        $taxon .= " ".$status;
                        }
                }
            elseif ($family != '') # if no taxon, but family is given use family as taxon name
                {
                $taxon = $family;
                $taxon_jacq = $taxon;
                if ($status == 'aff.' || $status == 'cf.') $taxon = $tatus." ".$taxon;                
                }
                
            
            #garden no
            if (isset($data_line['garden_no'])) $garden_no = $data_line['garden_no'];
            elseif(isset($data_line['Garden'])) $garden_no = $data_line['Garden'];
            else $garden_no = '';
            
            
            
            #voucher
            if (isset($data_line['voucher'])) $voucher = $data_line['voucher'];
            else $voucher = '';
            
            
            
            #collectors
            $add_collectors = '';
            if (isset($data_line['collectors'])) $collectors = $data_line['collectors'];
            elseif(isset($data_line['Sammler'])) $collectors = $data_line['Sammler'];
            elseif(isset($data_line['First_collector']) || isset($data_line['first collector']))
                {
                if (isset($data_line['First_collector'])) $collectors = $data_line['First_collector'];
                else $collectors = $data_line['first collector'];
                
                
                if (isset($data_line['add_collectors'])) $add_collectors = $data_line['add_collectors'];
                elseif(isset($data_line['Add_collectors'])) $add_collectors = $data_line['Add_collectors'];
                elseif(isset($data_line['add collectors'])) $add_collectors = $data_line['add collectors'];
                else $add_collectors = '';
                
                if ($add_collectors != '')
                    {
                    if (preg_match("/&/",$add_collectors)) $collectors .= ", ".$add_collectors;
                    else $collectors .= " & ".$add_collectors;
                    }

                }
            else $collectors = '';
            
            
            
            #hybrid
            if (isset($data_line['hybrid'])) $hybrid = $data_line['hybrid'];
            elseif(isset($data_line['hybride'])) $hybrid = $data_line['hybride'];
            else $hybrid = '';
            
            

            # series
            if (isset($data_line['series'])) $series = $data_line['series'];
            elseif(isset($data_line['Series'])) $series = $data_line['Series'];
            else $series = '';
            
            
            
            # series_number
            if (isset($data_line['series_number'])) $series_number = $data_line['series_number'];
            elseif(isset($data_line['Series_number'])) $series_number = $data_line['Series_number'];
            else $series_number = '';
            
            
            # date1
            if (isset($data_line['date1'])) $date1 = $data_line['date1'];
            elseif(isset($data_line['Datum'])) $date1 = $data_line['Datum'];
            elseif(isset($data_line['Coll_Date'])) $date1 = $data_line['Coll_Date'];
            elseif(isset($data_line['datum_von'])) $date1 = $data_line['datum_von'];
            else $date1 = '';
            
            if (is_numeric($date1) && $date1 > 2099) #if date has nummeric value and not a year => convert date from integer to YYYY-MM-DD
                {
                $date1_tmp = ($date1 - 25569) * 86400; # convert to unix-date
                $date1 = gmdate("Y-m-d", $date1_tmp); # convert to YYYY-MM-DD
                }
          
            
            
            # date2
            if (isset($data_line['date2'])) $date2 = $data_line['date2'];
            elseif(isset($data_line['Datum2'])) $date2 = $data_line['Datum2'];
            elseif(isset($data_line['Coll_Date_2'])) $date2 = $data_line['Coll_Date_2'];
            elseif(isset($data_line['datum_bis'])) $date2 = $data_line['datum_bis'];
            else $date2 = '';
            
            if (is_numeric($date2) && $date2 > 2099) #if date has nummeric value and not a year => convert date from integer to YYYY-MM-DD
                {
                $date2_tmp = ($date2 - 25569) * 86400; # convert to unix-date
                $date2 = gmdate("Y-m-d", $date2_tmp); # convert to YYYY-MM-DD
                }
            
            
            
            # det
            if (isset($data_line['det'])) $det = $data_line['det'];
            elseif(isset($data_line['det_rev_conf'])) $det = $data_line['det_rev_conf'];
            elseif(isset($data_line['detrevconf'])) $det = $data_line['detrevconf'];
            else $det = '';
            
            
            
            # typified
            if (isset($data_line['typified'])) $typified = $data_line['typified'];
            elseif(isset($data_line['Typified_by'])) $typified = $data_line['Typified_by'];
            else $typified = '';

            
            
            # type_information
            if (isset($data_line['type_information'])) $type_information = $data_line['type_information'];
            elseif(isset($data_line['Typus'])) $type_information = $data_line['Typus'];
            elseif(isset($data_line['typus'])) $type_information = $data_line['typus'];
            elseif(isset($data_line['Type_information'])) $type_information = $data_line['Type_information'];
            else $type_information = '';
            
            

            # ident_history
            if (isset($data_line['ident_history'])) $ident_history= $data_line['ident_history'];
            elseif(isset($data_line['taxon_alt'])) $ident_history = $data_line['taxon_alt'];
            elseif(isset($data_line['history'])) $ident_history = $data_line['history'];
            else $ident_history = '';
            
            
            
            # country
            if (isset($data_line['country'])) $country = $data_line['country'];
            elseif(isset($data_line['nation_engl'])) $country = $data_line['nation_engl'];
            elseif(isset($data_line['Country'])) $country = $data_line['Country'];
            else $country = '';

            
            
            # province
            if (isset($data_line['province'])) $province = $data_line['province'];
            elseif(isset($data_line['provinz'])) $province = $data_line['provinz'];
            elseif(isset($data_line['Province'])) $province = $data_line['Province'];
            else $province = '';

            
            
            # locality
            if (isset($data_line['locality'])) $locality = $data_line['locality'];
            elseif(isset($data_line['Fundort'])) $locality = $data_line['Fundort'];
            elseif(isset($data_line['Location'])) $locality = $data_line['Location'];
            else $locality = '';
            
            
            
            # locality_en
            if (isset($data_line['locality_en'])) $locality_en = $data_line['locality_en'];
            elseif(isset($data_line['Fundort_engl'])) $locality_en = $data_line['Fundort_engl'];
            else $locality_en = '';
             
            
            
            # finding_place_no
            if (isset($data_line['finding_place_no'])) $finding_place_no = $data_line['finding_place_no'];
            elseif(isset($data_line['fundort nummer'])) $finding_place_no = $data_line['fundort nummer'];
            else $finding_place_no = '';
           
            
            
            # habitat
            if (isset($data_line['habitat'])) $habitat = $data_line['habitat'];
            elseif(isset($data_line['Habitat'])) $habitat = $data_line['Habitat'];
            else $habitat = '';
            
            
            
            # habitus
            if (isset($data_line['habitus'])) $habitus = $data_line['habitus'];
            elseif(isset($data_line['Habitus'])) $habitus = $data_line['Habitus'];
            else $habitus = '';
            
            
            
            # annotations
            if (isset($data_line['annotations'])) $annotations = $data_line['annotations'];
            elseif(isset($data_line['Bemerkungen'])) $annotations = $data_line['Bemerkungen'];
            else $annotations = '';
            
            
            
            # coordinates
            if (isset($data_line['coordinates'])) $coordinates = $data_line['coordinates'];
            else $coordinates = '';
           

            # lat
            if (isset($data_line['lat'])) $lat = $data_line['lat'];
            elseif(isset($data_line['Latitude'])) $lat = $data_line['Latitude'];
            else $lat = '';
            

            # long
            if (isset($data_line['long'])) $long = $data_line['long'];
            elseif(isset($data_line['Longitude'])) $long = $data_line['Longitude'];
            else $long = '';
          
           
            # lat_card
            if (isset($data_line['lat_card'])) $lat_card = $data_line['lat_card'];
            elseif(isset($data_line['coord_NS'])) $lat_card = $data_line['coord_NS'];
            elseif(isset($data_line['Lat_Hemisphere'])) $lat_card = $data_line['Lat_Hemisphere'];
            elseif(isset($data_line['N-S'])) $lat_card = $data_line['N-S'];
            else $lat_card = '';
          
            
            
            # lat_deg
            if (isset($data_line['lat_deg'])) $lat_deg = $data_line['lat_deg'];
            elseif(isset($data_line['lat_degree'])) $lat_deg = $data_line['lat_degree'];
            elseif(isset($data_line['Lat_degree'])) $lat_deg = $data_line['Lat_degree'];
            elseif(isset($data_line['g-la'])) $lat_deg = $data_line['g-la'];
            else $lat_deg = '';
            
            
            # lat_min
            if (isset($data_line['lat_min'])) $lat_min = $data_line['lat_min'];
            elseif(isset($data_line['lat_minute'])) $lat_min = $data_line['lat_minute'];
            elseif(isset($data_line['Lat_minute'])) $lat_min = $data_line['Lat_minute'];
            elseif(isset($data_line['m-la'])) $lat_min = $data_line['m-la'];
            else $lat_min = '';
            
            
            # lat_sec
            if (isset($data_line['lat_sec'])) $lat_sec = $data_line['lat_sec'];
            elseif(isset($data_line['lat_second'])) $lat_sec = $data_line['lat_second'];
            elseif(isset($data_line['Lat_second'])) $lat_sec = $data_line['Lat_second'];
            elseif(isset($data_line['s-la'])) $lat_sec = $data_line['s-la'];
            else $lat_sec = '';
            
            
            # long_card
            if (isset($data_line['long_card'])) $long_card = $data_line['long_card'];
            elseif(isset($data_line['coord_WE'])) $long_card = $data_line['coord_WE'];
            elseif(isset($data_line['Long_Hemisphere'])) $long_card = $data_line['Long_Hemisphere'];
            elseif(isset($data_line['W-E'])) $long_card = $data_line['W-E'];
            else $long_card = '';
           
           if ($long_card == 'O') $long_card = 'E';
            
            
            # long_deg
            if (isset($data_line['long_deg'])) $long_deg = $data_line['long_deg'];
            elseif(isset($data_line['long_degree'])) $long_deg = $data_line['long_degree'];
            elseif(isset($data_line['Long_degree'])) $long_deg = $data_line['Long_degree'];
            elseif(isset($data_line['g-lo'])) $long_deg = $data_line['g-lo'];
            else $long_deg = '';
            
            
            
            # long_min
            if (isset($data_line['long_min'])) $long_min = $data_line['long_min'];
            elseif(isset($data_line['long_minute'])) $long_min = $data_line['long_minute'];
            elseif(isset($data_line['Long_minute'])) $long_min = $data_line['Long_minute'];
            elseif(isset($data_line['m-lo'])) $long_min = $data_line['m-lo'];
            else $long_min = '';

            
            
            # long_sec
            if (isset($data_line['long_sec'])) $long_sec = $data_line['long_sec'];
            elseif(isset($data_line['long_second'])) $long_sec = $data_line['long_second'];
            elseif(isset($data_line['Long_second'])) $long_sec = $data_line['Long_second'];
            elseif(isset($data_line['s-lo'])) $long_sec = $data_line['s-lo'];
            else $long_sec = '';
            
            
            
            #exactness (of geographical coordinates)
            if (isset($data_line['exactness'])) $exactness = $data_line['exactness'];
            else $exactness = '';
            
            
            # quadrant
            $quadrant = '';
            $quadrant_error = '';
            $gf = '';
            $qu = '';
            if (isset($data_line['quadrant']) && preg_match("/^((([0-1]{1}[0-9]{2}){2})|([0-9]{2}){2})\/[1-4]{1}$/",$data_line['quadrant'])) # only quadrants having a ground field of 4 or 6 digits 
                {
                $quadrant = $data_line['quadrant']; # full quadrant no
                $qu_arr = explode('/',$quadrant);
                $gf = $qu_arr[0]; # ground field
                $qu = $qu_arr[1]; # quadrant
                }
            else
                {
                # ground field ('gf')
                if (isset($data_line['gf'])) $gf = $data_line['gf'];
                elseif(isset($data_line['quadrant'])) $gf = $data_line['quadrant'];
                elseif(isset($data_line['Quadrant'])) $gf = $data_line['Quadrant'];
                # quadrant ('qu')
                if (isset($data_line['qu'])) $qu = $data_line['qu'];
                elseif(isset($data_line['quadrant_sub'])) $qu = $data_line['quadrant_sub'];
                elseif(isset($data_line['Quadrant_sub'])) $qu = $data_line['Quadrant_sub'];
                
                if ($gf != '' && $qu != '')
                    {
                    $quadrant = $gf."/".$qu;
                    if (!preg_match("/^((([0-1]{1}[0-9]{2}){2})|([0-9]{2}){2})\/[1-4]{1}$/",$quadrant)) # check if quadrant has the correct syntax
                        {
                        $quadrant_error = "Quadrant \"".$quadrant."\" is not valid!";
                        $quadrant = '';
                        }
                    }
                }

            $lat = str_replace("°","",$lat);
            $lat = str_replace(" ","",$lat);
            $lat = str_replace(".",",",$lat);
            
            $long = str_replace("°","",$long);
            $long = str_replace(" ","",$long);
            $long = str_replace(".",",",$long);
                
                
            
            # completion of emty coordinate or quadrant field ...
            $coordinates_dms = ''; #standardisized coodinates in deg-min-sec-format
            $coord_arr = array(); # array will be used for standardisized output of coordinat formats
            if ($coordinates != '') $coord_arr = gps_gesamt_ausgabe2($coordinates);
            elseif ($lat != '' && $long != '') $coord_arr = gps_gesamt_ausgabe2($lat.", ".$long);
            elseif ($lat_card != '' &&
                    $lat_deg != '' &&
                    $lat_min != '' &&
                    $lat_sec != '' &&
                    $long_card != '' &&
                    $long_deg != '' &&
                    $long_min != '' &&
                    $long_sec != '') $coord_arr = gps_gesamt_ausgabe2($lat_card." ".$lat_deg."°".$lat_min."'".$lat_sec."''".$long_card." ".$long_deg."°".$long_min."'".$long_sec."''");
            
            
            if (isset($coord_arr['gradminsec1'])) $coordinates_dms = $coord_arr['gradminsec1'];
            
            if ($long == '' && $lat == '') # if decimal coordinates are missing
                {
                if (isset($coord_arr['dezimalgrad_breite'])) $lat = $coord_arr['dezimalgrad_breite'];
                if (isset($coord_arr['dezimalgrad_laenge'])) $long = $coord_arr['dezimalgrad_laenge'];
                }
             
             if ($lat_card == '' &&
                    $lat_deg == '' &&
                    $lat_min == '' &&
                    $lat_sec == '' &&
                    $long_card == '' &&
                    $long_deg == '' &&
                    $long_min == '' &&
                    $long_sec == '')
                {
                $coord_arr2 = array();
                if ($coordinates_dms != '') $coord_arr2 = gps_trennung[$coordinates_dms];
                if (isset($coord_arr2['ausr_breite'])) $lat_card = $coord_arr2['ausr_breite'];
                if (isset($coord_arr2['grad_breite'])) $lat_deg = $coord_arr2['grad_breite'];
                if (isset($coord_arr2['min_breite'])) $lat_min = $coord_arr2['min_breite'];
                if (isset($coord_arr2['sec_breite_komma1'])) $lat_sec = $coord_arr2['sec_breite_komma1'];
                if (isset($coord_arr2['ausr_laenge'])) $long_card = $coord_arr2['ausr_laenge'];
                if (isset($coord_arr2['grad_laenge'])) $long_deg = $coord_arr2['grad_laenge'];
                if (isset($coord_arr2['min_laenge'])) $long_min = $coord_arr2['min_laenge'];
                if (isset($coord_arr2['sec_laenge_komma1'])) $long_sec = $coord_arr2['sec_laenge_komma1'];
                }
            
            
            
            #alt_approx (should have value '1' if given altitue is only approximately)
            if (isset($data_line['alt_approx'])) $alt_approx = $data_line['exactness'];
            else $alt_approx = '';
           
           
            # alt_min
            if (isset($data_line['alt_min'])) $alt_min = $data_line['alt_min'];
            elseif(isset($data_line['Altitude_lower'])) $alt_min = $data_line['Altitude_lower'];
            elseif(isset($data_line['Höhe von'])) $alt_min = $data_line['Höhe von'];
            else $alt_min = '';
           
           
           
             # alt_max
            if (isset($data_line['alt_max'])) $alt_max = $data_line['alt_max'];
            elseif(isset($data_line['Altitude_higher'])) $alt_max = $data_line['Altitude_higher'];
            elseif(isset($data_line['Höhe bis'])) $alt_max = $data_line['Höhe bis'];
            else $alt_max = '';
          
           
            # digital_image
            if (isset($data_line['digital_image'])) $digital_image = $data_line['digital_image'];
            elseif(isset($data_line['dig_image'])) $digital_image = $data_line['dig_image'];
            else $digital_image = '';
          
           
           
            # digital_image_obs
            if (isset($data_line['digital_image_obs'])) $digital_image_obs = $data_line['digital_image_obs'];
            elseif(isset($data_line['dig_img_obs'])) $digital_image_obs = $data_line['dig_img_obs'];
            else $digital_image_obs = '';
          
           

           # observation
            if (isset($data_line['observation'])) $observation = $data_line['observation'];
            else $observation = '';

            
            
            # checked
            if (isset($data_line['checked'])) $checked = $data_line['checked'];
            else $checked = '';
          
                     
           
            # accessible
            if (isset($data_line['accessible'])) $accessible = $data_line['accessible'];
            else $accessible = '';

            
            
            for ($nn = ($coll_no_range1 == '' ? 1 : $coll_no_range1) ; $nn <= ($coll_no_range2 == '' ? 1 : $coll_no_range2); $nn++) # if coll_nr_range1 and coll_nr_range2 not set, then just one iteration
                {
                for ($qq = 1; $qq <= $quantity; $qq++) # multiplicates data as definded by quantity (for identical labels)
                    {
                    if ($coll_no_range1 != '') $coll_no = $nn; # if coll_no_range is set => assign the resulting collection number to each of the labels
                    
                    $output_arr[] = array(
                        "quantity" => $quantity ,
                        "language" => $language ,
                        "coll_no_range1" => $coll_no_range1 ,
                        "coll_no_range2" => $coll_no_range2 ,
                        "herbarium_no" => $herbarium_no ,
                        "institution_code" => $institution_code ,
                        "institution_subcollection" => $institution_subcollection ,
                        "subcollection_number" => $subcollection_number ,
                        "institution_subcollection_val" => $institution_subcollection_val ,
                        "institution_subcollection_id" => $institution_subcollection_id ,
                        "institution_subcollection_error" => $institution_subcollection_error ,
                        "status" => $status ,
                        "taxon" => $taxon ,
                        "taxon_html" => $taxon_html ,
                        "taxon_jacq" => $taxon_jacq ,
                        "family" => $family ,
                        "genus" => $genus ,
                        "species" => $species ,
                        "author" => $author ,
                        "rank" => $rank ,
                        "infra_spec" => $infra_spec ,
                        "infra_author" => $infra_author ,
                        "garden_no" => $garden_no ,
                        "voucher" => $voucher ,
                        "collectors" => $collectors ,
                        "add_collectors" => $add_collectors ,
                        "hybrid" => $hybrid ,
                        "series" => $series ,
                        "series_number" => $series_number ,
                        "coll_no" => $coll_no ,
                        "coll_no2" => $coll_no2 ,
                        "date1" => $date1 ,
                        "date2" => $date2 ,
                        "det" => $det ,
                        "typified" => $typified ,
                        "type_information" => $type_information ,
                        "ident_history" => $ident_history ,
                        "country" => $country ,
                        "province" => $province ,
                        "locality" => $locality ,
                        "locality_en" => $locality_en ,
                        "finding_place_no" => $finding_place_no ,
                        "habitat" => $habitat ,
                        "habitus" => $habitus ,
                        "annotations" => $annotations ,
                        "coordinates" => $coordinates ,
                        "lat" => $lat ,
                        "long" => $long ,
                        "lat_card" => $lat_card ,
                        "lat_deg" => $lat_deg ,
                        "lat_min" => $lat_min ,
                        "lat_sec" => $lat_sec ,
                        "long_card" => $long_card ,
                        "long_deg" => $long_deg ,
                        "long_min" => $long_min ,
                        "long_sec" => $long_sec ,
                        "coordinates_dms" => $coordinates_dms ,
                        "quadrant_error" => $quadrant_error ,
                        "quadrant" => $quadrant ,
                        "gf" => $gf ,
                        "qu" => $qu ,
                        "exactness" => $exactness ,
                        "alt_approx" => $alt_approx ,
                        "alt_min" => $alt_min ,
                        "alt_max" => $alt_max ,
                        "digital_image" => $digital_image ,
                        "digital_image_obs" => $digital_image_obs ,
                        "observation" => $observation ,
                        "checked" => $checked ,
                        "accessible" => $accessible);
                    
                    
                    } # end of data multipication (quantity)
                } # end of data multipliction (coll_nr_range)
            } # end of foreach (data_array)
        }
    return $output_arr;
    }


?>
