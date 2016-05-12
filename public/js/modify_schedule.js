/**
 * Define some constants used throughout this JS file.
 */
var host = "http://aria.cse.unr.edu";
var public_key = "1ff591984b";
var private_key = "c4efb4676e0d6a6";

if (window.location.href.indexOf('nnmta.org') != -1) {
  host = "http://www.nnmta.org";
  public_key = "0035d1a323";
  private_key = "f2d4546aab2c06a";
}

/**
 * This function is responsible for calling the appropriate PHP code to generate
 * documents serverside.
 *
 */
function generateDocuments() {
  console.log("hi");

  // determine the name of the competition
  var compName = document.getElementById("comp-name-bold").innerHTML;
  var data = {};
  data.compName = compName;

  // request the server to generate competition documents
  var targetFunc = host + "/wp-content/plugins/ARIA/admin/scheduler/document-client.php";
  jQuery.post(targetFunc, data, function(response) {
    //console.log(response);
    window.location = response;
    //window.location = targetFunc;
     console.log("generateDocuments response>>", response);
  });
}

/**
 * This function is responsible for calling the appropriate PHP code to email
 * parents and teachers regarding the competition info of their students.
 */
function emailParentsAndTeachers() {
  console.log("hi2");

  // determine the name of the competition
  var compName = document.getElementById("comp-name-bold").innerHTML;
  var data = {};
  data.compName = compName;

  // request the server to send emails to parents and teachers
  var targetFunc = host + "/wp-content/plugins/ARIA/admin/scheduler/parent-teacher-email-client.php";
  jQuery.post(targetFunc, data, function(response) {
    console.log("emailParentsAndTeachers response>>", response);
  });
}
