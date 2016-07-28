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
//require_once('externallib.php');
require_login();
$courseid = optional_param('id', '', PARAM_INT);        // course id
$charttype = optional_param('type', '', PARAM_ALPHANUM);
$submit = optional_param('submit', '', PARAM_ALPHANUM);
$reset = optional_param('reset', '', PARAM_ALPHANUM);
$reportid = optional_param('reportid', '', PARAM_INT);
$reportname = optional_param('reportname', '', PARAM_TEXT);
$context = context_system::instance();
$from_date = optional_param('from_date', '', PARAM_TEXT);
$to_date = optional_param('to_date', '', PARAM_TEXT);

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/moodleanalytics/tabularreports.php');
$PAGE->requires->js('/local/moodleanalytics/module.js', true);
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$returnurl = new moodle_url($CFG->wwwroot . '/local/moodleanalytics/tabularreports.php');

if ($reset) {
    redirect($returnurl);
}

echo $OUTPUT->header();
$errors = array();

$report_array = get_coursereports();
$fromdate = $from_date;
$todate = $to_date;
//
//if (empty($from_date)) {
//    $from_date_default = userdate((time() - (DAYSECS * 7)), '%Y-%m-%d');
//    $fromdate = $from_date_default;
//    $from_date = new DateTime($from_date_default);
//} else {
//    $from_date = new DateTime($from_date);
//}
//if (empty($to_date)) {
//    $to_date_default = userdate(time(), '%Y-%m-%d');
//    $todate = $to_date_default;
//    $to_date = new DateTime($to_date_default);
//} else {
//    $to_date = new DateTime($to_date);
//}

$from_date = new DateTime($from_date);
$to_date = new DateTime($to_date);
$params = array();
//$params['from_date'] = $from_date;
//$params['to_date'] = $to_date;
$params['fromdate'] = $from_date;
$params['todate'] = $to_date;

$reportobj = new stdClass();
if (!empty($reportname)) {
    $reportobj = get_report_class($reportname);

    $reportobj->process_reportdata($reportobj, $params);
    $axis = new stdClass();
    $axis = $reportobj->get_axis_names($reportname);
}

$formcontent = html_writer::start_tag('div');
if (!empty($errors)) {
    $error = implode(", ", $errors);
    $formcontent .= html_writer::div("Please select $error", 'alert alert-danger');
}
$formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/tabularreports.php'), 'method' => 'post'));
$formcontent .= 'Report Name : ' . html_writer::select($report_array, 'reportname', $reportname, array('' => 'Select report'), array('id' => 'reportdropdown'));
//$formcontent .= html_writer::tag('p', 'From Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'from_date', 'value' => $fromdate)), array('id' => 'from_date'));
//$formcontent .= html_writer::tag('p', 'To Date : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'to_date', 'value' => $todate)), array('id' => 'to_date'));
$formcontent .= html_writer::tag('p', 'From Date' . html_writer::empty_tag('input', array('size' => '10', 'type' => 'text', 'name' => 'from_date', 'class' => 'program-management-datepicker', 'value' => $fromdate)) , array( 'id' => 'from_date'));
$formcontent .= html_writer::tag('p', 'To Date' . html_writer::empty_tag('input', array('size' => '10', 'type' => 'text', 'name' => 'to_date', 'class' => 'program-management-datepicker', 'value' => $todate)) , array( 'id' => 'to_date'));
$formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => 'submit'));
$formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'reset', 'value' => 'reset'));
$formcontent .= html_writer::end_tag('form');
$formcontent .= html_writer::end_tag('div');
echo $formcontent;
?>
<script type="text/javascript"
        src="https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart', 'table','annotationchart']
        }]
}"></script>
<div id="Tabular-Page">
    <div class="row">
        <div class="Tabular-box">
            <h3><?php echo!empty($reportname) ? $report_array[$reportname] : ''; ?></h3>
            <p><?php
            if ($reportname == 'uploads') {
                echo html_writer::tag('h4', get_string('uploadmessage', 'local_moodleanalytics'));
            }
            ?></p>
            <div id="Tabularreports"></div>
        </div> 
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
                    var table = new google.visualization.<?php echo $reportobj->charttype; ?>(document.getElementById('Tabularreports'));
                    table.draw(data, {showRowNumber: true, width: '100%', height: '100%', pageSize :10});
            }

</script>

<?php
echo $OUTPUT->footer();
