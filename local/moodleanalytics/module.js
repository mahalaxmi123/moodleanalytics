/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


M.local_programmanagement = {}

M.local_programmanagement.init_manage = function (Y) {

    Y.on('click', function (e) {
        if (e.target.get('checked')) {
            checkall();
        } else {
            checknone();
        }
    }, '#user_list');
};


$(document).ready(function () {

    $("select[name='organization']").change(function () {


        $.getJSON("ajax.php", {org: $(this).val(), fname: 'get_depts', ajax: 'true'}, function (j) {
            var options = '';

            for (var key in j) {
                if (key != '') {
                    options += '<option value="' + key + '">' + j[key] + '</option>';
                }
            }
            $("select[name='department']").html(options);
            $("select[name='department']").prepend("<option value='' selected='selected'>Select Department</option>");

        })

    });

    $("select[name='fee_category']").change(function () {

        var dataroot = $('#wwwroot').val();
        $.getJSON(dataroot + "/local/programmanagement/ajax.php", {feecatid: $(this).val(), fname: 'get_feeheads', ajax: 'true'}, function (m) {
            var options = '';

            for (var key in m) {
                if (key != '') {
                    options += '<option value="' + key + '">' + m[key] + '</option>';
                }
            }
            $("select[name='fee_head']").html(options);
            $("select[name='fee_head']").prepend("<option value='' selected='selected'>Select Fee Head</option>");

        })

    });


    $("select[name='organization']").change(function () {

        $.getJSON("ajax.php", {org: $(this).val(), fname: 'get_progs', ajax: 'true'}, function (n) {
            var options = '';

            for (var key in n) {
                if (key != '') {
                    options += '<option value="' + key + '">' + n[key] + '</option>';
                }
            }
            $("select[name='name']").html(options);
            $("select[name='name']").prepend("<option value='' selected='selected'>Select Program</option>");
        })

    });


    $("select[name='planid']").change(function () {
        var dataroot = $('#wwwroot').val();
        var pid = $('#pid').val();
        $.getJSON(dataroot + "/local/programmanagement/ajax.php", {pid: pid, planid: $(this).val(), fname: 'get_cycles', ajax: 'true'}, function (d) {
            var options = '';
            for (var key in d) {
                if (key != '') {
                    options += '<option value="' + key + '">' + d[key] + '</option>';
                }
            }
            $("select[name='cid']").html(options);
            $("select[name='cid']").prepend("<option value='' selected='selected'>Select Plan Name</option>");
        })

    });

    $("select[name='fee_head']").change(function () {


        $.getJSON("ajax.php", {feeheadid: $(this).val(), fname: 'get_default_amount', ajax: 'true'}, function (j) {
            var option = '';
            option = j;
            document.getElementById('amount').value = option;
        })

    });

    $("select[name='organizationid']").change(function () {
        var dataroot = $('#wwwroot').val();
        $.getJSON(dataroot + "/local/programmanagement/ajax.php", {org: $(this).val(), fname: 'get_progs_org', ajax: 'true'}, function (k) {
            var options = '';

            for (var key in k) {
                if (key != '') {
                    options += '<option value="' + key + '">' + k[key] + '</option>';
                }
            }
            $("select[name='pid']").html(options);
            $("select[name='pid']").prepend("<option value='' selected='selected'>Select Program</option>");

        })

    });
    $("select[name='pid']").change(function () {
        var dataroot = $('#wwwroot').val();
        $.getJSON(dataroot + "/local/programmanagement/ajax.php", {pid: $(this).val(), fname: 'get_plans', ajax: 'true'}, function (i) {
            var options = '';

            for (var key in i) {
                if (key != '') {
                    options += '<option value="' + key + '">' + i[key] + '</option>';
                }
            }
            $("select[name='planid']").html(options);
            $("select[name='planid']").prepend("<option value='' selected='selected'>Select Plan Type</option>");

        })

    });
//    $("select[name='planid']").change(function () {
//        var dataroot = $('#wwwroot').val();
//        var pid = $("select[name='pid']").val();
//        $.getJSON(dataroot + "/local/programmanagement/ajax.php", {pid: pid, planid: $(this).val(), fname: 'get_cycles_plan', ajax: 'true'}, function (c) {
//            var options = '';
//
//            for (var key in c) {
//                if (key != '') {
//                    options += '<option value="' + key + '">' + c[key] + '</option>';
//                }
//            }
//            $("select[name='cid']").html(options);
//            $("select[name='cid']").prepend("<option value='' selected='selected'>Select Plan Name</option>");
//        })
//
//    });
    $("select[name='pid']").change(function () {
        var dataroot = $('#wwwroot').val();
        $.getJSON(dataroot + "/local/programmanagement/ajax.php", {pid: $(this).val(), fname: 'get_bills', ajax: 'true'}, function (a) {
            var options = '';

            for (var key in a) {
                if (key != '') {
                    options += '<option value="' + key + '">' + a[key] + '</option>';
                }
            }
            $("select[name='manualcid']").html(options);
            $("select[name='manualcid']").prepend("<option value='' selected='selected'>Select Manual Plan</option>");

        })

    });
});
