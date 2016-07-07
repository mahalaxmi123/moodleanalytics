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
$users = optional_param_array('username', '', PARAM_TEXT);
$from_date = optional_param('from_date', '', PARAM_TEXT);
$to_date = optional_param('to_date', '', PARAM_TEXT);
$view = optional_param('view', 'first', PARAM_ALPHA);
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
$reportobj = get_report_class(4);
$params = array();
$reportobj->process_reportdata($reportobj, $params);

$axis = new stdClass();
if (!empty($reportid) & $reportid >= 1) {
    $axis = $reportobj->get_axis_names('Registrations');
}

//if (!empty($submit)) {
//    if (empty($from_date)) {
//        $errors[] = 'From Date';
//    }
//    if (empty($to_date)) {
//        $errors[] = 'To Date';
//    }
//} else {
//    echo html_writer::div('Please select the filters to proceed.', 'alert alert-info');
//}
//$params = array();
//$fromdate = $from_date;
//$todate = $to_date;
//$params['timestart'] = new DateTime($from_date);
//$params['timefinish'] = new DateTime($to_date);

$params1 = new stdClass();
$reportobj1 = new stdClass();
$reportobj1 = get_report_class(13);
$reportobj1->process_reportdata($reportobj1, $params1);

$reportobj2 = new stdClass();
$reportobj2 = get_report_class(21);
$params2 = new stdClass();
$reportobj2->process_reportdata($reportobj2, $params2);

$params3 = array();
$params3['endtime'] = time();
$reportobj3 = new stdClass();
$reportobj3 = get_report_class(32);
if ($view == 'monthly' || $view == 'first') {
    $reportobj3->process_reportdata_month($reportobj3, $params3);
}
if ($view == 'weekly') {
    $reportobj3->process_reportdata_week($reportobj3, $params3);
}
if ($view == 'daily') {
    $reportobj3->process_reportdata_daily($reportobj3, $params3);
}
echo $OUTPUT->single_button(new moodle_url('/local/moodleanalytics/dashboard.php?view=monthly', array()), get_string('monthly', 'local_moodleanalytics'));
echo $OUTPUT->single_button(new moodle_url('/local/moodleanalytics/dashboard.php?view=weekly', array()), get_string('weekly', 'local_moodleanalytics'));
echo $OUTPUT->single_button(new moodle_url('/local/moodleanalytics/dashboard.php?view=daily', array()), get_string('daily', 'local_moodleanalytics'));
?>
<script type="text/javascript"
        src="https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart', 'line']
        }]
}"></script>
<div>
    <div class="box45">
        <h3>Users Activity</h3>
        <div id="linechart_material" style="width: 900px; height: 500px"></div>
    </div>
    <div class="box45 pull-left">
        <h3>Registrations</h3>
        <div id="countries" style="width:500px; height:500px;"></div>
    </div>
    <!--    <div class="box45 pull-right">
            <h3>Enrollment per-course</h3>
                //<?php
//        $formcontent = "";
//        $formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/dashboard.php'), 'method' => 'post'));
//        $formcontent .= html_writer::tag('p', 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'from_date', 'value' => $fromdate)), array('id' => 'from_date'));
//        $formcontent .= html_writer::tag('p', 'To Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'to_date', 'value' => $todate)), array('id' => 'to_date'));
//        $formcontent .= html_writer::tag('p', 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'from_date', 'value' => $fromdate)), array('id' => 'from_date'));
//        $formcontent .= html_writer::tag('p', 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'to_date', 'value' => $todate)), array('id' => 'to_date'));
//        $formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => 'submit'));
//        $formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'reset', 'value' => 'reset'));
//        echo $formcontent;
//        if (empty($reportobj1->charttype)) {
//            echo '<h4>Sorry! no record found</h4>';
//        }
//        
?>
            <div id="enrollmentpercourse" style="width: 400px; height:400px;"></div>
        </div>-->

    <div class = "box45 pull-right">
        <h3>Enrollments</h3>
        <div id="user-enrol" style="width: 400px; height: 400px;"></div>
    </div>
    <div>
        <h3>Participation</h3>
        <div id="top_x_div" style="width: 900px; height: 500px;"></div>
    </div>
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
<script type="text/javascript">
    google.setOnLoadCallback(drawChart2);
            function drawChart2() {
            var data = google.visualization.arrayToDataTable([
                    [<?php echo $reportobj1->axis->xaxis . ',' . $reportobj1->axis->yaxis; ?>],
<?php echo implode(',', $reportobj1->data); ?>

            ]);
//                    var options = {
//                    title: <?php echo "'" . $reportobj1->charttitle . "'"; ?>
//                    };
                    var chart = new google.visualization.<?php echo $reportobj1->charttype; ?>(document.getElementById('user-enrol'));
//                    chart.draw(data, options);
                    chart.draw(data, {});
            }
</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawStuff);
            function drawStuff() {
<?php if (!empty($reportobj->data)) { ?>
                var data = google.visualization.arrayToDataTable([
                        [     <?php echo $reportobj2->title; ?>, { role: 'annotation' } ],
    <?php echo implode(',', $reportobj2->data); ?>
                ]);
                        var options = {
                        width: 600,
                                height: 400,
                                legend: { position: 'top', maxLines: 3 },
                                bar: { groupWidth: '75%' },
                                isStacked: true,
                        };
<?php } ?>
            var chart = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('top_x_div'));
                    // Convert the Classic options to Material options.
                    chart.draw(data, options);
            };
</script>
<script type="text/javascript">
//  google.charts.load('current', {'packages':['line']});
      google.setOnLoadCallback(drawChart);

    function drawChart() {

      var data = new google.visualization.DataTable();
        <?php foreach ($reportobj3->headers as $header) { ?>
        <?php if (!empty($header)) { ?>
                        data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
        <?php } ?>
    <?php } ?>

data.addRows([<?php echo implode(',', $reportobj3->data); ?>]);


      var options = {
       chart: {
        },
        width: 900,
        height: 500
      };

      var chart = new google.charts.Line(document.getElementById('linechart_material'));

      chart.draw(data, options);
    }
    </script>

<?php
echo $OUTPUT->footer();
