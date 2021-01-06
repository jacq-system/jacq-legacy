<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>JACQ - Statistics</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="language" content="en" />
    <link rel="shortcut icon" href="JACQ_LOGO.png"/>

    <!-- blueprint CSS framework -->
    <link rel="stylesheet" type="text/css" href="css/statistics/screen.css" media="screen, projection" />
    <link rel="stylesheet" type="text/css" href="css/statistics/print.css" media="print" />
    <!--[if lt IE 8]>
    <link rel="stylesheet" type="text/css" href="css/statistics/ie.css" media="screen, projection" />
    <![endif]-->

    <link rel="stylesheet" type="text/css" href="css/statistics/main.css" />
    <link rel="stylesheet" type="text/css" href="css/statistics/form.css" />

    <!-- jQuery -->
    <script type="text/javascript" src="js/jquery-1.8.3.js"></script>
    <!-- jQuery-ui -->
    <link rel="stylesheet" href="css/statistics/jquery-ui-1.9.2.css" type="text/css" />
    <script type="text/javascript" src="js/jquery-ui-1.9.2.min.js"></script>
    <!-- flot -->
    <script type="text/javascript" src="js/jquery.flot/jquery.flot.js" ></script>

    <script type="text/javascript" src="js/jacqStatistics_functions.js"></script>

    <script type="text/javascript" src="js/jacqStatistics_document_ready.js"></script>

    <script type="text/javascript">
        var plotData;
    </script>
</head>

<body>
    <div class="container" id="page">
        <div id="cssmenu"><ul><li><a>&nbsp;</a></li></ul></div>
        <img id="logo" src="images/jacq_logo.png" width="120" height="60" />

        <div id="content">
            <div align="left">
                <form action='#' onsubmit="return false;">
                    <input id="statistics_period_start" type="text" value="<?php echo date('Y') . '-01-01'; ?>" maxlength="10" size="10" />
                    &mdash;
                    <input id="statistics_period_end" type="text" value="<?php echo date('Y-m-d'); ?>" maxlength="10" size="10" />
                    &rightarrow;
                    <select id="statistics_updated">
                        <option value="0" selected="selected">New</option>
                        <option value="1">Updated</option>
                    </select>
                    <select id="statistics_type">
                        <option value="names">Names</option>
                        <option value="citations">Citations</option>
                        <option value="names_citations">Names used in Citations</option>
                        <option value="specimens" selected="selected">Specimens</option>
                        <option value="type_specimens">Type-Specimens</option>
                        <option value="names_type_specimens">use of names for Type-Specimens</option>
                        <option value="types_name">Types per Name</option>
                        <option value="synonyms">Synonyms</option>
                    </select>
                    /
                    <select id="statistics_interval">
                        <option value="year">year</option>
                        <option value="month">month</option>
                        <option value="week" selected="selected">week</option>
                        <option value="day">day</option>
                    </select>
                    &nbsp;
                    <input id="statistics_send" type="image" src="images/magnifier.png" alt="submit" />
                    <br />
                </form>
                <div id="statistics_result" style="padding-top: 10px; padding-bottom: 10px; overflow:auto;"></div>
                <div id="statistics_plot" style="width: 100%; height: 300px;"></div>
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
