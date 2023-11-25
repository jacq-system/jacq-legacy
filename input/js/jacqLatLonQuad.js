function jacqLatLonQuadInit()
{
    dialog_latLonQu = $("#latLonQuDialog").dialog({
        autoOpen: false,
        height: 'auto',
        width: 'auto',
        modal: true,
        position: { my: "right bottom", at: "center", of: window },
        buttons: {
            Cancel: function() {
                dialog_latLonQu.dialog("close");
            },
            "Accept": function() {
                if (confirmBoundingBox()) {
                    setLatLonQuadSub();
                }
            }
        },
        open: getLatLonQuadSub,
        close: function() {
        }
    });
    $("#open_latLonQuDialog").on("click", function() {
        dialog_latLonQu.dialog("open");
        // const dialog_btn_check = $("#d_btn_check");
        // dialog_btn_check.height(dialog_btn_check.parent().height() - 10);
        return false;
    });
    $("#d_btn_dms_convert").on("click", function () {
        const lat_d = parseInt($("input[name='lat_dms_d']").val());
        const lat_m = parseInt($("input[name='lat_dms_m']").val());
        const lat_s = parseFloat($("input[name='lat_dms_s']").val().replaceAll(',', '.'));
        const lon_d = parseInt($("input[name='lon_dms_d']").val());
        const lon_m = parseInt($("input[name='lon_dms_m']").val());
        const lon_s = parseFloat($("input[name='lon_dms_s']").val().replaceAll(',', '.'));
        const quad  = latLon2Quadrant(lat_d + lat_m / 60.0 + lat_s / 3600.0, lon_d + lon_m / 60.0 + lon_s / 3600.0);
        $("input[name='lat_dmm_d']").val(lat_d);
        $("input[name='lat_dmm_m']").val(lat_m + Math.round((lat_s / 60.0) * 10000) / 10000);
        $("input[name='lon_dmm_d']").val(lon_d);
        $("input[name='lon_dmm_m']").val(lon_m + Math.round((lon_s / 60.0) * 10000) / 10000);
        $("input[name='lat_ddd']").val(Math.sign(lat_d) * (Math.abs(lat_d) + Math.round((lat_m / 60.0 + lat_s / 3600.0) * 100000) / 100000));
        $("input[name='lon_ddd']").val(Math.sign(lon_d) * (Math.abs(lon_d) + Math.round((lon_m / 60.0 + lon_s / 3600.0) * 100000) / 100000));
        $("input[name='quad']").val(quad[0]);
        $("input[name='quad_sub']").val(quad[1]);
        return false;
    });
    $("#d_btn_dmm_convert").on("click", function () {
        const lat_d = parseInt($("input[name='lat_dmm_d']").val());
        const lat_m = parseFloat($("input[name='lat_dmm_m']").val().replaceAll(',', '.'));
        const lon_d = parseInt($("input[name='lon_dmm_d']").val());
        const lon_m = parseFloat($("input[name='lon_dmm_m']").val().replaceAll(',', '.'));
        const quad  = latLon2Quadrant(lat_d + lat_m / 60.0, lon_d + lon_m / 60.0);
        $("input[name='lat_dms_d']").val(lat_d);
        $("input[name='lat_dms_m']").val(Math.floor(lat_m));
        $("input[name='lat_dms_s']").val(Math.round((lat_m - Math.floor(lat_m)) * 6000) / 100);
        $("input[name='lon_dms_d']").val(lon_d);
        $("input[name='lon_dms_m']").val(Math.floor(lon_m));
        $("input[name='lon_dms_s']").val(Math.round((lon_m - Math.floor(lon_m)) * 6000) / 100);
        $("input[name='lat_ddd']").val(Math.sign(lat_d) * (Math.abs(lat_d) + Math.round(lat_m / 60.0 * 100000) / 100000));
        $("input[name='lon_ddd']").val(Math.sign(lon_d) * (Math.abs(lon_d) + Math.round(lon_m / 60.0 * 100000) / 100000));
        $("input[name='quad']").val(quad[0]);
        $("input[name='quad_sub']").val(quad[1]);
        return false;
    });
    $("#d_btn_ddd_convert").on("click", function () {
        const lat_d = parseFloat($("input[name='lat_ddd']").val().replaceAll(',', '.'));
        const lat_du = Math.abs(lat_d);
        const lat_mc = (lat_du - Math.floor(lat_du)) * 60.0;
        const lon_d = parseFloat($("input[name='lon_ddd']").val().replaceAll(',', '.'));
        const lon_du = Math.abs(lon_d);
        const lon_mc = (lon_du - Math.floor(lon_du)) * 60.0;
        const quad  = latLon2Quadrant(lat_d, lon_d);
        $("input[name='lat_dms_d']").val(Math.sign(lat_d) * Math.floor(lat_du));
        $("input[name='lat_dms_m']").val(Math.floor(lat_mc));
        $("input[name='lat_dms_s']").val(Math.round((lat_mc - Math.floor(lat_mc)) * 6000) / 100);
        $("input[name='lon_dms_d']").val(Math.sign(lon_d) * Math.floor(lon_du));
        $("input[name='lon_dms_m']").val(Math.floor(lon_mc));
        $("input[name='lon_dms_s']").val(Math.round((lon_mc - Math.floor(lon_mc)) * 6000) / 100);
        $("input[name='lat_dmm_d']").val(Math.sign(lat_d) * Math.floor(lat_du));
        $("input[name='lat_dmm_m']").val(Math.round(lat_mc * 10000) / 10000);
        $("input[name='lon_dmm_d']").val(Math.sign(lon_d) * Math.floor(lon_du));
        $("input[name='lon_dmm_m']").val(Math.round(lon_mc * 10000) / 10000);
        $("input[name='quad']").val(quad[0]);
        $("input[name='quad_sub']").val(quad[1]);
        return false;
    });
    $("#d_btn_quad_convert").on("click", function () {
        const latlon = quadrant2LatLon($("input[name='quad']").val(), $("input[name='quad_sub']").val());
        if (latlon[0] && latlon[1]) {
            const lat_mc = (latlon[0] - Math.floor(latlon[0])) * 60.0;
            const lon_mc = (latlon[1] - Math.floor(latlon[1])) * 60.0;
            $("input[name='lat_dms_d']").val(Math.floor(latlon[0]));
            $("input[name='lat_dms_m']").val(Math.floor(lat_mc));
            $("input[name='lat_dms_s']").val(Math.round((lat_mc - Math.floor(lat_mc)) * 6000) / 100);
            $("input[name='lon_dms_d']").val(Math.floor(latlon[1]));
            $("input[name='lon_dms_m']").val(Math.floor(lon_mc));
            $("input[name='lon_dms_s']").val(Math.round((lon_mc - Math.floor(lon_mc)) * 6000) / 100);
            $("input[name='lat_dmm_d']").val(Math.floor(latlon[0]));
            $("input[name='lat_dmm_m']").val(Math.round(lat_mc * 10000) / 10000);
            $("input[name='lon_dmm_d']").val(Math.floor(latlon[1]));
            $("input[name='lon_dmm_m']").val(Math.round(lon_mc * 10000) / 10000);
            $("input[name='lat_ddd']").val(Math.round(latlon[0] * 100000) / 100000);
            $("input[name='lon_ddd']").val(Math.round(latlon[1] * 100000) / 100000);
        }
        return false;
    });
    $("#d_btn_utm_convert").on("click", function () {
        $('html').addClass('waiting');
        $.ajax({
            url: "https://services.jacq.org/jacq-services/rest/geo/convert?utm=" + $("input[name='utm']").val(),
            // url: "http://localhost/develop.jacq/services/rest/geo/convert?utm=" + $("input[name='utm']").val(),
            crossDomain: true,
            success: function (data) {
                $('html').removeClass('waiting');
                const lat_mc = (data.latlon.lat - Math.floor(data.latlon.lat)) * 60.0;
                const lon_mc = (data.latlon.lon - Math.floor(data.latlon.lon)) * 60.0;
                $("input[name='lat_dms_d']").val(Math.floor(data.latlon.lat));
                $("input[name='lat_dms_m']").val(Math.floor(lat_mc));
                $("input[name='lat_dms_s']").val(Math.round((lat_mc - Math.floor(lat_mc)) * 6000) / 100);
                $("input[name='lon_dms_d']").val(Math.floor(data.latlon.lon));
                $("input[name='lon_dms_m']").val(Math.floor(lon_mc));
                $("input[name='lon_dms_s']").val(Math.round((lon_mc - Math.floor(lon_mc)) * 6000) / 100);
                $("input[name='lat_dmm_d']").val(Math.floor(data.latlon.lat));
                $("input[name='lat_dmm_m']").val(Math.round(lat_mc * 10000) / 10000);
                $("input[name='lon_dmm_d']").val(Math.floor(data.latlon.lon));
                $("input[name='lon_dmm_m']").val(Math.round(lon_mc * 10000) / 10000);
                $("input[name='lat_ddd']").val(Math.round(data.latlon.lat * 100000) / 100000);
                $("input[name='lon_ddd']").val(Math.round(data.latlon.lon * 100000) / 100000);
            }
        });
    });
    $("#d_btn_mgrs_convert").on("click", function () {
        $('html').addClass('waiting');
        $.ajax({
            url: "https://services.jacq.org/jacq-services/rest/geo/convert?mgrs=" + $("input[name='mgrs']").val(),
            // url: "http://localhost/develop.jacq/services/rest/geo/convert?mgrs=" + $("input[name='mgrs']").val(),
            crossDomain: true,
            success: function (data) {
                $('html').removeClass('waiting');
                const lat_mc = (data.latlon.lat - Math.floor(data.latlon.lat)) * 60.0;
                const lon_mc = (data.latlon.lon - Math.floor(data.latlon.lon)) * 60.0;
                $("input[name='lat_dms_d']").val(Math.floor(data.latlon.lat));
                $("input[name='lat_dms_m']").val(Math.floor(lat_mc));
                $("input[name='lat_dms_s']").val(Math.round((lat_mc - Math.floor(lat_mc)) * 6000) / 100);
                $("input[name='lon_dms_d']").val(Math.floor(data.latlon.lon));
                $("input[name='lon_dms_m']").val(Math.floor(lon_mc));
                $("input[name='lon_dms_s']").val(Math.round((lon_mc - Math.floor(lon_mc)) * 6000) / 100);
                $("input[name='lat_dmm_d']").val(Math.floor(data.latlon.lat));
                $("input[name='lat_dmm_m']").val(Math.round(lat_mc * 10000) / 10000);
                $("input[name='lon_dmm_d']").val(Math.floor(data.latlon.lon));
                $("input[name='lon_dmm_m']").val(Math.round(lon_mc * 10000) / 10000);
                $("input[name='lat_ddd']").val(Math.round(data.latlon.lat * 100000) / 100000);
                $("input[name='lon_ddd']").val(Math.round(data.latlon.lon * 100000) / 100000);
                $("input[name=utm]").val(data.utm.string);
            }
        });
    });
    $("#d_btn_check").on("click", function () {
       // alert(geoname_user + $("input[name='lat_ddd']").val() + " / " + $("input[name='lon_ddd']").val());
       $('html').addClass('waiting');
       const url = "https://secure.geonames.org/countrySubdivisionJSON?username=" + geoname_user
           + "&radius=10&lat=" + $("input[name='lat_ddd']").val() + "&lng=" + $("input[name='lon_ddd']").val();
       $.getJSON("jacqServices_ptlp.php?type=raw&resource=" + encodeURIComponent(url), function(data) {
           $('html').removeClass('waiting');
           const countryCode = ($("select[name='nation'] option:selected").text()).split('(')[1].slice(0, -1);
           const full_province = $("select[name='province'] option:selected").text();
           let province;
           let adminCode1;
           if (full_province.indexOf('(') > -1) {
               province = full_province.split(' (')[0];
               adminCode1 = full_province.split(' (')[1].slice(0, -1);
           } else {
               province = full_province;
               adminCode1 = '';
           }
           if (data.countryCode == countryCode && (data.adminName1 == province || data.adminCode1 == adminCode1)) {
               $.alert("Country and Province seem to be OK", "coordinates checked");
           } else if (data.status && data.status.message) {
               $.alert(data.status.message, "coordinates checked");
           } else if (!data.adminName1) {
               $.alert("Country should be " + data.countryName + "<br>Province could not be found", "coordinates checked");
           } else if (data.countryCode == countryCode) {
               $.alert("Country seem to be OK<br>Province should be " + data.adminName1, "coordinates checked");
           } else {
               $.alert("Country should be " + data.countryName + "<br>Province should be " + data.adminName1, "coordinates checked");
           }
       });
       // $.ajax({
       //     url: url,
       //     crossDomain: true,
       //     success: function (data) {
       //         alert(data);
       //     }
       // })
    });
    $(".dialog_int").on("keydown", function (event) {
        const num = event.keyCode;
        if ((num > 95 && num < 106) || (num > 36 && num < 41) || num == 9) {
            return;
        }
        if (event.shiftKey || event.ctrlKey || event.altKey ||
            (num != 46 && num != 8 && isNaN(parseInt(String.fromCharCode(event.which))))) {
            event.preventDefault();
        }
    });
    $(".dialog_sint").on("keydown", function (event) {
        const num = event.keyCode;
        if ((num > 95 && num < 106) || (num > 36 && num < 41) || num == 9 || num == 173) {
            return;
        }
        if (event.shiftKey || event.ctrlKey || event.altKey ||
            (num != 46 && num != 8 && isNaN(parseInt(String.fromCharCode(event.which))))) {
            event.preventDefault();
        }
    });
    $(".dialog_float").on("keydown", function (event) {
        const num = event.keyCode;
        if ((num > 95 && num < 109) || (num > 36 && num < 41) || num == 9) {
            return;
        }
        if (event.shiftKey || event.ctrlKey || event.altKey ||
            (num != 46 && num != 8 && num != 190 && num != 188 && isNaN(parseInt(String.fromCharCode(event.which))))) {
            event.preventDefault();
        }
    });
    $(".dialog_sfloat").on("keydown", function (event) {
        const num = event.keyCode;
        if ((num > 95 && num < 109) || (num > 36 && num < 41) || num == 9 || num == 173) {
            return;
        }
        if (event.shiftKey || event.ctrlKey || event.altKey ||
            (num != 46 && num != 8 && num != 190 && num != 188 && isNaN(parseInt(String.fromCharCode(event.which))))) {
            event.preventDefault();
        }
    });
}

function quadrant2LatLon(quadrant, quadrant_sub)
{
    let latLon = new Array(2);

    const xx = quadrant.substr(quadrant.length - 2, 2);
    const yy = quadrant.substr(0, quadrant.length - 2);

    let xD = parseInt(((xx - 2) / 6) + 6);
    let xM = 0;
    let xS = Math.round((((((xx - 2) / 6) + 6) * 60) % 60) * 60);
    let yD = parseInt(((-yy / 10) + 56));
    let yM = 0;
    let yS = Math.round(((((-yy / 10) + 56) * 60) % 60) * 60);

    if (yD < 0) {  // error: out of bounds
        latLon[1] = '';
        latLon[0] = '';
    } else {
        if (quadrant_sub == 0 || quadrant_sub > 4) {
            xM += 5;
            xS += 0.001;  // to prevent rounding errors
            yM -= 3;
        } else {
            xM += ((quadrant_sub - 1) % 2) * 5;
            yM -= parseInt((quadrant_sub - 1) / 2) * 3;
            xS += (60 * 5) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
            yS -= (60 * 3) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
        }

        latLon[1] = xD + (xM / 60.0) + (xS / 3600.0);
        latLon[0] = yD + (yM / 60.0) + (yS / 3600.0);
    }
    return latLon;
}

function latLon2Quadrant(lat, lon)
{
    let quadrant = new Array(3);
    let sub;

    lat *= 60;
    lon *= 60;
    if (lon < 340 || lon >= 1340 || lat < 0 || lat > 3360) { // error: out of bounds
        quadrant[0] = '';
        quadrant[1] = '';
    } else {
        let xq = Math.floor((lon - 340) / 10);
        let yq = Math.floor((3360 - lat) / 6);
        let x_off = (lon - 340) - xq * 10;
        let y_off = (3360 - lat) - yq * 6;
        if (x_off < 5) {
            if (y_off < 3) {
                sub = 1;
            } else {
                sub = 3;
            }
        } else {
            if (y_off < 3) {
                sub = 2;
            } else {
                sub = 4;
            }
        }

        quadrant[0] = yq + ("00" + xq).slice(-2);
        quadrant[1] = sub;
    }
    return quadrant;
}

function confirmBoundingBox()
{
    const nationID = $("select[name='nation'] option:selected").val();
    const provinceID = $("select[name='province'] option:selected").val();
    const lat_d = parseInt($("input[name='lat_dms_d']").val());
    const lat_m = parseInt($("input[name='lat_dms_m']").val());
    const lat_s = parseFloat($("input[name='lat_dms_s']").val().replaceAll(',', '.'));
    const lon_d = parseInt($("input[name='lon_dms_d']").val());
    const lon_m = parseInt($("input[name='lon_dms_m']").val());
    const lon_s = parseFloat($("input[name='lon_dms_s']").val().replaceAll(',', '.'));
    const lat = Math.sign(lat_d) * (Math.abs(lat_d) + Math.round((lat_m / 60.0 + lat_s / 3600.0) * 100000) / 100000);
    const lon = Math.sign(lon_d) * (Math.abs(lon_d) + Math.round((lon_m / 60.0 + lon_s / 3600.0) * 100000) / 100000);
    let nation = true;
    let province = true;
    let errortext = "Coordinates seem to be outside of ";
    $('html').addClass('waiting');
    $.ajax({
        url: "https://services.jacq.org/jacq-services/rest/geo/checkBoundaries?nationID=" + nationID + "&provinceID=" + provinceID + "&lat=" + lat + "&lon=" + lon,
        // url: "http://localhost/develop.jacq/services/rest/geo/checkBoundaries?nationID=" + nationID + "&provinceID=" + provinceID + "&lat=" + lat + "&lon=" + lon,
        crossDomain: true,
        async: false,
        success: function (data) {
            $('html').removeClass('waiting');
            if (!data.error) {
                if (data.nation.nrBoundaries > 0) {
                    nation = data.nation.inside;
                }
                if (data.province.nrBoundaries > 0) {
                    province = data.province.inside;
                }
            }
        }
    });
    if (!nation || !province) {
        if (!nation && !province) {
            errortext += "country and province.";
        } else if (!nation) {
            errortext += "the country.";
        } else {
            errortext += "the province.";
        }
        return confirm(errortext);
    } else {
        return true;
    }
}

function alertBoundingBox()
{
    const nationID = $("select[name='nation'] option:selected").val();
    const provinceID = $("select[name='province'] option:selected").val();
    const lat_d = Math.abs(parseInt($("input[name='lat_deg']").val())) * (($("select[name='lat']").val() == 'S') ? -1 : 1);
    const lat_m = Math.abs(parseInt($("input[name='lat_min']").val().replaceAll(',', '.')));
    const lat_s = Math.abs(parseFloat($("input[name='lat_sec']").val().replaceAll(',', '.')));
    const lon_d = Math.abs(parseInt($("input[name='lon_deg']").val())) * (($("select[name='lon']").val() == 'W') ? -1 : 1);
    const lon_m = Math.abs(parseInt($("input[name='lon_min']").val()));
    const lon_s = Math.abs(parseFloat($("input[name='lon_sec']").val().replaceAll(',', '.')));
    const lat = Math.sign(lat_d) * (Math.abs(lat_d) + Math.round((lat_m / 60.0 + lat_s / 3600.0) * 100000) / 100000);
    const lon = Math.sign(lon_d) * (Math.abs(lon_d) + Math.round((lon_m / 60.0 + lon_s / 3600.0) * 100000) / 100000);
    let nation = true;
    let province = true;
    let errortext = "Coordinates seem to be outside of ";
    $('html').addClass('waiting');
    $.ajax({
        url: "https://services.jacq.org/jacq-services/rest/geo/checkBoundaries?nationID=" + nationID + "&provinceID=" + provinceID + "&lat=" + lat + "&lon=" + lon,
        // url: "http://localhost/develop.jacq/services/rest/geo/checkBoundaries?nationID=" + nationID + "&provinceID=" + provinceID + "&lat=" + lat + "&lon=" + lon,
        crossDomain: true,
        async: false,
        success: function (data) {
            $('html').removeClass('waiting');
            if (!data.error) {
                if (data.nation.nrBoundaries > 0) {
                    nation = data.nation.inside;
                }
                if (data.province.nrBoundaries > 0) {
                    province = data.province.inside;
                }
            }
        }
    });
    if (!nation || !province) {
        if (!nation && !province) {
            errortext += "country and province.";
        } else if (!nation) {
            errortext += "the country.";
        } else {
            errortext += "the province.";
        }
        alert(errortext);
    }
}

function setLatLonQuadSub()
{
    const lat_d = parseInt($("input[name='lat_dms_d']").val());
    const lon_d = parseInt($("input[name='lon_dms_d']").val());
    $("input[name='lat_deg']").val(Math.abs(lat_d));
    if ($("input[name='lat_dms_m']").val() !== 'NaN') {
        $("input[name='lat_min']").val($("input[name='lat_dms_m']").val());
    } else {
        $("input[name='lat_min']").val("");
    }
    if ($("input[name='lat_dms_s']").val() !== 'NaN') {
        $("input[name='lat_sec']").val($("input[name='lat_dms_s']").val().replaceAll(',', '.'));
    } else {
        $("input[name='lat_sec']").val("");
    }
    $("select[name='lat']").val((lat_d >= 0) ? 'N' : 'S');
    $("input[name='lon_deg']").val(Math.abs(lon_d));
    if ($("input[name='lon_dms_m']").val() !== 'NaN') {
        $("input[name='lon_min']").val($("input[name='lon_dms_m']").val());
    } else {
        $("input[name='lon_min']").val("");
    }
    if ($("input[name='lon_dms_s']").val() !== 'NaN') {
        $("input[name='lon_sec']").val($("input[name='lon_dms_s']").val().replaceAll(',', '.'));
    } else {
        $("input[name='lon_sec']").val("");
    }
    $("select[name='lon']").val((lon_d >= 0) ? 'E' : 'W');
    $("input[name='quadrant']").val($("input[name='quad']").val());
    $("input[name='quadrant_sub']").val($("input[name='quad_sub']").val());
    dialog_latLonQu.dialog("close");
}

function getLatLonQuadSub()
{
    const lat_d = Math.abs(parseInt($("input[name='lat_deg']").val())) * (($("select[name='lat']").val() == 'S') ? -1 : 1);
    const lat_m = Math.abs(parseInt($("input[name='lat_min']").val().replaceAll(',', '.')));
    const lat_s = Math.abs(parseFloat($("input[name='lat_sec']").val().replaceAll(',', '.')));
    const lon_d = Math.abs(parseInt($("input[name='lon_deg']").val())) * (($("select[name='lon']").val() == 'W') ? -1 : 1);
    const lon_m = Math.abs(parseInt($("input[name='lon_min']").val()));
    const lon_s = Math.abs(parseFloat($("input[name='lon_sec']").val().replaceAll(',', '.')));
    $("input[name='lat_dms_d']").val(lat_d);
    $("input[name='lat_dms_m']").val(lat_m);
    $("input[name='lat_dms_s']").val(lat_s);
    $("input[name='lon_dms_d']").val(lon_d);
    $("input[name='lon_dms_m']").val(lon_m);
    $("input[name='lon_dms_s']").val(lon_s);
    $("input[name='lat_dmm_d']").val(lat_d);
    $("input[name='lat_dmm_m']").val(lat_m + Math.round((lat_s / 60.0) * 10000) / 10000);
    $("input[name='lon_dmm_d']").val(lon_d);
    $("input[name='lon_dmm_m']").val(lon_m + Math.round((lon_s / 60.0) * 10000) / 10000);
    $("input[name='lat_ddd']").val(Math.sign(lat_d) * (Math.abs(lat_d) + Math.round((lat_m / 60.0 + lat_s / 3600.0) * 100000) / 100000));
    $("input[name='lon_ddd']").val(Math.sign(lon_d) * (Math.abs(lon_d) + Math.round((lon_m / 60.0 + lon_s / 3600.0) * 100000) / 100000));
    $("input[name='quad']").val($("input[name='quadrant']").val());
    $("input[name='quad_sub']").val($("input[name='quadrant_sub']").val());
}
