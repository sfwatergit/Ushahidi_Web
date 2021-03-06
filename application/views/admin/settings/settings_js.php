/**
 * JavaScript for the map settings page
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://github.com/ushahidi/Ushahidi_Web
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

$(document).ready(function() {
	
	var default_map = '<?php print $default_map ?>';
	var all_maps = <?php echo $all_maps_json; ?>;
	
	// Current zoom level - updated on each zoom updated
	var currentZoom = <?php echo $default_zoom; ?>;
	
	<?php echo map::layers_js(TRUE); ?>
	var mapConfig = {
		zoom: currentZoom,
		center: {
			latitude: <?php echo $default_lat; ?>,
			longitude: <?php echo $default_lon; ?>
		},
		mapControls: [
		    new OpenLayers.Control.Navigation(),
		    new OpenLayers.Control.MouseDefaults(),
		    new OpenLayers.Control.PanZoom(),
		    new OpenLayers.Control.ArgParser(),
		    new OpenLayers.Control.MousePosition(),
		    new OpenLayers.Control.LoadingPanel({minSize: new OpenLayers.Size(573, 366)})
		],
		
		// Base layers
		baseLayers: <?php echo map::layers_array(TRUE); ?>,
		
		// Detect map zoom events
		detectMapZoom: true
	};
	
	var map = new Ushahidi.Map('map', mapConfig);
	map.addLayer(Ushahidi.DEFAULT);
	
	// Marker position changed - for the map center
	map.register("markerpositionchanged", function(coords){
		$("#default_lat").attr("value", coords.latitude);
		$("#default_lon").attr("value", coords.longitude);
	});
	
	// Zoom changed
	map.register("zoomchanged", function(zoom) {
		$("select#default_zoom").val(zoom);
		$("select#default_zoom").trigger("click");
	});
	
	// Zoom slider detection
	$('select#default_zoom').selectToUISlider({
		labels: 5,
		sliderOptions: {
			change:function(e, ui) {
				var new_zoom = parseInt($("#default_zoom").val());
				if (currentZoom !== new_zoom) {
					currentZoom = new_zoom;
					$('#zoom_level').html('"' + new_zoom + '"');
					map.trigger("zoomchanged", new_zoom);
				}
			}
		}
	}).hide();
		
	// Detect country dropdown change, then zoom to selected country
	$('#default_country').change(function(){
		address = $('#default_country :selected').text();
		
		var geocoder = new google.maps.Geocoder();
		
		if (geocoder) {
			geocoder.geocode({ 'address': address },
				function(results, status) {
					if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
						alert(address + " not found");
					} else if (status == google.maps.GeocoderStatus.OK) {
						
						// Get the lat/lon from the result; accuracy of the map center is off 
						// because the lookup address does not include the name of the capital city
						var point = results[0].geometry.location;
						map.trigger("mapcenterchanged", {latitude: point.lat(), longitude: point.lng()});
					}
			});
		}
	});

	// detect map provider dropdown change
	$('#default_map').change(function(){					
		var selected_map = $(":selected", this).text();
		for (var i in all_maps) {
			if (all_maps[i].title == selected_map) {
				
				// Trigger baselayer changed event
				map.trigger("baselayerchanged", selected_map);

				if (all_maps[i].api_signup) {
					$('#api_link').attr('href', all_maps[i].api_signup);
					$('#api_link').attr('target', '_blank');
				} else {
					$('#api_link').attr('href', 'javascript:alert(\'Your current selection does not require an API key!\')');
					$('#api_link').attr('target', '_top');
				}
				
				if (all_maps[i].openlayers == 'Google') {
					$("#api_div_google").show();
				} else {
					$("#api_div_google").hide();
				}
			}
		}
	});
		
});

// Retrieve Cities From Geonames DB (Ajax)
function retrieveCities() {
	var selected = $("#default_country option[selected]");
	country = selected.val();
	if (!country || country =='') {
		alert('Please select a country from the dropdown');
	} else {
		$('#cities_loading').html('<img src="<?php echo url::file_loc('img')."media/img/loading_g.gif"; ?>">');
		$.getJSON("<?php echo url::site() . 'admin/settings/updateCities/' ?>" + country,
			function(data){
				if (data.status == 'success'){
					$('#city_count').show();
					$('#city_count').html(data.response);
					$('#cities_loading').html('');
				} else	{
					alert(data.response);
				}
				$('#cities_loading').html('');
		  	}, "json");
	}
}
