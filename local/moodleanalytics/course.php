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

if (!empty($submit)) {
    if (empty($reportid)) {
        $errors[] = 'Report Name';
    }
    if (empty($from_date) && $reportid != 5) {
        $errors[] = 'From Date';
    }
    if (empty($to_date) && $reportid != 5) {
        $errors[] = 'To Date';
    }
} else {
    echo html_writer::div('Please select the filters to proceed.', 'alert alert-info');
}

$report_array = get_coursereports();
$fromdate = $from_date;
$todate = $to_date;
$from_date = new DateTime($from_date);
//$from_date = $fromdate->format('U');
$to_date = new DateTime($to_date);

$params = array();
$params['fromdate'] = $from_date;
$params['todate'] = $to_date;

$reportobj = new stdClass();
if ($reportid) {
    $reportobj = get_report_class($reportid);
    $reportobj->process_reportdata($reportobj, $params);
}
$axis = new stdClass();
if (!empty($reportid) & $reportid >= 1) {
    $axis = $reportobj->get_axis_names();
}


$formcontent = html_writer::start_tag('div');
if (!empty($errors)) {
    $error = implode(", ", $errors);
    $formcontent .= html_writer::div("Please select $error", 'alert alert-danger');
}
$formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php'), 'method' => 'post'));
$formcontent .= 'Report Name : ' . html_writer::select($report_array, 'reportid', $reportid, array('' => 'Select report'), array('id' => 'reportdropdown'));
$formcontent .= html_writer::tag('p', 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'from_date', 'value' => $fromdate)), array('id' => 'from_date'));
$formcontent .= html_writer::tag('p', 'To Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'to_date', 'value' => $todate)), array('id' => 'to_date'));
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
        <?php if($reportid == 9){
            echo '<p>File Size is in <strong>Bytes</strong></p>';
        } ?>
        <h5><?php echo isset($reportobj->info) ? $reportobj->info : ''; ?></h5>
        <div id="course-grade" style="width:1000px; height:800px;"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawChart);
            function drawChart() {
<?php if (!empty($reportobj->data)) { ?>
                var data = new google.visualization.DataTable();
        <?php foreach ($reportobj->gradeheaders as $gradehead) { ?>
            <?php if (!empty($gradehead)) { ?>
                    data.addColumn(<?php echo $gradehead->type; ?>,<?php echo $gradehead->name; ?>);
    <?php } ?>
        <?php } ?>
                data.addRows([<?php echo implode(',', $reportobj->data); ?>]);
<?php } ?>
            var chart = new google.visualization.<?php echo $reportobj->charttype; ?>(document.getElementById('course-grade'));
                    var options = {
                    hAxis: {
                    title: '<?php echo isset($axis->xaxis) ? $axis->xaxis : ''; ?>',
                    },
                            vAxis: {
                            title: '<?php echo isset($axis->yaxis) ? $axis->yaxis : ''; ?>',
                            },

}
<?php if (empty($errors)) { ?>
                chart.draw(data, options);
<?php } ?>
    };
</script>
<?php
echo $OUTPUT->footer();
