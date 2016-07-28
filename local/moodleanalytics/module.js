/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function () {

//    $('#coursedropdown').change(function () {
//
//
//        $.getJSON("ajax.php", {id: $(this).val(), fname: 'get_course_users', ajax: 'true'}, function (j) {
//            var options = '';
//
//            for (var key in j) {
//                if (key != '') {
//                    options += '<option value="' + key + '">' + j[key] + '</option>';
//                }
//            }
//            $('#userdropdown').html(options);
//            $('#userdropdown').prepend("<option value='' selected='selected'>Select User</option>");
//
//        })
//
//    });
//
//
//    $('.coursedropdown').change(function () {
//
//        if ($('#reportdropdown').val() == 2) {
//            $.getJSON("ajax.php", {courseid: $(this).val(), fname: 'get_course_quiz', ajax: 'true'}, function (j) {
//                var options = '';
//
//                for (var key in j) {
//                    if (key != '') {
//                        options += '<option value="' + key + '">' + j[key] + '</option>';
//                    }
//                }
//                $('#quizdropdown').html(options);
//                $('#quizdropdown').prepend("<option value='' selected='selected'>Select Quiz</option>");
//
//            })
//        }
//    });

    if ($('#reportdropdown').val() && $('#reportdropdown').val() == 'course_with_zero_activity') {
        $('#from_date').hide();
        $('#to_date').hide();
    } else {
        $('#from_date').show();
        $('#to_date').show();
    }

    $('#reportdropdown').change(function () {
        if ($('#reportdropdown').val() == 'course_with_zero_activity') {
//            $('#from_date').prop('disabled', true);
//            $('#to_date').prop('disabled', true);
            $('#from_date').hide();
            $('#to_date').hide();
        } else {
//            $('#from_date').prop('disabled', false);
//            $('#to_date').prop('disabled', false);
            $('#from_date').show();
            $('#to_date').show();
        }
    });
    
    /*
     * Add date picker on text box
     */
    $('form').on('focus',"input.program-management-datepicker[type='text']", function(){
        console.log($("input.program-management-datepicker[type='text']").parent());
        $(this).datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat:"yy-mm-dd"
          });
    });
    /*
     * Add date picker on text box on focus
     */
    $('body').on('keydown',"input.program-management-datepicker[type='text']", function(e){
        return false;
    });
	
	$('.textPrevious').hide();
	$('.arrow_text').hover(function(){
			$('.fa-chevron-circle-left').hide();
			$('.textPrevious').show();}, function(){
        	$('.fa-chevron-circle-left').show();
	});


	$('.textNext').hide();
		$('.arrow_text').hover(function(){
				$('.fa-chevron-circle-right').hide();
				$('.textNext').show();}, function(){
				$('.fa-chevron-circle-right').show();
		});
	});