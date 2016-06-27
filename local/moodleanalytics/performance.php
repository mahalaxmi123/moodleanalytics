<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

global $CFG;
require('../../config.php');
require_once('lib.php');
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
$PAGE->set_url('/local/moodleanalytics/performance.php');
$PAGE->requires->js('/local/moodleanalytics/module.js', true);
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/performance.php');

if ($reset) {
    redirect($returnurl);
}
echo $OUTPUT->header();
$errors = array();
$reportobj2 = new stdClass();
$reportobj2 = get_report_class(12);
$params = array();
$reportobj2->process_reportdata($reportobj2, $params);

$axis2 = new stdClass();
$axis2 = $reportobj2->get_axis_names('coursesize');
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
    <div class="box46">
        <h3>Course Size</h3>
        <div id="coursesize" style="width: 400px; height:400px;"></div>
    </div>
</div>
<script type = "text/javascript" >
            google.setOnLoadCallback(drawCourseSize);
            function drawCourseSize() {
<?php if (!empty($reportobj2->data)) { ?>
                var data = new google.visualization.DataTable();
    <?php foreach ($reportobj2->headers as $header) { ?>
        <?php if (!empty($header)) { ?>
                        data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
        <?php } ?>
    <?php } ?>
                data.addRows([<?php echo implode(',', $reportobj2->data); ?>]);
<?php } ?>
            var chart = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('coursesize'));
                    var options = {
                    hAxis: {
                    title: '<?php echo isset($axis2->xaxis) ? $axis2->xaxis : ''; ?>',
                    },
                            vAxis: {
                            title: '<?php echo isset($axis2->yaxis) ? $axis2->yaxis : ''; ?>',
                            },
                    };
                    chart.draw(data, options);
            }

</script>
<?php
echo $OUTPUT->footer();
