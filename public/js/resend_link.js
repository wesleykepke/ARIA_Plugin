function resend_link_init(current_form_id, host)
{
	(function($){
    var input_prefix = '#input_' + current_form_id + '_';
    var competition_field = input_prefix + '1';
    var teacher_field = input_prefix + '2';
    var name_field = input_prefix + '3';
    var students, teachers;

    var associated_form_ids = $(competition_field).val().split('_');
    //var teacher_public_id = associated_form_ids[2];
    var teacher_master_id = associated_form_ids[1];
    var student_master_id = associated_form_ids[2];

    // !!! remove
    //teacher_public_id = 677;
    teacher_master_id = 675;
    student_master_id = 674;

    //var teacher_form = get_form(host, teacher_public_id)["response"];

    // get fields ids
    //alert(JSON.stringify(teacher_form["ariaFieldIds"]));

    // get teacher entries

    // get student entries

    $(competition_field).change(function(){
      //associated_forms = get_form(host, associated_form_ids[1] + ';' + associated_form_ids[2] )["response"];
      // clear teachers
      // clear students

      // load teachers
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

  function get_entries( form_id ){

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
    $.ajax({
              type: "GET",
              url: url,
              async: false,

              success: function(result) {
                test = result
              }
          }).then( function(){
            returnedValue = test;
            //alert( test );
          });
    return returnedValue;
  }// end of get music form id function


// get field IDs function
function get_student_ids(host){

  return (function($){
    var idResult, temp;
    var myUrl = host + "/wp-content/plugins/ARIA/includes/aria-get-student.php";
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