/**
 * Function for initializing the teacher registration form.
 *
 * This function will
 *
 * @param   host  String  The location of where to run the PHP script.
 * @param   current_form_id   int   The form ID of the current teacher registration form.
 *
 * @return  int   The form ID of the music DB.
 */
function teacher_reg_init(current_form_id, host){
  (function($){
    // get music DB form id from Gravity Forms
    var music_form_id = get_music_form_id(host);

    // get field ids
    var field_id_arr = get_teacher_registration_field_ids(host);

    // prefix IDs (check the HTML to see this is now the tag IDs are formatted)
    var input_prefix = '#input_' + current_form_id + '_';
    var input_id_arr = [];
    for (key in field_id_arr) {
      input_id_arr[key] = input_prefix + field_id_arr[key];
    }

    // define variables for the volunteer time selection field
    var volunteer_time_selection_field = input_id_arr['volunteer_time'];
    var volunteer_time_selection_checkboxes = 'choice_' + current_form_id + '_' + field_id_arr['volunteer_time'];
    var volunteer_time_selection_error_msg = "Error: Please select at least TWO volunteer times before submitting this form.";

    // define variables for the field that asks if a teacher is judging
    var judging_field = input_id_arr['is_judging'];
    var is_judging = true;

    // determine whether the teacher is judging
    $(judging_field).change(function() {
      judging_answer = $('input[name=input_' + field_id_arr['is_judging'] + ']:checked').val();

      // if the teacher is not judging, prevent them from submitting the form
      if (judging_answer == "No") {
        // display error message instructing teacher how to proceed
        is_judging = false;
        $('#gform_submit_button_' + current_form_id).before("<p class='volunteer_error'>"+volunteer_time_selection_error_msg+"</p>");
        $('#gform_submit_button_' + current_form_id).prop("disabled", true);
      }

      // otherwise, do not display an error
      else{
          $('.volunteer_error').remove();
          $('#gform_submit_button_' + current_form_id).removeAttr("disabled");
      }
    });

    // add an action when user checks or unchecks the options in the volunteer time selection field
    $(volunteer_time_selection_field).change(function(){
      if (is_judging == false) {
        // if the user is not judging, check to see how many volunteer options they have selected
        var num_selected = $('input[id^=' + volunteer_time_selection_checkboxes + ']:checkbox:checked').length;

        // disable the submit button if less than two boxes are selected
        if (num_selected < 2) {
          if ($('.volunteer_error').length) {
            // ?
          }
          else {
            $('#gform_submit_button_' + current_form_id).before("<p class='volunteer_error'>"+volunteer_time_selection_error_msg+"</p>");
          }
          $('#gform_submit_button_' + current_form_id).prop("disabled", true);
        }

        // if two volunteer options have been selected, remove the warning and allow submission
        else {
          $('.volunteer_error').remove();
          $('#gform_submit_button_' + current_form_id).removeAttr("disabled");
        }
      }
    });

    // get student level
    var student_level = $(input_id_arr['student_level']).val();

    // request for all songs of given level
    var levelField = field_id_arr['song_level'];
    var music = get_songs(student_level, levelField, music_form_id);
    var period_arr = {
      selected_val: { 1: '', 2: '' },
      selected_text: { 1: '', 2: '' },
    };

    // prevent teacher from being able to change some of the student's prepopulated data
    $(input_id_arr['student_level']).prop("disabled", true);
    $(input_id_arr['student_name']+'_3').prop("disabled", true);
    $(input_id_arr['student_name']+'_6').prop("disabled", true);

    // do something
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

/**
 * Function for getting the form ID of music DB.
 *
 * This function will accept as a parameter the host of where to send the
 * request. Then, it will calculate a url that will be used to acquire the ID
 * of the form that represents the NNMTA music database.
 *
 * @param   host  String  The location of where to run the PHP script.
 *
 * @return  int   The form ID of the music DB.
 */
function get_music_form_id(host){
  // initialize variables used to calculate the url for GF API
  var d = new Date;
  var expiration = 3600;
  var unixtime = parseInt(d.getTime() / 1000);
  var future_unixtime = expiration + unixtime;
  var method = "GET";
  var route = "forms";
  var stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;
  var sig = CalculateSig(stringToSign, private_key);

  // use previously declared variables to calculate url for GF API
  var url = host + "/gravityformsapi/" + route;
  url += "/?api_key=" + public_key;
  url += "&signature=" + sig + "&expires=" + future_unixtime;
  url += "?paging[page_size]=500";

  /*
   * NOTE: key in search is just field ID not formID.fieldID, search for
   * entry[levelID] == level
   */
  var returnedValue;
  var test;

  // submit API call
  (function($){
    $.ajax({
      type: "GET",
      url: url,
      async: false,
      success: function(result) {
        // iterate through all forms until the music DB is located
        for (key in result['response']) {
          if (result['response'][key]['title'] == "NNMTA: Music Database") {
            test = result['response'][key]['id'];
          }
        }
      }
    }).then(function() {
      returnedValue = test;
    });
  })(jQuery);

  return returnedValue;
}

/**
 * Function for getting the fields IDs.
 *
 * This function will accept as a parameter the host of where to send the
 * request. Then, it will calculate a url that will be used to acquire the ID
 * of the form that represents the NNMTA music database.
 *
 * @param   host  String  The location of where to run the PHP script.
 *
 * @return
 */
function get_teacher_registration_field_ids(host) {
  var fieldIDs;
  var temp;
  var myUrl = host + "/wp-content/plugins/ARIA/includes/aria-get-teacher.php";

  (function($){
    $.ajax({
      type: "GET",
      url: myUrl,
      async: false,
      success: function(result) {
       temp = result;
      }
    }).then(function() {
      fieldIDs = JSON.parse(temp);
    });
  })(jQuery);

  return fieldIDs;
}

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

  /**
   * This fucntion will calculate a signature.
   *
   * ??
   */
  function CalculateSig(stringToSign, privateKey){
    var hash = CryptoJS.HmacSHA1(stringToSign, privateKey);
    var base64 = hash.toString(CryptoJS.enc.Base64);
    return encodeURIComponent(base64);
  }// end of CalcSig
