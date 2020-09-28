/* global jacq_url */

// called once jquery is ready
$(function() {

    classificationProgressbarMax = 0;
    classificationProgressbarCurr = 0;

    // initialize the jsTree
    init_jstree();

    // update references when new type is chosen
    $('#classificationBrowser_referenceType')
        .change(function() {
            if ($('#classificationBrowser_referenceType').val()) {
                $.ajax( {
                    url: "classificationBrowser_ptlp.php?type=referenceType",
                    data: {
                        referenceType: $('#classificationBrowser_referenceType').val()
                    },
                    dataType: "json"
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
            }
        })
        .change();

    // update tree when a new reference is chosen
    $('#classificationBrowser_referenceID')
        .change(function() {
            initital_data = null;
            init_jstree();
        });

    // add click handlers for jsTree nodes
    $(document)
        .on('click', '#jstree_classificationBrowser a', function() {
            if ($('#open_all')[0].checked) {
                $('html').addClass('waiting');
                var taxonID = $(this).attr('data-taxon-id');
                var referenceId = $(this).attr('data-reference-id');
                var clickTarget = jQuery(this);
                $.ajax({
                    url: "classificationBrowser_ptlp.php?type=open_all",
                    data: {
                        referenceID: referenceId,
                        taxonID: taxonID
                    },
                    dataType: "json",
                    success: function(data) {
                        $('html').removeClass('waiting');
                        var decision = false;
                        if (data < 20) {
                            decision = true;
                        } else if (data >= 20 && data < 100) {
                            if (confirm("Caution!\n\nThis will take some time.\n(" + data + " nodes)")) {
                                decision = true;
                            }
                        } else if (confirm("Caution!\n\nThis will a long time.\n(" + data + " nodes)")) {
                            decision = true;
                        }
                        if (decision) {
                            classificationProgressbarMax = data;
                            classificationProgressbarCurr = 0;
                            $("#progressbar").progressbar({
                                value: 0
                            });
                            $('#jstree_classificationBrowser').jstree('true').close_all(clickTarget);
                            $('#jstree_classificationBrowser').jstree('true').open_all(clickTarget, 1);
                        }
                    }
                });
            } else {
                $('#jstree_classificationBrowser').jstree('true').toggle_node(jQuery(this));
            }
            return false;
        });

    $(document)
        .on('click', '#jstree_classificationBrowser a span', function() {
            return false;
        });

    // add hover handler for all info links
    $(document)
        .on('click', '#jstree_classificationBrowser .infoBox', function() {
            var position = $(this).position();
            var taxonID = $(this).parent().attr('data-taxon-id');
            var referenceId = $(this).parent().attr('data-reference-id');
            var liElement = $(this).parent().parent();

            // keep reference to li-Element
            $('#infoBox').data('liElement', liElement);

            // re-position the infobox
            $('#infoBox').css("top", position.top);
            $('#infoBox').css("left", position.left + $(this).width() + 10);

            // display loading & show the infobox
            $('#infoBox').html( "loading..." );
            $('#infoBox').fadeIn(100);

            if (taxonID > 0) {
                // query the JSON-services for detail information
                $.ajax({
                    url: "classificationBrowser_ptlp.php?type=infoBox_references",
                    data: {
                        taxonID: taxonID,
                        excludeReferenceId: referenceId
                    },
                    dataType: "json",
                    success: function(data) {
                        // check if we found additional references
                        if( data && data.length && data.length > 0 ) {
                            $('#infoBox').html('<b>also used in:</b><br/>');

                            // remember return reference-data
                            $('#infoBox').data('referenceData', data);

                            // add all found references to infobox
                            var referenceInfos = new Array();
                            for( var i = 0; i < data.length; i++ ) {
                                var referenceInfo = data[i].referenceName +
                                    '&nbsp;<span id="arrow_down_' + i + '" style="cursor: pointer;" onclick="arrow_down(' + i + '); return false;"><img src="images/arrow_down.png"></span>' +
                                    '&nbsp;<span id="world_link_' + i + '" style="cursor: pointer;" onclick="world_link(' + i + '); return false;"><img src="images/world_link.png"></span>';
                                referenceInfos.push(referenceInfo);
                            }
                            $('#infoBox').html($('#infoBox').html() + referenceInfos.join("<br/>"));
                        }
                        // if not display notification
                        else {
                            $('#infoBox').html('<i>no other references</i>');
                        }

                        // add download link
                        $('#infoBox').html($('#infoBox').html() + '<br /><b>actions</b><br />');
                        $('#infoBox').html($('#infoBox').html() + '<span style="cursor: pointer;" onclick="download(\'citation\', ' + referenceId + ',' + taxonID + '); return false;"><img src="images/disk.png"></span>');

                        // finally show the info box
                        $('#infoBox').show();
                    }
                });
            } else {
                // query the JSON-services for detail information
                $.ajax({
                    url: "classificationBrowser_ptlp.php?type=infoBox_statistics",
                    data: {
                        referenceID: referenceId
                    },
                    dataType: "json",
                    success: function(data) {
                        // check if we found additional references
                        var iBoxData = '<table class="infoBox">'
                                     + '<tr>'
                                     + '<td style="text-align:right; border-bottom: 1px solid black;">' + data.nrAccTaxa + '</td>'
                                     + '<td colspan="2" style="border-bottom: 1px solid black;">accepted Taxa</td>'
                                     + '<td style="text-align:right; border-left:1px solid black; border-bottom: 1px solid black;">' + data.nrSynonyms + '</td>'
                                     + '<td style="border-bottom: 1px solid black;">Synonyms.</td>'
                                     + '</tr>';

                        // remember return reference-data
                        $('#infoBox').data('statisticsData', data);

                        if( data.ranks && data.ranks.length && data.ranks.length > 0 ) {
                            for( var i = 0; i < data.ranks.length; i++ ) {
                                iBoxData += '<tr>'
                                          + '<td>' + data.ranks[i].rank + '</td>'
                                          + '<td style="text-align:right;">' + data.ranks[i].nrAccTaxa + '</td>'
                                          + '<td>acc.</td>'
                                          + '<td style="text-align:right; border-left:1px solid black;">' + data.ranks[i].nrSynTaxa + '</td>'
                                          + '<td>syn.</td>'
                                          + '</tr>';
                            }
                        }
                        $('#infoBox').html(iBoxData + "</table>");

                        // add download link
                        $('#infoBox').html($('#infoBox').html() + '</td></tr></table><b>actions</b><br />');
                        $('#infoBox').html($('#infoBox').html() + '<span style="cursor: pointer;" onclick="download(\'citation\', ' + referenceId + ',' + taxonID + '); return false;"><img src="images/disk.png"></span>');

                        // finally show the info box
                        $('#infoBox').show();
                    }
                });
            }

            return false;
        });

    // add click handler for all specimen links
    $(document)
        .on('click', '#jstree_classificationBrowser .specimenBox', function() {
            var taxon = $(this).parent().attr('data-taxon');
            window.open('https://www.jacq.org/index.php?taxon=' + encodeURIComponent(taxon));

            return false;
        });

    // add click handler for all type links
    $(document).
        on('click', '#jstree_classificationBrowser .typeBox', function() {
            var taxon = $(this).parent().attr('data-taxon');
            window.open('https://www.jacq.org/index.php?taxon=' + encodeURIComponent(taxon));

            return false;
        });

    // add click handler for access handling
    $(document)
        .on('click', '#jstree_classificationBrowser .acl', function() {
            var tax_syn_ID = $(this).attr('data-tax-syn-id');

            // load authorization view and assign it to div
            $('#authorization_view').load(
                    jacq_url + "index.php?r=authorization/ajaxClassificationAccess&tax_syn_ID=" + tax_syn_ID,
                    null,
                    function(responseText, textStatus, XMLHttpRequest) {
                        $('#authorization_management_dialog').dialog('open');
                    }
            );

            return false;
        });

    // Add hover-behaviour for infoBox
    $('#infoBox')
        .mouseleave(function(evt) {
            if( $(evt.target).attr('id') != 'infoBox' ) {
                return;
            }

            $(this).fadeOut(100);
        });

    // initialize auto-complete
    $('#scientificName')
        .autocomplete({
            source: 'classificationBrowser_ptlp.php?type=scientificNameAc',
            minLength: 2,
            select: function (event, ui) {
                if (typeof ui.item !== "undefined") {
                    $("#filter_taxonID").val(ui.item.id);
                }
            },
            change: function (event, ui) {
                if (ui.item == null) {
                    $("#filter_taxonID").val(0);
                }
            }
        });

    // bind to click handler for filter
    $('#filter_button')
        .click(function() {
            var filter_id = $('#filter_taxonID').val();
            var reference_type = $('#classificationBrowser_referenceType').val();
            var reference_id = $('#classificationBrowser_referenceID').val();

            if( filter_id > 0 && reference_type != "" && reference_id > 0 ) {
                $('#jstree_classificationBrowser').jstree('destroy');
                $('#jstree_classificationBrowser').html('');

                $.ajax({
                    url: 'classificationBrowser_ptlp.php?type=filter_button',
                    data: {
                        referenceType: reference_type,
                        referenceId: reference_id,
                        filterId: filter_id
                    },
                    dataType: "json",
                    success: function(data) {
                        // remember initital data
                        initital_data = data;

                        // re-inititalize jstree
                        init_jstree();
                    }
                });
            }
        });
});