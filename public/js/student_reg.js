function student_reg_init(current_form_id, host)
{
	(function($){
		var field_id_arr = get_student_ids(host);
		console.log("field_id_arr>>", field_id_arr);
		var level_pay_field = '#input_' + current_form_id + '_' + field_id_arr['level_pricing'];
		var level_field = '#input_' + current_form_id + '_' + field_id_arr['student_level'];
		var hidden_student_level = '#field_' + current_form_id + '_' + field_id_arr['student_level'];
		$(hidden_student_level).hide();

		// Update student level
		$(level_pay_field).change(function(){
		var values = $(level_pay_field).val().split("|");
			//alert(values[0]);
			$(level_field).val(values[0]).change();
		});

	})(jQuery);
}

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
