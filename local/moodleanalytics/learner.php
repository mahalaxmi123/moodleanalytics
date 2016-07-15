<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
global $CFG;
require('../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/user/renderer.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/grader/lib.php');
require_login();
$courseid = optional_param('id', '', PARAM_INT);        // course id
$submit16 = optional_param('submit16', '', PARAM_ALPHANUM);
$reset16 = optional_param('reset16', '', PARAM_ALPHANUM);
//$reportid = optional_param('reportid', '', PARAM_INT);
$from_date_16 = optional_param('from_date_16', '', PARAM_TEXT);
$to_date_16 = optional_param('to_date_16', '', PARAM_TEXT);
$users = optional_param_array('username', '', PARAM_TEXT);
$timelink = optional_param('time', 0, PARAM_INT);
$linktime = date('d-m-Y H:i:s', $timelink);
$view = optional_param('view', 'now', PARAM_ALPHA);
$print = optional_param('print', 0, PARAM_ALPHA);
$id = optional_param('courseid', 1, PARAM_ALPHA);
$month = optional_param('month', '', PARAM_TEXT);
$year = optional_param('year', '', PARAM_TEXT);
$context = context_system::instance();
//$context = context_course::instance($id, MUST_EXIST);
$context = context_system::instance();
if (!empty($courseid)) {
    $context = context_course::instance($courseid);
}
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/moodleanalytics/learner.php');
$PAGE->requires->js('/local/moodleanalytics/module.js', true);
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/learner.php');

if ($reset16) {
    redirect($returnurl);
}
$reportname = get_string('learner', 'local_moodleanalytics');
echo $OUTPUT->header();
$errors = array();

$fromdate16 = $from_date_16;
$todate16 = $to_date_16;

if (empty($from_date_16)) {
    $from_date_default_16 = userdate((time() - (DAYSECS * 7)), '%Y-%m-%d');
    $fromdate16 = $from_date_default_16;
    $from_date_16 = new DateTime($from_date_default_16);
} else {
    $from_date_16 = new DateTime($from_date_16);
}
if (empty($to_date_16)) {
    $to_date_default_16 = userdate(time(), '%Y-%m-%d');
    $todate16 = $to_date_default_16;
    $to_date_16 = new DateTime($to_date_default_16);
} else {
    $to_date_16 = new DateTime($to_date_16);
}

$params1 = array();
$params1['fromdate'] = $from_date_16;
$params1['todate'] = $to_date_16;

$reportobj1 = new stdClass();
$reportobj1 = get_report_class('unique_sessions');
$reportobj1->process_reportdata($reportobj1, $params1);
$axis1 = new stdClass();
$axis1 = $reportobj1->get_axis_names();
$formcontent1 = "";

$params2 = array();
$reportobj2 = new stdClass();
$reportobj2 = get_report_class('registrants');
$reportobj2->process_reportdata($reportobj2, $params2);
//$axis2 = new stdClass();
//$axis2 = $reportobj2->get_axis_names();


$time = time();
$presentmonth = date('m', $time);
$presentyear = date('Y', $time);
if (!empty($month) && empty($year)) {
    $year = $presentyear;
} else if (empty($month) && !empty($year)) {
    $month = $presentmonth;
}


$link = $CFG->wwwroot . '/local/moodleanalytics/learner.php?month=' . $month . 'year=' . $year;
$month_names = array('01' => "January", '02' => "February", '03' => "March", '04' => "April", '05' => "May", '06' => "June", '07' => "July", '08' => "August", '09' => "September", '10' => "October", '11' => "November", '12' => "December");
$yeararray = array();
$month = optional_param('month', '', PARAM_TEXT);
$year = optional_param('year', '', PARAM_TEXT);
$presentyear = date('Y', time());
for ($i = $presentyear - 20; $i <= $presentyear + 2; $i++) {
    $yeararray[] = $i;
}
$yeararray = array_combine($yeararray, $yeararray);

if (empty($_SESSION['current_date_time'])) {
    $_SESSION['current_date_time'] = 0;
}
//echo $_SESSION['current_date_time'];
if (!isset($_SESSION['current_month']) || ($view == 'current')) {
    $_SESSION['current_month'] = date('Y-m');
} else if (($view == 'next') && ($_SESSION['current_date_time'] != $linktime)) {
    $_SESSION['current_month'] = date('Y-m', strtotime('+1 month', strtotime($_SESSION['current_month'])));
    $_SESSION['current_date_time'] = date('d-m-Y H:i:s', $timelink);
} else if (($view == 'previous') && ($_SESSION['current_date_time'] != $linktime)) {
    $_SESSION['current_month'] = date('Y-m', strtotime('-1 month', strtotime($_SESSION['current_month'])));
    $_SESSION['current_date_time'] = date('d-m-Y H:i:s', $timelink);
}
if (!empty($month) && !empty($year)) {
    $_SESSION['current_month'] = "$year-$month";
}

$reportobj3 = new stdClass();
$reportobj3 = get_report_class('newregistrants');
$params3 = array();
$params3[] = $_SESSION['current_month'];
$reportobj3->process_reportdata($reportobj3, $params3);


$reportobj4 = new stdClass();
$reportobj4 = get_report_class('enrolments_analytics');
$params4 = array();
$reportobj4->process_reportdata($reportobj4, $params4);
?>

<script type = "text/javascript"
        src = "https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart','line','table',annotationchart]
        }]
}"></script>

<div id="Learners-Page">
    <div class="row">
        <div class="learnerbar row-fluid">
            <div class="learner-total span8"> 
                <p>643<br/><span style="font-size:16px; font-weight: normal;">Total</span></p>
                <p>605<br/><span style="font-size:16px; font-weight: normal;">Registered</span></p>
                <p>5<br/><span style="font-size:16px; font-weight: normal;">Suspended</span></p>
                <p>13<br/><span style="font-size:16px; font-weight: normal;">Deleted</span></p>
            </div>		
        </div>	
    </div>
    <!--<h3><?php // echo isset($report_array[$reportid]) ? $report_array[$reportid] : '';   ?></h3>-->
    <div>
        <div class = "box45">
            <h3>Unique Sessions</h3>
            <?php
            if (empty($reportobj1->data)) {
                echo html_writer::tag('p', 'Sorry! No data exist for given period.', array('class' => 'alert alert-error'));
            }
            $formcontent1 .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/learner.php'), 'method' => 'post'));
//            $formcontent1 .= 'From Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'from_date_16', 'value' => $fromdate16, 'id' => 'from_date_16'));
//            $formcontent1 .= 'To Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'to_date_16', 'value' => $todate16, 'id' => 'to_date_16'));
            $formcontent1 .= html_writer::empty_tag('input', array('size' => '10', 'type' => 'text', 'name' => 'from_date_16', 'id' => 'from_date_16', 'class' => 'program-management-datepicker', 'value' => $fromdate16));
            $formcontent1 .= html_writer::empty_tag('input', array('size' => '10', 'type' => 'text', 'name' => 'to_date_16', 'id' => 'to_date_16', 'class' => 'program-management-datepicker', 'value' => $todate16));
            $formcontent1 .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit16', 'value' => 'submit'));
            $formcontent1 .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'reset16', 'value' => 'reset'));
            $formcontent1 .= html_writer::end_tag('form');
            //$formcontent1 .= html_writer::end_tag('div');
            echo $formcontent1;
            ?>
            <div id="unique_sessions" style="width: 600px; height:400px;"></div>
        </div>
    </div>

    <div>
        <h3>Registrants</h3>
        <div id="registrants" style="width: 500px; height: 500px;"></div>
    </div>

    <div class="coursebar row-fluid">
        <div class="left-Coursebar-total span8"> 
            <p><span style="font-size:16px; font-weight: normal;">Enrolled Learners</span></p>
            <p>640<br/><span style="font-size:16px; font-weight: normal;">Manual</span></p>
            <p>3<br/><span style="font-size:16px; font-weight: normal;">Self</span></p>
            <p>0<br/><span style="font-size:16px; font-weight: normal;">Cohort</span></p>
        </div>

        <div class="Right-Coursebar-total span4">
            <p><span style="font-size:16px; font-weight: normal;">Status</span></p>
            <p>501<br/><span style="font-size:16px; font-weight: normal;">Active</span></p>
            <p>142<br/><span style="font-size:16px; font-weight: normal;">Inactive</span></p>
        </div>		
    </div>	

    <div>
        <h3>New Registrants</h3>
        <?php
        echo '<a class="arrow_link previous" href="learner.php?view=previous&time=' . $time . '"title="previous"><span class="arrow_text">Previous Month </span></a>';
        echo '<a class="arrow_link previous" href="learner.php?view=current" title="previous"><span class="arrow_text">Current Month </span></a>';
        echo '<a class="arrow_link next" href="learner.php?view=next&time=' . $time . '"title="next"><span class="arrow_text">Next Month</span></a>';

//        echo $OUTPUT->heading(get_string('annotationchartnewregistrants', 'local_moodleanalytics'));
        $monthwithyear = monthname($_SESSION['current_month']);
        echo $OUTPUT->heading($monthwithyear);

        $content = '';
        $content .= html_writer::start_tag('form', array('action' => new moodle_url($link), 'method' => 'post'));
        $content .= html_writer::start_tag('div', array('class' => 'monthandyear'));
        $content .= get_string('selectyourmonth', 'local_moodleanalytics');
        $content .= html_writer::select($month_names, 'month', $month);
        $content .= get_string('selectyouryear', 'local_moodleanalytics');
        $content .= html_writer::select($yeararray, 'year', $year);
        $content .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('filter')));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('form');
        echo $content;
        ?>
        <div id='chart_div_new' style='width: 900px; height: 500px;'></div>
    </div>

    <div>
        <h3>Enrollment Analytics</h3>
        <div id="enrollment_analytics" style="width: 500px; height: 500px;"></div>
    </div>
</div>

<script type="text/javascript">
            google.setOnLoadCallback(drawChart);
            function drawChart() {
<?php if (!empty($reportobj1->data)) { ?>
                var data = new google.visualization.DataTable();
    <?php foreach ($reportobj1->headers as $header) { ?>
        <?php if (!empty($header)) { ?>
                        data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
        <?php } ?>
    <?php } ?>
                data.addRows([<?php echo implode(',', $reportobj1->data); ?>]);
<?php } ?>
            var chart = new google.visualization.<?php echo $reportobj1->charttype; ?>(document.getElementById('unique_sessions'));
                    var options = {
                    hAxis: {
                    title: '<?php echo isset($axis1->xaxis) ? $axis1->xaxis : ''; ?>',
                    },
                            vAxis: {
                            title: '<?php echo isset($axis1->yaxis) ? $axis1->yaxis : ''; ?>',
                            },
<?php // if($reportobj->charttype == 'Table'){            ?>
                    //                                pageSize : 10,
<?php // }           ?>
                    }
<?php if (empty($errors)) { ?>
                chart.draw(data, options);
<?php } ?>
            };</script>

<script type="text/javascript">
            google.setOnLoadCallback(drawChart3);
            function drawChart3() {
<?php if (!empty($reportobj2->data)) { ?>
                var data = google.visualization.arrayToDataTable([
                        [<?php echo $reportobj2->axis->xaxis . ',' . $reportobj2->axis->yaxis; ?>],
    <?php
    for ($i = 0; $i < count($reportobj2->data); $i++) {
        echo $reportobj2->data[$i];
    }
    ?>
                ]);
                        var options = {
                        pieHole: 0.4,
                        };
<?php } ?>

            var chart = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('registrants'));
                    chart.draw(data, options);
            }
</script>

<script type="text/javascript">
    //    google.charts.load('current', {'packages': ['annotationchart']});
    google.setOnLoadCallback(drawChart);
            function drawChart() {
            var data = new google.visualization.DataTable();
<?php foreach ($reportobj3->headers as $header) { ?>
    <?php if (!empty($header)) { ?>
                    data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
    <?php } ?>
<?php } ?>
            var strength = function (number) {
            var str = number.toString();
                    return str;
            };
                    var addNode = function (date, title1, noofstudent) {
                    data.addRows([
                            [new Date(date), noofstudent, title1, strength(noofstudent)]
                    ]);
                    };
                    var add = function (date, title1, noofstudent) {
                    var activityDate = date;
                            addNode(new Date(activityDate), title1, noofstudent);
                    };
<?php for ($i = 0; $i < count($reportobj3->data); $i++) { ?>
                add(<?php echo $reportobj3->data[$i]; ?>);
<?php } ?>

            var chart = new google.visualization.<?php echo $reportobj3->charttype; ?>(document.getElementById('chart_div_new'));
                    var options = {
                    displayAnnotations: true,
                            displayZoomButtons: true
                    };
                    chart.draw(data, options);
            }

</script>

<script type="text/javascript">
//    google.charts.load("current", {packages:['corechart','geochart']});
    google.setOnLoadCallback(drawChart);
            function drawChart() {
            var data = google.visualization.arrayToDataTable([
                    ['Enrolments', 'NumOfEnrolments', { role: "style" } ],
<?php
for ($i = 0; $i < count($reportobj4->data); $i++) {
    echo $reportobj4->data[$i];
}
?>
            ]);
                    var view = new google.visualization.DataView(data);
                    view.setColumns([0, 1,
                    { calc: "stringify",
                            sourceColumn: 1,
                            type: "string",
                            role: "annotation" },
                            2]);
                    var options = {
                    //  title: "Density of Precious Metals, in g/cm^3",
                    width: 600,
                            height: 400,
                            bar: {groupWidth: "95%"},
                            legend: { position: "none" },
                    };
                    var chart = new google.visualization.ColumnChart(document.getElementById("enrollment_analytics"));
                    chart.draw(view, options);
            }
</script>

<?php
echo $OUTPUT->footer();

