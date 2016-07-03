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
require_once('externallib.php');
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
$PAGE->set_url('/local/moodleanalytics/tabularreports.php');
$PAGE->requires->js('/local/moodleanalytics/module.js', true);
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/tabularreports.php');

if ($reset) {
    redirect($returnurl);
}

echo $OUTPUT->header();
$errors = array();

$reportobj = new stdClass();
$reportobj = get_tabular_reports_class('activity_progress');
$params = new stdClass();

$reportobj->process_reportdata($reportobj, $params);
$axis = new stdClass();
$axis = $reportobj->get_axis_names('student_performance');
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
    <div class="box45 pull-right">
        <h3>Learning progress</h3>
        <div id="Learningprogress"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawLearningprogress);
            function drawLearningprogress() {

            var data = new google.visualization.DataTable();
<?php foreach ($reportobj->headers as $header) { ?>
    <?php if (!empty($header)) { ?>
                    data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
    <?php } ?>
<?php } ?>
            data.addRows([<?php echo implode(',', $reportobj->data); ?>]);
                    var table = new google.visualization.<?php echo $reportobj->charttype; ?>(document.getElementById('Learningprogress'));
                    table.draw(data, {showRowNumber: true, width: '100%', height: '100%', pageSize :10});
            }

</script>

<?php
echo $OUTPUT->footer();
