/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function () {

    $('#coursedropdown').change(function () {


        $.getJSON("ajax.php", {id: $(this).val(), fname: 'get_course_users', ajax: 'true'}, function (j) {
            var options = '';

            for (var key in j) {
                if (key != '') {
                    options += '<option value="' + key + '">' + j[key] + '</option>';
                }
            }
            $('#userdropdown').html(options);
            $('#userdropdown').prepend("<option value='' selected='selected'>Select User</option>");

        })

    });


    $('.coursedropdown').change(function () {

        if ($('#reportdropdown').val() == 2) {
            $.getJSON("ajax.php", {courseid: $(this).val(), fname: 'get_course_quiz', ajax: 'true'}, function (j) {
                var options = '';

                for (var key in j) {
                    if (key != '') {
                        options += '<option value="' + key + '">' + j[key] + '</option>';
                    }
                }
                $('#quizdropdown').html(options);
                $('#quizdropdown').prepend("<option value='' selected='selected'>Select Quiz</option>");

            })
        }
    });

    if ($('#reportdropdown').val() && $('#reportdropdown').val() == 2) {
        $('#quizdropdown').prop('disabled', false);
    } else {
        $('#quizdropdown').prop('disabled', true);
    }

    $('#reportdropdown').change(function () {
        if ($('#reportdropdown').val() == 2) {
            $('#quizdropdown').prop('disabled', false);
        } else {
            $('#quizdropdown').prop('disabled', true);
        }
    });
});
