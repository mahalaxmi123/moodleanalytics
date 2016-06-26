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
$reportid = optional_param('reportid', 4, PARAM_INT);
$quizid = optional_param('quizid', '', PARAM_INT);
$users = optional_param_array('username', '', PARAM_TEXT);
$context = context_system::instance();

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
if ($reportid) {
    $reportobj = get_report_class($reportid);
}
$params = new stdClass();
$reportobj->process_reportdata($reportobj, $params);
$axis = new stdClass();
if (!empty($reportid) & $reportid >= 1) {
    $axis = $reportobj->get_axis_names('Registrations');
}

$reportobj1 = new stdClass();
$reportobj1 = get_report_class(5);
$params1 = new stdClass();
$reportobj1->process_reportdata($reportobj1, $params1);
$axis1 = new stdClass();
$axis1 = $reportobj1->get_axis_names('enrollmentspercourse');

$reportobj2 = new stdClass();
$reportobj2 = get_report_class(7);
$params2 = new stdClass();
$reportobj2->process_reportdata($reportobj2, $params2);
$axis2 = new stdClass();
$axis2 = $reportobj2->get_axis_names('courseenrollments');
?>
<script type="text/javascript"
        src="https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart']
        }]
}"></script>
<div>
    <div class="box45 pull-left">
        <h3>Course enrollments</h3>
        <div id="course-enrollments" style="width:500px; height:500px;"></div>
    </div>
    <div class="box45 pull-right">
        <h3>Teaching activity</h3>
        <div id="enrollmentpercourse" style="width: 400px; height:400px;"></div>
    </div>
</div>

<script type="text/javascript">
            google.charts.load('current', {'packages':['table']});
            google.charts.setOnLoadCallback(drawCourseenrolments);
            function drawCourseenrolments() {
<?php foreach ($reportobj2->headers as $header) { ?>
    <?php if (!empty($header)) { ?>
                    data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
    <?php } ?>
<?php } ?>
            data.addRows([<?php echo implode(',', $reportobj2->data); ?>]);
                    var table = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('course-enrollments'));
                    table.draw(data, {showRowNumber: true, width: '100%', height: '100%'});
            }

</script>

<?php
echo $OUTPUT->footer();
