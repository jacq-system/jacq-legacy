<?php
require_once __DIR__ . '/vendor/autoload.php';

// get all parameters
$filterId      = intval(filter_input(INPUT_GET, 'filterId', FILTER_SANITIZE_NUMBER_INT));
$referenceId   = intval(filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT));
$referenceType = filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING);

$insertSeries  = intval(filter_input(INPUT_GET, 'insertSeries', FILTER_SANITIZE_NUMBER_INT));
$editSeries    = intval(filter_input(INPUT_GET, 'editSeries', FILTER_SANITIZE_NUMBER_INT));

// check if a valid request was made
if ($referenceType == 'citation' && $referenceId > 0) {
    // prepare the local service-url to ask for data (ajax pass-through landing page)
    $url = "http://localhost" . dirname(filter_input(INPUT_SERVER, 'SCRIPT_NAME', FILTER_SANITIZE_STRING))
         . "/classificationBrowser_ptlp.php?type=jstree&referenceType=citation&referenceId=" . $referenceId;
    if ($insertSeries) {
        $url .= "&insertSeries=" . $insertSeries;
    }
    // check if we are looking for a specific name
    if ($filterId > 0) {
        $data = file_get_contents($url . "&filterId=" . $filterId);
    }
    // .. if not, fetch the "normal" tree for this reference
    else {
        $data = file_get_contents($url);
    }
} else {
    $data = null;
}

$config = \Jacq\Settings::Load();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>JACQ - ClassificationBrowser</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="language" content="en" />
    <link rel="shortcut icon" href="JACQ_LOGO.png"/>

    <!-- blueprint CSS framework -->
    <link rel="stylesheet" type="text/css" href="css/cb/screen.css" media="screen, projection" />
    <link rel="stylesheet" type="text/css" href="css/cb/print.css" media="print" />
    <!--[if lt IE 8]>
    <link rel="stylesheet" type="text/css" href="css/cb/ie.css" media="screen, projection" />
    <![endif]-->

    <link rel="stylesheet" type="text/css" href="css/cb/main.css" />
    <link rel="stylesheet" type="text/css" href="css/cb/form.css" />

    <!-- jQuery -->
    <script type="text/javascript" src="js/jquery-1.8.3.js"></script>
    <!-- jQuery-ui -->
    <link rel="stylesheet" href="css/cb/jquery-ui-1.9.2.css" type="text/css" />
    <script type="text/javascript" src="js/jquery-ui-1.9.2.min.js"></script>
    <!-- jsTree -->
    <script type="text/javascript" src="js/jquery.jstree/jquery.jstree.js"></script>
    <link rel="stylesheet" href="js/jquery.jstree/themes/default/style.css" />

    <!-- initialize jstree for classification browser -->
    <script type="text/javascript" src="js/classBrowser_jstree_fct.js"></script>

    <script type="text/javascript" src="js/classBrowser_functions.js"></script>

    <script type="text/javascript" src="js/classBrowser_document_ready.js"></script>

    <script type="text/javascript">
        var classBrowser = '<?php echo filter_input(INPUT_SERVER, 'SCRIPT_NAME', FILTER_SANITIZE_STRING); ?>?id=1';
        var download_url = 'classificationBrowser_download.php?type=csv';
        var initital_data = <?php echo ($data) ? $data : 'null'; ?>;
        var insertSeries = <?php echo $insertSeries; ?>;
        var editSeries = <?php echo $editSeries; ?>;
        var JACQServices = '<?php echo $config->get('JACQ_SERVICES'); ?>';
    </script>
    <style>
        .ui-autocomplete-loading {
            background: white url("images/loading.gif") right center no-repeat;
        }
    </style>
</head>

<body>
    <div class="container" id="page">
      <div id="cssmenu"><ul><li><a>&nbsp;</a></li></ul><span id="login" style="float:right">&pi;&nbsp;</span></div>
        <img id="logo" src="images/jacq_logo.png" width="120" height="60" />

        <div id="content">
            <div align="left">
                <form action='#' onsubmit="return false;" style="<?php if ($referenceType == 'citation' && $referenceId > 0) { echo "display: none;"; }?>">
                    <select id="classificationBrowser_referenceType">
                        <option value="">select reference type</option>
                        <!--<option value="person">person</option>-->
                        <!--<option value="service">service</option>-->
                        <!--<option value="specimen">specimen</option>-->
                        <option value="periodical">citation</option>
                    </select>
                    <br />
                    <select id="classificationBrowser_referenceID">
                        <option value="">select classification reference</option>
                    </select>
                    <br />
                    <input id="filter_taxonID" type="hidden" />
                    <div style="margin-top: 15px;">
                        hide author names
                        <select id="hide-scientific-name-authors">
                            <option value="">Auto</option>
                            <option value="true">Yes</option>
                            <option value="false">No</option>
                        </select>
                    </div>

                    <label for="scientificName">Filter:</label>
                    <input id="scientificName"/>
                    <input id="filter_button" type="image" src="images/magnifier.png" alt="filter" />

                    <span style="margin-left: 30px;">
                        <label><input type="checkbox" id="open_all"> expand Subhierarchies</label>
                    </span>
                    <div id="progressbar" style="width:50%; height:10px; position:fixed; top:60px;"></div>
                    <br />
                </form>
                <div id="jstree_classificationBrowser" style="padding-top: 10px; padding-bottom: 10px;"></div>
                <div id="infoBox" style="display: none; padding: 5px; background: #FFFFFF; border: 1px solid #000000; position: absolute; top: 0px; left: 0px;">Info</div>
            </div>
        </div><!-- content -->
        <div class="clear"></div>

        <div id="footer">
            Copyright &copy; <?php echo date('Y'); ?> University Vienna, Museum of Natural History Vienna, Austrian Academy of Sciences.<br/>
            All Rights Reserved.
        </div><!-- footer -->

    </div><!-- page -->
</body>
</html>
