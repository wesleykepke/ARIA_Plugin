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
 * This function is responsible for sending the updated student scores to the
 * server and saving that information.
 *
 */
function sendScoresToServer() {
  // determine the name of the competition
  var compName = document.getElementById("comp-name-bold").innerHTML;
  var schedule = document.getElementById("schedule");
  var allStudentsInfo = schedule.getElementsByClassName("student-info");
  var data = {};
  data.compName = compName;

  // iterate through all of the student's information and and stip away data needed
  // for command performance
  var allStudents = [];
  for (var i = 0; i < allStudentsInfo.length; i++) {
    var singleStudent = allStudentsInfo[i];
    var singleStudentData = {};

    // get the student's name
    var singleStudentName = singleStudent.getElementsByClassName("student-name")[0];
    var nameStart = "Student Name: ".length;
    singleStudentName = singleStudentName.innerHTML.slice(
      nameStart,
      singleStudentName.innerHTML.length
    );
    //singleStudentData.name = singleStudentName;

    // get the student's skill level
    var singleStudentSkillLevel = singleStudent.getElementsByClassName("student-level")[0];
    var skillLevelStart = "Student Level: ".length;
    singleStudentSkillLevel = singleStudentSkillLevel.innerHTML.slice(
      skillLevelStart,
      singleStudentSkillLevel.innerHTML.length
    );
    //singleStudentData.skillLevel = singleStudentSkillLevel;

    // consolidate the name and skill level together
    singleStudentData.studentToFind = [];
    singleStudentData.studentToFind[0] = singleStudentName;
    singleStudentData.studentToFind[1] = singleStudentSkillLevel;

    // get the student's result
    var singleStudentResult = singleStudent.getElementsByClassName("student-result")[0];
    var singleStudentResultOptions = singleStudentResult.getElementsByClassName("my-indent");
    for (var j = 0; j < singleStudentResultOptions.length; j++) {
      if (singleStudentResultOptions[j].checked) {
        singleStudentData.result = singleStudentResultOptions[j].value;
      }
    }

    // get the student's song for command performance
    var singleStudentSong = singleStudent.getElementsByClassName("student-song")[0];
    var singleStudentSongOptions = singleStudentSong.getElementsByClassName("my-indent");
    for (var j = 0; j < singleStudentSongOptions.length; j++) {
      if (singleStudentSongOptions[j].checked) {
        singleStudentData.song = singleStudentSongOptions[j].value;
      }
    }

    allStudents.push(singleStudentData);
  }

  data.students = allStudents;
  console.log(data);

  // define the location of PHP script
  var targetFunc = host + "/wp-content/plugins/ARIA/admin/scheduler/score-input-client.php";

  // send the student scores to the server
  jQuery.post(targetFunc, data, function(response) {
    //window.location = response;
    console.log(response);
    alert("Scores were successfully saved.");
  });
}
