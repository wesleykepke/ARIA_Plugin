

// aria
var host = "http://aria.cse.unr.edu";
var public_key = "1ff591984b";
var private_key = "c4efb4676e0d6a6";

if( window.location.href.indexOf('nnmta.org') != -1)
{
  host = "http://www.nnmta.org";
  public_key = "0035d1a323";
  private_key ="f2d4546aab2c06a";
}

jQuery(document).ready(function($) {

  var form_name = $('.gform_title').text();

  // get current  form id from current form
  var current_form = $('.gform_fields').attr('id');
  var current_form_id = -1;
  if( current_form )
  {
    current_form_id = current_form.split('_');
    current_form_id = current_form_id[current_form_id.length -1];
  }
  if(!form_name)
  {
    form_name = "";
  }

  if( form_name.indexOf("Resend a Teacher Registration Link") != -1)
  {
    resend_link_init(current_form_id, host);
  }

  ///////// Student registration
  if( form_name.indexOf( "Student Registration" ) != -1 ){
    student_reg_init(current_form_id, host);
  }

  ////////// Teacher registration
  if( form_name.indexOf( "Teacher Registration" ) != -1 ){
    teacher_reg_init(current_form_id, host);
  }

  // rearranging students in scheduler
    (function($){
      $( "#sortable1, #sortable2" ).sortable({
            connectWith: ".connectedSortable"
          }).disableSelection();

  })(jQuery);
});

// This function inserts newNode after referenceNode
function insertAfter(referenceNode, newNode) {
  referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function sendScheduleToServer() {
  // get necessary content from HTML
  var compName = document.getElementById("comp-name-bold").innerHTML;
  var schedule = document.getElementById("schedule");
  var scheduleTable = document.getElementById("schedule-table");
  var taggedSectionInfos = schedule.getElementsByTagName("th");
  var taggedTimeBlocks = schedule.getElementsByClassName("section");
  var formattedSectionInfos = [];
  var formattedStudentInfos = [];

    // define location of PHP script
  var myUrl = host + "/wp-content/plugins/ARIA/admin/scheduler/scheduler-client.php";

  // iterate though all of the time blocks in the schedule
  for (var i = 0; i < taggedTimeBlocks.length; i++) {
    // iterate through all of the students in a given time block
    var singleTimeBlock = taggedTimeBlocks[i];
    var listOfStudents = singleTimeBlock.getElementsByClassName("student-info");
    var listOfStudentsArray = [];
    for (var j = 0; j < listOfStudents.length; j++) {
      // iterate through all of that student's data
      var singleStudent = listOfStudents[j];
      var singleStudentData = [];
      var singleStudentAttributes = singleStudent.getElementsByTagName("li");
      for (var k = 0; k < singleStudentAttributes.length; k++) {
        // find the name of the student
        if (singleStudentAttributes[k].innerHTML.indexOf("Student Name:") > -1) {
          var nameStart = "Student Name: ".length;
          var name = singleStudentAttributes[k].innerHTML.slice(
            nameStart,
            singleStudentAttributes[k].innerHTML.length
          );

          singleStudentData.push(name);
        }

        // find the skill level of the student
        if (singleStudentAttributes[k].innerHTML.indexOf("Student Skill Level:") > -1) {
          var skillLevelStart = "Student Skill Level: ".length;
          var skillLevel = singleStudentAttributes[k].innerHTML.slice(
            skillLevelStart,
            singleStudentAttributes[k].innerHTML.length
          );

          singleStudentData.push(skillLevel);
        }

        // find the student's first song
        if (singleStudentAttributes[k].innerHTML.indexOf("Song #1:") > -1) {
          var song1Start = "Song #1: ".length;
          var song1 = singleStudentAttributes[k].innerHTML.slice(
            song1Start,
            singleStudentAttributes[k].innerHTML.length
          );

          singleStudentData.push(song1);
        }

        // find the student's second song
        if (singleStudentAttributes[k].innerHTML.indexOf("Song #2:") > -1) {
          var song2Start = "Song #2: ".length;
          var song2 = singleStudentAttributes[k].innerHTML.slice(
            song2Start,
            singleStudentAttributes[k].innerHTML.length
          );

          singleStudentData.push(song2);
        }
      }

      // add the student's data (in JSON format) to list of students in section
      listOfStudentsArray.push(singleStudentData);
    }

    // add the data of all students in a section to the accumulating list of
    // students per section (if there are students in the section)
    if (listOfStudents.length > 0) {
      formattedStudentInfos.push(listOfStudentsArray);
    }
    else {
      // send "EMPTY" if there are no students in the section
      formattedStudentInfos.push("EMPTY");
    }
  }

  console.log('hello');
  console.log("Formatted student entries>>", formattedStudentInfos);

  // iterate through all of the tags with the section information we want
  for (var i = 0; i < taggedSectionInfos.length; i++) {
    // obtain all the information that the user can modify from the schedule
    var singleTaggedSectionInfo = taggedSectionInfos[i];
    var modifiableSectionInfo = singleTaggedSectionInfo.getElementsByTagName("span");

    if (singleTaggedSectionInfo.innerHTML.indexOf("Section") > -1) {
      /*
      console.log(i,
                  singleTaggedSectionInfo.innerHTML,
                  "\n",
                  modifiableSectionInfo,
                  modifiableSectionInfo.length);
      */

      // configure the aforementioned modifiable data into JSON
      var jsonSectionInfo = {};
      jsonSectionInfo.data = [];

      // if students are registered for a section,
      if (modifiableSectionInfo.length > 0) {
        //console.log("STUDENTS IN SECTION");
        for (var j = 0; j < modifiableSectionInfo.length; j++) {
          jsonSectionInfo.data.push(modifiableSectionInfo[j].innerHTML);
        }
      }

      // if no students are regitered for a section, add a dummy value
      else {
        //console.log("NO STUDENTS IN SECTION");
        jsonSectionInfo.data.push("EMPTY");
      }

      // add section data to array of accumulating sections
      formattedSectionInfos.push(jsonSectionInfo);
    }
  }

  //console.log(formattedStudentInfos);

  // consolidate all data into a single JSON object
  data = {
    compName: compName,
    modifiableData: formattedSectionInfos,
    studentData: formattedStudentInfos
  };

  //console.log("Data being sent to server", data);

  // send the data to the server
  jQuery.post(myUrl, data, function(response) {
    alert("Schedule has been saved.");

    //console.log(response);
    document.getElementById("schedule").innerHTML = '';
    document.getElementById("schedule").innerHTML = response;
    // rearranging students in scheduler
    (function ($) {
        $( "#sortable1, #sortable2" ).sortable({
            connectWith: ".connectedSortable"
          }).disableSelection();
      })(jQuery);
    });
}// end of send schedule to server function
