function resend_link_init(current_form_id)
{
    var input_prefix = '#input_' + current_form_id + '_';
    var competition_field = input_prefix + '1';
    var teacher_field = input_prefix + '2';
    var name_field = input_prefix + '3';
    var students, teachers;
/*
    var associated_form_ids = $(competition_field).val().split('_');
    associated_forms = get_form(associated_form_ids[0] + ';' + associated_form_ids[1] )["response"];
    $(competition_field).change(function(){
      associated_forms = get_form(associated_form_ids[0] + ';' + associated_form_ids[1] )["response"];
    });

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
}




  // Calculate sig
  function CalculateSig(stringToSign, privateKey){
    var hash = CryptoJS.HmacSHA1(stringToSign, privateKey);
    var base64 = hash.toString(CryptoJS.enc.Base64);
    return encodeURIComponent(base64);
  }// end of CalcSig

  // Get test
  function get_form( form_id ){

        var d = new Date,
      expiration = 3600,
        unixtime = parseInt( d.getTime() / 1000 ),
      future_unixtime = expiration + unixtime,
        method = "GET",
        // !!! FIX THIS
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
});

