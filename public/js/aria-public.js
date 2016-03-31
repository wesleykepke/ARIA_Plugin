jQuery(document).ready(function($) {
//	alert("in public");
	// ---- only load on specific page (if statement with student level?)

	// Page load functions
	//local
	var local_host = "http://aria.cse.unr.edu";
	//var local_host = "http://192.168.245.140";
	// aria
	var host = "http://aria.cse.unr.edu";
	var public_key = "1ff591984b";
	var private_key = "c4efb4676e0d6a6";

	//local

	var form_name = $('.gform_title').text();
	//get_test();

	if( form_name.indexOf( "Teacher Registration" ) != -1 ){

//alert("running");
	//alert( $('.gform_title').text() );
	// get teacher  form id from current form
	var teacher_form = $('.gform_fields').attr('id');
	var teacher_form_id = teacher_form.split('_');
	teacher_form_id = teacher_form_id[teacher_form_id.length -1];

		//alert( "teacher form:" + teacher_form_id );

	// get music DB form id from Gravity Forms
	var music_form_id = get_music_form_id();


	// get field ids
        var field_id_arr = get_ids();

    // prefix IDs
    var input_prefix = '#input_' + teacher_form_id + '_';
	var input_id_arr = [];
	for( key in field_id_arr ){
		input_id_arr[key] = input_prefix + field_id_arr[key];
	//	alert( key + ':' + field_id_arr[key] + input_id_arr[key] );
	}


	// get student level
	var st_level = $(input_id_arr['student_level']).val();

	// request for all songs of given level
	var levelField = field_id_arr['song_level'];
	var music = get_songs(st_level, levelField);
	var period_arr = {
		selected_val: { 1: '', 2: '' },
		selected_text: { 1: '', 2: '' },
	};

	// For testing purposes, allow change in level
	$(input_id_arr['student_level']).live("change", function() {
		st_level = $(input_id_arr['student_level']).val();
		music = get_songs(st_level, levelField);
	});

	// Disable student fields
	$(input_id_arr['student_level']).prop("disabled", true);
	$(input_id_arr['student_name']+'_3').prop("disabled", true);
	$(input_id_arr['student_name']+'_6').prop("disabled", true);

	var period_html = store_periods();


	// Song 1 Selection

	// user selects period
	$(input_id_arr['song_1_period']).live("change", function() {
		period_arr[ 'selected_val' ][1] = $(input_id_arr['song_1_period']).val();
		period_arr[ 'selected_text' ][1] = $(input_id_arr['song_1_period'] + '  option:selected').text();

		// !!!restore selection
		load_periods('2', period_html);
		if( period_arr['selected_val'][2] != '' ){
			$(input_id_arr['song_2_period']).val( period_arr['selected_val'][2]);
		}

		// remove period from song 2 options
		$(input_id_arr['song_2_period'] + " option[value='" + period_arr['selected_val'][1]  + "']").remove( );

		// disable song selection
		$(input_id_arr['song_1_selection']).empty();
		$(input_id_arr['song_1_selection']).attr('disabled', true);

		// populate period composers
		load_composers( period_arr[ 'selected_val' ][1], '1' );
	});

	// user selects composer
	var composer_val_1; // = $(input_id_arr['song_1_composer']).val();
	$(input_id_arr['song_1_composer']).live("change", function(){

		composer_val_1 = $(input_id_arr['song_1_composer']).val();
		// enable song selection
		load_songs( composer_val_1, '1' );
		// populate song selection
		$(input_id_arr['song_1_selection']).removeAttr('disabled');
	});


	function load_songs( composer, song_num ){
		var html = create_placeholder( "Select Song..." );
		var song_field = input_prefix + field_id_arr['song_' + song_num + '_selection'];
		//alert( composer_field );
		var data_composer_field   = '' +  field_id_arr['song_composer'];
		var data_song_field = '' + field_id_arr['song_name'];
	//	alert(song_field);
		//var data_composer_field = data_composer_field_int.toString();
		music.forEach( function(entry){
			var song  = entry[ data_song_field ];
			//alert( composer );
			if( entry[data_composer_field] == composer && html.indexOf( song ) == -1 ){
				//alert( composer );
				html += '<option value="' + song + '">' + toTitleCase(song) + '</option>';
			}
		});

		$(song_field).empty();
		$(song_field).append(html);
	}

	// Song 2 Selection

	// user selects period
	$(input_id_arr['song_2_period']).live("change", function() {
		period_arr[ 'selected_val' ][2] = $(input_id_arr['song_2_period']).val();
		period_arr[ 'selected_text' ][2] = $(input_id_arr['song_2_period'] + '  option:selected').text();

		// !!!restore selection
		load_periods('1', period_html);
		if( period_arr['selected_val'][1] != '' ){
			$(input_id_arr['song_1_period']).val( period_arr['selected_val'][1]);
		}

		// remove period from song 2 options
		$(input_id_arr['song_1_period'] + " option[value='" + period_arr['selected_val'][2]  + "']").remove( );

		// disable song selection
		$(input_id_arr['song_2_selection']).empty();
		$(input_id_arr['song_2_selection']).attr('disabled', true);

		// populate period composers
		load_composers( period_arr[ 'selected_val' ][2], '2' );
	});

	// user selects composer
	var composer_val_2; // = $(input_id_arr['song_1_composer']).val();
	$(input_id_arr['song_2_composer']).live("change", function(){

		composer_val_2 = $(input_id_arr['song_2_composer']).val();
		// enable song selection
		load_songs( composer_val_2, '2' );
		// populate song selection
		$(input_id_arr['song_2_selection']).removeAttr('disabled');
	});


/////////////////////add above ////////////
	}
	else{
		//alert ("not teacher form");
	}


	// Calculate sig
	function CalculateSig(stringToSign, privateKey){
		var hash = CryptoJS.HmacSHA1(stringToSign, privateKey);
		var base64 = hash.toString(CryptoJS.enc.Base64);
		return encodeURIComponent(base64);
	}// end of CalcSig

	// Get form ID of music DB
	function get_music_form_id( ){

        var d = new Date,
	    expiration = 3600,
        unixtime = parseInt( d.getTime() / 1000 ),
	    future_unixtime = expiration + unixtime,
        method = "GET",
	    route = "forms";
        stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;

        sig = CalculateSig( stringToSign, private_key );
        url = host + "/gravityformsapi/" + route;
		url += "/?api_key=" + public_key;
		url += "&signature=" + sig + "&expires=" + future_unixtime;

		//NOTE: key in search is just field ID not formID.fieldID
		// search for entry[levelID] == level
		var returnedValue;
		var test;
		$.ajax({
	            type: "GET",
	            url: url,
	            async: false,

	            success: function(result) {
					for(key in result['response'])
					{
						// !!! base off stored name instead of hard coded
						if( result['response'][key]['title'] == "NNMTA Music Database" ){
							test = result['response'][key]['id'];
						}
					}
	            }
	        }).then( function(){
	        	returnedValue = test;
	        	//alert( test );
	        });
		return returnedValue;
	}// end of get music form id function

	// get field IDs function
        function get_ids(){
		var idResult, temp;
		// !!! local
		var myUrl = local_host + "/wp-content/plugins/ARIA/includes/aria_get_ids.php";
		$.ajax({
			type: "GET",
			url: myUrl,
			async: false,

			success: function(result){
				temp = result;
			}

		}).then( function(){
		//	alert(temp);
			idResult = JSON.parse(temp);
		});


		return idResult;

	} // end of get IDs function


	function get_songs( level, levelID ){
        	var d = new Date,
	        expiration = 3600,
        	unixtime = parseInt( d.getTime() / 1000 ),
	        future_unixtime = expiration + unixtime,
        	method = "GET",
	        route = "forms/" + music_form_id + "/entries";
        	stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;

        	sig = CalculateSig( stringToSign, private_key );

		// local
        	url = host + "/gravityformsapi/" + route;
		url += "/?api_key=" + public_key;
		url += "&signature=" + sig + "&expires=" + future_unixtime;

		//NOTE: key in search is just field ID not formID.fieldID
		// search for entry[levelID] == level

		var search;

		if( level != 11 )
		{
			search = {
				field_filters : [
					{
					key: levelID,
					operator: 'is',
					value: level
					}
				],
				mode : 'any'
			}
		}
		else
		{
			search = {
				field_filters : {
					mode: 'any',
					0:
						{
						key: levelID,
						operator: 'is',
						value: 9
						},
					1:
						{
						key: levelID,
						operator: 'is',
						value: 10
						}
				}
			}

		}


		var searchJSON = JSON.stringify( search );

		//NOTE: paging requires &
		//NOTE: max page size?
		url += '&paging[page_size]=300' + '&search=' + searchJSON;
		url += '&sorting[key]=3&sorting[direction]=ASC';

		var returnedValue;
		var test;
		$.ajax({
	            type: "GET",
	            url: url,
	            async: false,

	            success: function(result) {
	                test = result['response']['entries'];
	            }
	        }).then( function(){
	        	returnedValue = test;
	        });
		return returnedValue;
	}// end of getMusic function

	function store_periods(){
		//alert( $(field_id_arr['song_1_period'] ).html() );
		var html = '';
		//!!! if placeholder $(input_id_arr['song_1_period'] + ' option:not(:first)' ).each(function() {
		$(input_id_arr['song_1_period'] + ' option' ).each(function() {
			html += '<option value="' + $(this).val() + '">' + $(this).text() + '</option>';
		});
		return html;

	}

	function load_composers( period, song ){
		// !!! Move to function
		var html = create_placeholder( "Select Composer..." );
		var composer_field = input_prefix + field_id_arr['song_' + song + '_composer'];
		//alert( composer_field );
		var data_composer_field   = '' +  field_id_arr['song_composer'];
		var data_period_field = '' + field_id_arr['song_period'];
		//var data_composer_field = data_composer_field_int.toString();
		music.forEach( function(entry){
			var composer = entry[ data_composer_field ];
			//alert( composer );
			if( entry[data_period_field] == period && html.indexOf( composer ) == -1 ){
				//alert( composer );
				html += '<option value="' + composer + '">' + toTitleCase(composer) + '</option>';
			}
		});

		$(composer_field).empty();
		$(composer_field).append(html);

		//$(composer_field).val(  $(composer_field + ' option:first').val() );
	}

	function load_periods(song, periods){
		//alert( $(field_id_arr['song_1_period'] ).html() );
		$(input_id_arr['song_' + song + '_period']).empty();
		$(input_id_arr['song_' + song + '_period']).append(periods);

	}

	function create_placeholder( str ){

		return '<option class="gf_placeholder" selected="selected" value="">' + str + '</option>';
	}

	function toTitleCase( str ){
		return str.replace( /\w\S*/g, function(txt){
			return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
	}

	// Get test
	function get_test( ){

        var d = new Date,
	    expiration = 3600,
        unixtime = parseInt( d.getTime() / 1000 ),
	    future_unixtime = expiration + unixtime,
        method = "GET",
        // !!! FIX THIS
	    route = "forms/442";
        stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;

        sig = CalculateSig( stringToSign, private_key );
        url = host + "/gravityformsapi/" + route;
		url += "/?api_key=" + public_key;
		url += "&signature=" + sig + "&expires=" + future_unixtime;

		//NOTE: key in search is just field ID not formID.fieldID
		// search for entry[levelID] == level
		var returnedValue;
		var test;
		$.ajax({
	            type: "GET",
	            url: url,
	            async: false,

	            success: function(result) {
					document.write(JSON.stringify(result));
	            }
	        }).then( function(){
	        	returnedValue = test;
	        	//alert( test );
	        });
		return returnedValue;
	}// end of get music form id function
});
