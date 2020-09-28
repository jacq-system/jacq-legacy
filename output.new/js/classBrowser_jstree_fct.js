/* global initital_data, jacq_url, classificationProgressbarCurr, classificationProgressbarMax */

// handler function for jstree ajax data
var jstree_data = function (n)
{
    // extract citation & taxon information from selected node
    var link = (n.children) ? n.children('a').first() : n;
    var taxon_id = (link.attr) ? link.attr("data-taxon-id") : 0;
    var reference_id = (link.attr) ? link.attr("data-reference-id") : 0;
    var reference_type = (link.attr) ? link.attr("data-reference-type") : 0;

    // check if we have a valid reference-type, if not use the default one
    if (!reference_type) {
        reference_type = $('#classificationBrowser_referenceType').val();
    }

    // check for a set reference, if not use default one
    if (!reference_id) {
        reference_id = $('#classificationBrowser_referenceID').val();
    }

    // return information
    return {
        "referenceType": reference_type,
        "referenceId": reference_id,
        "taxonID": taxon_id
    };
};

// init function for jstree
function init_jstree ()
{
    // delete any old instance
    $('#jstree_classificationBrowser').jstree('destroy');
    // hide info box
    $('#infoBox').hide();

    // initialize jsTree for organisation
    $('#jstree_classificationBrowser').jstree({
        "json_data" : {
                "data" : initital_data,
                "ajax" : {
                    "url" : "classificationBrowser_ptlp.php?type=jstree",
                    "data": jstree_data,
                    "dataType": "json"
                }
        },
        "plugins" : [ "themes", "json_data" ],
        "core": {"html_titles": true}
    });

    $('#jstree_classificationBrowser')
        .on('after_open.jstree', function(e, data) {
            if ($('#open_all')[0].checked) {
                classificationProgressbarCurr++;
                if (classificationProgressbarCurr < classificationProgressbarMax) {
                    $("#progressbar").progressbar("option", "value", classificationProgressbarCurr / classificationProgressbarMax * 100);
                } else {
                    $("#progressbar").progressbar("destroy");
                }
            }
        });
}