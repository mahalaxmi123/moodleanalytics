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
require_once('lib.php');
require_login();
$courseid = optional_param('id', '', PARAM_INT);        // course id
$charttype = optional_param('type', '', PARAM_ALPHANUM);
$submit = optional_param('submit', '', PARAM_ALPHANUM);
$reset = optional_param('reset', '', PARAM_ALPHANUM);
$reportid = optional_param('reportid', '', PARAM_INT);
$quizid = optional_param('quizid', '', PARAM_INT);
$users = optional_param_array('username', '', PARAM_TEXT);
$context = context_system::instance();
$timelink = optional_param('time', 0, PARAM_INT);
$linktime = date('d-m-Y H:i:s', $timelink);
$view = optional_param('view', 'now', PARAM_ALPHA);
$print = optional_param('print', 0, PARAM_ALPHA);

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/moodleanalytics/coursereport.php');
$PAGE->requires->js('/local/moodleanalytics/module.js', true);
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/coursereport.php');

if ($reset) {
    redirect($returnurl);
}

echo $OUTPUT->header();
$errors = array();

$reportobj = new stdClass();
$reportobj = get_report_class(4);
$params = new stdClass();

$reportobj->process_reportdata($reportobj, $params);
$axis = new stdClass();
if (!empty($reportid) & $reportid >= 1) {
    $axis = $reportobj->get_axis_names('Registrations');
}

$reportobj1 = new stdClass();
$reportobj1 = get_report_class(8);
$params1 = array();
$reportobj1->process_reportdata($reportobj1, $params1);
$axis1 = new stdClass();
$axis1 = $reportobj1->get_axis_names('teachingactivity');

$reportobj2 = new stdClass();
$reportobj2 = get_report_class(7);
$params2 = array();
$reportobj2->process_reportdata($reportobj2, $params2);
$axis2 = new stdClass();
$axis2 = $reportobj2->get_axis_names('courseenrollments');

//echo "$linktime";
$time = time();
echo '<a class="arrow_link previous" href="' . $CFG->wwwroot . '/local/moodleanalytics/coursereport.php?view=previous&time=' . $time . '"title="previous"><span class="arrow_text">Previous Month </span></a>';
echo '<a class="arrow_link previous" href="' . $CFG->wwwroot . '/local/moodleanalytics/coursereport.php??view=current" title="previous"><span class="arrow_text">Current Month </span></a>';
echo '<a class="arrow_link next" href="' . $CFG->wwwroot . '/local/moodleanalytics/coursereport.php??view=next&time=' . $time . '"title="next"><span class="arrow_text">Next Month</span></a>';

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

$monthwithyear = monthname($_SESSION['current_month']);
//$firstdate = $date("Y,m",)    

$reportobj3 = new stdClass();
$reportobj3 = get_report_class(12);
$params = array();
$params[] = $_SESSION['current_month'];
$reportobj3->process_reportdata($reportobj3, $params);

$reportobj5 = new stdClass();
$reportobj5 = get_report_class(13);
$params = new stdClass();
$reportobj5->process_reportdata($reportobj5, $params);

//if (!$print) {
//    echo '<a class="arrow_link previous" href="threedates_annotation.php?view=previous&time=' . $time . '"title="previous"><span class="arrow_text">Previous Month </span></a>';
//    echo '<a class="arrow_link previous" href="threedates_annotation.php?view=current" title="previous"><span class="arrow_text">Current Month </span></a>';
//    echo '<a class="arrow_link next" href="threedates_annotation.php?view=next&time=' . $time . '"title="next"><span class="arrow_text">Next Month</span></a>';
//}
//
//if (empty($_SESSION['current_date_time'])) {
//    $_SESSION['current_date_time'] = 0;
//}
////echo $_SESSION['current_date_time'];
//if (!isset($_SESSION['current_month']) || ($view == 'current')) {
//    $_SESSION['current_month'] = date('Y-m');
//} else if (($view == 'next') && ($_SESSION['current_date_time'] != $linktime)) {
//    $_SESSION['current_month'] = date('Y-m', strtotime('+1 month', strtotime($_SESSION['current_month'])));
//    $_SESSION['current_date_time'] = date('d-m-Y H:i:s', $timelink);
//} else if (($view == 'previous') && ($_SESSION['current_date_time'] != $linktime)) {
//    $_SESSION['current_month'] = date('Y-m', strtotime('-1 month', strtotime($_SESSION['current_month'])));
//    $_SESSION['current_date_time'] = date('d-m-Y H:i:s', $timelink);
//}

$reportobj4 = new stdClass();
$reportobj4 = get_report_class(11);
$params = array();
$params[] = $_SESSION['current_month'];
$reportobj4->process_reportdata($reportobj4, $params);

$reportobj6 = new stdClass();
$reportobj6 = get_report_class(20);
$params6 = new stdClass();
$reportobj6->process_reportdata($reportobj6, $params6);
?>
<script type="text/javascript"
        src="https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart', 'table','annotationchart']
        }]
}"></script>
<div>
    <h3>New Courses</h3>
    <div id='chart_div' style='width: 900px; height: 500px;'></div>
</div>
<div>
    <h3>New Registrants</h3>
    <div id='chart_div_new' style='width: 900px; height: 500px;'></div>
</div>
<div>
    <div class="box45 pull-right">
        <h3>Teaching activity</h3>
        <div id="teachinactivity" style="width: 400px; height:400px;"></div>
    </div>
    <div class="box45 pull-left">
        <h3>Course enrollments</h3>
        <div id="course-enrollments" style="width:500px; height:500px;"></div>
    </div>
    <div>
        <h3>Registrants</h3>
        <div id="registrants" style="width: 500px; height: 500px;"></div>
    </div>
</div>
<div>
    <div class="box45 pull-left">
        <h3>User enrol</h3>
        <div id="user-enrol" style="width: 500px; height: 500px;"></div>
    </div>
</div>
<script type="text/javascript">
//            google.charts.load('current', {'packages':['table']});
            google.setOnLoadCallback(drawCourseenrolments);
            function drawCourseenrolments() {

            var data = new google.visualization.DataTable();
<?php foreach ($reportobj1->headers as $header) { ?>
    <?php if (!empty($header)) { ?>
                    data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
    <?php } ?>
<?php } ?>
            data.addRows([<?php echo implode(',', $reportobj1->data); ?>]);
                    var table = new google.visualization.<?php echo $reportobj1->charttype; ?>(document.getElementById('teachinactivity'));
                    table.draw(data, {showRowNumber: true, width: '100%', height: '100%', pageSize :10});
            }

</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawCourseenrolments);
            function drawCourseenrolments() {
            var data = new google.visualization.DataTable();
<?php foreach ($reportobj2->headers as $header) { ?>
    <?php if (!empty($header)) { ?>
                    data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
    <?php } ?>
<?php } ?>
            data.addRows([<?php echo implode(',', $reportobj2->data); ?>]);
                    var table = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('course-enrollments'));
                    table.draw(data, {showRowNumber: true, width: '100%', height: '100%', pageSize :10});
            }

</script>
<script type = "text/javascript">
    google.setOnLoadCallback(drawChart);
            function drawChart() {
            var data = new google.visualization.DataTable();
<?php foreach ($reportobj3->headers as $header) { ?>
    <?php if (!empty($header)) { ?>
                    data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
    <?php } ?>
<?php } ?>
            var strength = function (number) {
            if (number <= 20) {
            return "Poor";
            } else if (number > 20 && number <= 40) {
            return "Below Average";
            } else if (number > 40 && number <= 60) {
            return "Above Average";
            } else if (number > 60 && number <= 80) {
            return "Good";
            } else if (number >= 80) {
            return "Excellent";
            } else {
            return "not a number";
            }
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
                    add(<?php echo $reportobj3->firstdatestring; ?>, 'Strength of Enroll Students',<?php echo $reportobj3->nfirst; ?>);
                    add(<?php echo $reportobj3->middatestring; ?>, 'Strength of Enroll Students', <?php echo $reportobj3->nmid; ?>);
                    add(<?php echo $reportobj3->lastdatestring; ?>, 'Strength of Enroll Students', <?php echo $reportobj3->nlast; ?>);
                    var chart = new google.visualization.<?php echo $reportobj3->charttype; ?>(document.getElementById('chart_div'));
                    var options = {
                    displayAnnotations: true
                    };
                    chart.draw(data, options);
            }

</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawChart1);
            function drawChart1() {
            var data = new google.visualization.DataTable();
<?php foreach ($reportobj4->headers as $header) { ?>
    <?php if (!empty($header)) { ?>
                    data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
    <?php } ?>
<?php } ?>
            var strength = function (number) {
            if (number <= 20) {
            return "Poor";
            } else if (number > 20 && number <= 40) {
            return "Below Average";
            } else if (number > 40 && number <= 60) {
            return "Above Average";
            } else if (number > 60 && number <= 80) {
            return "Good";
            } else if (number >= 80) {
            return "Excellent";
            } else {
            return "not a number";
            }
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
                    add(<?php echo $reportobj4->firstdatestring; ?>, 'Strength of Enroll Students',<?php echo $reportobj4->nfirst; ?>);
                    add(<?php echo $reportobj4->middatestring; ?>, 'Strength of Enroll Students', <?php echo $reportobj4->nmid; ?>);
                    add(<?php echo $reportobj4->lastdatestring; ?>, 'Strength of Enroll Students', <?php echo $reportobj4->nlast; ?>);
                    var chart = new google.visualization.<?php echo $reportobj4->charttype; ?>(document.getElementById('chart_div_new'));
                    var options = {
                    displayAnnotations: true
                    };
                    chart.draw(data, options);
            }

</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawChart2);
            function drawChart2() {
            var data = google.visualization.arrayToDataTable([
                    [<?php echo $reportobj5->axis->xaxis . ',' . $reportobj5->axis->yaxis; ?>],
<?php echo implode(',', $reportobj5->data); ?>

            ]);
                    var options = {
                    title: <?php echo "'" . $reportobj5->charttitle . "'"; ?>
                    };
                    var chart = new google.visualization.<?php echo $reportobj5->charttype; ?>(document.getElementById('user-enrol'));
                    chart.draw(data, options);
            }
</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawChart3);
            function drawChart3() {
<?php if (!empty($reportobj6->data)) { ?>
                var data = google.visualization.arrayToDataTable([
                        [<?php echo $reportobj6->axis->xaxis . ',' . $reportobj6->axis->yaxis; ?>],
    <?php
    for ($i = 0; $i < count($reportobj6->data); $i++) {
        echo $reportobj6->data[$i];
    }
    ?>
                ]);
                        var options = {
                        title: <?php echo "'" . $reportobj6->charttitle . "'"; ?>,
                                pieHole: 0.4,
                        };
<?php } ?>

            var chart = new google.visualization.<?php echo $reportobj6->charttype; ?>(document.getElementById('registrants'));
                    chart.draw(data, options);
            }
</script>
</head>

<?php
echo $OUTPUT->footer();
