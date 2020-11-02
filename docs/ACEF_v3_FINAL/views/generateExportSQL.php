<?php

$familyNames = array(
    'i23' => 'Amaranthaceae',
    'i30' => 'Annonaceae',
    'i115' => 'Chenopodiaceae',
    'i161' => 'Degeneriaceae',
    'i182' => 'Ebenaceae',
    'i205' => 'Eupomatiaceae',
    'i249' => 'Himantandraceae',
    'i351' => 'Myristicaceae',
    'i421' => 'Portulacaceae',
);

header("Content-Type: text/plain");
echo generateExportMysql($familyNames);

function generateExportMysql($familyNames, $templatePath = null)
{
    if (!is_array($familyNames)) {
        $familyNames = array($familyNames);
    }

    if (empty($templatePath)) {
        $templatePath = './export_sp2000.template.sql';
    }

    if (!is_file($templatePath)) {
        return "Template not found";
    }

    $template = file_get_contents($templatePath);
    if (strlen($template) == 0) {
        return "Template empty";
    }

    $ret1 = '';
    $ret3 = '';
    $ret4 = '';
    $ret6 = '';
    foreach ($familyNames as $familyID => $familyName) {
        $ret1 .= "\n"
              . "\n"
              . "\n"
              . "# ===========================================\n"
              . "# Dataexport for: {$familyName}\n"
              . "# ===========================================\n"
              . "\n"
              . "\n"
              . str_replace("IFAMILYNAME", $familyName, $template);

        if (substr($familyID, 0, 1) == 'i') {
            $ret3 .= ",'" . substr($familyID, 1) . "'";
            $ret6 .= ",'" . substr($familyID, 1) . "'";
        } else {
            $ret4 .= ",'{$familyName}'";
            $ret6 .= ",'?'";
        }
    }

    if (strlen($ret3) > 0 || strlen($ret4) > 0) {
        $ret5 = "# ( ";
        if (strlen($ret3) > 0) {
            $ret5 .= " tg.familyID IN(" . substr($ret3, 1) . ")  ";
            if (strlen($ret4) > 0) {
                $ret5 .= " OR ";
            }
        }
        if (strlen($ret4) > 0) {
            $ret5 .= "  tf.family IN(" . substr($ret4, 1) . ") ";
        }
        $ret5 .= " )";
    } else {
        $ret5 = "";
    }

    $ret = "#<pre>\n"
         . "# ===========================================\n"
         . "# http://dev.mysql.com/doc/refman/5.0/en/stored-program-restrictions.html\n"
         . "# no stored procedure for this operation possible in mysql.\n"
         . "# => MYSQL Script to generate...\n"
         . "# ===========================================\n"
         . "#\n"
         . "# tg.familyID IN (" . substr($ret6, 1) . ") -- tf.family IN('" . implode("','", $familyNames) . "')\n"
         . "{$ret5}\n"
         . "#\n"
         . "\n"
         . "{$ret1}\n"
         . "\n"
         . "#</pre>";
    return $ret;
}
