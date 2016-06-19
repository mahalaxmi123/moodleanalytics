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
$days = optional_param('days_filter', '', PARAM_TEXT);
$from_date = optional_param('from_date', '', PARAM_TEXT);
$to_date = optional_param('to_date', '', PARAM_TEXT);
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

$reportobj->quizid = $quizid;

//$fromdate = new DateTime($from_date);
//$from_date = $fromdate->format('U');
//$todate = new DateTime($to_date);
//$to_date = $todate->format('U') + DAY_1;

$from_date = strtotime($from_date);
$to_date = strtotime($to_date)+DAY_1;

// return tracking object
if (!empty($submit)) {
    if ($reportid != 4 && $reportid !== 5 && !empty($courseid) && !empty($users) && !empty($charttype)) {
        $reportobj->process_reportdata($reportobj, $courseid, $users, $charttype);
    } elseif($reportid == 4) {
        $reportobj->process_reportdata($reportobj, $from_date, $to_date);
    } else {
        $reportobj->process_reportdata($reportobj);
    }
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
$days_filter = array();
//$chartoptions = array('BarChart', 'GeoChart', 'ColumnChart', 'Histogram', 'PieChart', 'LineChart');
$chartoptions = get_chart_types();
$userlist = get_course_users($courseid);
$days_filter = get_days_filter();
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
$formcontent .= html_writer::select($days_filter, 'days_filter', $days, array('' => 'Select days'), array('id' => 'daysdropdown'));
$formcontent .= 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'from_date'));
$formcontent .= 'To Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'to_date'));
$formcontent .= 'Course : ' . html_writer::select($courses, 'id', $courseid, array('' => 'Select course'), array('id' => 'coursedropdown', 'class' => 'coursedropdown'));
$formcontent .= 'Activity Name : ' . html_writer::select(isset($reportobj->quiz_array) ? $reportobj->quiz_array : array(''), 'quizid', $quizid, array('' => 'Select Quiz'), array('id' => 'quizdropdown'));
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
        'packages':['corechart','geochart','line','table']
        }]
}"></script>
<div>
    <div class="box45 pull-left">
        <h3><?php echo isset($report_array[$reportid]) ? $report_array[$reportid] : ''; ?></h3>
        <h5><?php echo isset($reportobj->info) ? $reportobj->info : ''; ?></h5>
        <div id="course-grade" style="width:1000px; height:800px;"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawChart);
            function drawChart() {
<?php if (!empty($reportobj->data)) { ?>
                var data = new google.visualization.DataTable();
                        data.addColumn('string', 'Data');
    <?php foreach ($reportobj->gradeheaders as $gradehead) { ?>
        <?php if (!empty($gradehead) && $reportid != 5) { ?>
                        data.addColumn('number',<?php echo $gradehead; ?>);
        <?php } else { ?>
                        data.addColumn('string',<?php echo $gradehead; ?>);
        <?php }?>
    <?php } ?>
                data.addRows([<?php echo implode(',', $reportobj->data); ?>]);
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
        echo (isset($reportobj->act_avg_position) ? $reportobj->act_avg_position : '');
        ?>: {type: 'line', color : 'black'}},
                                    //                                    trendlines : {<?php echo $reportobj->act_avg_position; ?>:{
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
