window.refreshTimeout;
window.refreshDelay = 500;


var COLOR_FIRST_STEP = 0.6;
var COLOR_SECOND_STEP = 2;
var COLOR_THIRD_STEP = 4;
var MAX_CLUSTER_POINTS = 1000;
var MAX_CLUSTER_RADIUS = 320;
var MIN_CLUSTER_RADIUS = 20;
var CLUSTER_RADIUS_STEP = 10;
var geocoder;
var map;
var centerLatitude = 43.9167;
var centerLongitude = 12.9000;
var maxZoom = 18;
var minZoom = 5;
var startZoom = 14;
var DEF_DISABLE_CLUSTERING_ZOOM = 20;
var DEF_CLUSTERING_RADIUS = 20;
var clusteringRadius = DEF_CLUSTERING_RADIUS;
var clusteringEnabled = true;
var currentPPEs = null;

// when the whole document has loaded call the init function
$(document).ready(init);

function init() {
  geocoder = new google.maps.Geocoder();

    // base layer
  var osmUrl = '/osm-tiles/{z}/{x}/{y}.png';
  var osmAttrib = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';

  var lyrOsm = new L.TileLayer(osmUrl, {
    minZoom: minZoom,
    maxZoom: maxZoom,
    attribution: osmAttrib
  });

    // set the starting location for the centre of the map
  var start = new L.LatLng(centerLatitude, centerLongitude);

    // create the map
  map = new L.Map('mapdiv', {		// use the div called mapdiv
    center: start,				// centre the map as above
    zoom: startZoom,			// start up zoom level
    layers: [lyrOsm]		    // layers to add
  });

  map.on({
    moveend: refreshAfterAWhile
  });

  $('#options-clustering').change(function () {
    if ($(this).is(':checked')) {
            // enable clustering
      console.log('Enable clustering');
      generateLayer(true);

      $('#options-clustering-bigger').show();
      $('#options-clustering-smaller').show();
    } else {
            // disable clustering
      console.log('Disable clustering');
      generateLayer(false);

      $('#options-clustering-bigger').hide();
      $('#options-clustering-smaller').hide();
    }
  });

  $('#options-clustering-bigger').click(function () {
        // re-enable "smaller" button
    $('#options-clustering-smaller').prop('disabled', false);
    clusteringRadius *= 2;
    if (clusteringRadius > MAX_CLUSTER_RADIUS) {
      clusteringRadius = MAX_CLUSTER_RADIUS;
            // disable button
      $('#options-clustering-bigger').prop('disabled', true);
    }
    generateLayer(true);
  });
  $('#options-clustering-smaller').click(function () {
        // re-enable "bigger" button
    $('#options-clustering-bigger').prop('disabled', false);
    clusteringRadius /= 2;
    if (clusteringRadius < MIN_CLUSTER_RADIUS) {
      clusteringRadius = MIN_CLUSTER_RADIUS;
      $('#options-clustering-smaller').prop('disabled', true);
    }
    generateLayer(true);
  });

  generateLayer(clusteringEnabled);

  if (clusteringEnabled) {
    $('#options-clustering').trigger('click');
  }
}

function generateLayer(clustered) {
  if (window.markers) {
    map.removeLayer(markers);
  }

  clusteringAtZoom = 1;
  clusteringEnabled = false;
  if (clustered) {
    var clusteringAtZoom = DEF_DISABLE_CLUSTERING_ZOOM;
    clusteringEnabled = true;
  }

  window.markers = new L.MarkerClusterGroup({
    disableClusteringAtZoom: clusteringAtZoom,
    singleMarkerMode: true,
    maxClusterRadius: clusteringRadius,
    polygonOptions: {
      color: 'green'
    },
    iconCreateFunction: function (cluster) {
      var avgPPE = getAvgPPE(cluster);

            // Change color on the basis of AveragePPE
      var c = ' marker-cluster-';
      var green = 0, red = 0, blue = 0;
      var pitch;

      var light = 255;
      if (avgPPE <= COLOR_FIRST_STEP) {
        green = 1;
        red = 1 / COLOR_FIRST_STEP * avgPPE;
        light = 127 + 128 * (1 / COLOR_FIRST_STEP) * avgPPE;
        c += 'small';
      } else if (avgPPE > COLOR_FIRST_STEP && avgPPE < COLOR_SECOND_STEP) {
        pitch = COLOR_SECOND_STEP - COLOR_FIRST_STEP;
        red = 1;
        green = - 1 / pitch * avgPPE + COLOR_SECOND_STEP/pitch;
        c += 'medium';
      } else if (avgPPE < COLOR_THIRD_STEP) {
        pitch = COLOR_THIRD_STEP - COLOR_SECOND_STEP;
        red = 1;
        blue = Math.min(1, (avgPPE - pitch) / pitch);
        c += 'large';
      } else {
        red = green = blue = 0;
        c += 'large';
      }

      red = Math.floor(red * 255);
      green = Math.floor(green * light);
      blue = Math.floor(blue * 255);

            // Change clusterSize on the basis of ZoomLevel?
      var clusterSize = 10;
      if (clusteringEnabled) {
        clusterSize = 10 + (90 *
                Math.log(Math.min(MAX_CLUSTER_POINTS, cluster.getChildCount()))
                / Math.log(MAX_CLUSTER_POINTS));
      }
      var innerSize = (clusterSize * 0.9);
      var margin = (clusterSize - innerSize) / 2;

      return new L.DivIcon({
        html: '<div style="'
                + 'background-color: rgba(' + red + ', ' + green + ', ' + blue + ', 0.9);'
                + 'width: ' + innerSize + 'px;'
                + 'height: ' + innerSize + 'px;'
                + 'margin-top: ' + margin + 'px;'
                + 'margin-left: ' + margin + 'px;"></div>',
        className: 'marker-cluster' + c,
        iconSize: new L.Point(clusterSize, clusterSize)
      });
    }
  });

  map.addLayer(markers);

  if (currentPPEs){
    showPPE(currentPPEs);
  }
  else{
    askForPPE();
  }
}

function refreshAfterAWhile() {
  clearTimeout(window.refreshTimeout);
  window.refreshTimeout = setTimeout(askForPPE, refreshDelay);
}

function askForPPE() {
  var data = 'bbox=' + map.getBounds().toBBoxString() + '&zoom_level=' + map.getZoom();
  $.ajax({
    url: '../ws/',
    dataType: 'json',
    data: data,
    success: function (ajxResponse) {
      currentPPEs = ajxResponse;
      showPPE(currentPPEs);
    }
  });
}

function showPPE(ajxresponse) {
  console.log('Data: ' + ajxresponse.features.length);

  markers.clearLayers();

  var data = [];
  for (var i = 0; i < ajxresponse.features.length; i++) {
    data.push(generateMarker(ajxresponse.features[i]));
  }
  markers.addLayers(data);
}

function getAvgPPE(cluster) {
  var childCount = cluster.getChildCount();

  var totalPPE = 0;
  for (var i = 0; i < childCount; i++) {
    totalPPE += parseFloat(cluster.getAllChildMarkers()[i].options.title);
  }

  return totalPPE / childCount;
}

function generateMarker(a) {
  var marker = L.marker(new L.LatLng(
        a.geometry.coordinates[1],
        a.geometry.coordinates[0]), {
          title: a.ppe
        });
  marker.bindPopup(a.ppe);
  marker.on('mouseover', function (e) {
    this.openPopup();
  });
  marker.on('mouseout', function (e) {
    this.closePopup();
  });
  return marker;
}

// make address geocoding, then center the map on the coordinates retrieved
function codeAddress() {
  var address = document.getElementById('address').value;
  geocoder.geocode({'address': address}, function (results, status) {
    if (status == google.maps.GeocoderStatus.OK) {
      map.panTo(new L.LatLng(
                results[0].geometry.location.lat(),
                results[0].geometry.location.lng()
            ));
    } else {
      alert('Sorry, no results for this query');
    }
  });
}
