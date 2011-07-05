// marc wick, october 2005

var isAltitude = false;
var isPopulation = false;
var showFlags = false;
var numRows = 50;
var username = "";

var isWikipedia = false;
var language = 'en';

var geoNameArray= new Array();
// keep displayed markers top open infoWin from list result 
var markerArray= new Array();
// we don't reload map if we just open an info window
var isOpeningInfoWindow = false;
// during movement of overview we temporarily disable mapupdate till the movement stops (moveend event)
var isMapUpdateDisabled = false;

// listener waiting for new position while moving
var movingListener;


// center of map
// used if the search result contains new center lat/lng/zoom values for the map
var centerLat;
var centerLng;
var centerZoom;
// don't recenter if user is moving map with mouse
var isCenterAndZoomEnabled = false;
var isCenterAndZoomRequired = false;




// Create a base icon for all of our markers that specifies the shadow, icon
// dimensions, etc.
var baseIcon = new GIcon();
baseIcon.shadow = "http://www.google.com/mapfiles/shadow50.png";
baseIcon.iconSize = new GSize(15,26);
baseIcon.shadowSize = new GSize(26, 26);
baseIcon.iconAnchor = new GPoint(7, 26);
baseIcon.infoWindowAnchor = new GPoint(9, 2);
baseIcon.infoShadowAnchor = new GPoint(18, 25);


// flag icon
var flagIcon = new GIcon();
flagIcon.shadow = "http://www.google.com/mapfiles/shadow50.png";
flagIcon.iconSize = new GSize(20,14);
flagIcon.shadowSize = new GSize(27, 14);
flagIcon.iconAnchor = new GPoint(9, 4);
flagIcon.infoWindowAnchor = new GPoint(9, 2);
flagIcon.infoShadowAnchor = new GPoint(18, 25);

function createMarker(point, geoName,pHtml, icon) {
  var marker = new GMarker(point,icon);

  GEvent.addListener(marker, "click", function() {
    openInfoWindowHtml(marker,geoName,pHtml);
  });

  GEvent.addListener(marker,"click",function () {isOpeningInfoWindow=true;});
  //GEvent.addListener(marker,"infowindowclose",function () {isOpeningInfoWindow=false;});
  GEvent.addListener(marker,"infowindowclose",function () {infowindowcloseHandler();});

  return marker;
}

function infowindowcloseHandler() {
  if (datasourceUserId ==0) {
     isOpeningInfoWindow=false;
  }
}

function openInfoWindowHtml(m,geoName,pHtml) {
      if (geoName != null && geoName.hasAddress) {
        var tab1 = new GInfoWindowTab("Info", pHtml);
        var tab2 = new GInfoWindowTab("Address", '<b>' + geoName.geoName + '<b></br><div id="addressTab" style="text-align:left;">loading ...</div>');
        var infoTabs = [tab1,tab2];
        m.openInfoWindowTabsHtml(infoTabs);
        jsonCallbackRequest('/servlet/geonames?srv=402&geonameId=' + geoName.geonameid + '&callback=loadAddressTab'); 
      } else {
        m.openInfoWindowHtml(pHtml);
      }
}

function GeoName() {
	this.featureClass = '';
        this.lat = 0;
	this.lng = 0;
        this.geonameid = 0;
        this.geoName = '';
        this.altitude = '';
        this.population = '';
        this.cc2='';
        this.tag='';
	
	this.getMarkerColor = function () {
	   return getMarkerColor(this.featureClass);
	}
	
	this.getTableRow = function(rowNr) {
	  var tableRow = '';
      if (rowNr % 2== 0) {
		 tableRow =  '<tr>';
      } else {
         tableRow =  '<tr class=\"odd\">';
      }
      tableRow += '<td nowrap>';
      if (rowNr<9) {
        tableRow += '&nbsp;';
      }
      tableRow += (rowNr+1) + 
      '&nbsp;<a href=javascript:showInfo(' + rowNr + ');><img src=/maps/markers/m10-'+ this.getMarkerColor() +'-' + this.featureClass  + 
        '.png border=0></a></td>';

      tableRow += '<td>' + this.geoName;
      if (this.wikipediaURL != null && this.wikipediaURL != '') {
        tableRow += '&nbsp;<a href="http://' + this.wikipediaURL + '" target=_new><img src="/img/20px-Wikipedia-logo.png" width="15" border="0" alt="wikipedia article"></a>';
      }
      tableRow += '</td></td>';
      tableRow += '<td>';
      if (showFlags) {
         tableRow += '<img src=/flags/s/'+ this.countryCode.toLowerCase() + '.png> ';
      }
      var countryName = this.countryName;
      if (this.countryName == '' && this.cc2 != null) {
         countryName = this.cc2;
      }
      tableRow += countryName + '</td></td>';
      if (isAltitude) {
         tableRow += '<td>' + this.altitude + ' m</td></td>';
      } else if (isPopulation) {
         tableRow += '<td>' + this.population + '</td></td>';
      }
      tableRow += '<td>' + this.featureCodeName + '</td>';
	  
/*	  
var clip = new ZeroClipboard.Client();
clip.setText(this.geonameid);
	  tableRow += '<td><a class="geonameid1">' + this.geonameid + '</a>'+clip.getHTML( 70, 20 )+'</td>';
*/
      tableRow += '<td><a href="javascript:selectGeoname(\'' + this.geonameid + '\')">Select '+this.geonameid+'</a></td>';
      tableRow += '<td><a href=javascript:showInfo(' + rowNr + ');><img src=/maps/markers/m10-'+ this.getMarkerColor() +'-' + this.featureClass  + '.png border=0> Show</a></td>';
	  tableRow += '<td>' + (Math.round(this.distToCenter*100)/100) + ' km</td></tr>';
      return tableRow;
   }

   this.getDMSLat = function () {
        var dir = "N";
        if (this.lat < 0) {
            dir = "S";
        }
        return dir + " " + getCoordinateAsString(this.lat);
    }

    this.getDMSLng = function() {
        var dir = "E";
        if (this.lng < 0) {
            dir = "W";
        }
        return dir + " " + getCoordinateAsString(this.lng);
    }



  this.getInfoWindowHtml = function(idx) {
      var html = '<div id=geonameWin style="text-align:left;">';
      if (this.wikipediaURL != null && this.wikipediaURL != '') {
         html += '<a href="http://' + this.wikipediaURL + '"><img src="/img/20px-Wikipedia-logo.png" border="0"  alt="wikipedia icon" align="middle"></a>&nbsp;';
      }

     if ('FR' == this.countryCode && (this.featureCode=='ADM1' || this.featureCode=='ADM2' || this.featureCode=='ADM4')) {
         html += '<a href="http://fr.wikipedia.org/wiki/Blasons_de_France"><img src="http://geotree.geonames.org/img/blasons/' + this.countryCode + '/' + this.geonameid + '-18.png" border="0" alt="coat of arms" align="middle"></a>&nbsp;';
      } else if ('FI' == this.countryCode && (this.featureCode=='ADM1' || this.featureCode=='ADM2' || this.featureCode=='ADM3')) {
         html += '<a href="http://fi.wikipedia.org/wiki/Wikipedia:Wikiprojekti_Vaakunat"><img src="http://geotree.geonames.org/img/blasons/' + this.countryCode + '/' + this.geonameid + '-18.png" border="0" alt="coat of arms" align="middle"></a>&nbsp;';
      }


      html = html + '<b>'+ this.geoName + '</b> <small>';
      if (this.altitude != null) {
        html = html + this.altitude + ' m';
      } else if (this.gtopo30 != -9999) {
        html = html + 'ca. ' + this.gtopo30 + ' m';
      }
      html = html + '<br/>';

      if (this.altName != null && this.altName != ''){
         var altNames = this.altName;
         if (altNames.length > 50) {
           altNames = altNames.substring(0,45) + ' ...';
         }
         html = html + '<a href="javascript:loadAlternateNamesForGeoNameId('+ this.geonameid +')" title="alternate names or name variants">' + altNames +'</a>';
         html = html + '<br/>';
      }

      if (this.countryCode != null) {
        var countryName = this.countryName;
        if (countryName == '' && this.cc2 != null) {
          countryName = this.cc2;
        }

        html = html + countryName;
        if (this.adminCode1Name != '') {
          html = html + '&nbsp;&raquo;&nbsp;' + this.adminCode1Name;
        }
        if (this.adminCode2Name != '') {
          html = html + '&nbsp;&raquo;&nbsp;' + this.adminCode2Name;
        }
        if (this.adminCode3Name != '') {
          html = html + '&nbsp;&raquo;&nbsp;' + this.adminCode3Name;
        }
        if (this.adminCode4Name != '') {
          html = html + '&nbsp;&raquo;&nbsp;' + this.adminCode4Name;
        }

      }


      html = html + '<br/>';
      html = html + this.featureCodeName;
      html = html + '<br/>';
      if (this.population > 0) {
         html = html + 'population : ' + this.population + '<br/>';
      }
      html = html + this.getDMSLat() + ' ' + this.getDMSLng() + '<br/>';
      html = html + this.lat + ' / ' + this.lng + '<br/>';
      html = html + 'GeoNameId : ' + this.geonameid + '<br/>';
/*
var clip = new ZeroClipboard.Client();
clip.setText(this.geonameid);

	  html = html + '<a class="geonameid1">' + this.geonameid + '</a>'+clip.getHTML( 70, 20 )+'';
*/
      html = html + '<a href="javascript:selectGeoname(\'' + this.geonameid + '\')">Select '+this.geonameid+'</a>';
	  if (this.tag != null && this.tag != '') {
        html += 'public tags:' + this.tag + '<br/>';
      }

      html = html + '</small>';
      html = html + '<br/>';
      if (this.discussionURL != null && this.discussionURL != '') {
        html = html + '<small><a href="' + this.discussionURL + '" title="discuss this toponym in the forum" target="_blank">discussion</a></small><br/><br/>';
      }

      if (this.urls != null && this.urls.length>0) {
         for (var i=0;i<this.urls.length;i++) {
           var url = this.urls[i];
           var anchor = "book";
           if (url.indexOf('diytravel') > -1) {
             anchor = "book with diytravel";
           } else if (url.indexOf('laterooms.com') >-1) {
             anchor = "book with laterooms";
           } else if (url.indexOf('ian.com')>-1) {
             anchor = "book with hotels.com";
           }
           html += '<small><a href="' + url + '" title="book" target="_blank">' + anchor + '</a></small><br/>';
         }
         html += '<br/>';
      }

      html = html + '<div id="infoWinMenuDiv">';
      html = html + '<a href=javascript:zoomIn('+ this.lat +',' + this.lng + ') title="center and zoom in">zoom</a>';
      html = html + '&nbsp;<a href="javascript:moveName('+idx+')" title="move">move</a>';
      html = html + '&nbsp;<a href="javascript:editName('+idx+')" title="edit">edit</a>';
      html = html + '&nbsp;<a href="javascript:showHistory('+ this.geonameid +')" title="show history">history</a>';
      html = html + '&nbsp;<a href="javascript:showTagForm('+ this.geonameid +')" title="tag this name">tag</a>';
      html = html + '&nbsp;<a href="javascript:showDeleteForm('+ this.geonameid +')" title="delete">delete</a>';
      html = html + '&nbsp;<a href="javascript:loadAlternateNamesForGeoNameId('+ this.geonameid +')" title="alternate names or name variants">alternate names</a>';

      html = html + '<br/>';
      html = html + '<a href="' + this.url + '" target=_blank >perma link</a>';
      html = html + '&nbsp;<a href="http://geotree.geonames.org/' + this.geonameid + '/" target=_blank>geotree</a>';
      html = html + '&nbsp;<a href="http://sws.geonames.org/' + this.geonameid + '/about.rdf" target=_blank >semantic web rdf</a>';

      if (this.featureCode == 'BCH' || this.featureCode == 'ST') {
        html = html + '<br/>';
        html = html + '<a href="javascript:addLine('+idx+')" title="edit">add polyline</a>';
      }

      html = html + '<br/>';
      html = html + '<a href="javascript:showHierarchy(' + this.geonameid + ',false)" title="part of">part of</a>';
      html = html + '&nbsp;<a href="javascript:showHierarchy(' + this.geonameid + ',true)" title="contains">contains</a>';


      html = html + '</div>';
      html = html + '</div>';
      return html;
    }

    this.setEditForm = function() {
       document.nameForm.geoname.value = this.geoName;
       if (this.altitude == null) {
         document.nameForm.altitude.value = '';
       } else {
         document.nameForm.altitude.value = this.altitude;
       }
       if (this.population == null) {
         document.nameForm.population.value = '';
       } else {
         document.nameForm.population.value = this.population;
       }
       if (this.cc2 == null) {
         document.nameForm.cc2.value = '';
       } else {
         document.nameForm.cc2.value = this.cc2;
       } 
       for (i=0;i< document.nameForm.classOption.length;i++) {
	   var fclass = document.nameForm.classOption[i].value;
         var isSelected = this.featureClass == fclass;
         if (isSelected) {
	       document.nameForm.classOption[i].selected = true;
         }
       }
       if (this.tag == null) {
         document.nameForm.tag.value = '';
       } else {
         document.nameForm.tag.value = this.tag;
       }

       if (this.geonameid == 0) {
         this.countryName = '';
       } else if (this.countryName == ' '  || this.countryName == '' || this.countryName == null) {
         this.countryName = 'no country';
       }
       document.getElementById("nameFormCountryName").innerHTML = this.countryName ;

       var adminName = this.adminCode1Name;
       if (this.geonameid == 0) {
         adminName = '';
       } else if (adminName == '') {
           if ("00" == this.adminCode1) {
             adminName = 'general (00)';
           } else {
             adminName = 'no admin1';
           }
       }
       document.getElementById("nameFormAdmin1Name").innerHTML = adminName;

       adminName = this.adminCode2Name;
       if (this.geonameid == 0) {
         adminName = '';
       } else if (adminName == '') {
           adminName = 'no admin2';
       }
       document.getElementById("nameFormAdmin2Name").innerHTML = adminName;

       adminName = this.adminCode3Name;
       if (this.geonameid == 0) {
         adminName = '';
       } else if (adminName == '') {
         adminName = 'no admin3';
       }
       document.getElementById("nameFormAdmin3Name").innerHTML = adminName;

       adminName = this.adminCode4Name;
       if (this.geonameid == 0) {
         adminName = '';
       } else if (adminName == '') {
           adminName = 'no admin4';
       }
       document.getElementById("nameFormAdmin4Name").innerHTML = adminName;


       var html = '';
       if (this.geonameid > 0) {
          // update existing record
	      html = html + '<a href="javascript:updateName(' + this.geonameid + ');" title="update name"><img src=/img/save.png border=0></a>';
          html = html + '&nbsp;<a href="javascript:refresh();" title="cancel edit"><img src=/img/cancel.png border=0></a>';
        } else {
          // insert new
          html = html + '<a href="javascript:saveNewName(' + this.lat + ',' + this.lng + ');" title="save new name"><img src=/img/save.png border=0></a>';
	      html = html + '&nbsp;<a href="javascript:cancel();" title="cancel insert"><img src=/img/cancel.png border=0></a>';
        }
        document.getElementById("nameFormButtons").innerHTML = html;
    }    
}

function parseGeoName(xmlMarker){
   var geoName = new GeoName();
   geoName.geonameid = parseInt(xmlMarker.getAttribute("id")) ;
   geoName.geoName = xmlMarker.getAttribute("name");
   geoName.altName = xmlMarker.getAttribute("altname");
   geoName.featureClass = xmlMarker.getAttribute("fc");
   geoName.featureCode = xmlMarker.getAttribute("c");
   geoName.featureCodeName = xmlMarker.getAttribute("fcn");
   geoName.lat = parseFloat(xmlMarker.getAttribute("lat"));
   geoName.lng = parseFloat(xmlMarker.getAttribute("lng"));
   geoName.altitude =  xmlMarker.getAttribute("elevation");
   geoName.gtopo30 = xmlMarker.getAttribute("gtopo30");
   geoName.population =  xmlMarker.getAttribute("population");
   geoName.countryName = xmlMarker.getAttribute("cn");
   geoName.countryCode = xmlMarker.getAttribute("cc");
   geoName.cc2 = xmlMarker.getAttribute("cc2");
   geoName.tag = xmlMarker.getAttribute("tag");
   geoName.adminCode1 = xmlMarker.getAttribute("admc1");
   geoName.adminCode1Name = xmlMarker.getAttribute("admn1");
   geoName.adminCode2 = xmlMarker.getAttribute("admc2");
   geoName.adminCode2Name = xmlMarker.getAttribute("admn2");
   geoName.adminCode3 = xmlMarker.getAttribute("admc3");
   geoName.adminCode3Name = xmlMarker.getAttribute("admn3");
   geoName.adminCode4 = xmlMarker.getAttribute("admc4");
   geoName.adminCode4Name = xmlMarker.getAttribute("admn4");
   geoName.url = xmlMarker.getAttribute("url");
   geoName.discussionURL = xmlMarker.getAttribute("discussionURL");
   geoName.wikipediaURL = xmlMarker.getAttribute("wikipediaURL");
   geoName.line = xmlMarker.getAttribute("line");
   geoName.hasAddress = xmlMarker.getAttribute("hasAddress");
   geoName.urls = new Array();
   var urls = xmlMarker.getElementsByTagName("url");
   for (var i=0;i<urls.length;i++) {
     geoName.urls.push(urls[i].firstChild.nodeValue);
   }
   return geoName;
}

// create table header for result table
function getResultTableHeader() {
    listHtml = '<table class=restable><tr><th></th><th>Name</th><th>country</th>';
    if (isAltitude) {
      listHtml = listHtml + '<th>altitude</th>';
    } else if (isPopulation) {
      listHtml = listHtml + '<th>population</th>';
    }
    listHtml = listHtml + '<th>feature</th>';
	listHtml = listHtml + '<th>GeoNameId</th>';
	listHtml = listHtml + '<th>Show</th>';
    listHtml = listHtml + '<th>km to center</th></tr>';
    return listHtml;
}

function sortByName(a,b) {
  if (a.geoName < b.geoName) return -1;
  if (a.geoName > b.geoName) return 1;
  return 0;
}

function sortByDistToCenter(a,b) {
  if (a.distToCenter < b.distToCenter) return -1;
  if (a.distToCenter > b.distToCenter) return 1;
  return 0;
}


function getResultTable() {
    if (!isAltitude && !isPopulation && document.searchForm.q.value == '') {
      geoNameArray.sort(sortByDistToCenter);
    }

    var listHtml = '';
    if (geoNameArray.length >= numRows) {
       listHtml = listHtml + '<font color=red>only '+ numRows + ' objects displayed, zoom in or deselect some features</font>';
    }

    // create table header for result table
    listHtml = listHtml + getResultTableHeader();
    
    for (var i = 0; i < geoNameArray.length; i++) {
      var geoName = geoNameArray[i];
      listHtml = listHtml + geoName.getTableRow(i);
    }   // end for 
    listHtml = listHtml + '<tr class=tfooter><td colspan=7>Export : csv <a href="javascript:exportGeodata(\'csv\');" title="export as character separated values file"><img src=/img/ico_file_csv.png border=0></a>';
    listHtml = listHtml + ' , png <a href="javascript:exportGeodata(\'png\');" title="export as png image"><img src=/img/ico_img.png border=0></a>';
    listHtml = listHtml + '</td></tr>';
    listHtml = listHtml + '</table>';
    return listHtml;
}

function setMarkers() {
    markerArray= new Array();
    // we keep a hashtable of the markers with the coordinates as key
    // to slighly shift markers sharing the same position
    var markersHT = new Array();
    bounds = map.getBounds();

    for (var i = 0; i < geoNameArray.length; i++) {
      var icon;
      var geoName = geoNameArray[i];
      var mcolor = geoName.getMarkerColor();
      if (showFlags) {
        icon = new GIcon(flagIcon);
        icon.image = '/flags/s/' + geoName.countryCode.toLowerCase() + '.png';
      } else {
        icon = new GIcon(baseIcon);
        icon.image = '/maps/markers/marker-' + mcolor + "-" + geoName.featureClass + "-15.png";
      }

      var keyHT = 'lat' + geoName.lat + '-lng' + geoName.lng;
      var lat = geoName.lat;
      var lng = geoName.lng;
      if (markersHT[keyHT] != null) {
         // two markers at same position, we randomly move the diplayed pos
         lat = lat + (signum(0.5-Math.random()))*(bounds.getNorthEast().lat() - bounds.getSouthWest().lat())/140*(0.4 + Math.random());
         lng = lng + (signum(0.5-Math.random()))*(bounds.getNorthEast().lng() - bounds.getSouthWest().lng())/140*(0.4 + Math.random());
      }
      markersHT[keyHT] = keyHT;                    

      var point = new GLatLng(lat,lng);
      var infoWinHtml = geoName.getInfoWindowHtml(i);
      var marker = createMarker(point,geoName,infoWinHtml,icon);
      marker.geoname = geoName;
      marker.geohtml = infoWinHtml;
      markerArray[i] = marker;
      map.addOverlay(marker);

	  
      // line
      if (geoName.line != null) {
         var polyline = createPolyline(geoName);
         map.addOverlay(polyline);
      }

    }   // end for 
}

function createPolyline(geoName) {
      if (geoName.line != null) {
        var polygon = new Array();
        var coordsArray = geoName.line.split(' ');
        for (i=0;i<coordsArray.length;i=i+2) {
           var point = new GLatLng(coordsArray[i], coordsArray[i+1]);
           polygon.push(point);
         }
         return new GPolyline(polygon);
      }
      return null;
}


function fulltextsearch() {
  if (document.searchForm.q.value != '' && document.searchForm.q.value.search(/[a-zA-Z]/)==-1) {
    var latlng = document.searchForm.q.value.replace(/^\s*|\s*$/g,"");;
    if (latlng.search(/ /) >0) {
      var latlngArray = latlng.split(' ');
    } else if (latlng.search(/,/) >0){
      var latlngArray = latlng.split(',');
    }
    if (latlng != null) {
      var lat,lng;
      lat = latlngArray[0];
      lng = latlngArray[1];
      map.panTo(new GLatLng(lat,lng));
      refresh();
    }
  } else  {
    searchTag = '';
    search();
  }
}

function search() {
  var q = document.searchForm.q.value;
  reset();
  document.searchForm.q.value = q;
  isCenterAndZoomEnabled = true;
  mapHandler();
}

function displaySearchCriteria() {
  var html = '';
  if (document.searchForm.q.value != '') {
    html = 'searching for "' + document.searchForm.q.value + '"';
    html = html + '<br/>';
  }
  if (searchTag != '') {
    if (searchTag == '@' + username) {
       html = 'displaying all tags for user '+ username + '.';
    } else if (plainTag != searchTag) {
       html = html + 'displaying tag ' + plainTag;
       html = html + '<br/><a href="javascript:deleteTag(\'' + plainTag + '\')">delete tag ' + plainTag + '</a>';
    }
    html = html + '<br/>Use the <a href="javascript:refresh();">refresh</a> button above to return to all features in area.';
    html = html + '<p>';
  }
  if (datasourceUserId >0) {
    html = 'displaying ' + datasourceName + ' id : ' + datasourceNameId;
    html += '<br/><a href="javascript:refresh();">refresh</a> to display all features in area';
    isOpeningInfoWindow =true;
  }

  if (!isMapUpdateDisabled) {
    document.getElementById("cockpit").innerHTML = html;
  }
}
 
function setCenter() {
  var centerPt = map.getCenter();
  if (isCenterAndZoomEnabled && centerPt.lng != centerLng && centerPt.lat != centerLat) {
    map.setCenter(new GLatLng(centerLat,centerLng),centerZoom);
  }
  isMapUpdateDisabled = false;
  isCenterAndZoomEnabled = false;
}

function setCenterPoint() {
  var centerPt = map.getCenter();
  var cName = new GeoName();
  cName.lat = centerPt.y;
  cName.lng = centerPt.x;
	
  var cPtHtml = "";
  cPtHtml = 'Map center : ' + cName.getDMSLat() + ' ' + cName.getDMSLng();
  document.getElementById("centerPt").innerHTML = cPtHtml;

  var tagzaniaHTML = '<a href="http://www.tagzania.com/near/' + cName.lat + '/' + cName.lng + '/" target=_blank>tagzania</a> ';
  document.getElementById("tagzania").innerHTML = tagzaniaHTML;

  url     = "http://www.geonames.org/kml/";
  url += map.getCenter().lat();
  url += '_' + map.getCenter().lng();
  url += '_' + map.getZoom();
  url += '.kml';
  document.getElementById("googleearth").href = url;

}


function getMapHandlerUrl() {
  if (datasourceUserId >0) {
     return "/servlet/geonames?srv=42&dsUserId=" + datasourceUserId +"&dsNameId=" + datasourceNameId +"&zoom="+ datasourceZoom;
  }

  var options = getHttpParams();
  return "/servlet/geonames?srv=2&" + options;
}


function mapHandler() {
   if (isWikipedia) {
      mapHandlerWikipedia();
   } else {
      mapHandlerGeonames();
   }
   setCenterPoint();
}

function mapHandlerGeonames() {
    if (!isOpeningInfoWindow && !isMapUpdateDisabled) {
        map.clearOverlays();
        var request = GXmlHttp.create();

        var listObj =  document.getElementById("list");
        listObj.innerHTML = 'loading ... <img src=/img/loading.gif>' ;

        var url = getMapHandlerUrl();
        request.open("GET", url, true);
        request.onreadystatechange = function() {
          if (request.readyState == 4) {
            var listHtml = '';
            if (request.status == 200) {
	            var xmlDoc = request.responseXML;

	            var cPt = xmlDoc.documentElement.getElementsByTagName("centerPt")[0];
	            if (cPt != null) {
	              centerLat = parseFloat(cPt.getAttribute("lat")); 
	              centerLng = parseFloat(cPt.getAttribute("lng")); 
	              centerZoom = parseFloat(cPt.getAttribute("zoom"));
	              
	              var cName = new GeoName();
	              cName.lat = centerLat;
	              cName.lng = centerLng;
	
	              var cPtHtml = "";
	              gtopo30 = cPt.getAttribute("gtopo30");
	              cPtHtml = 'Map center : ' + cName.getDMSLat() + ' ' + cName.getDMSLng() + ' ' + cPt.getAttribute("country");
	              gtopo30 = cPt.getAttribute("gtopo30");
	              if (gtopo30 != '') {
	                 cPtHtml = cPtHtml + ', ' + cPt.getAttribute("gtopo30") + 'm';
	              }
	              document.getElementById("centerPt").innerHTML = cPtHtml; 
	            }
	
                    var markers = xmlDoc.documentElement.getElementsByTagName("marker");
	            if (markers.length >0) {
	                geoNameArray.length=0;
	                for (var i = 0; i < markers.length; i++) {
	                   var geoName = parseGeoName(markers[i]);
	                   geoName.distToCenter = distance(geoName.lat, geoName.lng, centerLat,centerLng);
	                   geoNameArray[i] = geoName;
	                }
	                listHtml = getResultTable();
	                setMarkers();
	            } else  {
	              listHtml = 'no features found in map area. zoom out or move area';
	            }
	            
	            if (isCenterAndZoomEnabled) {
	                // the search result contained new center coords and new zoom
                       setCenter();
	            }
            } else {
              listHtml = '<font color=red>the server did not respond. (errorcode ' + request.status + ')</font>';
            }
            var listObj =  document.getElementById("list");
            listObj.innerHTML = listHtml;

            if (datasourceUserId >0) {
               // display info window for Geoname
               showInfo(0);
            }

          } // ready state
       } // function
       request.send(null);
    } else { 
      // map update disabled 
    }
    displaySearchCriteria();
}

function signum(x) {
    if (x==0.0){
       return 0;
    } else if (x > 0.0) {
      return 1;
    }
    return -1;
}

function showInfo(idx) {
    isOpeningInfoWindow=true;
    m = markerArray[idx];
    if (m != null) {
      openInfoWindowHtml(m,m.geoname,m.geohtml);
      isOpeningInfoWindow=true;
    }
}

function loadAddressTab(address) {
  var html =  '';
  html += '<table><tr><td>Address</td><td>' + address.address + '</td></tr>';
  html += '<tr><td>Postal Code</td><td>' + address.postalCode + '</td></tr>';
  html += '<tr><td>Place Name</td><td>' + address.placeName + '</td></tr>';
  html += '<tr><td>Country</td><td>' + address.countryName + '</td></tr>';
  html += '</table>';
  document.getElementById("addressTab").innerHTML = html ;
}


function moveName(idx) {
  if (movingListener != null) {
     GEvent.removeListener(movingListener);
     movingListener = null;
  } else {
     m = markerArray[idx];
     var html = 'move <b>' + m.geoname.geoName + '</b><br/>' ;
     html = html + ' &nbsp; 1. point mouse to location  (or <a href="javascript:latLngForm(\'updateWithLatLng()\')">gps</a>)<br/>';
     html = html + ' &nbsp; 2. save new coordinates<br/>';
     html = html + '<center><a href="javascript:cancel()"><img src=/img/cancel.png border=0></a></center>';
     document.getElementById("cockpit").innerHTML = html; 
     document.getElementById("codeFormDiv").style.visibility = 'hidden';
     document.getElementById("userTagsFrameDiv").style.visibility = 'hidden';
     isMapUpdateDisabled = true;

     // remove other listeners
     GEvent.clearListeners(map,'click');

	 movingListener = GEvent.addListener(map, 'click', function(overlay, point) {
	  if (point) {
	    map.clearOverlays();
	    setMarkers();
	    var marker = new GMarker(point);
	    map.addOverlay(marker);
	    m = markerArray[idx];
            var mpoint = new GLatLng(m.geoname.lat,m.geoname.lng);
	    var dist = distance(mpoint.lat(),mpoint.lng(),point.lat(),point.lng());
	    var points = [];
	    points.push(mpoint);
	    points.push(point);
	    map.addOverlay(new GPolyline(points,'#ff0000',5,0.9));
	    var html = 'moving <b>'+ m.geoname.geoName + '</b><br/>';
	    var newName = new GeoName();
	    newName.lat = point.y;
	    newName.lng = point.x;
	    html = html + 'new : ' + newName.getDMSLat() + newName.getDMSLng() + '<br/>';
	    html = html + 'old : ' + m.geoname.getDMSLat() + m.geoname.getDMSLng() + '<br/>';
	    html = html + 'distance : ' + (Math.round(dist*10)/10) + 'km<br/>';
	    html = html + "<a href=javascript:saveMovement("+m.geoname.geonameid +"," + point.y +","+ point.x +");><img src=/img/save.png border=0></a>";
	    html = html + "&nbsp;<a href=javascript:cancel();><img src=/img/cancel.png border=0></a>";
	    marker.openInfoWindowHtml(html);
	  }
	});
}
}

function saveMovement(geonameid,y,x) {
   document.getElementById("cockpit").innerHTML = 'saving new coordinates ...';
   map.clearOverlays();
   setMarkers();
   var url = "/servlet/geonames?srv=5&id="+geonameid +"&lat=" + y +"&lng="+ x;
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = '';
          document.getElementById("codeForm").style.display = 'inline';
          refresh();

        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error while saving:<br/>'+ errMessage + '</font>';
        }
     }
   } // function
   request.send(null);
}

function cancel() {
     document.getElementById("cockpit").innerHTML = '';
     document.getElementById("codeFormDiv").style.visibility = 'visible';
     document.getElementById("userTagsFrameDiv").style.visibility = 'visible';
     document.getElementById("editForm").style.visibility='hidden';
     document.getElementById("deleteForm").style.visibility='hidden';
     refresh();
}

function hideEditForm() {
  document.getElementById("editForm").style.visibility='hidden';
}

function editName(idx) {
     m = markerArray[idx];
     map.clearOverlays();
     setMarkers();
     document.getElementById("editForm").style.visibility='visible';
     m.geoname.setEditForm(); 
     changeCodeOption(m.geoname);
}

function insertWithLatLng(){
         var newName = new GeoName();
         newName.lat = document.getElementById("latlngForm").lat.value;
         newName.lng = document.getElementById("latlngForm").lng.value;

         if (isNaN(newName.lat) || isNaN(newName.lng)) { 
           alert ('please enter coordinates in decimal');
         } else {
           var point = new GLatLng (newName.lat,newName.lng);
           map.setCenter(point,15);
           isMapUpdateDisabled = false;
           mapHandler();
           document.getElementById("editForm").style.visibility='visible';
           newName.setEditForm();
         }
}

function updateWithLatLng(){
         var lat = document.getElementById("latlngForm").lat.value;
         var lng = document.getElementById("latlngForm").lng.value;
         saveMovement(m.geoname.geonameid,lat,lng);
}


function latLngForm(latLngFunction){
     html = '<form id=latlngForm><table>';
     html += '<tr><td>latitude:</td><td><input class="topmenu" type=text maxlength=10 size=10 name=lat></td></tr>';
     html += '<tr><td>longitude:</td><td><input class="topmenu" type=text maxlength=10 size=10 name=lng></td></tr>';
     html += '</table></form>';
     html += '<a href="javascript:' + latLngFunction + '">next</a>';
     document.getElementById("cockpit").innerHTML = html;
}



function insert() {
     if (username == '') {
       showLoginForm();
       return;
     }
     var html = 'inserting new name : <br/>' ;
     html = html + ' &nbsp; 1. point mouse to location (or <a href="javascript:latLngForm(\'insertWithLatLng()\')">gps</a>)<br/>';
     html = html + ' &nbsp; 2. enter form and save<br/>';
     html = html + '<center><a href="javascript:cancel()"><img src=/img/cancel.png border=0></a></center>';
     document.getElementById("cockpit").innerHTML = html; 
     document.getElementById("codeFormDiv").style.visibility = 'hidden';
     document.getElementById("userTagsFrameDiv").style.visibility = 'hidden';
     isMapUpdateDisabled = true;
     // remove other listeners
     GEvent.clearListeners(map,'click');

     movingListener = GEvent.addListener(map, 'click', function(overlay, point) {
       if (overlay) {
	    map.removeOverlay(overlay);
       } else if (point) {
    
          if (map.getZoom() < 14) {
             map.setCenter(point,14);
          }
	  
          map.clearOverlays();
	  setMarkers();
	  var marker = new GMarker(point);
	  map.addOverlay(marker);
	  var newName = new GeoName();
          newName.lat = point.y;
          newName.lng = point.x;
          document.getElementById("editForm").style.visibility='visible';
          newName.setEditForm();
       }
     });
}

function saveNewName(lat,lng) {
   if (document.nameForm.classOption.selectedIndex == 0) {
      alert('please select a feature class');
      return;
   }

   var request = GXmlHttp.create();
   request.open("GET", '/servlet/geonames?srv=8&' + getNameFormParams() + '&lat=' + lat + '&lng=' + lng, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = '';
          GEvent.removeListener(movingListener);
          movingListener = null;
          document.getElementById("codeFormDiv").style.visibility = 'visible';
          document.getElementById("userTagsFrameDiv").style.visibility = 'visible';
          document.getElementById("editForm").style.visibility='hidden';
          isMapUpdateDisabled = false;
          mapHandler();
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error while saving:<br/>'+ errMessage + '</font>';
          document.getElementById("editForm").style.visibility='visible';
        }
     }
   } // function
   request.send(null);
   document.getElementById("editForm").style.visibility='hidden';
   document.getElementById("cockpit").innerHTML = 'saving, please wait ...';
}

function showDeleteForm(idx) {
   if (username == '') {
       showLoginForm();
       return;
   }
  document.deleteForm.geonameid.value = idx;
  document.getElementById("deleteForm").style.visibility='visible';

}

function hideDeleteForm() {
  document.getElementById("deleteForm").style.visibility='hidden';
}

function deleteName() {
   hideDeleteForm();
   if (username == '') {
       showLoginForm();
       return;
   }

   document.getElementById("cockpit").innerHTML = 'deleting ...';
   map.clearOverlays();
   setMarkers();
   var url = '/servlet/geonames?srv=40&id=' + document.deleteForm.geonameid.value + '&comment=' + encodeURIComponent(document.deleteForm.comment.value);
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = '';
          document.getElementById("codeForm").style.display = 'inline';
          refresh();
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error while saving:<br/>'+ errMessage + '</font>';
        }
     }
   } // function
   request.send(null);
}

function changeCodeOption(geoName) {
	var selectedClass = document.nameForm.classOption.selectedIndex;
	document.nameForm.codeOption.length=0;
	var featureClass = '';
        var featureCode = '';
        if (geoName == null) {
           featureClass = document.nameForm.classOption[selectedClass].value;
        } else {
           featureClass = geoName.featureClass;
           featureCode = geoName.featureCode;
        }
        var codesForClass = currentClassHT[featureClass];
	document.nameForm.codeOption[0] = new Option("","",false,false);
	for (i=0;i<codesForClass.length;i++) {
	   var code = codesForClass[i];
           isSelected = code == featureCode;
	   document.nameForm.codeOption[i+1] = new Option(cnHT[code],code,false,isSelected);
	}
}

function getNameFormParams() {
   var geoName = new GeoName();
   geoName.geoName = document.nameForm.geoname.value;
   geoName.featureClass = document.nameForm.classOption[document.nameForm.classOption.selectedIndex].value;
   geoName.featureCode = document.nameForm.codeOption[document.nameForm.codeOption.selectedIndex].value;
   geoName.altitude = document.nameForm.altitude.value;
   geoName.population = document.nameForm.population.value;
   geoName.cc2= document.nameForm.cc2.value;
   geoName.tag= document.nameForm.tag.value;

   var options = 'name='+ encodeURIComponent(geoName.geoName) + '&fclass=' + geoName.featureClass + '&c='+geoName.featureCode + '&elevation=' + geoName.altitude + '&population=' + geoName.population + '&cc2=' + geoName.cc2 + '&tag=' + geoName.tag;
   return options;
}

function updateName(id) {

   if (document.nameForm.classOption.selectedIndex == 0) {
      alert('please select a feature class');
      return;
   }

   var request = GXmlHttp.create();
   request.open("GET", '/servlet/geonames?srv=9&' + getNameFormParams() + '&id=' + id, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = '';
          document.getElementById("editForm").style.visibility='hidden';
          document.getElementById("codeFormDiv").style.visibility = 'visible';
          document.getElementById("userTagsFrameDiv").style.visibility = 'visible';
          isOpeningInfoWindow = false;
          isMapUpdateDisabled = false;
          mapHandler();
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error while saving:<br/>'+ errMessage + '</font>';
        }
     }
   } // function
   request.send(null);
}

function bookmark() {
  window.location = '/servlet/geonames?srv=30&'+ getHttpParams();
}


function getHttpParams() {
    bounds = map.getBounds();

    var httpParams = '';

    if (isCenterAndZoomEnabled) {
      httpParams = 'recenter=1';
    } else {
      httpParams = "south="+ bounds.getSouthWest().lat() + 
         "&north=" + bounds.getNorthEast().lat() + 
         "&west=" + bounds.getSouthWest().lng() + 
         "&east="+ bounds.getNorthEast().lng();
    }
    
    var q = document.searchForm.q.value.replace(/^\s*|\s*$/g,"");
    document.searchForm.q.value = q;
    if (q.length> 0) {
       httpParams = httpParams + '&q=' + q;
    }
    if (searchTag.length> 0) {
       httpParams = httpParams + '&tags=' + searchTag;
    }

    
	if (document.codeForm.A.checked){
	  httpParams = httpParams + '&A=1';
	}
	if (document.codeForm.H.checked){
	  httpParams = httpParams + '&H=1';
	}
	if (document.codeForm.L.checked){
	  httpParams = httpParams + '&L=1';
	}
	if (document.codeForm.P.checked){
	  httpParams = httpParams + '&P=1';
	}
	if (document.codeForm.R.checked){
	  httpParams = httpParams + '&R=1';
	}
	if (document.codeForm.S.checked){
	  httpParams = httpParams + '&S=1';
	}
	if (document.codeForm.T.checked){
	  httpParams = httpParams + '&T=1';
	}
	if (document.codeForm.U.checked){
	  httpParams = httpParams + '&U=1';
	}
	if (document.codeForm.V.checked){
	  httpParams = httpParams + '&V=1';
	}
	
	var centerPt = map.getCenter();
	httpParams = httpParams + '&lat=' + centerPt.lat() + '&lng=' + centerPt.lng();

        httpParams = httpParams + '&zoom=' + map.getZoom();

	if (isAltitude) {
	  httpParams = httpParams + '&orderby=elevation';
	} else if (isPopulation) {
	  httpParams = httpParams + '&orderby=population';
	}

    var objCheckBoxes = document.forms['codeForm'].elements['code'];
    if (objCheckBoxes != null) {
      for(var i = 0; i < objCheckBoxes.length; i++) {
	   if (objCheckBoxes[i].checked) {
	     httpParams = httpParams + "&fcode="+objCheckBoxes[i].value;
        }
      }
    }
    httpParams = httpParams + '&maxRows=' + numRows;
    return httpParams;
}

function hideCodeForm() {
   document.getElementById("codeFormDiv").style.visibility = 'hidden';
}

function showCodeForm() {
   document.getElementById("codeFormDiv").style.visibility = 'visible';
}

function featureClassChange() {
   isAltitude = false;
   isPopulation = false;
   showFlags = false;
   mapHandler();
}

function setAltitude() {
  isAltitude = true;
  isPopulation = false;
  uncheckFeatureClasses();
  document.codeForm.T.checked = true;
  numRows = 10;
} 

function setPopulation() {
  isAltitude = false;
  isPopulation = true;
  uncheckFeatureClasses();
  document.codeForm.P.checked = true;
  numRows = 10;
}

function distance(lat1, lon1, lat2, lon2) {
	var radlat1 = Math.PI * lat1/180
	var radlat2 = Math.PI * lat2/180
	var radlon1 = Math.PI * lon1/180
	var radlon2 = Math.PI * lon2/180
	var theta = lon1-lon2
	var radtheta = Math.PI * theta/180
	var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
	dist = Math.acos(dist)
	dist = dist * 180/Math.PI
	dist = dist * 60 * 1.1515
	dist = dist * 1.609344;
	return dist
}

function getCoordinateAsString(coordinate) {
        var absCoord = Math.abs(coordinate);

        // add minimal value to make up for rounding differences
        absCoord = absCoord + 0.00001;

        var degree = Math.floor(absCoord);

        var fractionMinute = (absCoord - Math.floor(absCoord)) * 60;
        var minute = Math.floor(fractionMinute);
        var second = Math.floor((fractionMinute - minute) * 60);

        return degree + "&deg; " + minute + "' " + second + "''";
}

function exportGeodata(type) {
  var options = getHttpParams();
  if (type == 'csv') {
     window.open('/servlet/geonames?srv=7&' + options);
  } else if (type == 'png') {
     window.open('/servlet/geonames?srv=6&' + options);
  }
}

function reset() {
     if (movingListener != null) {
       GEvent.removeListener(movingListener);
       movingListener = null;
     }
     GEvent.clearListeners(map,'click');
     document.getElementById("editForm").style.visibility='hidden';

     hideLoginForm();
     hideDeleteForm();
     hideEmailForm();
     hideTagForm();

     clearDatasource();

     isMapUpdateDisabled = false;
     isOpeningInfoWindow = false;
     map.clearOverlays();

     document.searchForm.q.value = '';
     document.getElementById("cockpit").innerHTML = '';

     document.getElementById("codeFormDiv").style.visibility = 'visible';
     document.getElementById("userTagsFrameDiv").style.visibility = 'visible';

     hideAlternateNamesDiv();
     hideHierarchyDiv();
}

function refresh() {
     searchTag  = '';
     reset();
     mapHandler();
}

function logout() {
   document.getElementById("topmenulogin").innerHTML = 'login';
   var url = "/servlet/geonames?srv=13&xml=true";
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
   } // function
   request.send(null);
   document.getElementById("userTagsFrameDiv").innerHTML = '';
}

function showLoginForm() {
  document.getElementById("loginFormDiv").style.visibility='visible';
}

function hideLoginForm() {
  document.getElementById("loginFormDiv").style.visibility='hidden';
}

function login() {
   hideLoginForm();
   var url = "/servlet/geonames?srv=12&xml=true&username="+document.loginForm.username.value + '&password='+ document.loginForm.password.value;
   if (document.loginForm.rememberme.checked == true) {
     url = url + '&rememberme=1';
   }
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          username =  status.getAttribute("message");
          document.getElementById("topmenulogin").innerHTML = username;
          document.getElementById("cockpit").innerHTML = 'Welcome ' + username;
          var html = parseUserTags(xmlDoc);
          document.getElementById("userTagsFrameDiv").innerHTML = html;
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>login error :<br/>'+ errMessage + '</font>';
          document.getElementById("loginerror").innerHTML = '<font color=red>login error :<br/>'+ errMessage + '</font>';
          showLoginForm();
        }
     }
   } // function
   request.send(null);

}


function showEmailForm() {
   document.getElementById("emailFormDiv").style.visibility= 'visible';
}

function hideEmailForm() {
   document.getElementById("emailFormDiv").style.visibility= 'hidden';
}

function sendEmail() {
   var to = document.emailForm.to.value;
   var from = document.emailForm.from.value;
   var cc  = document.emailForm.cc.value;
   var subj = document.emailForm.subject.value;
   var txt = document.emailForm.text.value;

   if (to == '') {
      document.getElementById("emailError").innerHTML = '<font color=red>please enter a "to" email address.</font>';
      return;
   }
   if (from == '') {
      document.getElementById("emailError").innerHTML = '<font color=red>please enter a "from" email address.</font>';
      return;
   }
   document.getElementById("emailError").innerHTML = '';


   var url = '/servlet/geonames?srv=32&to=' + to + '&from=' + from + '&cc=' + cc + '&subject=' + subj + '&text=' + txt +'&' + getHttpParams();
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = 'email sent successfully';
          // clear error messages
          document.getElementById("emailError").innerHTML = '';
        } else {
          // error
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error sending email:<br/>'+ errMessage + '</font>';
          document.getElementById("emailError").innerHTML = '<font color=red>error sending email:<br/>'+ errMessage + '</font>';
          showEmailForm();
        }

     }
   } // function
   request.send(null);

   hideEmailForm();
}

function showHistory(id) {
   var url = "/servlet/geonames?srv=50&id="+id;
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var htmlDoc = request.responseText;
        var listObj =  document.getElementById("list");
        listObj.innerHTML = htmlDoc;
     }
   } // function
   request.send(null);
}


function zoomIn(lat,lng) {
  var zoom = map.getZoom();
  zoom = Math.min(16,zoom+1);
  zoom = Math.max(14,zoom);
  map.setCenter(new GLatLng(lat,lng),zoom);
  map.clearOverlays();
  setMarkers();
}

function syncOverview(){
       map2.clearOverlays();

       if (Math.abs(map.getZoom() - map2.getZoom()) > 7) {
         var overviewIcon = new GIcon(baseIcon);
         overviewIcon.iconSize = new GSize(12,20);
         overviewIcon.shadowSize = new GSize(20,20 );
         overviewIcon.iconAnchor = new GPoint(6,20);
         overviewIcon.image = '/maps/markers/marker12.png';
         map2.addOverlay(new GMarker(map.getCenter(),overviewIcon));
       } else {
         var bounds = map.getBounds();

         var points = [];
         points[0] = bounds.getSouthWest();
         points[1] = new GLatLng(bounds.getNorthEast().lat(),bounds.getSouthWest().lng());
         points[2] = bounds.getNorthEast();
         points[3] = new GLatLng(bounds.getSouthWest().lat(),bounds.getNorthEast().lng());
         points[4] = points[0];
         map2.addOverlay(new GPolyline(points));
       }
}

//******************************************************
//Datasource
//******************************************************

var datasourceUserId = 0;
var datasourceNameId = '';
var datasourceName = '';
var datasourceZoom = 2;

function setDatasource(pUserId,pNameId,pSourceName,pZoom) {
   datasourceUserId = pUserId;
   datasourceNameId = pNameId;
   datasourceName = pSourceName;
   datasourceZoom = pZoom;
   hideCodeForm();

   //enable center and zoom
   isCenterAndZoomEnabled = true;
}

function clearDatasource() {
  datasourceUserId = 0;
  datasourceNameId = '';
  datasourceName = '';
}

// codes

function jsonCallbackRequest(request) {
  // Create a new script object
  aObj = new JSONscriptRequest(request);
  // Build the script tag
  aObj.buildScriptTag();
  // Execute (add) the script tag
  aObj.addScriptTag();
}



function updateCode(url) {
    document.getElementById("cockpit").innerHTML = 'saving';
    var request = GXmlHttp.create();

    request.open("GET", url, true);
    request.onreadystatechange = function() {
      if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          cancelCodeUpdate();
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error while saving:<br>'+ errMessage + '</font>';
        }
      }
    } // function
    request.send(null);

}

function getAdminCodes1(jData) {
 getAdminCodes(1,jData);
}

function getAdminCodes2(jData) {
 getAdminCodes(2,jData);
}

function getAdminCodes3(jData) {
 getAdminCodes(3,jData);
}

function getAdminCodes4(jData) {
 getAdminCodes(4,jData);
}


function getAdminCodes(level, jData) {
  var html = '<form name="adminCodesForm"><select name="admincode">';
  html += '<option value="">no admin div - or unknown (null)</option>';
  if (level==1) {
    html += '<option value="00">general, countrywide (00)</option>';
  }
  for (var i = 0; i < jData.geonames.length; i++) {
    html += '<option value=';

    var admCode = jData.geonames[i].adminCode1;
    var code = jData.geonames[i].countryCode + '.'
    code += jData.geonames[i].adminCode1;
    if (level==2) {
      code += '.' + jData.geonames[i].adminCode2;
      admCode = jData.geonames[i].adminCode2;
    }
    if (level==3) {
      code += '.' + jData.geonames[i].adminCode3;
      admCode = jData.geonames[i].adminCode3;
    }
    if (level==4) {
      code += '.' + jData.geonames[i].adminCode4;
      admCode = jData.geonames[i].adminCode4;
    }
    html += code;
    html += ' >';
    html += jData.geonames[i].name;
    html += ' (' + admCode + ')';
    html += '</option>';
  }
  html += '</select>';
  html += '<p/>';
  html = html + '<a href="javascript:updateAdminCode(\'' + level + '\');" title="update name"><img src=/img/save.png border=0></a>';
  html = html + '&nbsp;<a href="javascript:cancelCodeUpdate();" title="cancel insert"><img src=/img/cancel.png border=0></a>';
  html += '</form>';

  document.getElementById("codeEditFormDiv").innerHTML = html;
  document.getElementById("nameFormButtons").innerHTML = '';
}

function updateAdminCode(level) {
  updateCode('/servlet/geonames?srv=37&type=XML&geonameId=' + m.geoname.geonameid + '&adminCode' + level + '=' + document.adminCodesForm.admincode.value + '&featureCode=ADM' + level);
}

function getCountryCodes(jData) {
  if (jData == null) {
    alert("There was a problem parsing search results.");
    return;
  }

  var html = '<form name="countryCodesForm"><select name="countrycode">';
  html += '<option value="">no country</option>';
  for (var i = 0; i < jData.geonames.length; i++) {
    html += '<option value=';
    html += jData.geonames[i].code;
    html += ' >';
    html += jData.geonames[i].name;
    html += '</option>';
  }
  html += '</select>';
  html += '<p/>';
  html = html + '<a href="javascript:updateCountryCode();" title="update name"><img src=/img/save.png border=0></a>';
  html = html + '&nbsp;<a href="javascript:cancelCodeUpdate();" title="cancel insert"><img src=/img/cancel.png border=0></a>';
  html += '</form>';

  document.getElementById("codeEditFormDiv").innerHTML = html;
  document.getElementById("nameFormButtons").innerHTML = '';
}


function updateCountryCode() {
  updateCode('/servlet/geonames?srv=36&type=xml&geonameId=' + m.geoname.geonameid + '&country=' + document.countryCodesForm.countrycode.value);
}


function editCountry() {
   document.getElementById("editForm").style.visibility='hidden';
   document.getElementById("codeEditFormDiv").style.visibility='visible';
   jsonCallbackRequest('/servlet/geonames?srv=162&callback=getCountryCodes'); 
}

function cancelCodeUpdate() {
  document.getElementById("codeEditFormDiv").innerHTML = '';
  document.getElementById("codeEditFormDiv").style.visibility='hidden';
  refresh();
}


function editAdmin(level) {
   document.getElementById("editForm").style.visibility='hidden';
   document.getElementById("codeEditFormDiv").style.visibility='visible';
   var adm = '';
   if (level > 1) {
     adm += '&adminCode1=' + m.geoname.adminCode1;
     if (level >2) {
       adm += '&adminCode2=' + m.geoname.adminCode2;
       if (level >3) {
          adm += '&adminCode3=' + m.geoname.adminCode3;
       }
     }
   }
   jsonCallbackRequest('/servlet/geonames?srv=163&country=' + m.geoname.countryCode + adm + '&callback=getAdminCodes'+ level+ '&featureCode=ADM' + level);
}

//*****************************************************


function addLine(idx) {
  if (movingListener != null) {
     GEvent.removeListener(movingListener);
     movingListener = null;
  } else {
     m = markerArray[idx];
     var html = 'move <b>' + m.geoname.geoName + '</b><br/>' ;
     html = html + ' &nbsp; 1. point mouse to new location <br/>';
     html = html + ' &nbsp; 2. <a href="javascript:saveLine()">save new line</a><br/><br/>';
     html = html + '<center><a href="javascript:saveLine()"><img src=/img/save.png border=0></a> &nbsp; <a href="javascript:cancel()"><img src=/img/cancel.png border=0></a></center>';
     document.getElementById("cockpit").innerHTML = html; 
     document.getElementById("codeFormDiv").style.visibility = 'hidden';
     document.getElementById("userTagsFrameDiv").style.visibility = 'hidden';
     isMapUpdateDisabled = true;

     // remove other listeners
     GEvent.clearListeners(map,'click');

     m.geoname.polygon = new Array();
     movingListener = GEvent.addListener(map, 'click', function(overlay, point) {
          if (!overlay) {
            if (m.geoname.polygon.length==0) {
              map.addOverlay(new GMarker(point));
            }
            m.geoname.polygon.push(point);
            
            map.addOverlay(new GPolyline(m.geoname.polygon));
          }
     });
}
}


function saveLine() {
   document.getElementById("cockpit").innerHTML = 'saving new coordinates ...';
   map.clearOverlays();
   setMarkers();
   var line = '';
   for (i=0;i<m.geoname.polygon.length;i++) {
     line+= m.geoname.polygon[i].lat() + ' ' + m.geoname.polygon[i].lng() + ' ';
   }

   var url = "/servlet/geonames?srv=39&id="+ m.geoname.geonameid +"&line=" + encodeURIComponent(line);
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = '';
          document.getElementById("codeForm").style.display = 'inline';
          refresh();

        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error while saving:<br/>'+ errMessage + '</font>';
        }
     }
   } // function
   request.send(null);
}

//******************************************************
// Usertags
//******************************************************

var userTags = new Array();
var searchTag = '';
var plainTag = '';

function searchForTag(tag , pUsername) {
  searchTag = tag;
  plainTag  = tag;
  if (pUsername != '') {
    searchTag = searchTag + '@' + pUsername;
  }

  search();
}

function showTagForm(id) {
   if (username == '') {
       showLoginForm();
       return;
   }
   var url = '/servlet/geonames?srv=60&id='+ id;
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = '';
          document.tagForm.tags.value = status.getAttribute("message");
          document.tagForm.geonameid.value = id;
          document.getElementById("tagFormDiv").style.visibility='visible';
 
          // prepare most favorite tags as suggestion
          if (userTags.length >0) {
             var sugHtml = 'your favorite tags : ';
             var numSug = Math.min(userTags.length,6);
             for (var i = 0; i < numSug; i++) {
                var tagName = userTags[i].tagName;
                sugHtml = sugHtml + '<a href="javascript:chooseTagSuggestion(\'' + tagName + '\')">' + tagName + '</a>&nbsp;&nbsp;';
             }
             document.getElementById("tagFormSuggestDiv").innerHTML = sugHtml;
          } 
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error getting tags:<br/>'+ errMessage + '</font>';
        }
     }
   } // function
   request.send(null);
}

function chooseTagSuggestion(tagName) {
  var currentTags = document.tagForm.tags.value + ' ';
  // only add tag if not already present
  if (currentTags.indexOf(tagName + ' ') == -1) {
    document.tagForm.tags.value = document.tagForm.tags.value + ' ' + tagName;
  }
}

function hideTagForm() {
  document.getElementById("tagFormDiv").style.visibility='hidden';
}

function saveTags() {
   var url = '/servlet/geonames?srv=61&id='+ document.tagForm.geonameid.value + '&tags=' + document.tagForm.tags.value;
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = 'tags saved successfully';
          document.getElementById("userTagsFrameDiv").innerHTML = parseUserTags(xmlDoc);
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error updating tags:<br/>'+ errMessage + '</font>';
        }
     }
   } // function
   request.send(null);
   hideTagForm();
}

function deleteTag(tag) {
   if (!confirm('Do your really want to delete the tag "' + tag + '"')) {
      return;  
   }

   var url = '/servlet/geonames?srv=62&tags=' + tag;
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var status = xmlDoc.documentElement.getElementsByTagName("status")[0];
        if (status.getAttribute("value") == 0) {
          document.getElementById("cockpit").innerHTML = 'tag deleted successfully';
          document.getElementById("userTagsFrameDiv").innerHTML = parseUserTags(xmlDoc);
        } else {
          var errMessage = status.getAttribute("message");
          document.getElementById("cockpit").innerHTML = '<font color=red>error deleting tag:<br/>'+ errMessage + '</font>';
        }
     }
   } // function
   request.send(null);
}

function UserTag (xml) {
   this.tagName =  xml.getAttribute("tagName");
   this.tagCount = parseInt(xml.getAttribute("tagCount"));
}

function userTagsToHtml() {
     var html = '<b>' + username +'\'s tags </b>: <br/>';
     var countAllTags = 0;
     for (var i = 0; i < userTags.length; i++) {
        var tag = userTags[i];
        html = html + '&nbsp;&nbsp;&raquo;&nbsp;&nbsp;<a href="javascript:searchForTag(\''+ tag.tagName +'\',\''+ username + '\')">' + tag.tagName + '</a> (' + tag.tagCount +')<br/>';
        countAllTags = countAllTags + tag.tagCount;
     }
     html = html + '&nbsp;&nbsp;&raquo;&nbsp;&nbsp;<a href="javascript:searchForTag(\'\',\''+ username + '\')">all your tags</a><br/>';
     return html;
}

function parseUserTags(xmlDoc) {
   var tags = xmlDoc.documentElement.getElementsByTagName("tag");
   var html = '';
   if (tags.length > 0) {
       userTags.length = 0;
       for (var i = 0; i < tags.length; i++) {
         var tag = new UserTag(tags[i]);
         userTags[i] = tag;
       }
       html = userTagsToHtml();
   } else {
       html = 'no tags for user '+ username;
   }
   return html;
}

function loadUserTags() {
   var url = '/servlet/geonames?srv=63';
   var request = GXmlHttp.create();
   request.open("GET", url, true);
   request.onreadystatechange = function() {
     if (request.readyState == 4) {
        var xmlDoc = request.responseXML;
        var tags = xmlDoc.documentElement.getElementsByTagName("tag");
        var html = parseUserTags(xmlDoc);
        document.getElementById("userTagsFrameDiv").innerHTML = html;
     }
   } // function
   request.send(null);
}



function mapquest() {
  // google zoom from 17 - 0
  var zoom = map.getZoom();
  // mapquest zoom from 0 - 9
  zoom = 10 - zoom*0.8;
  zoom = Math.round(Math.max(zoom,2));
  zoom = Math.min(zoom,9);
  var centerPt = map.getCenter();
  window.open('http://www.mapquest.com/maps/map.adp?zoom=' + zoom + '&latlongtype=decimal&latitude=' + centerPt.y + '&longitude=' + centerPt.x);
}


function loadPage() {
    syncOverview();

    //map1 on move
    GEvent.addListener(map, "move", function() {
       if(map2.mymap.movedByOther)return
       map2.mymap.movedByOther = true
       map2.setCenter(map.getCenter(),map2.getZoom());
       map2.mymap.movedByOther = false
       syncOverview();
    });

    //map2 on move
    GEvent.addListener(map2, "move", function() {
       if(map.mymap.movedByOther)return
       isMapUpdateDisabled = true;
       var listObj =  document.getElementById("list");
       listObj.innerHTML = 'waiting for overview movement to stop ...' ;
       map.mymap.movedByOther = true
       map.setCenter(map2.getCenter(),map.getZoom());
       map.mymap.movedByOther = false
       syncOverview();
    });
    // we wait for the moveend event to update map
    GEvent.addListener(map2, "moveend", function() {
       if(map.mymap.movedByOther)return
       isMapUpdateDisabled = false;
       mapHandler();
    });
    
    mapHandler();

    if (username != '') {
      loadUserTags();
    }
}

window.onload = loadPage;




