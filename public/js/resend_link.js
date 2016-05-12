function resend_link_init(current_form_id, host)
{
	(function($){
    var input_prefix = '#input_' + current_form_id + '_';
    var competition_field = input_prefix + '1';
    var teacher_field = input_prefix + '2';
    var student_field = input_prefix + '3';
    var students, teachers;

    var associated_form_ids = $(competition_field).val().split('_');
    //var teacher_public_id = associated_form_ids[2];
    var teacher_master_id = associated_form_ids[1];
    var student_master_id = associated_form_ids[2];

    // !!! remove
    //teacher_public_id = 677;
    teacher_master_id = 740;
    student_master_id = 739;

    //var teacher_form = get_form(host, teacher_public_id)["response"];

    // get fields ids
    //alert(JSON.stringify(teacher_form["ariaFieldIds"]));

    // get teacher entries
    var teacher_fields = '';//get_teacher_master_ids(host);
    var teacher_entries = get_entries(host, teacher_master_id);

    // get student entries
    var student_fields = '';//get_student_master_ids(host);
    var student_entries = get_entries(host, student_master_id);


    // populate
    populate_teachers(teacher_entries, teacher_fields, teacher_field);

    $(competition_field).change(function(){
      associated_form_ids = $(competition_field).val().split('_');
      teacher_master_id = associated_form_ids[1];
      student_master_id = associated_form_ids[2];

      teacher_entries = get_entries(host, teacher_master_id);
      student_entries = get_entries(host, student_master_id);
      populate_teachers(teacher_entries, teacher_fields, teacher_field);
    });

    $(teacher_field).change(function(){
      var selected_teacher = $(teacher_field).val();
      populate_students(student_entries, student_fields, student_field, selected_teacher);
    });

    //alert(associated_forms);

/*
    $.each(associated_forms, function(key, value){
      if(value['isStudentMasterForm']){
        //students = get_entries(key);
        //alert(JSON.stringify(students));
      }
      else if(value['isTeacherMasterForm']){
        //teachers = get_entries(key);

        //alert(JSON.stringify(teachers));
      }
    });*/

	})(jQuery);
}




// Calculate sig
function CalculateSig(stringToSign, privateKey){
  var hash = CryptoJS.HmacSHA1(stringToSign, privateKey);
  var base64 = hash.toString(CryptoJS.enc.Base64);
  return encodeURIComponent(base64);
}// end of CalcSig

// Get test
function get_form( host, form_id ){

  var d = new Date,
  expiration = 3600,
  unixtime = parseInt( d.getTime() / 1000 ),
  future_unixtime = expiration + unixtime,
  method = "GET",
  route = "forms/" + form_id;
  stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;

  sig = CalculateSig( stringToSign, private_key );
  url = host + "/gravityformsapi/" + route;
  url += "/?api_key=" + public_key;
  url += "&signature=" + sig + "&expires=" + future_unixtime;

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
        test = result
      }
      }).then( function(){
        returnedValue = test;
      });

  })(jQuery);
  return returnedValue;
}// end of get music form id function

  function get_entries( host, form_id ){

    var d = new Date,
    expiration = 3600,
    unixtime = parseInt( d.getTime() / 1000 ),
    future_unixtime = expiration + unixtime,
    method = "GET",
        // !!! FIX THIS
    route = "forms/" + form_id + "/entries";
    stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;

    sig = CalculateSig( stringToSign, private_key );
    url = host + "/gravityformsapi/" + route;
    url += "/?api_key=" + public_key;
    url += "&signature=" + sig + "&expires=" + future_unixtime;
    url += '&paging[page_size]=700'

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
                  test = result
                }
            }).then( function(){
              returnedValue = test["response"]["entries"];
              //alert( test );
            });

    })(jQuery);
    return returnedValue;
  }// end of get music form id function


// get field IDs function
function get_student_ids(host){

  return (function($){
    var idResult, temp;
    var myUrl = host + "/wp-content/plugins/ARIA/includes/aria-get-student-master.php";
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
    return idResult;

  })(jQuery);
} // end of get IDs function

// get field IDs function
function get_teacher_ids(host) {
  var idResult, temp;
  var myUrl = host + "/wp-content/plugins/ARIA/includes/aria-get-teacher-masterphp";

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

    
function populate_teachers(teacher_entries, teacher_fields, teacher_field)
{
  (function($){
    var html = create_placeholder( "Select Teacher..." );
    $.each(teacher_entries, function(key,value){
      html += '<option value="' + value[5] + '">' + value[2.3] + ' ' + value[2.6] + '</option>';
    });

    $(teacher_field).empty();
    $(teacher_field).append(html);
  })(jQuery);
}

function populate_students(student_entries, student_fields, student_field, selected_teacher)
{
  (function($){
    var html = create_placeholder( "Select Student..." );
    var hash_field = 5;//student_fields['student_first_name'];
    var first_name_field = 3.3;//student_fields['student_last_name'];
    var last_name_field = 3.6;//student_fields['hash'];
    var teacher_field = 5;//student_fields['teacher_name'];
    $.each(student_entries, function(key,value){
      var teacher = value[teacher_field];
      if( teacher.indexOf(selected_teacher) != -1){
        html += '<option value="' + value[hash_field] + '">' + value[first_name_field] + ' ' + value[last_name_field] + '</option>';
      }
    });
   
    $(student_field).empty();
    $(student_field).append(html);
  })(jQuery);
}

function generate_url(host)
{

  host = "http://www.nnmta.org";
  public_key = "0035d1a323";
  private_key ="f2d4546aab2c06a";
     var d = new Date,
    expiration = 3600,
    unixtime = parseInt( d.getTime() / 1000 ),
    future_unixtime = expiration + unixtime,
    method = "PUT",
        // !!! FIX THIS
    route = "entries/2568";
    stringToSign = public_key + ":" + method + ":" + route + ":" + future_unixtime;

    sig = CalculateSig( stringToSign, private_key );
    url = host + "/gravityformsapi/" + route;
    url += "/?api_key=" + public_key;
    url += "&signature=" + sig + "&expires=" + future_unixtime;
    console.log(url);
}