/* global classBrowser, download_url */

/**
 * open link to other classification
 * @param {integer} p_i index into references
 */
function world_link (p_i)
{
    var index = p_i;
    var referenceData = $('#infoBox').data('referenceData');
    referenceData = referenceData[index];

    var url = classBrowser + '&referenceType=' + referenceData.referenceType
                           + '&referenceId=' + referenceData.referenceId
                           + "&filterId=" + referenceData.taxonID;
    window.open(url);
}

/**
 * add other reference to tree
 * @param {integer} p_i index into references
 */
function arrow_down (p_i)
{
    var index = p_i;
    var referenceData = $('#infoBox').data('referenceData');
    referenceData = referenceData[index];
    var liElement = $('#infoBox').data('liElement');
    var addedReferences = liElement.data(referenceData.referenceType);

    // check if there are references stored already
    if (addedReferences == null) {
        addedReferences = {};
    }

    // ignore if references was already added
    if (typeof addedReferences[referenceData.referenceId] !== "undefined") return;

    // setup node data
    var nodeData = {
        data: {
            title: referenceData.referenceName,
            attr: {
                "data-taxon-id": referenceData.taxonID,
                "data-reference-id": referenceData.referenceId,
                "data-reference-type": referenceData.referenceType
            },
            icon: "images/book_open.png"
        }
    };

    // check if node has children
    if (referenceData.hasChildren) {
        nodeData.state = 'closed';
    }

    // finally add the node to the classification-browser
    $('#jstree_classificationBrowser').jstree('create_node', liElement, "after", nodeData);

    // remember added reference
    addedReferences[referenceData.referenceId] = true;
    liElement.data(referenceData.referenceType, addedReferences);
}

function download (referenceType, referenceId, scientificNameId)
{
    var url = download_url + '&referenceType=' + referenceType
                           + '&referenceId=' + referenceId
                           + '&scientificNameId=' + scientificNameId
                           + '&hideScientificNameAuthors=' + $('#hide-scientific-name-authors').val();
    window.location = url;
}