<?php
// require configuration
require( 'inc/variables.php' );
?>
<html>
    <head>
        <title>Virtual Herbaria / classification browser</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <link rel="stylesheet" href="css/herbarium.css" type="text/css">

        <!-- jQuery -->
        <script type="text/javascript" src="js/jquery-1.8.1.min.js" ></script>
        <!-- jsTree -->
        <script type="text/javascript" src="js/jquery.jstree/jquery.jstree.js" ></script>

        <!-- initialize jstree for classification browser -->
        <script type="text/javascript">
            var jacq_url = '<?php echo $_CONFIG['JACQ_URL']; ?>';
            
            $(document).ready(function(){
                // initialize jsTree for organisation
                $('#jstree_classificationBrowser').jstree({
                    "json_data": {
                        "ajax": {
                            "url": jacq_url + "index.php?r=jSONjsTree/japi&action=classificationBrowser",
                            "data": function(n) {
                                // extract citation & taxon information from selected node
                                var link = (n.children) ? n.children('a').first() : n;
                                var taxon_id = (link.attr) ? link.attr("data-taxon-id") : 0;
                                var reference_id = (link.attr) ? link.attr("data-reference-id") : 0;
                                var reference_type = (link.attr) ? link.attr("data-reference-type") : 0;

                                // check if we have a valid reference-type, if not use the default one
                                if( !reference_type ) {
                                    reference_type = $('#classificationBrowser_referenceType').val();
                                }
                                
                                // check for a set reference, if not use default one
                                if( !reference_id ) {
                                    reference_id = $('#classificationBrowser_referenceID').val();
                                }

                                // return information
                                return {
                                    "referenceType": reference_type,
                                    "referenceID": reference_id,
                                    "taxonID": taxon_id
                                };
                            }
                        }
                    },
                    "plugins": ["json_data", "themes"]
                });
                
                // update references when new type is chosen
                $('#classificationBrowser_referenceType').bind('change', function() {
                    $.ajax( {
                        url: jacq_url + "index.php?r=jSONClassification/japi&action=references",
                        data: {
                            referenceType: $('#classificationBrowser_referenceType').val()
                        }
                    } ).done( function(data) {
                        // reset reference drop-down
                        $('#classificationBrowser_referenceID').html('<option value="">select classification reference</option>');
                        
                        // add all references as drop-down options
                        for( var i = 0; i < data.length; i++ ) {
                            // create new option element for reference
                            var referenceOption = $('<option>');
                            referenceOption.val(data[i].id);
                            referenceOption.html(data[i].name);
                            // append new option to reference select
                            $('#classificationBrowser_referenceID').append(referenceOption);
                        }
                        
                        // trigger a change to the reference dropdown (since content was updated)
                        $('#classificationBrowser_referenceID').trigger('change');
                    } );
                });

                // update tree when a new reference is chosen
                $('#classificationBrowser_referenceID').bind('change', function() {
                    $('#jstree_classificationBrowser').jstree( 'refresh' );
                });
                
                // add click handlers for jsTree nodes (since they should do nothing)
                $('#jstree_classificationBrowser a').live('click', function() {return false;});
            });
        </script>
    </head>

    <body bgcolor="#ffffff">
        <div align="center">
            <table border="0" cellpadding="0" cellspacing="0" width="800">
                <tr>
                    <td height="10" valign="top" colspan="9"></td>
                </tr>
                <tr>
                    <!-- Shim row, height 1. -->
                    <td><img src="images/spacer.gif" width="198" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="197" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="198" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="200" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="1" height="1" border="0"></td>
                    <td><img src="images/spacer.gif" width="1" height="1" border="0"></td>
                </tr>
                <tr>
                    <!-- row 1 -->
                    <td colspan="8"><img name="databasemenu_r1_c1" src="images/databasemenu_r1_c1.gif" width="800" height="93" border="0" alt="virtual herbaria austria"></td>
                    <td><img src="images/spacer.gif" width="1" height="93" border="0"></td>
                </tr>
                <tr>
                    <!-- row 2 -->
                    <td><a href="../index.htm"><img name="databasemenu_r2_c1" src="images/databasemenu_r2_c1.gif" width="198" height="37" border="0" alt="home"></a></td>
                    <td><img name="databasemenu_r2_c2" src="images/databasemenu_r2_c2.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
                    <td><a href="index.php"><img name="databasemenu_r2_c3" src="images/databasemenu_r2_c3.gif" width="197" height="37" border="0" alt="general information"></a></td>
                    <td><img name="databasemenu_r2_c4" src="images/databasemenu_r2_c4.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
                    <td><a href="collections.htm"><img name="databasemenu_r2_c5" src="images/databasemenu_r2_c5.gif" width="198" height="37" border="0" alt="collections"></a></td>
                    <td><img name="databasemenu_r2_c6" src="images/databasemenu_r2_c6.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
                    <td><a href="refsystems.htm"><img name="databasemenu_r2_c7" src="images/databasemenu_r2_c7.gif" width="200" height="37" border="0" alt="reference systems"></a></td>
                    <td><img name="databasemenu_r2_c8" src="images/databasemenu_r2_c8.gif" width="1" height="37" border="0" alt="herbarmenu"></td>
                    <td><img src="images/spacer.gif" width="1" height="37" border="0"></td>
                </tr>
                <tr>
                    <td height="20" valign="top" colspan="9">&nbsp;</td>
                </tr>
                <tr>
                    <td valign="top" colspan="9">
                        <form action=''>
                            <select id="classificationBrowser_referenceType">
                                <option value="">select reference type</option>
                                <!--<option value="person">person</option>
                                <option value="service">service</option>
                                <option value="specimen">specimen</option>-->
                                <option value="periodical">citation</option>
                            </select>
                            <br />
                            <select id="classificationBrowser_referenceID">
                                <option value="">select classification reference</option>
                            </select>
                            <br />
                            <br />
                            
                            <div id="jstree_classificationBrowser" style="height: 400px;"></div>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>