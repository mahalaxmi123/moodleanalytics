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
//$charttype = optional_param('type', '', PARAM_ALPHANUM);
$submit14 = optional_param('submit14', '', PARAM_ALPHANUM);
$submit5 = optional_param('submit5', '', PARAM_ALPHANUM);
$reset14 = optional_param('reset14', '', PARAM_ALPHANUM);
$reset5 = optional_param('reset5', '', PARAM_ALPHANUM);
//$reportid = optional_param('reportid', '', PARAM_INT);
//$quizid = optional_param('quizid', '', PARAM_INT);
//$days = optional_param('days_filter', '', PARAM_TEXT);
$from_date_14 = optional_param('from_date_14', '', PARAM_TEXT);
$from_date_5 = optional_param('from_date_5', '', PARAM_TEXT);
$to_date_14 = optional_param('to_date_14', '', PARAM_TEXT);
$to_date_5 = optional_param('to_date_5', '', PARAM_TEXT);
//$users = optional_param_array('username', '', PARAM_TEXT);
$context = context_system::instance();
if (!empty($courseid)) {
    $context = context_course::instance($courseid);
}
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/moodleanalytics/course.php');
$PAGE->requires->js('/local/moodleanalytics/module.js', true);
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php');

$reportname = get_string('course');

$fromdate14 = $from_date_14;
$todate14 = $to_date_14;

if (empty($from_date_14)) {
    $from_date_default_14 = userdate((time() - (DAYSECS * 7)), '%Y-%m-%d');
    $fromdate14 = $from_date_default_14;
    $from_date_14 = new DateTime($from_date_default_14);
} else {
    $from_date_14 = new DateTime($from_date_14);
    $_SESSION['fromdate'] = $from_date_14;
}
if (empty($to_date_14)) {
    $to_date_default_14 = userdate(time(), '%Y-%m-%d');
    $todate14 = $to_date_default_14;
    $to_date_14 = new DateTime($to_date_default_14);
} else {
    $to_date_14 = new DateTime($to_date_14);
    $_SESSION['todate'] = $to_date_14;
}

if ($reset14) {
    unset($_SESSION['fromdate']);
    unset($_SESSION['todate']);
    redirect($returnurl);
}

$fromdate5 = $from_date_5;
$todate5 = $to_date_5;

if (empty($from_date_5)) {
    $from_date_default_5 = userdate((time() - (DAYSECS * 7)), '%Y-%m-%d');
    $fromdate5 = $from_date_default_5;
    $from_date_5 = new DateTime($from_date_default_5);
} else {
    $from_date_5 = new DateTime($from_date_5);
    $_SESSION['timestart'] = $from_date_5;
}
if (empty($to_date_5)) {
    $to_date_default_5 = userdate(time(), '%Y-%m-%d');
    $todate5 = $to_date_default_5;
    $to_date_5 = new DateTime($to_date_default_5);
} else {
    $to_date_5 = new DateTime($to_date_5);
    $_SESSION['timefinish'] = $to_date_5;
}

if ($reset5) {
    unset($_SESSION['timestart']);
    unset($_SESSION['timefinish']);
    redirect($returnurl);
}

echo $OUTPUT->header();
$errors = array();

$params1 = array();
if (empty($_SESSION['from_date']) && empty($_SESSION['todate'])) {
    $params1['fromdate'] = $from_date_14;
    $params1['todate'] = $to_date_14;
} else {
    $params1['fromdate'] = $_SESSION['fromdate'];
    $params1['todate'] = $_SESSION['todate'];
}

$reportobj1 = new stdClass();
$reportobj1 = get_report_class('new_courses');
$reportobj1->process_reportdata($reportobj1, $params1);
$axis1 = new stdClass();
$axis1 = $reportobj1->get_axis_names();
$formcontent1 = "";

$params2 = array();
if (empty($_SESSION['timestart']) && empty($_SESSION['timefinish'])) {
    $params2['timestart'] = $from_date_5;
    $params2['timefinish'] = $to_date_5;
} else {
    $params2['timestart'] = $_SESSION['timestart'];
    $params2['timefinish'] = $_SESSION['timefinish'];
}

$reportobj2 = new stdClass();
$reportobj2 = get_report_class('enrollmentspercourse');
$reportobj2->process_reportdata($reportobj2, $params2);
$axis2 = new stdClass();
$axis2 = $reportobj2->get_axis_names('enrollmentspercourse');
$formcontent2 = "";
?>
<script type = "text/javascript"
        src = "https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart','line','table']
        }]
}"></script>

<div id="Coursedasboard-Page">
    <div class="row">
        <div class="coursebar row-fluid">
            <div class="left-Coursebar-total span8"> 
                <p>138<br/><span style="font-size:16px; font-weight: normal;">Total</span></p>
                <p>130<br/><span style="font-size:16px; font-weight: normal;">Visible</span></p>
                <p>8<br/><span style="font-size:16px; font-weight: normal;">Hidden</span></p>
                <p>1553<br/><span style="font-size:16px; font-weight: normal;">Module</span></p>
            </div>

            <div class="Right-Coursebar-total span4"> 
                <p>23<br/><span style="font-size:16px; font-weight: normal;">Trainers</span></p>
                <p>610<br/><span style="font-size:16px; font-weight: normal;">Learners</span></p>
            </div>		
        </div>	

        <div>
            <div class = "box45">
                <h3>New Courses</h3>
                <?php
                if (empty($reportobj1->data)) {
                    echo html_writer::div('Sorry! No data exist for given period.', 'alert alert-error');
                }
                $formcontent1 .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php'), 'method' => 'post'));
                $formcontent1 .= 'From Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'from_date_14', 'value' => $fromdate14, 'id' => 'from_date_14'));
                $formcontent1 .= 'To Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'to_date_14', 'value' => $todate14, 'id' => 'to_date_14'));
                $formcontent1 .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit14', 'value' => 'submit'));
                $formcontent1 .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'reset14', 'value' => 'reset'));
                $formcontent1 .= html_writer::end_tag('form');
                $formcontent1 .= html_writer::end_tag('div');
                echo $formcontent1;
                ?>
                <div id="new_courses" style="width: 900px; height:400px;"></div>
            </div>
            <div class = "box45">
                <h3>Enrollments Per Course</h3>
                <?php
                if (empty($reportobj2->data)) {
                    echo html_writer::div('Sorry! No data exist for given period.', 'alert alert-error');
                }
                $formcontent2 .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php'), 'method' => 'post'));
                $formcontent2 .= 'From Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'from_date_5', 'value' => $fromdate5, 'id' => 'from_date_5'));
                $formcontent2 .= 'To Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'to_date_5', 'value' => $todate5, 'id' => 'to_date_5'));
                $formcontent2 .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit5', 'value' => 'submit'));
                $formcontent2 .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'reset5', 'value' => 'reset'));
                $formcontent2 .= html_writer::end_tag('form');
                $formcontent2 .= html_writer::end_tag('div');
                echo $formcontent2;
                ?>
                <div id="enrollmentpercourse" style="width: 900px; height:400px;"></div>
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
                    var chart = new google.visualization.<?php echo $reportobj1->charttype; ?>(document.getElementById('new_courses'));
                            var options = {
                            hAxis: {
                            title: '<?php echo isset($axis1->xaxis) ? $axis1->xaxis : ''; ?>',
                            },
                                    vAxis: {
                                    title: '<?php echo isset($axis1->yaxis) ? $axis1->yaxis : ''; ?>',
                                    },
<?php // if($reportobj->charttype == 'Table'){        ?>
                            //                                pageSize : 10,
<?php // }       ?>
                            }
<?php if (empty($errors)) { ?>
                        chart.draw(data, options);
<?php } ?>
                    };</script>


        <script type="text/javascript">
                    google.setOnLoadCallback(drawEnrolments);
                    function drawEnrolments() {
<?php if (!empty($reportobj2->data)) { ?>
                        var data = new google.visualization.DataTable();
    <?php foreach ($reportobj2->headers as $header) { ?>
        <?php if (!empty($header)) { ?>
                                data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
        <?php } ?>
    <?php } ?>
                        data.addRows([<?php echo implode(',', $reportobj2->data); ?>]);
<?php } ?>
                    var chart = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('enrollmentpercourse'));
                            var options = {
                            hAxis: {
                            title: '<?php echo isset($axis2->xaxis) ? $axis2->xaxis : ''; ?>',
                            },
                                    vAxis: {
                                    title: '<?php echo isset($axis2->yaxis) ? $axis2->yaxis : ''; ?>',
                                    },
                                    backgroundColor:{fill:"transparent"},
                                    title: '',
                                    pieHole: 0.4,
                                    chartArea: {
                                    width: '100%'
                                    }
                            };
                            chart.draw(data, options);
                    }

        </script>
        <?php
        echo $OUTPUT->footer();
        