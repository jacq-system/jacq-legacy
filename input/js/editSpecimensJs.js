function callUpdateNomService()
{
    $("#nomService").text("connecting...");
    jaxon_updateNomService(document.f.taxonIndex.value);
}

function callUpdateGgbnIdentifier()
{
    $("#ggbnIdentifier").text("");
    if (!currentSpecimenId) {
        return;
    }
    jaxon_updateGgbnIdentifier(currentSpecimenId);
}

function showInstitutionChangeDialog(originalValue)
{
    const $inst = $('[name="institution"]');
    const resetInstitution = function() {
        if ($inst.length) {
            $inst.val(originalValue);
        }
    };

    if (canMoveAcrossInstitutions) {
        const confirmMessage = "You are about to move this specimen to a different source Institution.\n\nDo you want to continue?";
        let confirmed = false;

        if ($.ui && $.ui.dialog) {
            $('<div></div>')
                .html('<p>Changing the source Institution will move this specimen to a different institution.</p><p>The form will now reload and preselect a collection of that institution.</p><p>Do you want to continue?</p>')
                .dialog({
                    modal: true,
                    title: 'Confirm institution change',
                    buttons: {
                        'Continue': function() {
                            confirmed = true;
                            $(this).dialog('close');
                            reload = true;
                            $('#f').trigger('submit');
                        },
                        'Cancel': function() {
                            resetInstitution();
                            $(this).dialog('close');
                        }
                    },
                    close: function() {
                        if (!confirmed) {
                            resetInstitution();
                        }
                        $(this).remove();
                    }
                });
        } else if (!confirm(confirmMessage)) {
            resetInstitution();
        } else {
            reload = true;
            $('#f').trigger('submit');
        }
        return;
    }

    const $dialog = $('#institutionChangeDialog');
    if ($dialog.length && $.ui && $.ui.dialog) {
        if (!$dialog.data('ui-dialog')) {
            $dialog.dialog({
                modal: true,
                buttons: {
                    'OK': function() {
                        $(this).dialog('close');
                    }
                },
                close: function() {
                    resetInstitution();
                }
            });
        } else {
            $dialog.dialog('open');
        }
    } else {
        alert("The source Institution of a specimen should not be changed.\n\nFor new specimens based on existing data please use \"New & Copy\" instead of \"Edit/Update\".\n\nFor existing specimens with a wrong institution please contact one of the admins: Heimo / Dominik / Johannes");
        resetInstitution();
    }
}

function makeOptions()
{
    let options = "width=";
    if (screen.availWidth<990) {
        options += (screen.availWidth - 10) + ",height=";
    } else {
        options += "990, height=";
    }
    if (screen.availHeight<710) {
        options += (screen.availHeight - 10);
    } else {
        options += "710";
    }
    options += ", top=10,left=10,scrollbars=yes,resizable=yes";
    return options;
}

function editCollector(sel)
{
    let target, MeinFenster;

    target = "editCollector.php?sel=" + encodeURIComponent(sel.value);
    MeinFenster = window.open(target, "editCollector", "width=850,height=250,top=50,left=50,scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function editCollector2(sel)
{
    let target, MeinFenster;

    target = "editCollector2.php?sel=" + encodeURIComponent(sel.value);
    MeinFenster = window.open(target, "editCollector2", "width=500,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function searchCollector()
{
    let MeinFenster = window.open("searchCollector.php", "searchCollector", "scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function searchCollector2()
{
    let MeinFenster = window.open("searchCollector2.php", "searchCollector2", "scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function editSpecies(sel)
{
    let target, MeinFenster;

    target = "editSpecies.php?sel=" + encodeURIComponent(sel.value);
    MeinFenster = window.open(target,"Species",makeOptions());
    MeinFenster.focus();
}

function editVoucher()
{
    let target, MeinFenster;

    target = "editVoucher.php?sel=" + document.f.voucher.options[document.f.voucher.selectedIndex].value;
    MeinFenster = window.open(target,"editVoucher","width=500,height=150,top=50,left=50,scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function editSeries()
{
    let target, MeinFenster;

    target = "editSeries.php?sel=" + $( '#seriesIndex' ).val();
    MeinFenster = window.open(target,"editSeries","width=500,height=150,top=50,left=50,scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function editSpecimensTypes(sel)
{
    let target, MeinFenster;

    target = "listSpecimensTypes.php?ID=" + encodeURIComponent(sel);
    MeinFenster = window.open(target,"listSpecimensTypes","width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function editLabel(sel)
{
    let target, MeinFenster;

    target = "editLabel.php?sel=<" + sel + ">";
    MeinFenster = window.open(target,"Labels",makeOptions());
    MeinFenster.focus();
}

function updateBatch(sel,sw)
{
    let target, MeinFenster;

    if (document.f.batch.checked == true || sw) {
        option2 = "&sw=2";
    } else {
        option2 = "&sw=1";
    }
    target = "updateBatch.php?nr=" + encodeURIComponent(sel) + option2;
    MeinFenster = window.open(target,"updateBatch","width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes");
}

function reloadButtonPressed()
{
    reload = true;
}

function checkMandatory(outText)
{
    let missing = 0;
    let header = "";
    let text = "";

    if (reload == true) {
        return true;
    }

    if (document.f.collection.selectedIndex == 0) {
        missing++; text += "Collection\n";
    }
    if (document.f.taxon.value.indexOf("<") < 0 || document.f.taxon.value.indexOf(">") < 0) {
        missing++; text += "taxon\n";
    }
    if (document.f.det.value.length == 0) {
        missing++; text += "det / rev / conf\n";
    }
    if (document.f.taxon_alt.value.length == 0) {
        missing++; text += "ident. history\n";
    }
    if (document.f.sammler.value.indexOf("<") < 0 || document.f.sammler.value.indexOf(">") < 0) {
        missing++; text += "collector\n";
    }
    if (document.f.Nummer.value.length == 0 && document.f.alt_number.value.length == 0) {
        missing++; text += "Number and alt.Nr.\n enter s.n. in alt.Nr. if no number is available";
    }
    if (document.f.Nummer.value.length > 0 && parseInt(document.f.Nummer.value, 10) != document.f.Nummer.value) {
        missing++; text += "Number must be an integer value. Move to alt.Nr.";
    }
    if (document.f.Datum.value.length == 0) {
        missing++; text += "Date\n";
    }
    if (document.f.Fundort1.value.length == 0) {
        missing++; text += "Locality\n";
    }

    if (missing > 0) {
        if (missing > 1) {
            header = "The following " + missing + " entries are missing or invalid:\n";
        } else {
            header = "The following entry is missing or invalid:\n";
        }
        if (outText !== 0) {
            alert(header + text);
        }
        return false;
    } else {
        return true;
    }
}

function doSubmit( p_type )
{
    let nameSelector = $('[name="taxon_alt"]');
    // if we use the taxon-alt textarea, copy the content to the text input field
    if (nameSelector.css("display") == "none") {
        nameSelector.val($('[name="taxon_alt_ta"]').val());
    }

    // If all fields are set, trigger a submit
    if( checkMandatory(1) ) {
        if (confirmBoundingBox(0)) {  // check if coordinates are inside country and/or province
            $('#submit_type').val(p_type);
            $('#f').submit();
        }
    }
}

// function quadrant2LatLon(quadrant,quadrant_sub)
// {
//     var xx = quadrant.substr(quadrant.length-2,2);
//     var yy = quadrant.substr(0,quadrant.length-2);
//
//     var xD = parseInt(((xx - 2) / 6) + 6);
//     var xM = 0;
//     var xS = Math.round((((((xx - 2) / 6) + 6) * 60) % 60) * 60);
//     var yD = parseInt(((-yy / 10) + 56));
//     var yM = 0;
//     var yS = Math.round(((((-yy / 10) + 56) * 60) % 60) * 60);
//
//     if (quadrant_sub==0 || quadrant_sub>4) {
//         xM += 5;
//         yM -= 3;
//     } else {
//         xS += ((quadrant_sub - 1) % 2) * (5 * 60);
//         yS -= parseInt((quadrant_sub - 1) / 2) * (3 * 60);
//         xS += (60 * 5) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
//         yS -= (60 * 3) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
//     }
//
//     var latLon = new Array(2);
//     latLon[1] = xD + (xM / 60) + (xS / 3600);
//     latLon[0] = yD + (yM / 60) + (yS / 3600);
//
//     return latLon;
// }
//
// function convert()
// {
//     var latLon = quadrant2LatLon(document.f.quadrant.value,document.f.quadrant_sub.value);
//
//     if (document.f.lon_deg.value || document.f.lon_min.value || document.f.lon_sec.value || document.f.lat_deg.value || document.f.lat_min.value || document.f.lat_sec.value)    {
//         alert('Coordinates have already been entered');
//     } else {
//         document.f.lon_deg.value = Math.floor(Math.abs(latLon[1]));
//         document.f.lon_min.value = Math.floor(Math.abs(latLon[1]) * 60 % 60);
//         document.f.lon_sec.value = Math.floor(Math.abs(latLon[1]) * 3600 % 60);
//         if (latLon[1]<0) {
//             document.f.lon.options.selectedIndex = 0;
//         } else {
//             document.f.lon.options.selectedIndex = 1;
//         }
//         document.f.lat_deg.value = Math.floor(Math.abs(latLon[0]));
//         document.f.lat_min.value = Math.floor(Math.abs(latLon[0]) * 60 % 60);
//         document.f.lat_sec.value = Math.floor(Math.abs(latLon[0]) * 3600 % 60);
//         if (latLon[0]>=0) {
//             document.f.lat.options.selectedIndex = 0;
//         } else {
//             document.f.lat.options.selectedIndex = 1;
//         }
//     }
// }

function fillLocation(lon_deg, lon_min, lon_sec, lon_dir, lat_deg, lat_min, lat_sec, lat_dir, nationID)
{
    let overwrite;

    if (document.f.lon_deg.value || document.f.lon_min.value || document.f.lon_sec.value || document.f.lat_deg.value || document.f.lat_min.value || document.f.lat_sec.value)    {
        overwrite = confirm('Coordinates have already been entered.\nOn confirming, they will be replaced by selected ones.');
    } else {
        overwrite = true;
    }

    if (overwrite) {
        document.f.lon_deg.value = lon_deg;
        document.f.lon_min.value = lon_min;
        document.f.lon_sec.value = lon_sec;
        if (lon_dir == 'W') {
            document.f.lon.options.selectedIndex = 0;
        } else {
            document.f.lon.options.selectedIndex = 1;
        }
        document.f.lat_deg.value = lat_deg;
        document.f.lat_min.value = lat_min;
        document.f.lat_sec.value = lat_sec;
        if (lat_dir == 'N') {
            document.f.lat.options.selectedIndex = 0;
        } else {
            document.f.lat.options.selectedIndex = 1;
        }
    }
    for (i = 0; i < document.f.nation.length; i++) {
        if (document.f.nation.options[i].value == nationID) {
            document.f.nation.selectedIndex = i;
            break;
        }
    }
    reload=true;
    self.document.f.submit();
}

function editNCBI(sel)
{
    let target, MeinFenster;

    target = "editNCBI.php?id=" + sel;
    MeinFenster = window.open(target,"editNCBI","width=350,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
    MeinFenster.focus();
}

function goBack(sel,check,edit,pid)
{
    let move;

    if (!check && checkMandatory(0)) {
        move = confirm("Are you sure you want to leave?\nDataset will not be inserted!");
    } else if (check && edit) {
        move = confirm("Are you sure you want to leave?\nDataset will not be updated!");
    } else {
        move = true;
    }
    if (move) {
        if (pid) {
            self.location.href = 'listTypeSpecimens.php?ID=' + pid + '&nr=' + sel;
        } else {
            self.location.href = 'listSpecimens.php?page=' + currentListPage + '&nr=' + sel;
        }
    }
}

function call_toggleLanguage()
{
    jaxon_toggleLanguage(jaxon.getFormValues('f'));
    return false;
}

function call_makeAutocompleter(name)
{
    $('#' + name).autocomplete ({
        source: 'index_jq_autocomplete.php?field=taxon',
        minLength: 2
    });
}

function open_stblIDbox()
{
    let stblIDboxSel = $("#stblIDbox");

    stblIDboxSel.dialog("option", "title", "show multiple stable identifiers");
    stblIDboxSel.dialog("open");
}

function linkEditUpdateDirtyState(form) {
    if (!form || !form.length) {
        return;
    }
    form.data('dirty', form.serialize() !== linkEditUnsaved.initial);
}

function setupLinkEditForm()
{
    const form = $('#f_iBox');
    if (!form.length) {
        return;
    }

    form.off('.linkEdit');

    const tableBody = form.find('#linkRows');
    const templateRow = tableBody.find('tr[data-template="1"]').first();
    const addButton = form.find('#addLinkRow');
    let nextIndex = parseInt($('#linkRowNextIndex').val(), 10);
    if (isNaN(nextIndex) || nextIndex < 1) {
        nextIndex = 1;
    }
    $('#linkRowNextIndex').val(nextIndex);

    if (!templateRow.length) {
        linkEditUnsaved.initial = form.serialize();
        linkEditUnsaved.tracking = true;
        form.data('dirty', false);
        return;
    }

    const templateQualifierDefault = templateRow.find('select[name^="linkQualifier_"]').val() || '';
    const templateSourceDefault = templateRow.find('select[name^="linkInstitution_"]').val() || '';

    templateRow.addClass('link-row-template');
    templateRow.find('.link-delete-btn').hide();
    resetLinkRowState(templateRow);

    form.on('change.linkEdit input.linkEdit', 'input, select, textarea', function() {
        linkEditUpdateDirtyState(form);
    });

    form.on('click.linkEdit', '.link-delete-btn', function() {
        const row = $(this).closest('tr');
        const rowId = String(row.attr('data-row-id') || '');
        const hidden = form.find('#linkDelete_' + rowId);

        if (row.is('[data-template="1"]')) {
            resetLinkRowState(row);
            linkEditUpdateDirtyState(form);
            return;
        }

        if (!hidden.length || rowId.indexOf('new') === 0) {
            row.remove();
            linkEditUpdateDirtyState(form);
            return;
        }

        const marked = hidden.val() === '1';
        if (marked) {
            hidden.val('0');
            row.removeClass('link-delete-marked');
            row.css({'text-decoration': '', 'opacity': ''});
        } else {
            hidden.val('1');
            row.addClass('link-delete-marked');
            row.css({'text-decoration': 'line-through', 'opacity': '0.6'});
        }
        linkEditUpdateDirtyState(form);
    });

    if (addButton.length) {
        addButton.off('.linkEdit').on('click.linkEdit', function() {
            const newId = 'new' + nextIndex++;
            const newRow = templateRow.clone();
            newRow.removeAttr('data-template').removeClass('link-row-template');
            newRow.find('.link-delete-btn').show();
            updateRowIdentifiers(newRow, newId);
            resetLinkRowState(newRow);

            templateRow.before(newRow);

            newRow.find('input[type="text"]').first().focus();

            $('#linkRowNextIndex').val(nextIndex);
            linkEditUpdateDirtyState(form);
        });
    }

    form.find('input[id^="linkDelete_"]').each(function() {
        if ($(this).val() === '1') {
            const row = $(this).closest('tr');
            row.addClass('link-delete-marked');
            row.css({'text-decoration': 'line-through', 'opacity': '0.6'});
        }
    });

    linkEditUnsaved.initial = form.serialize();
    linkEditUnsaved.tracking = true;
    form.data('dirty', false);

    function updateRowIdentifiers(row, suffix) {
        row.attr('data-row-id', suffix);
        row.data('row-id', suffix);

        row.find('[id]').each(function() {
            const current = $(this).attr('id');
            const updated = replaceSuffix(current, suffix);
            if (updated !== current) {
                $(this).attr('id', updated);
            }
        });

        row.find('[name]').each(function() {
            const current = $(this).attr('name');
            const updated = replaceSuffix(current, suffix);
            if (updated !== current) {
                $(this).attr('name', updated);
            }
        });

        row.find('[for]').each(function() {
            const current = $(this).attr('for');
            const updated = replaceSuffix(current, suffix);
            if (updated !== current) {
                $(this).attr('for', updated);
            }
        });

        row.find('.link-delete-btn').attr('data-target', suffix);
    }

    function replaceSuffix(value, suffix) {
        if (!value) {
            return value;
        }
        return value.replace(/(link(?:Qualifier|Institution|Specimen|Delete)_)[\w-]+/, '$1' + suffix);
    }

    function resetLinkRowState(row) {
        row.removeClass('link-delete-marked');
        row.css({'text-decoration': '', 'opacity': ''});
        row.find('select[name^="linkQualifier_"]').val(templateQualifierDefault);
        row.find('select[name^="linkInstitution_"]').val(templateSourceDefault);
        row.find('input[type="text"]').val('');
        row.find('input[type="hidden"]').val('0');
    }
}

function iBoxMarkClean()
{
    const form = $('#f_iBox');
    if (form.length) {
        form.off('.linkEdit');
        form.find('#addLinkRow').off('.linkEdit');
        linkEditUnsaved.initial = form.serialize();
        form.data('dirty', false);
    }
    linkEditUnsaved.tracking = false;
}

function hasUnsavedLinkChanges()
{
    const form = $('#f_iBox');
    if (!form.length || !linkEditUnsaved.tracking) {
        return false;
    }
    linkEditUpdateDirtyState(form);
    return !!form.data('dirty');
}

$(function()
{
    if (institutionEditLocked) {
        const $inst = $('[name="institution"]');
        if ($inst.length) {
            const original = $inst.val();
            $inst.data('original', original);
            $inst.on('change', function() {
                const originalValue = $(this).data('original');
                if ($(this).val() !== originalValue) {
                    showInstitutionChangeDialog(originalValue);
                }
            });
        }
    }

    jaxon_displayMultiTaxa(currentSpecimenId);

    $('#iBox_content').dialog( {
        autoOpen: false,
        modal: true,
        bgiframe: true,
        width: 750,
        height: 600,
        beforeClose: function() {
            if (hasUnsavedLinkChanges()) {
                if (!confirm('Are you sure you want to leave?\nDataset will not be updated!')) {
                    return false;
                }
            }
            iBoxMarkClean();
        }
    } );
    $("#stblIDbox").dialog({
        autoOpen: false,
        height: 'auto',
        width: 'auto',
        modal: true,
    });
    $('#sammlerIndex').on('change', function() {
        jaxon_displayCollectorLinks($(this).val());
    });
    $('#sammlerIndex').trigger('change');

    $('[name="HerbNummer"]')
        .blur(function() {
            this.value = this.value.trim();
            let number = this.value;
            // convert StableURI to collection HerbNummer
            // var r = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/ // Regex Pattern
            let r = /^\D/;  // RegEx; searchstring must start with any non-digit char
            if (r.test(number)) {
                $.ajax({
                    url: "ajax/convStabURItoHerbnummer.php",
                    data: {querytext: number},
                    type: 'post',
                    dataType: "json",
                    success: function (data) {
                        $('[name="HerbNummer"]').val(data['HerbNummer']).change();
                        //console.log("Success, you submit your form" + data);
                    }
                });
                // HerbNummer = this.value;
                // var institutionNr = $('[name="institution"]').val();
                // var institutionName = $('[name="institution"] option:selected').text();
            } else {
                if (oldHerbNumber > 0 && oldHerbNumber != number) {
                    if (!confirm("HerbarNr. differs from stored one.\nPlease confirm the difference.")) {
                        setTimeout(() => $(this).focus(), 1)
                    }
                }
                if (specifiedHerbNummerLength && number.length != specifiedHerbNummerLength) {
                    if (!confirm("HerbarNr. should have a length of " + specifiedHerbNummerLength + " digits.\nPlease confirm the different length.")) {
                        setTimeout(() => $(this).focus(), 1)
                    }
                }
            }
        })
        .keydown(function(event){
            if (event.keyCode == 13){
                event.preventDefault()
                event.stopPropagation()
                $('[name="HerbNummer"]').blur()
                return false;
            }
        });

    jacqLatLonQuadInit();
    $("#open_latLonQuDialog").button({
        icon: "ui-icon-pencil",
        showLabel: false,
        label: "Edit Lat/Lon and Quadrant"
    });
    $("#del_latLon")
        .button({
            icon: "ui-icon-trash",
            showLabel: false,
            label: "Delete Lat/Lon, Quadrant and Exactness"
        })
        .on("click", function() {
            $("input[name='lat_deg']").val("");
            $("input[name='lat_min']").val("");
            $("input[name='lat_sec']").val("");
            $("select[name='lat']").val("N")
            $("input[name='lon_deg']").val("");
            $("input[name='lon_min']").val("");
            $("input[name='lon_sec']").val("");
            $("select[name='lon']").val("E")
            $("input[name='quadrant']").val("");
            $("input[name='quadrant_sub']").val("");
            $("input[name='exactness']").val("");
            return false;
        });

    $('[name="taxon_alt_ta"]').hide();
    $("#taxon_alt_toggle")
        .button({
            icon: "ui-icon-arrow-2-n-s",
            showLabel: false,
            label: "Toggle ident. history input field"
        })
        .on("click", function() {
            if ($('[name="taxon_alt_ta"]').css("display") == "none") {
                $('[name="taxon_alt_ta"]').val($('[name="taxon_alt"]').val()).show();
                $('[name="taxon_alt"]').hide();
                $("#taxon_alt_toggle").button("option", "icon", "ui-icon-arrowthick-2-n-s");
            } else {
                $('[name="taxon_alt"]').val($('[name="taxon_alt_ta"]').val()).show();
                $('[name="taxon_alt_ta"]').hide();
                $("#taxon_alt_toggle").button("option", "icon", "ui-icon-arrow-2-n-s");
            }
            return false;
        });

    const raiseSelectors = [
        '#Fundort1',
        'textarea[name="habitat"]',
        'textarea[name="habitus"]',
        'textarea[name="Bemerkungen"]',
        'textarea[name="taxon_alt_ta"]',
        'input[name="taxon_alt"]'
    ];
    raiseSelectors.forEach(function(sel) {
        const $el = $(sel);
        $el.on('focus mousedown', function() {
            $(this).addClass('textarea-raise');
            $(this).css('overflow', 'visible');
        });
        $el.on('blur', function() {
            $(this).removeClass('textarea-raise');
            $(this).css('overflow', '');
        });
    });

    $('#taxonIndex').change(function() {
        setTimeout(callUpdateNomService, 0);
    } );
    setTimeout(callUpdateNomService, 0);

    setTimeout(callUpdateGgbnIdentifier, 0);
});
