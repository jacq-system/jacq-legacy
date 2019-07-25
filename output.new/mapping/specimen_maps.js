var startZoom = 3;
var map;

function addMarker(latitude, longitude, description)
{
	var marker = new GMarker(new GLatLng(latitude, longitude));
	
	GEvent.addListener(marker, 'click',
		function() {
			window.location.href = description;
			//marker.openInfoWindowHtml(description);
		}
	);
	
	map.addOverlay(marker);
}

function init()
{
   	if (GBrowserIsCompatible()) {
       	map = new GMap2(document.getElementById("map"));
		map.addControl(new GLargeMapControl()); // pan, zoom
		//map.addControl(new GSmallMapControl()); // pan, zoom
		map.addControl(new GMapTypeControl()); // map, satellite, hybrid
		map.addControl(new GOverviewMapControl()); // small overview in corner

		initDynamic();

		 //<input name="specimen" value="Poa+annua" type="hidden">
		 //<input name="lat" value="50" type="hidden">
		 //<input name="lon" value="-105" type="hidden">
		 //<input name="mark" value="48.00,-85.00,00924299;51.61,-101.58,00923498;"
   	}
}

function setCenter(centerLatitude, centerLongitude)
{
	var location = new GLatLng(centerLatitude, centerLongitude);
	if (centerLatitude == 0 && centerLongitude == 0)
	{
		startZoom = 2;
	}
   	map.setCenter(location, startZoom);
}

function addMarkers(markerString)
{
	var markers = markerString.split(";");
	for (var loop=0; loop < markers.length; loop++)
	{
	    var singleMarkerString = markers[loop];
		var elements = singleMarkerString.split(",");
		var latitude = elements[0];
		var longitude = elements[1];
		var specimenID = elements[2];
		
		//addMarker(latitude, longitude, '<a href="http://mobot.mobot.org/cgi-bin/search_vast?ssdp=' + specimenID + '">Click for detail</a>');
		addMarker(latitude, longitude, 'http://mobot.mobot.org/cgi-bin/search_vast?ssdp=' + specimenID);
	}
}

window.onload = init;
window.onunload = GUnload;

