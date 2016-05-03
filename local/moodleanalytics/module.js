/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function () {

    $("select[name='id']").change(function () {


        $.getJSON("ajax.php", {id: $(this).val(), fname: 'get_course_users', ajax: 'true'}, function (j) {
            var options = '';

            for (var key in j) {
                if (key != '') {
                    options += '<option value="' + key + '">' + j[key] + '</option>';
                }
            }
            $("select[name='userid']").html(options);
            $("select[name='userid']").prepend("<option value='' selected='selected'>Select User</option>");

        })

    });

});
