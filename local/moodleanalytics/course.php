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
$charttype = optional_param('type', '', PARAM_ALPHANUM);
$submit = optional_param('submit', '', PARAM_ALPHANUM);
$reset = optional_param('reset', '', PARAM_ALPHANUM);
$reportid = optional_param('reportid', '', PARAM_INT);
$quizid = optional_param('quizid', '', PARAM_INT);
$users = optional_param_array('username', '', PARAM_TEXT);
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

if ($reset) {
    redirect($returnurl);
}
$reportname = get_string('course');
echo $OUTPUT->header();
$errors = array();

$reportobj = new stdClass();
if ($reportid) {
    $reportobj = get_report_class($reportid);
}

// return tracking object
if (!empty($submit) && !empty($courseid) && !empty($users) && !empty($charttype)) {
    $reportobj->process_reportdata($reportobj,$courseid, $users, $charttype);
} elseif (!empty($submit)) {
    if (empty($reportid)) {
        $errors[] = 'Report Name';
    }
    if (empty($courseid)) {
        $errors[] = 'Course';
    }
    if (empty($charttype)) {
        $errors[] = 'Chart Type';
    }
    if (empty($users)) {
        $errors[] = 'User(s)';
    }
} else {
    echo html_writer::div('Please select the filters to proceed.', 'alert alert-info');
}

$report_array = get_coursereports();
$chartoptions = array();
$userlist = array();
//$chartoptions = array('BarChart', 'GeoChart', 'ColumnChart', 'Histogram', 'PieChart', 'LineChart');
$chartoptions = get_chart_types();
$userlist = get_course_users($courseid);
if ($reportid == 2) {
    
}

if ($reportid == 3) {
    
}

if ($reportid == 1 && !empty($users)) {
    
}

$courselist = get_courses();
$courses = array();
foreach ($courselist as $course) {
    if ($course->id != SITEID) {
        $courses[$course->id] = $course->fullname;
    }
}
$axis = new stdClass();
if (!empty($reportid) & $reportid >= 1) {
    $axis = $reportobj->get_axis_names($report_array[$reportid]);
}
$formcontent = html_writer::start_tag('div');
if (!empty($errors)) {
    $error = implode(", ", $errors);
    $formcontent .= html_writer::div("Please select $error", 'alert alert-danger');
}
$formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php'), 'method' => 'post'));
$formcontent .= 'Report Name : ' . html_writer::select($report_array, 'reportid', $reportid, array('' => 'Select report'), array('id' => 'reportdropdown'));
$formcontent .= 'Course : ' . html_writer::select($courses, 'id', $courseid, array('' => 'Select course'), array('id' => 'coursedropdown', 'class' => 'coursedropdown'));
if (isset($reportobj->quiz_array)) {
    $formcontent .= 'Activity Name : ' . html_writer::select($reportobj->quiz_array, 'quizid', $quizid, array('' => 'Select quiz'), array('id' => 'quizdropdown'));
}
$formcontent .= 'Chart Type : ' . html_writer::select($chartoptions, 'type', $charttype, array('Select chart'), array('id' => 'chartdropdown'));
$formcontent .= '</br>';
$formcontent .= 'User(s) (You can select <strong>single / multiple</strong> users here): ' . html_writer::select($userlist, 'username[]', $users, array('' => 'Select User(s)'), array('id' => 'userdropdown', 'multiple' => 'multiple'));
$formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => 'submit'));
$formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'reset', 'value' => 'reset'));
$formcontent .= html_writer::end_tag('form');
$formcontent .= html_writer::end_tag('div');
echo $formcontent;
?>
<script type = "text/javascript"
        src = "https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart','line']
        }]
}"></script>
<div>
    <div class="box45 pull-left">
        <h3><?php echo isset($report_array[$reportid]) ? $report_array[$reportid] : ''; ?></h3>
        <h5><?php echo isset($info) ? $info : ''; ?></h5>
        <div id="course-grade" style="width:1000px; height:800px;"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawChart);
            function drawChart() {
            var data = new google.visualization.DataTable();
                    data.addColumn('string', 'Data');
<?php foreach ($reportobj->gradeheaders as $gradehead) { ?>
                data.addColumn('number',<?php echo $gradehead; ?>);
<?php } if ($reportid == 1) { ?>
                data.addRows([<?php echo implode(',', $reportobj->json_grades_array); ?>]);
<?php } elseif ($reportid == 2) { ?>
                data.addRows([<?php echo implode(',', $quizdetails); ?>]);
<?php } else { ?>
                data.addRows([<?php echo implode(',', $chartdetails); ?>]);
<?php } ?>
            var chart = new google.visualization.<?php echo $chartoptions[$charttype]; ?>(document.getElementById('course-grade'));
                    var options = {
                    hAxis: {
                    title: '<?php echo isset($axis->xaxis) ? $axis->xaxis : ''; ?>',
                    },
                            vAxis: {
                            title: '<?php echo isset($axis->yaxis) ? $axis->yaxis : ''; ?>',
                            },
<?php if ($chartoptions[$charttype] == 'BubbleChart' && $reportid == 3) { ?>
                        bubble: {textStyle: {fontSize: 11}}
<?php } ?>
<?php if ($chartoptions[$charttype] == 'ComboChart') { ?>
                        seriesType: 'bars',
    <?php if ($reportid == 1) { ?>
                            series: {<?php
        echo (isset($position) ? $position : '');
        ?>: {type: 'line', color : 'black'}},
                                    //                                    trendlines : {<?php echo $position; ?>:{
                                    //                                type: 'exponential',
                                    //                                        color: 'green',
                                    //                                        visibleInLegend: true,
                                    //                                        pointVisible : true,
                                    //                                        pointSize : 10,
                                    //                                }},

        <?php
    }
}
?>
                    };
<?php if (empty($errors)) { ?>
                chart.draw(data, options);
<?php } ?>
            }
</script>
<?php
echo $OUTPUT->footer();
