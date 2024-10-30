;(function( $ ) {
	'use strict';

	var isAddressFilled = false;
	var selectedMethodID = false;

	var request = null;

	function setParcelID(id){
		$("#lagersystem-parcelpickup-id").val(id);
	}
	function setParcelData(obj){
		$("#lagersystem-parcelpickup-data").val(JSON.stringify(obj));
	}

	function addChangeButton(parcelDiv){
		parcelDiv.append('<div class="lagersystem-parcelpickup-changeBtn-container"><button type="button" class="lagersystem-parcelpickup-changeBtn">Skift udleveringssted</button></div>');

		//setup ajax button wih Magnific
		$('.lagersystem-parcelpickup-changeBtn').magnificPopup({
			callbacks: {
				open: function() {
					// Will fire when this exact popup is opened
					// this - is Magnific Popup object
				},
			},
			items: {
				src: '#lagersystem-parcelpickup-popup',
				type: 'inline'
			}
		});
	}

 	$(function() {
		$(document).on('change', '.shipping_method', updateSelectedMethod);
		$(document).on('change', '#billing_address_1, #billing_postcode, #billing_city, #billing_country', detectIfAddressIsFilled);
		$(document).on('updated_checkout', updateSelectedMethod);

		$( "body" ).on( "selectLocation", ".lagersystem-parcelpickup-div", function(event, data) {
			var parcelDiv = $(".lagersystem-parcelpickup:visible");
			parcelDiv.html(data.title + "<br/>");
			parcelDiv.append(data.street + "<br/>");
			parcelDiv.append(data.zip + " " + data.city + "<br/>");
			addChangeButton(parcelDiv);
			setParcelID(data.id);
			setParcelData(data);
		});

		//initial
		detectIfAddressIsFilled();
	});

	function detectIfAddressIsFilled(){
		var addr = $("#billing_address_1").val() != "";
		var zip = $("#billing_postcode").val() != "";
		var city = $("#billing_city").val() != "";
		var country = $("#billing_country").val() != "";

		isAddressFilled = false;

		if(addr && zip && city && country){
			isAddressFilled = true;
		}

		updateSelectedMethod();
	}

 	function updateSelectedMethod(){
 		selectedMethodID = $("input[type='hidden'].shipping_method,input[type='radio'].shipping_method:checked").val();
		$(".lagersystem-parcelpickup-container").hide();
		$(".lagersystem-parcelpickup").hide();
		$("#lagersystem-parcelpickup-id").val('');
		$("#lagersystem-parcelpickup-data").val('');
 		if($(".lagersystem-parcelpickup[data-instanceid='"+selectedMethodID+"']").length){
			$(".lagersystem-parcelpickup-container").show();
			$(".lagersystem-parcelpickup[data-instanceid='"+selectedMethodID+"']").show();
			updateParcelDiv();
			disableOtherShipping();
		}else{
 			enableOtherShipping();
			if (request) {
				request.abort();
			}
		}
	}

	function disableOtherShipping(){
		if($("#ship-to-different-address-checkbox").is(':checked')){
			$("#ship-to-different-address-checkbox").click(); //uncheck
		}
		$("#ship-to-different-address-checkbox").prop("disabled", true);
	}

	function enableOtherShipping(){
		$("#ship-to-different-address-checkbox").prop("disabled", false);
	}

	function updateParcelDiv(){
		var parcelDiv = $(".lagersystem-parcelpickup:visible");
		if(parcelDiv.length) {
			if (isAddressFilled) {
				parcelDiv.html("Søger efter udleveringssted...");
				var carrier = parcelDiv.data('carrier');
				searchSingleParcelAjax(carrier, parcelDiv);
			} else {
				parcelDiv.html("Udfyld venligst adresse...");
			}
		}

	}

	function searchSingleParcelAjax(carrier, parcelDiv){
		var data = {
			'action': 'search_parcels',
			'post_type': 'POST',
			'carrier': carrier,
			'address': $("#billing_address_1").val(),
			'zip': $("#billing_postcode").val(),
			'city': $("#billing_city").val(),
			'country': $("#billing_country").val()
		};

		if (request) {
			request.abort();
		}

		request = $.post(lsAdminAjax, data, function(response) {
			var parcelDiv = $(".lagersystem-parcelpickup:visible");
			if(parcelDiv.length) {
				if (response.places[0]) {
					var place = response.places[0];
					parcelDiv.html(place.title + "<br/>");
					parcelDiv.append(place.street + "<br/>");
					parcelDiv.append(place.zip + " " + place.city + "<br/>");
					addChangeButton(parcelDiv);
					setParcelID(place.id);
					setParcelData(place);
				}
			}
		}, 'json');
	}
})( jQuery );


