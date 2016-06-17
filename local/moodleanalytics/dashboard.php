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
$PAGE->set_url('/local/moodleanalytics/dashboard.php');
$PAGE->requires->js('/local/moodleanalytics/module.js', true);
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/dashboard.php');

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
        <h3>Registrations</h3>
        <div id="countries" style="width:500px; height:500px;"></div>
    </div>
<!--    <div class="box45 pull-right">
        <h3>Enrollment per-course</h3>
        <div id="enrollmentpercourse" style="width: 400px; height:400px;"></div>
    </div>-->
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawRegionsMap);
            function drawRegionsMap() {
<?php if (!empty($reportobj->data)) { ?>
                var data = new google.visualization.DataTable();
    <?php foreach ($reportobj->headers as $header) { ?>
        <?php if (!empty($header)) { ?>
                        data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
        <?php } ?>
    <?php } ?>
                data.addRows([<?php echo implode(',', $reportobj->data); ?>]);
<?php } ?>
            var chart = new google.visualization.<?php echo $reportobj->charttype; ?>(document.getElementById('countries'));
                    var options = {
                    hAxis: {
                    title: '<?php echo isset($axis->xaxis) ? $axis->xaxis : ''; ?>',
                    },
                            vAxis: {
                            title: '<?php echo isset($axis->yaxis) ? $axis->yaxis : ''; ?>',
                            }, };
                    chart.draw(data, {});
            }
</script>
<!--<script type="text/javascript">
    google.setOnLoadCallback(drawEnrolments);
            function drawEnrolments() {
            var data = google.visualization.arrayToDataTable([
                    ['fullname', 'nums'],<?php echo ($json_enrols) ? implode(",", $json_enrols) : ""; ?> ]);
                    var options = {
                    backgroundColor:{fill:"transparent"},
                            title: '',
                            pieHole: 0.4,
                            chartArea: {
                            width: '100%'
                            }
                    };
                    var chart = new google.visualization.<?php echo $chartoptions[$charttype]; ?>(document.getElementById('enrollmentpercourse'));
                    chart.draw(data, options);
            }

</script>-->

<?php
echo $OUTPUT->footer();
