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
//$charttype = optional_param('type', '', PARAM_ALPHANUM);
//$submit = optional_param('submit', '', PARAM_ALPHANUM);
//$reset = optional_param('reset', '', PARAM_ALPHANUM);
//$reportid = optional_param('reportid', '', PARAM_INT);
//$quizid = optional_param('quizid', '', PARAM_INT);
//$users = optional_param_array('username', '', PARAM_TEXT);
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

//if ($reset) {
//    redirect($returnurl);
//}
echo $OUTPUT->header();
$errors = array();
$reportobj1 = new stdClass();
$reportobj1 = get_report_class('coursesize');
$params = new stdClass();
$reportobj1->process_reportdata($reportobj1, $params);
$axis2 = new stdClass();
$axis2 = $reportobj1->get_axis_names('coursesize');

$reportobj2 = new stdClass();
$reportobj2 = get_report_class('activeip');
$params = new stdClass();
$reportobj2->process_reportdata($reportobj2, $params);

$reportobj3 = new stdClass();
$reportobj3 = get_report_class('languageused');
$params = new stdClass();
$reportobj3->process_reportdata($reportobj3, $params);

?>
<script type="text/javascript"
        src="https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart', 'table']
        }]
}"></script>
<div class="row">
	<div id="Load-page">
	 	<div class="performance row-fluid">
     		<div class="Performance-total ">
            	<h3 class="Performance-tota-header">Size On Disk</h3>
                <p>931.8<span style="font-size:16px; font-weight: bold">MB</span><br/><span style="font-size:16px; font-weight: bold;">Total</span></p>
                <p>619.5<span style="font-size:16px; font-weight: bold">MB</span><br/><span style="font-size:16px; font-weight: bold;">Courses</span></p>
                <p>109.5<span style="font-size:16px; font-weight: bold">MB</span><br/><span style="font-size:16px; font-weight: bold;">Users</span></p>
	  		</div>	
      </div>
      
       
      <div id="first-block"> 
          <div class="row-fluid" >
          	 <div class="languge-block span6">
          		<h3>Languages used</h3>
           		<div id="languagesused" style="width: 400px; height:400px;"></div>
              </div>
    
            <div class="Course-block span6">
          		<h3>Course Size</h3>
           		<div id="coursesize" style="width: 500px; height:400px;"></div>
            </div>
  		</div>
    </div>
  
  
   <div id="IP-address">
        <div class="row-fluid" >
        	<div class="IP-block span6">
                <h3>Active IP Address</h3>
                <div id="activeip" style="width: 400px; height:400px;"></div>
            </div>
        </div>
   </div>

<script type = "text/javascript" >
            google.setOnLoadCallback(drawCourseSize);
            function drawCourseSize() {
<?php if (!empty($reportobj1->data)) { ?>
                var data = new google.visualization.DataTable();
    <?php foreach ($reportobj1->headers as $header) { ?>
        <?php if (!empty($header)) { ?>
                        data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
        <?php } ?>
    <?php } ?>
                data.addRows([<?php echo implode(',', $reportobj1->data); ?>]);
<?php } ?>
            var chart = new google.visualization.<?php echo $reportobj1->charttype; ?>(document.getElementById('coursesize'));
                    chart.draw(data, {showRowNumber: true, width: '100%', height: '100%', pageSize :10});
            }

</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawTable);
            function drawTable() {
<?php if (!empty($reportobj2->data)) { ?>
                var data = new google.visualization.DataTable();
    <?php foreach ($reportobj2->headers as $header) { ?>
        <?php if (!empty($header)) { ?>
                        data.addColumn(<?php echo $header->type; ?>,<?php echo $header->name; ?>);
        <?php } ?>
    <?php } ?>
                data.addRows([
    <?php
    for ($i = 0; $i < count($reportobj2->data); $i++) {
        echo $reportobj2->data[$i];
    }
    ?>
<?php } ?>
            ]);
                    var table = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('activeip'));
                    table.draw(data, {showRowNumber: true, width: '100%', height: '100%', pageSize :10});
            }
</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawChart);
            function drawChart() {
<?php if (!empty($reportobj3->data)) { ?>
                var data = google.visualization.arrayToDataTable([
                        [<?php echo $reportobj3->axis->xaxis . ',' . $reportobj3->axis->yaxis; ?>],
    <?php
    for ($i = 0; $i < count($reportobj3->data); $i++) {
        echo $reportobj3->data[$i];
    }
    ?>
                ]);
                        var options = {
                                pieHole: 0.4,
                        };
<?php } ?>

            var chart = new google.visualization.<?php echo $reportobj3->charttype; ?>(document.getElementById('languagesused'));
                    chart.draw(data, options);
            }
</script>
<?php
echo $OUTPUT->footer();
