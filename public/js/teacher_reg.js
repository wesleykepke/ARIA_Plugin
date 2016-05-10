function teacher_reg_init(current_form_id, host){
  (function($){
    // get music DB form id from Gravity Forms
    var music_form_id = get_music_form_id(host);

    // get field ids
    var field_id_arr = get_teacher_ids(host);

    // prefix IDs
    var input_prefix = '#input_' + current_form_id + '_';
    var input_id_arr = [];
    for( key in field_id_arr ){
      input_id_arr[key] = input_prefix + field_id_arr[key];
    //  alert( key + ':' + field_id_arr[key] + input_id_arr[key] );
    }


    var volunteer_selects_field = input_id_arr['volunteer_time'];
    var volunteer_checkboxes = 'choice_' + current_form_id + '_' + field_id_arr['volunteer_time'];
    var judging_field = input_id_arr['is_judging'];
    var is_judging = true;

    var error_msg = "Error: Please select at least TWO volunteer times before submitting";

    // Determine whether user is judging
    $(judging_field).change(function(){
      judging_answer = $('input[name=input_' + field_id_arr['is_judging'] + ']:checked').val();
      if( judging_answer == "No" ){
        is_judging = false;
        $('#gform_submit_button_' + current_form_id).before("<p class='volunteer_error'>"+error_msg+"</p>");
        $('#gform_submit_button_' + current_form_id).prop("disabled", true);
      }
      else
      {

          $('.volunteer_error').remove();
          $('#gform_submit_button_' + current_form_id).removeAttr("disabled");
      }
    });


    // When user checks or unchecks volunteer time
    $(volunteer_selects_field).change(function(){
      // If they are not judging
      if( is_judging == false )
      {
        var num_selected = $('input[id^=' + volunteer_checkboxes + ']:checkbox:checked').length;
        // Disable submit if less than two boxes are selected
        if(num_selected < 2)
        {
          if($('.volunteer_error').length){

          }
          else
          {
            $('#gform_submit_button_' + current_form_id).before("<p class='volunteer_error'>"+error_msg+"</p>");

          }
          $('#gform_submit_button_' + current_form_id).prop("disabled", true);
        }
        else
        {
          $('.volunteer_error').remove();
          $('#gform_submit_button_' + current_form_id).removeAttr("disabled");
        }
      }
    });

    // get student level
    var st_level = $(input_id_arr['student_level']).val();

    // request for all songs of given level
    var levelField = field_id_arr['song_level'];
    var music = get_songs(st_level, levelField, music_form_id);
    var period_arr = {
      selected_val: { 1: '', 2: '' },
      selected_text: { 1: '', 2: '' },
    };

    // For testing purposes, allow change in level
    $(input_id_arr['student_level']).live("change", function() {
      st_level = $(input_id_arr['student_level']).val();
      music = get_songs(st_level, levelField);
    });

    // Disable teacher student field
    //$(input_id_arr['name']+'_3').prop("disabled", true);
    //$(input_id_arr['name']+'_6').prop("disabled", true);
    $(input_id_arr['student_level']).prop("disabled", true);
    $(input_id_arr['student_name']+'_3').prop("disabled", true);
    $(input_id_arr['student_name']+'_6').prop("disabled", true);

    var period_html = store_periods(input_id_arr);

    // Song 1 Selection

    // user selects period
    $(input_id_arr['song_1_period']).val(input_id_arr['song_1_period'] + ' option:first');
    $(input_id_arr['song_2_period']).val(input_id_arr['song_2_period'] + ' option:first');
    $(input_id_arr['song_1_period']).live("change", function() {
      period_arr[ 'selected_val' ][1] = $(input_id_arr['song_1_period']).val();
      period_arr[ 'selected_text' ][1] = $(input_id_arr['song_1_period'] + '  option:selected').text();

      // !!!restore selection
      load_periods('2', period_html, input_id_arr);
      if( period_arr['selected_val'][2] != '' ){
        $(input_id_arr['song_2_period']).val( period_arr['selected_val'][2]);
      }

      // remove period from song 2 options
      $(input_id_arr['song_2_period'] + " option[value='" + period_arr['selected_val'][1]  + "']").remove( );

      // disable song selection
      $(input_id_arr['song_1_selection']).empty();
      $(input_id_arr['song_1_selection']).attr('disabled', true);

      // populate period composers
      load_composers( music, period_arr[ 'selected_val' ][1], '1', input_prefix, field_id_arr );
    });

    // user selects composer
    var composer_val_1; // = $(input_id_arr['song_1_composer']).val();
    $(input_id_arr['song_1_composer']).live("change", function(){

      composer_val_1 = $(input_id_arr['song_1_composer']).val();
      // enable song selection
      load_songs( music, composer_val_1, '1', input_prefix, field_id_arr );
      // populate song selection
      $(input_id_arr['song_1_selection']).removeAttr('disabled');
    });



    // Song 2 Selection

    // user selects period
    $(input_id_arr['song_2_period']).live("change", function() {
      period_arr[ 'selected_val' ][2] = $(input_id_arr['song_2_period']).val();
      period_arr[ 'selected_text' ][2] = $(input_id_arr['song_2_period'] + '  option:selected').text();

      // !!!restore selection
      load_periods('1', period_html, input_id_arr);
      if( period_arr['selected_val'][1] != '' ){
        $(input_id_arr['song_1_period']).val( period_arr['selected_val'][1]);
      }

      // remove period from song 2 options
      $(input_id_arr['song_1_period'] + " option[value='" + period_arr['selected_val'][2]  + "']").remove( );

      // disable song selection
      $(input_id_arr['song_2_selection']).empty();
      $(input_id_arr['song_2_selection']).attr('disabled', true);

      // populate period composers
      load_composers( music, period_arr[ 'selected_val' ][2], '2', input_prefix, field_id_arr );
    });

    // user selects composer
    var composer_val_2; // = $(input_id_arr['song_1_composer']).val();
    $(input_id_arr['song_2_composer']).live("change", function(){

      composer_val_2 = $(input_id_arr['song_2_composer']).val();
      // enable song selection
      load_songs( music, composer_val_2, '2', input_prefix, field_id_arr );
      // populate song selection
      $(input_id_arr['song_2_selection']).removeAttr('disabled');
    });
  })(jQuery);
}

function load_songs( music, composer, song_num, input_prefix, field_id_arr ){
  (function($){
    var html = create_placeholder( "Select Song..." );
    var song_field = input_prefix + field_id_arr['song_' + song_num + '_selection'];
    //alert( composer_field );
    var data_composer_field   = '' +  field_id_arr['song_composer'];
    var data_song_field = '' + field_id_arr['song_name'];
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

  })(jQuery);
}


  // Get form ID of music DB
  function get_music_form_id(host){

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
    url += "?paging[page_size]=500";
    //NOTE: key in search is just field ID not formID.fieldID
    // search for entry[levelID] == level
    var returnedValue;
    var test;

    (function($){
      $.ajax({
        type: "GET",
        url: url,
        async: false,

        success: function(result) {
        for(key in result['response'])
        {
          // !!! base off stored name instead of hard coded
          if( result['response'][key]['title'] == "NNMTA: Music Database"){
            test = result['response'][key]['id'];
          }
        }
            }
        }).then( function(){
        returnedValue = test;
        });
    })(jQuery);
    return returnedValue;
  }// end of get music form id function



// get field IDs function
function get_teacher_ids(host) {
  var idResult, temp;
  var myUrl = host + "/wp-content/plugins/ARIA/includes/aria-get-teacher.php";

  (function($){
    $.ajax({
      type: "GET",
      url: myUrl,
      async: false,
      success: function(result){
        temp = result;
      }
      }).then( function(){
        idResult = JSON.parse(temp);
    });

  })(jQuery);
  return idResult;

} // end of get IDs function

function get_songs( level, levelID, music_form_id ){
  var d = new Date,
  expiration = 3600,
  unixtime = parseInt( d.getTime() / 1000 ),
  future_unixtime = expiration + unixtime,
  method = "GET",
  route = "forms/" + music_form_id + "/entries";
  stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;

  sig = CalculateSig( stringToSign, private_key );

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
    url += '&paging[page_size]=700' + '&search=' + searchJSON;
    url += '&sorting[key]=3&sorting[direction]=ASC';

    var returnedValue;
    var test;

    (function($){
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
    })(jQuery);

    return returnedValue;
  }// end of getMusic function


  function load_composers( music, period, song, input_prefix, field_id_arr ){

    (function($){
      // !!! Move to function
      var html = create_placeholder( "Select Composer..." );
      var composer_field = input_prefix + field_id_arr['song_' + song + '_composer'];
      //alert( composer_field );
      var data_composer_field   = '' +  field_id_arr['song_composer'];
      var data_period_field = '' + field_id_arr['song_period'];
      //var data_composer_field = data_composer_field_int.toString();
      music.forEach( function(entry){
      var composer = entry[ data_composer_field ];
        if( entry[data_period_field] == period && html.indexOf( composer ) == -1 ){
          //alert( composer );
          html += '<option value="' + composer + '">' + toTitleCase(composer) + '</option>';
        }
      });

      $(composer_field).empty();
      $(composer_field).append(html);

    })(jQuery);
  }

  function store_periods(input_id_arr){
    //alert( $(field_id_arr['song_1_period'] ).html() );
    var html = '';

    (function($){
      //!!! if placeholder $(input_id_arr['song_1_period'] + ' option:not(:first)' ).each(function() {
      $(input_id_arr['song_1_period'] + ' option' ).each(function() {
        html += '<option value="' + $(this).val() + '">' + $(this).text() + '</option>';
      });
    })(jQuery);
    return html;

  }

  function load_periods(song, periods, input_id_arr){
    (function($){
      $(input_id_arr['song_' + song + '_period']).empty();
      $(input_id_arr['song_' + song + '_period']).append(periods);
    })(jQuery);
  }

  function create_placeholder( str ){

    return '<option class="gf_placeholder" selected="selected" value="">' + str + '</option>';
  }

  function toTitleCase( str ){
    return str;
  }

  // Calculate sig
  function CalculateSig(stringToSign, privateKey){
    var hash = CryptoJS.HmacSHA1(stringToSign, privateKey);
    var base64 = hash.toString(CryptoJS.enc.Base64);
    return encodeURIComponent(base64);
  }// end of CalcSig