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
$courseid = optional_param('id', 0, PARAM_INT);        // course id
$charttype = optional_param('type', '', PARAM_ALPHANUM);
$userid = optional_param('userid', 0, PARAM_INT);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/moodleanalytics/course.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php');

$reportname = get_string('course', 'gradereport_grader');
echo $OUTPUT->header();
// return tracking object
$gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));

//first make sure we have proper final grades - this must be done before constructing of the grade tree
grade_regrade_final_grades($courseid);
//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
$report = new grade_report_grader($courseid, $gpr, $context, $page, $sortitemid);
$numusers = $report->get_numusers(true, true);

// final grades MUST be loaded after the processing
$report->load_users();
$report->load_final_grades();
$json_grades = array();
foreach ($report->grades as $grades => $grade) {
    foreach ($grade as $gradeval) {
        if ($gradeval->grade_item->itemtype != 'course') {
            if (!empty($userid) && ($gradeval->userid == $userid)) {
                $json_grades[$gradeval->grade_item->itemname] = "'".$gradeval->grade_item->itemname . "'".',' . (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
            }
        }
    }
}
$json_grades_array = array();
foreach ($json_grades as $key => $grade_info) {
    $grade_info = TRIM($grade_info, ',');
    $json_grades_array[] = "[" .$grade_info . "]";
}
$gradeheaders = '';
$user = $DB->get_record('user', array('id' => $userid));
$gradeheaders = "'" . $user->username . "'";
//$chartoptions = array('BarChart', 'GeoChart', 'ColumnChart', 'Histogram', 'PieChart', 'LineChart');
$chartoptions = array('LineChart');
$courselist = get_courses();
$courses = array();
foreach ($courselist as $course) {
    if ($course != SITEID) {
        $courses[$course->id] = $course->fullname;
    }
}
$formcontent = html_writer::start_tag('div');
$formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php?id=' . $courseid), 'method' => 'post'));
$formcontent .= 'Course : ' . html_writer::select($courses, 'id', $courseid);
$formcontent .= 'Chart Type : ' . html_writer::select($chartoptions, 'type', $charttype);
$formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => 'submit'));
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
        <h3>Course Grade Report</h3>
        <div id="course-grade" style="width:960px; height:600px;"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawChart);
            function drawChart() {
            var data = new google.visualization.DataTable();
                    data.addColumn('string',<?php echo $gradeheaders; ?>);
                    data.addColumn('number','Grade value');
                    data.addRows([<?php echo implode(',',$json_grades_array); ?>]);
                    var chart = new google.charts.Line(document.getElementById('course-grade'));
                    chart.draw(data, {
                    });
            }
</script>
<?php
echo $OUTPUT->footer();
