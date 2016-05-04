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
//$userid = optional_param('userid', 0, PARAM_INT);
$users = optional_param_array('userid', '', PARAM_INT);
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
// return tracking object
if (!empty($submit) && !empty($courseid) && !empty($users)) {
    $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));

//first make sure we have proper final grades - this must be done before constructing of the grade tree
    grade_regrade_final_grades($courseid);
//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
    $report = new grade_report_grader($courseid, $gpr, $context);
    $numusers = $report->get_numusers(true, true);

// final grades MUST be loaded after the processing
    $report->load_users();
    $report->load_final_grades();
} else {
    echo $OUTPUT->notification('Choose the filters');
}
$json_grades = array();
if (!empty($report) && !empty($report->grades)) {
    foreach ($report->grades as $grades => $grade) {
        foreach ($users as $key => $userid) {
            foreach ($grade as $gradeval) {
                if ($gradeval->grade_item->itemtype != 'course') {
                    if (!empty($userid) && ($gradeval->userid == $userid)) {
                        if (!empty($json_grades[$gradeval->grade_item->itemname])) {
                            $json_grades[$gradeval->grade_item->itemname] .= (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
                        } else {
                            $json_grades[$gradeval->grade_item->itemname] = "'" . $gradeval->grade_item->itemname . "'" . ',' . (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
                        }
                    }
                }
            }
        }
    }
    $averagegrade = $report->get_right_avg_row();
    $actavggrade = array();
    foreach ($averagegrade as $avggradvalue) {
        foreach ($avggradvalue->cells as $avggrade) {
            if (!empty($avggrade->text)) {
                $actavggrade[] = floatval($avggrade->text);
            }
        }
    }
    $count = 0;
    foreach ($json_grades as $key => $value) {
        $json_grades[$key] .= $actavggrade[$count] . ',';
        $count++;
    }
}

$json_grades_array = array();
foreach ($json_grades as $key => $grade_info) {
    $grade_info = TRIM($grade_info, ',');
    $json_grades_array[] = "[" . $grade_info . "]";
}
$gradeheaders = array();
if (!empty($users)) {
    foreach ($users as $key => $userid) {
        $user = $DB->get_record('user', array('id' => $userid));
        $gradeheaders[] = "'" . $user->username . " - Grade '";
    }
}
$gradeheaders[] = "'" . 'Trendline' . "'";
//$chartoptions = array('BarChart', 'GeoChart', 'ColumnChart', 'Histogram', 'PieChart', 'LineChart');
$chartoptions = array(1 => 'LineChart', 2 => 'ComboChart');
$courselist = get_courses();
$userlist = get_course_users($courseid);
$courses = array();
foreach ($courselist as $course) {
    if ($course != SITEID) {
        $courses[$course->id] = $course->fullname;
    }
}
$formcontent = html_writer::start_tag('div');
$formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php?id=' . $courseid), 'method' => 'post'));
$formcontent .= 'Course : ' . html_writer::select($courses, 'id', $courseid, array('' => 'Select course'), array('id' => 'coursedropdown'));
$formcontent .= 'Chart Type : ' . html_writer::select($chartoptions, 'type', $charttype);
$formcontent .= 'User : ' . html_writer::select($userlist, 'userid[]', $users, array('' => 'Select User'), array('id' => 'userdropdown', 'multiple' => 'multiple'));
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
        <h3>Course Progress Report</h3>
        <div id="course-grade" style="width:1000px; height:800px;"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawChart);
            function drawChart() {             var data = new google.visualization.DataTable();
                    data.addColumn('string', 'Activities');
<?php foreach ($gradeheaders as $gradehead) { ?>
                data.addColumn('number',<?php echo $gradehead; ?>);
<?php } ?>
            data.addRows([<?php echo implode(',', $json_grades_array); ?>]);
                    var chart = new google.visualization.<?php echo $chartoptions[$charttype]; ?>(document.getElementById('course-grade'));
                    var options = {
                    hAxis: {
                    title: 'Activities'
                    },
                            vAxis: {
                            title: 'Grades'
                            },
<?php if ($chartoptions[$charttype] == 'ComboChart') { ?>
                        seriesType: 'bars',
                                series: {2: {type: 'line'}}
<?php } ?>
                    };
                    chart.draw(data, options);
            }
</script>
<?php
echo $OUTPUT->footer();
