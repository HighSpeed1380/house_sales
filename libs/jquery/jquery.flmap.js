
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: JQUERY.FLMAP.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

/**
*
* jQuery Google Map plugin by Flynax 
*
**/
(function($){
	var base;
	
	$.flMap = function(el, options){
		base = this;
		
		// custom variable/object
		base.points = new Array();
		geocoder = new google.maps.Geocoder();
		base.map;
		base.infoWindow;
		base.bounds = new Array();
		base.markers = new Array();
		base.infoWindows = new Array();
		base.localSearch;
		base.placesService;
		base.lastPlace = false;
		base.lastPlaceIcon = false;
		base.loadedPlaces = new Array();
		base.placesMarkers = new Array();
		base.lastPlaceInfoWindow = false;
		base.placeInfoWindowContentTpl = '<div style="font-weight: bold;" class="dark_12">{name}</div><div class="dark_12">{address}</div><div style="padding: 5px 0 10px 0;" class="dark_12">{phone}</div><div>{website}</div>';
		base.areaOpened = true;
		//base.checkedIndex = 0; ???
		base.letters = [['A'],['B'],['C'],['D'],['E'],['F'],['G'],['H'],['I'],['J'],['K'],['L'],['M'],['N'],['O'],['P'],['Q'],['R'],['S'],['T'],['U'],['V'],['W'],['X'],['Y'],['Z']];
		
		//base.pointKey = ['Xb', 'bc', 'ac', '$b', 'rd'];
		base.getKeyPattern = new RegExp('q=([^&][\\w-]*)');

		// icons		
		base.icons = [
			rlConfig['libs_url']+'jquery/markers/orange-20.png',
			rlConfig['libs_url']+'jquery/markers/yellow-20.png',
			rlConfig['libs_url']+'jquery/markers/green-20.png',
			rlConfig['libs_url']+'jquery/markers/blue-20.png',
			rlConfig['libs_url']+'jquery/markers/gray-20.png',
			rlConfig['libs_url']+'jquery/markers/white-20.png',
			rlConfig['libs_url']+'jquery/markers/dark-20.png',
			rlConfig['libs_url']+'jquery/markers/brown-20.png'
			
		];
		
		base.redIcon = new google.maps.MarkerImage(
			rlConfig['libs_url']+'jquery/markers/red-20.png',
			new google.maps.Size(12, 20),
			new google.maps.Point(0, 0),
			new google.maps.Point(6, 20)
		);
		
		base.smallShadow = new google.maps.MarkerImage(
			rlConfig['libs_url']+'jquery/markers/shadow-20.png',
			new google.maps.Size(22, 20),
			new google.maps.Point(0, 0),
			new google.maps.Point(6, 20)
		);
		
		// access to jQuery and DOM versions of element
		base.$el = $(el);
		base.el = el;

		// add a reverse reference to the DOM object
		base.$el.data("flMap", base);

		base.init = function(){
			base.options = $.extend({},$.flMap.defaultOptions, options);

			// initialize working object id
			if ( $(base.el).attr('id') ) {
				base.options.id = $(base.el).attr('id');
			}
			else {
				$(base.el).attr('id', base.options.id);
			}
			
			// get points
			base.getPoints();
		};

		// get points by address
		base.getPoints = function()
		{
			var progress = new Array();
			var geocoderCount = 0;
			
			if ( base.options.addresses )
			{	
				for ( var i = 0; i < base.options.addresses.length; i++ )
				{
					// geocoder, collect points
					if ( base.options.addresses[i][2] == 'geocoder' )
					{
						geocoderCount++;
						
						if ( base.options.addresses[i][0] )
						{
							progress[i] = 'processing';
							
							eval("geocoder.geocode( {'address': base.options.addresses["+i+"][0]}, function(results, status) { \
								if ( status == google.maps.GeocoderStatus.OK ) \
								{ \
									base.points["+i+"] = (results[0].geometry.location); \
									progress["+i+"] = 'success'; \
								} \
								else \
								{ \
									progress["+i+"] = 'fail'; \
								} \
								\
								if ( progress.indexOf('processing') < 0 ) \
								{ \
									base.createMap(); \
								} \
							})");
						}
						else
						{
							base.createMap();
						}
					}
					else
					{
						var dPoint = base.options.addresses[i][0].split(',');
						base.points[i] = new google.maps.LatLng(dPoint[0], dPoint[1]);
					}
				}
				
				if ( geocoderCount == 0 )
				{
					base.createMap();
				}
			}
			else
			{
				if ( base.options.emptyMap )
				{
					base.createMap();
				}
			}
		};
		
		// create map
		base.createMap = function(){
			if ( base.points.length > 0 || base.options.emptyMap )
			{
				var center = base.options.emptyMap ? null : new google.maps.LatLng(base.points[0].lat(), base.points[0].lng());
				var options = {
					zoom: base.options.zoom,
					center: center,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					scrollwheel: base.options.scrollWheelZoom
				}
				base.map = new google.maps.Map(document.getElementById(base.options.id), options);
				
				google.maps.event.addListenerOnce(base.map, 'idle', function(){
					base.setMarkers();
				});
			}
			else
			{
				printMessage('error', base.options.phrases.notFound.replace('{location}', base.options.addresses[0][0]));
				return false;
			}
		};
		
		// set markers
		base.setMarkers = function(){
			base.bounds = new google.maps.LatLngBounds();
			
			if ( base.points.length > 0 ) {
				for ( var i = 0; i < base.points.length; i++ ) {
					var myLatLng = new google.maps.LatLng(base.points[i].lat(), base.points[i].lng());
					var icon = base.options.alphabetMarkers ? 'http://www.google.com/mapfiles/marker'+base.letters[i]+'.png' : null;
					var shadow = base.options.alphabetMarkers ? 
						new google.maps.MarkerImage('http://www.google.com/mapfiles/shadow50.png', new google.maps.Size(37, 37), new google.maps.Point(0, 0), new google.maps.Point(10, 34)) :
						null;
					
					base.markers[i] = new google.maps.Marker({
						position: myLatLng,
						map: base.map,
						icon: icon,
						shadow: shadow
					});
					
					base.attachInfo(base.markers[i], i);
					base.bounds.extend(myLatLng);
				}
				
				if ( base.points.length > 1 ) {
					base.map.fitBounds(base.bounds);
					if ( base.map.getZoom() > base.options.zoom ) {
						base.map.setZoom(base.options.zoom);
					}
				}
			}
			
			base.options.ready(base);
			
			// local search handler
			if ( base.options.localSearch && base.options.localSearch.services && google.maps.places) {	
				base.placesService = new google.maps.places.PlacesService(base.map);
				
				base.lastPlaceInfoWindow = new google.maps.InfoWindow({maxWidth: 200});
				google.maps.event.addListener(base.lastPlaceInfoWindow, 'closeclick', function() {
					base.lastPlace.setIcon(base.lastPlaceIcon);
					base.lastPlace = false;
				});

				// build services area
				base.buildArea();
			}
		};
		
		/* attache info to the marker */
		base.attachInfo = function(marker, i){
			base.infoWindows[i] = new google.maps.InfoWindow({
				content: base.options.addresses[i][1],
				size: new google.maps.Size(150,10)
			});
			
			google.maps.event.addListener(marker, 'click', function(){
				base.infoWindows[i].open(base.map, marker);
			});
		}
		
		// build services area
		base.buildArea = function() {
			var services = '';
			
			for ( var i = 0; i < base.options.localSearch.services.length; i++ ) {
				var checked = '';
				if ( base.options.localSearch.services[i][2] ) {
					checked = 'checked="checked"';
					base.runPlaces(i, true);
				}
				services += '<li id="lsmi_'+i+'" style="background: url('+base.icons[i]+') 18px 3px no-repeat;" class="flgService"><label><input class="default" '+checked+' id="flg_'+base.options.localSearch.services[i][0]+'" type="checkbox" />'+base.options.localSearch.services[i][1]+'</label></li>';
			}
			
			// add serices area
			var header_phrase = readCookie('mapServicesArea') == 'false' ? base.options.phrases.show : base.options.phrases.hide;
			var html = '<div class="flgServicesArea"><div class="caption">'+base.options.localSearch.caption+' <span class="fkgSlide">('+header_phrase+')</span></div><div class="flgBody"><ul class="body">'+services+'</ul></div></div>';
			$('#'+base.options.id).append(html);
			
			$('.fkgSlide').click(function(){
				if ( base.areaOpened ) {
					base.areaOpened = false;
					$(this).html('('+base.options.phrases.show+')');
					$('.flgServicesArea div.flgBody').slideUp();
				}
				else {
					base.areaOpened = true;
					$(this).html('('+base.options.phrases.hide+')');
					$('.flgServicesArea div.flgBody').slideDown();
				}
				createCookie('mapServicesArea', base.areaOpened, 31);
			});
			
			if ( readCookie('mapServicesArea') == 'false' ) {
				base.areaOpened = false;
				$(this).html('('+base.options.phrases.show+')');
				$('.flgServicesArea div.flgBody').hide();
			}
			
			// set services listener
			$('div.flgServicesArea ul li input').click(function(){
				var key = $(this).attr('id').split('_')[1];
				var index = $(this).closest('li').attr('id').split('_')[1];

				if ( $(this).is(':checked') ) {
					base.runPlaces(index, true, true);
				}
				else {
					base.runPlaces(index, false);
				}
			});
		};
		
		base.runPlaces = function(index, visibility, show_error) {
			var service = base.options.localSearch.services[index];
			
			if ( base.loadedPlaces[index] ) {
				for ( var i = 0, marker; marker = base.placesMarkers[index][i]; i++ ) {
					marker.setVisible(visibility);
				}
			}
			else {
				var request = {
					bounds: base.map.getBounds(),
					keyword: service[1]
				};
				
				base.placesService.nearbySearch(request, function(response, status){
					if ( status != google.maps.places.PlacesServiceStatus.OK ) {
						if (show_error) {
							printMessage('error', base.options.phrases.notFound.replace('{location}', service[1]));
						}

						setTimeout(function(){
							$('.flgServicesArea ul.body li#lsmi_'+index).slideUp();
						}, 3000);

						return;
					}
					
					base.placesMarkers[index] = new Array();
					
					for ( var i = 0, place; place = response[i]; i++ ) {
						var icon = new google.maps.MarkerImage(
							base.icons[index],
							new google.maps.Size(12, 20),
							new google.maps.Point(0, 0),
							new google.maps.Point(6, 20)
						);
						
						var marker = new google.maps.Marker({
							map: base.map,
							icon: icon,
							shadow: base.smallShadow,
							title: place.name,
							position: place.geometry.location,
							savedReference: place.reference
						});
						
						base.placesMarkers[index].push(marker);
						
						google.maps.event.addListener(marker, 'click', function() {
							if ( base.lastPlace ) {
								base.lastPlace.setIcon(base.lastPlaceIcon);
								base.lastPlaceInfoWindow.close();
							}
							
							this.setIcon(this == base.lastPlace ? icon : base.redIcon);
							
							base.lastPlace = this;
							base.lastPlaceIcon = icon;
							
							/* attache infoWindow */
							base.lastPlaceInfoWindow.setContent(lang['loading']);
							base.lastPlaceInfoWindow.open(base.map, this);
							
							var request = {
								reference: this.savedReference
							};

							base.placesService.getDetails(request, function(response, status){
								if ( status == google.maps.places.PlacesServiceStatus.OK ) {
									var html = base.placeInfoWindowContentTpl;

									html = html.replace('{name}', response.name);
									html = html.replace('{address}', response.formatted_address);
									var phone = response.formatted_phone_number == undefined ? '' : 'Tel: <a class="dark_12" href="tel:'+response.formatted_phone_number+'" target="_blank">'+response.formatted_phone_number+'</a>';
									html = html.replace('{phone}', phone);
									var website = response.website == undefined ? '' : '<a href="'+response.website+'" target="_blank">'+response.website+'</a>';
									html = html.replace('{website}', website);
									base.lastPlaceInfoWindow.setContent(html);
								}								
							});
						});
					}
					
					base.loadedPlaces[index] = true;
				});
			}
		}
		
		// run initializer
		base.init();
	};

	$.flMap.defaultOptions = {
		id: 'map',
		zoom: 8,
		scrollWheelZoom: true,
		localSearch: false,
		emptyMap: false,
		alphabetMarkers: false,
		phrases: {
			hide: 'Hide',
			show: 'Show',
			notFound: 'The <b>{location}</b> location not found'
		},
		ready: function(){}
	};

	$.flMap.get = function(){
		return base;
	};
	
	$.fn.flMap = function(options){
		return this.each(function(){
			(new $.flMap(this, options));
		});
	};

})(jQuery);