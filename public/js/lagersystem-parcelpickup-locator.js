;jQuery(document).trigger("lagersystem-parcelpickup-loaded");
(function($){
    $.fn.extend({
        // class constructor
        ParcelPickupLocator: function(options) {
            return this.each(function() {
                // Fields
                var $container = $(this);
                var $showElement = false;
                var $hideElement = false;
                var $cargoMethodInput = false;
                var $deliveryIdElement = false;
                var $mapElement = false;
                var $streetSourceElement = false;
                var $zipSourceElement = false;
                var $citySourceElement = false;
                var $countrySourceElement = false;
                var imgPath = false;

                var center = false;
                var geocoder = new google.maps.Geocoder();
                var map = false;
                var markers = [];
                var selectedMarker = null;
                var locationMarker = null;
                var count = 15;
                var provider = "";
                var comesFromParcel = null;

                var defaults = {
                    showElement: $container,
                    hideElement: $container,
                    centerLat: 55.701847,
                    centerLong: 10.179176,
                    mapOptions: {
                        zoom: 6
                    },
                    mapElement: '.lagersystem-parcelpickup-map',
                    deliveryIdElement: $("#lagersystem-parcelpickup-id"),
                    cargoMethodInput: $("input[name='shipping_method[0]']"),
                    findLocationsInAreaText: "Find lokationer i området",
                    noResultsText: "Ingen resultater",
                    streetSourceElement: $("#billing_address_1"),
                    zipSourceElement: $("#billing_postcode"),
                    citySourceElement: $("#billing_city"),
                    countrySourceElement: $("#billing_country"),
                    imgpath: $("#lagersystem-parcelpickup-popup").data('imgpath'),
                    cargoMethodAttachChangedEvent: true,
                    centerChangedCallback: function(selectedMarker){
                        if(selectedMarker){
                            selectedMarker.setAnimation(google.maps.Animation.BOUNCE);
                        }
                    },
                    mapErrorNotificationCallback: function(){ },
                    disableOtherDeliveryCallback: function(state, comesFromParcel){ },
                    basicCopyParcelAddressCallback: function(){ }

                };
                var opts = $.extend({}, defaults, options || {});

                // Methods
                var methods = {
                    init: function() {
                        $showElement = $(opts.showElement);
                        $hideElement = $(opts.hideElement);
                        $cargoMethodInput = $(opts.cargoMethodInput);
                        $deliveryIdElement = $(opts.deliveryIdElement);
                        $mapElement = $(opts.mapElement);
                        $streetSourceElement = $(opts.streetSourceElement);
                        $zipSourceElement = $(opts.zipSourceElement);
                        $citySourceElement = $(opts.citySourceElement);
                        $countrySourceElement = $(opts.countrySourceElement);

                        methods.initCount();
                        map = new google.maps.Map($mapElement.get(0), opts.mapOptions);
                        $mapElement.data("map", map);
                        center = new google.maps.LatLng(opts.centerLat, opts.centerLong);
                        map.setCenter(center);
                        google.maps.event.addListener(map, 'center_changed', methods.centerChanged);

                        $container.on("click", ".mapErrorNotification", methods.mapErrorNotification);
                        if(opts.cargoMethodAttachChangedEvent){
                            $cargoMethodInput.change(methods.cargoMethodChange);
                        }

                        $( "body" ).off( "click", ".lagersystem-parcelpickup-changeBtn");
                        $( "body" ).on( "click", ".lagersystem-parcelpickup-changeBtn", methods.cargoMethodChange);


                        $container.on("refresh",function(){
                            google.maps.event.trigger(map, 'resize');
                            map.setCenter(center);
                            methods.search();
                        });
                        $container.find(".lagersystem-parcelpickup-search-button").click(function() {
                            methods.search();
                            return false;
                        });
                        $container.find(".lagersystem-parcelpickup-results").on("click",".lagersystem-parcelpickup-search-result",function() {
                            methods.selectShop(this,this.marker);
                        });

                        $container.find(".lagersystem-parcelpickup-search-street, .lagersystem-parcelpickup-search-zip, .lagersystem-parcelpickup-search-city").keydown(function(e) {
                            methods.initCount();
                            if (e.keyCode == 13) {
                                methods.search();
                                e.preventDefault();
                                return false;
                            }
                        });

                        $(document).trigger("lagersystem-parcelpickup-init");
                    },
                    initCount: function(){
                        count = $(window).width() > 768 ? 15 : 5;
                    },
                    copyParcelAddress: function(){
                        if($streetSourceElement.length = 1){
                            $container.find(".lagersystem-parcelpickup-search-street").val($streetSourceElement.val());
                        }
                        if($zipSourceElement.length = 1){
                            $container.find(".lagersystem-parcelpickup-search-zip").val($zipSourceElement.val());
                        }
                        if($citySourceElement.length = 1){
                            $container.find(".lagersystem-parcelpickup-search-city").val($citySourceElement.val());
                        }
                        if($countrySourceElement.length = 1){
                            $container.find(".lagersystem-parcelpickup-search-country").val($countrySourceElement.val());
                        }
                        if(opts.basicCopyParcelAddressCallback){
                            opts.basicCopyParcelAddressCallback();
                        }
                    },
                    centerChanged: function(){
                        if (opts.centerChangedCallback){
                            opts.centerChangedCallback(selectedMarker);
                        }
                    },
                    mapErrorNotification: function(){
                        if (opts.mapErrorNotificationCallback){
                            opts.mapErrorNotificationCallback();
                        }
                    },

                    getActiveProvider: function(){
                        provider = false;
                        var parcelDiv = $(".lagersystem-parcelpickup:visible");
                        if (parcelDiv) {
                            provider = parcelDiv.data('carrier');
                        }
                        return provider;
                    },
                    cargoMethodChange: function() {
                        var deliveryID = $deliveryIdElement.val();
                        var activeProvider = methods.getActiveProvider();
                        if (activeProvider) {
                            methods.copyParcelAddress();
                            $container.trigger("selectLocation", {id: ""});
                            $showElement.show();
                            google.maps.event.trigger(map, 'resize');
                            provider = activeProvider;
                            methods.search(deliveryID, true);
                            if (opts.disableOtherDeliveryCallback) {
                                opts.disableOtherDeliveryCallback(true, comesFromParcel);
                            }
                            comesFromParcel = true;
                        }
                    },
                    selectShop: function(item, marker) {
                        $container.find(".lagersystem-parcelpickup-search-result.active").removeClass("active");
                        if (selectedMarker && marker != selectedMarker) {
                            selectedMarker.setAnimation(null);
                        }

                        $container.find(".lagersystem-parcelpickup-search-result .checkboxSpan").html("☐");
                        $(item).addClass("active");
                        $(item).find(".checkboxSpan").html("☑");
                        marker.setAnimation(google.maps.Animation.BOUNCE);
                        selectedMarker = marker;

                        var results = $container.find(".lagersystem-parcelpickup-results");
                        results.animate({
                            scrollTop: (results.get(0).scrollTop + $(item).position().top)+"px"
                        });

                        $container.trigger("selectLocation",item.data);
                    },
                    clearResult: function(removeActive) {
                        if (removeActive === undefined){
                            removeActive = false;
                        }
                        $container.find(".lagersystem-parcelpickup-search-noresult").remove();
                        $container.find(".lagersystem-parcelpickup-searching").remove();

                        var results = $container.find(".lagersystem-parcelpickup-results");
                        results.find(".lagersystem-parcelpickup-search-result:not(.active)").remove();
                        if(removeActive){
                            results.find(".lagersystem-parcelpickup-search-result.active").remove();
                        }
                        results.find(".lagersystem-parcelpickup-findmore").remove();

                        if (locationMarker) {
                            locationMarker.setMap(null);
                            locationMarker = null;
                        }

                        for (var i in markers) {
                            var marker = markers[i];
                            if (marker != selectedMarker || (marker == selectedMarker && removeActive)) {
                                marker.setMap(null);
                            }
                        }
                        if (selectedMarker) {
                            if(!removeActive) {
                                markers = [selectedMarker];
                            } else {
                                selectedMarker = null;
                                markers = [];
                            }
                        } else {
                            markers = [];
                        }
                    },
                    search: function(preselected, removeActive){
                        if(!provider){
                            //try locate
                            provider = methods.getActiveProvider();
                        }

                        if(!provider) return;

                        if(preselected === undefined){
                            preselected = false;
                        }

                        if(removeActive === undefined){
                            removeActive = false;
                        }

                        methods.clearResult(removeActive);

                        var results = $container.find(".lagersystem-parcelpickup-results");
                        results.append(
                            $("<div class='lagersystem-parcelpickup-searching'/>")
                                .append($("<div class='bubblingG'><span class='bubblingG_1'></span><span class='bubblingG_2'></span><span class='bubblingG_3'></span></div>"))
                                .append($("<div/>").text(opts.findLocationsInAreaText))
                        );

                        var address = "";
                        if ($container.find(".lagersystem-parcelpickup-search-street").val()) {
                            address = $container.find(".lagersystem-parcelpickup-search-street").val() + ","
                        }
                        address += $container.find(".lagersystem-parcelpickup-search-zip").val() + " " +$container.find(".lagersystem-parcelpickup-search-city").val();


                        var data = {
                            'action': 'search_parcels',
                            'post_type': 'POST',
                            'carrier': provider,
                            'address': $container.find(".lagersystem-parcelpickup-search-street").val(),
                            'zip': $container.find(".lagersystem-parcelpickup-search-zip").val(),
                            'city': $container.find(".lagersystem-parcelpickup-search-city").val(),
                            'country': $container.find(".lagersystem-parcelpickup-search-country").val()
                        };

                        $.post(lsAdminAjax, data, function(response) {
                            $container.find(".lagersystem-parcelpickup-searching").remove();
                            methods.clearResult(removeActive);
                            data = response.places;
                            if (data.length > 0) {

                                var bounds = new google.maps.LatLngBounds ();

                                var filter = $container.get(0).filter;

                                $.each(data,function() {
                                    var row = this;

                                    if (filter) {
                                        var ok = filter(row);
                                        if (!ok) {
                                            return;
                                        }
                                    }


                                    if (selectedMarker) {
                                        bounds.extend(selectedMarker.getPosition());
                                        if (row.id == selectedMarker.myid) {
                                            return;
                                        }
                                    }
                                    var position = new google.maps.LatLng(row.latitude, row.longitude);
                                    var marker = new google.maps.Marker({
                                        position: position,
                                        map: map,
                                        title: row.title+"\n"+row.street+"\n"+row.zip+" "+row.city
                                    });
                                    marker.myid = row.id;
                                    markers.push(marker);
                                    bounds.extend (position);


                                    var result = $("<div/>").addClass("lagersystem-parcelpickup-search-result").data("shopid",row.id).attr("shopid",row.id);
                                    result.get(0).marker = marker;
                                    result.get(0).data = row;

                                    var infoWrap = $("<div/>").addClass("infowrap");
                                    result.append(infoWrap);

                                    var checkboxCtn = $("<div/>").addClass("checkboxCtn");
                                    var checkboxSpan = $("<span/>").addClass("checkboxSpan");
                                    checkboxCtn.append(checkboxSpan);
                                    checkboxSpan.html("☐");
                                    infoWrap.append(checkboxCtn);

                                    var information = $("<div/>").addClass("information");
                                    information.append($("<span/>").text(row.title).addClass("title"));
                                    information.append($("<span/>").text(row.street).addClass("street"));
                                    information.append($("<span/>").text(row.zip).addClass("zip"));
                                    information.append($("<span/>").text(row.city).addClass("city"));
                                    infoWrap.append(information);

                                    var closebutton = $("<div/>").addClass("closebutton");
                                    closebutton.append($("<button/>").attr("type","button").text("Luk vælger").addClass("lagersystem-parcelpickup-close-button mfp-close"));
                                    infoWrap.append(closebutton);

                                    if (row.openingHours) {

                                        var openinghours = $("<table/>").addClass("openinghours");

                                        if (row.openingHours.monday) {
                                            openinghours.append($("<tr/>").append($("<td/>").text("Mandag")).append($("<td/>").text(row.openingHours.monday.from + "-"+row.openingHours.monday.to)));
                                        }
                                        if (row.openingHours.tuesday) {
                                            openinghours.append($("<tr/>").append($("<td/>").text("Tirsdag")).append($("<td/>").text(row.openingHours.tuesday.from + "-"+row.openingHours.tuesday.to)));
                                        }
                                        if (row.openingHours.wednesday) {
                                            openinghours.append($("<tr/>").append($("<td/>").text("Onsdag")).append($("<td/>").text(row.openingHours.wednesday.from + "-"+row.openingHours.wednesday.to)));
                                        }
                                        if (row.openingHours.thursday) {
                                            openinghours.append($("<tr/>").append($("<td/>").text("Torsdag")).append($("<td/>").text(row.openingHours.thursday.from + "-"+row.openingHours.thursday.to)));
                                        }
                                        if (row.openingHours.friday) {
                                            openinghours.append($("<tr/>").append($("<td/>").text("Fredag")).append($("<td/>").text(row.openingHours.friday.from + "-"+row.openingHours.friday.to)));
                                        }
                                        if (row.openingHours.saturday) {
                                            openinghours.append($("<tr/>").append($("<td/>").text("Lørdag")).append($("<td/>").text(row.openingHours.saturday.from + "-"+row.openingHours.saturday.to)));
                                        }
                                        if (row.openingHours.sunday) {
                                            openinghours.append($("<tr/>").append($("<td/>").text("Søndag")).append($("<td/>").text(row.openingHours.sunday.from + "-"+row.openingHours.sunday.to)));
                                        }
                                        result.append(openinghours);
                                    }

                                    if($container.find(".mapErrorNotification").length > 0) {
                                        $container.find(".mapErrorNotification").remove();
                                    }
                                    $container.find(".lagersystem-parcelpickup-inputFieldsContainer").show();

                                    results.append(result);


                                    google.maps.event.addListener(marker, 'click', function() {
                                        methods.selectShop(result.get(0), marker);
                                    });
                                });

                                map.fitBounds(bounds);
                                $mapElement.data("bounds", bounds);

                                if (count < 100) {
                                    var more = $("<a/>").addClass("lagersystem-parcelpickup-findmore").attr("href","#").text("Find flere steder").click(function(){
                                        count = count * 2;
                                        methods.search();
                                        return false;
                                    });
                                    results.append(more);
                                }

                                if (preselected) {
                                    if($container.find(".lagersystem-parcelpickup-search-result[shopid='"+preselected+"']").length === 0){
                                        preselected = $container.find(".lagersystem-parcelpickup-search-result:first").attr("shopid");
                                    }
                                    $container.find(".lagersystem-parcelpickup-search-result[shopid='"+preselected+"']").click();
                                }
                                $(document).trigger("parcelLoadedEvent");
                            } else {
                                results.append($("<div class='parcelpickup-search-noresult'>"+opts.noResultsText+"</div>"));
                                $container.find(".lagersystem-parcelpickup-inputFieldsContainer").hide();
                                if(map && center) {
                                    map.setZoom(6);
                                    map.setCenter(center);
                                    $mapElement.data("bounds", null);
                                    //Show error message
                                    var errorMessage = $container.find(".lagersystem-parcelpickup-errormsg").html();
                                    if($(window).width() > 480) {
                                        if($container.find(".lagersystem-parcelpickup-map .mapErrorNotification").length === 0){
                                            $container.find(".lagersystem-parcelpickup-map").append("<div class='mapErrorNotification'>"+errorMessage+"</div>");
                                        }
                                    } else {
                                        if($container.find(".lagersystem-parcelpickup-results .mapErrorNotification").length === 0){
                                            $container.find(".lagersystem-parcelpickup-results").append("<div class='mapErrorNotification'>"+errorMessage+"</div>");
                                        }
                                    }
                                }
                            }

                            //show billing address location
                            geocoder.geocode({ 'address': address }, function(results, status) {
                                if (status == google.maps.GeocoderStatus.OK) {
                                    locationMarker = new google.maps.Marker({
                                        map: map,
                                        icon: opts.imgpath + "house.png",
                                        position: results[0].geometry.location
                                    });
                                } else {
                                    //not important, if not found.
                                }
                            });

                        }, 'json');


                    }
                };

                methods.init();

            });
        }
    });
})(jQuery);
