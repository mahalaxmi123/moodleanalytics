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
$courseid = optional_param('id', SITEID, PARAM_INT);        // course id
$charttype = optional_param('type', '', PARAM_ALPHANUM);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/moodleanalytics/course.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php');

// basic access checks
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}

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
//foreach ($report->grades as $grades => $grade) {
//    foreach ($grade as $gradeval) {
//        if ($gradeval->grade_item->itemtype != 'course') {
//            $gradeheaders[$gradeval->grade_item->itemname] = $gradeval->grade_item->itemname;
//            $itemname = $gradeval->grade_item->itemname;
//            if (isset($json_grades)) {
//                $json_grades[$gradeval->userid] .= (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
//            } else {
//                $json_grades[$gradeval->userid] = (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
//            }
//        }
//    }
//}
foreach ($report->grades as $grades => $grade) {
    foreach ($grade as $gradeval) {
        if ($gradeval->grade_item->itemtype != 'course') {
            $user = $DB->get_record('user', array('id' => $gradeval->userid));
            $gradeheaders[$user->id] = $user->username;
            $itemname = $gradeval->grade_item->itemname;
            if (isset($json_grades[$gradeval->userid])) {
                $json_grades[$gradeval->userid] .= (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
            } else {
                $json_grades[$gradeval->userid] = (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
            }
        }
    }
}
$json_grades_array = array();
foreach ($json_grades as $key => $grade_info) {
    $grade_info = TRIM($grade_info, ',');
    $json_grades_array[] = "[" . $grade_info . "]";
}

$gradeheaders_array = array();
foreach ($gradeheaders as $key => $head) {
    $gradeheaders_array[] = "'" . $head . "'";
}
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
//            var data = google.visualization.arrayToDataTable([[<?php echo implode(',', $gradeheaders_array); ?>], <?php echo implode(',', $json_grades_array); ?>]);
//                    var chart = new google.visualization.<?php echo $charttype ? $chartoptions[$charttype] : $chartoptions[0]; ?>(document.getElementById('course-grade'));
            var data = new google.visualization.DataTable();
                    data.addColumn('number', 'Resource'),
<?php foreach ($gradeheaders as $gradeheader) {
    ?>
                data.addColumn('number', '<?php echo $gradeheader; ?>');
<?php }
?>
//                    data.addColumn('number', 'Day');
//                    data.addColumn('number', 'Guardians of the Galaxy');
//                    data.addColumn('number', 'The Avengers');
//                    data.addColumn('number', 'Transformers: Age of Extinction');
//                    data.addColumn('number', 'Transformers: Age of Extinction');
//                    data.addColumn('number', 'Transformers: Age of Extinction');
//                    data.addColumn('number', 'Transformers: Age of Extinction'); 
//                    data.addColumn('number', 'Transformers: Age of Extinction');
//                    data.addColumn('number', 'Transformers: Age of Extinction');
//                    data.addColumn('number', 'Transformers: Age of Extinction');
//                    data.addColumn('number', 'Transformers: Age of Extinction');
//                    data.addColumn('number', 'Transformers: Age of Extinction');
            data.addRows([
//                            [1, 37.8, 80.8, 41.8],
//                            [2, 30.9, 69.5, 32.4],
//                            [3, 25.4, 57, 25.7],
//                            [4, 11.7, 18.8, 10.5],
//                            [5, 11.9, 17.6, 10.4],
//                            [6, 8.8, 13.6, 7.7],
//                            [7, 7.6, 12.3, 9.6],
//                            [8, 12.3, 29.2, 10.6],
//                            [9, 16.9, 42.9, 14.8],
//                            [10, 12.8, 30.9, 11.6],
//                            [11, 5.3, 7.9, 4.7],
//                            [12, 6.6, 8.4, 5.2],
//                            [13, 4.8, 6.3, 3.6],
//                            [14, 4.2, 6.2, 3.4]
                    [0, 40.00000, 47.00000, 66.00000, 65.00000, 68.00000, 55.00000, 56.00000, 2.00000, 78.00000, 67.00000],
                    [1, 60.00000, 56.00000, 66.00000, 44.00000, 55.00000, 55.00000, 44.00000, 2.00000, 56.00000, 50.00000],
                    [2, 50.00000, 60.00000, 87.00000, 77.00000, 66.00000, 77.00000, 78.00000, 2.00000, 60.00000, 50.00000],
                    [3, 30.00000, 37.00000, 55.00000, 54.00000, 55.00000, 44.00000, 55.00000, 1.00000, 66.00000, 46.00000],
                    [4, 45.00000, 60.00000, 55.00000, 55.00000, 80.00000, 80.00000, 88.00000, 1.00000, 55.00000, 50.00000],
                    [5, 45.00000, 76.00000, 55.00000, 65.00000, 66.00000, 60.00000, 66.00000, 2.50000, 54.00000, 33.33000],
                    [6, 70.00000, 57.00000, 65.00000, 44.00000, 55.00000, 48.00000, 55.00000, 1.50000, 59.00000, 50.00000],
                    [7, 35.00000, 55.00000, 44.00000, 65.00000, 47.00000, 60.00000, 33.00000, 1.50000, 77.00000, 33.33000],
                    [8, 50.00000, 70.00000, 44.00000, 44.00000, 66.00000, 55.00000, 66.00000, 2.00000, 79.00000, 16.67000],
                    [9, 55.00000, 44.00000, 78.00000, 45.00000, 78.00000, 40.00000, 55.00000, 89.00000, 0, 0]
            ]);
                    var chart = new google.charts.Line(document.getElementById('course-grade'));
                    chart.draw(data, {});
            }
</script>
<?php
echo $OUTPUT->footer();
