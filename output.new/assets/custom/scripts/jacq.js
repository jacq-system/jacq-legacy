/* global M */

var clicked;

function isEmpty(s) {
    for (var i=0; i<s.length;i++) {
        var c = s.charAt(i);
        if ((c != ' ') && (c != '\n') && (c != '\t')) {
            return false;
        }
    }
    return true;
}

function check() {
    if (isEmpty(document.f.family.value) &&
        isEmpty(document.f.taxon.value) &&
        isEmpty(document.f.HerbNummer.value) &&
        isEmpty(document.f.Sammler.value) &&
        isEmpty(document.f.SammlerNr.value) &&
        isEmpty(document.f.CollDate.value) &&
        isEmpty(document.f.source_name.value) &&
        isEmpty(document.f.collection.value) &&
        isEmpty(document.f.taxon_alt.value) &&
        isEmpty(document.f.series.value) &&
        isEmpty(document.f.nation_engl.value) &&
        isEmpty(document.f.provinz.value) &&
        isEmpty(document.f.CollNummer.value) &&
        isEmpty(document.f.series.value) &&
        !document.f.type.checked &&
        !document.f.images.checked) {

        var msg = "You haven't stated any search criteria.\n" +
                  "So the searching may need a while!\n" +
                  "Search anyway?\n";
        return confirm(msg);
    } else {
        return true;
    }
}

function change_nation_engl() {
    $.ajax( {
        url: "ajax_lp.php?type=getCountry",
        data: {
            geo_general: $('#ajax_geo_general').val(),
            geo_region: $('#ajax_geo_region').val()
        },
        dataType: "json",
        success: function (data) {
            if (data) {
                $('#ajax_nation_engl_div').html(data);
                $('#ajax_nation_engl').formSelect();    // Reinitialize select
                $('#ajax_nation_engl')
                    .change(function() {
                        $.ajax( {
                            url: "ajax_lp.php?type=getProvince",
                            data: {
                                nation_engl: $('#ajax_nation_engl').val()
                            },
                            dataType: "json",
                            success: function (data) {
                                if (data) {
                                    $('#ajax_provinz_div').html(data);
                                    $("#ajax_provinz").formSelect();    // Reinitialize select
                                } else {
                                    $('#ajax_provinz_div').html('<input class="searchinput" placeholder="State/Province" name="provinz" type="text">');
                                    // Reinitialize text field.
                                    M.updateTextFields();
                                }
                            }
                        });
                    });
                $('#ajax_provinz_div').html('<input class="searchinput" placeholder="State/Province" name="provinz" type="text">'); // just in case...
            } else {
                $('#ajax_nation_engl_div').html('<input class="searchinput" placeholder="Country" name="nation_engl" type="text">');
                $('#ajax_provinz_div').html('<input class="searchinput" placeholder="State/Province" name="provinz" type="text">'); // just in case...
                // Reinitialize text field.
                M.updateTextFields();
            }
        }
    });
}

/**
*   Document Ready
**/
$(function() {

    /**
    *   Materialize CSS initializations
    **/
    $('.tabs').tabs();
    $('select').formSelect();
    $('.collapsible').collapsible();
    $('.modal').modal({startingTop: '0%', endingTop: '20%', opacity: 0.2, inDuration: 200, outDuration: 200});

    /**
    *   Menu routing
    **/
    $("#navbar .nav-content li>a")
        .click(function() {
            window.location.href = $(this).attr("href");
        });

    /**
    *   Progress bars
    **/
    $(".progress").hide();

    /**
    *   Search Form Handling
    **/
    $("#checkbox_type")
        .change(function() {
            $("[name='type']").val($(this).prop("checked") ? "only" : "all");
        });

    $("#checkbox_images")
        .change(function() {
            $("[name='images']").val($(this).prop("checked") ? "only" : "all");
        });

    $("#checkbox_synonym")
        .change(function() {
            $("[name='synonym']").val($(this).prop("checked") ? "all" : "only");
        });

    $("#ajax_source_name")
        .change(function(){
            $.ajax( {
                url: "ajax_lp.php?type=getCollection",
                data: {
                    source_name: $('#ajax_source_name').val()
                },
                dataType: "json",
                success: function (data) {
                    $('#ajax_collection').html(data);
                    $('#ajax_collection').formSelect();
                }
            });
        });

    $("#ajax_geo_general")
        .change(function() {
            change_nation_engl();
        });

    $("#ajax_geo_region")
        .change(function() {
            change_nation_engl();
        });

    // Save default text input fields for reset.
    var countryInput =  $("#ajax_nation_engl").html();
    var provinceInput =  $("#ajax_provinz").html();

    $("#ajax_f_reset")
        .click(function() {
            $('#ajax_nation_engl_div').html('<input class="searchinput" placeholder="Country" name="nation_engl" type="text">');
            $('#ajax_provinz_div').html('<input class="searchinput" placeholder="State/Province" name="provinz" type="text">');
            $("input[type='text']").val('');
            $("select").val('');
            $("select").formSelect();
            $('input[type=checkbox]').prop('checked',false);
            $("[name=type]").val('all');
            $("[name=images]").val('all');
            $("[name=synonym]").val('only');
            //$("#checkbox_type").prop('checked', false);
            //$("#checkbox_images").prop('checked', false);
            $("#ajax_nation_engl").html(countryInput);
            $("#ajax_provinz").html(provinceInput);
        });

    $("#ajax_f")
        .submit(function(event) {
            event.preventDefault();
            if(check()) {
                $('#results').html('');
                $(".progress-search").show();
                var form_data = $('#ajax_f').serialize();
                form_data +="&submit=Search";
                $.ajax( {
                    url: "ajax_lp.php?type=search",
                    type: "POST",
                    data: form_data,
                    success: function(result) {
                        $(".progress-search").hide();
                        $('#results').html(result);
                    }
                });
            }
        });
});

/**
 * Requests results.php with the given parameters
 * @param settings
 */
function reloadTable(settings) {
    let params = '';
    if (settings.order !== undefined) {
        params += '&order=' + settings.order;
    }
    if (settings.page !== undefined) {
        params += '&page=' + settings.page;
    }

    if (settings.ITEMS_PER_PAGE !== undefined) {
        params += '&ITEMS_PER_PAGE=' + settings.ITEMS_PER_PAGE;
    }

    $(".progress-paging").show();
    $.ajax({
        url: "ajax_lp.php?type=results" + params,
        type: "GET",
        success: function(result){
            $('#results').html(result);
        }
    });
}

/**
*   Ajax Complete
**/
$(document)
    .ajaxComplete(function(event, xhr, settings) {
        /**
        *   Materialize CSS reinitializations
        **/
        $('select').formSelect();

        /**
        *   Progress bars
        **/
        $(".progress").hide();

        /**
        *   Register events for added elements
        **/
        if (settings.url.includes("ajax_lp.php")) {
            $(".pagination>li")
                .click(function(){
                    var page = $(this).data('value');
                    if (page !== null) {
                        reloadTable({
                            page: page
                        });
                    }
                });

            $(".resulttax")
                .click(function(){
                    var page = $(this).data('value');
                    if (page !== null) {
                        reloadTable({
                            page: page,
                            order: 1
                        });
                    }
                });

            $(".resultcol")
                .click(function(){
                    var page = $(this).data('value');
                    if (page !== null) {
                        reloadTable({
                            page: page,
                            order: 2
                        });
                    }
                });

            $("select[name=ITEMS_PER_PAGE]")
                .change(function(event) {
                    reloadTable({
                        'ITEMS_PER_PAGE': $(this).val()
                    });
                });
        }
    });
