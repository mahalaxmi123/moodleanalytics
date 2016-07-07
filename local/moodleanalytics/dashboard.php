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


$reportobj5 = new stdClass();
$reportobj5 = get_report_class(13);
$params = new stdClass();
$reportobj5->process_reportdata($reportobj5, $params);

$reportobj2 = new stdClass();
$reportobj2 = get_report_class(21);
$params2 = new stdClass();
$reportobj2->process_reportdata($reportobj2, $params2);
?>
<script type="text/javascript"
        src="https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart']
        }]
}"></script>


   <!-- bootstrap theme -->
    <link href="css/style.css" rel="stylesheet">
     <link href="css/font-awesome.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet"/>    
  
<div id="main-page">
     <div class="row-fluid">
     		<div class="Information-block">
				<div class="col-md-3 col-sm-12 col-xs-12">
                	<div class="info-box blue-bg">
                     	<p>10</p>
						<div class="title">Admins</div>						
					</div><!--/.info-box-->			
				</div><!--/.col-->
				
				<div class="col-md-3 col-sm-12 col-xs-12">
					<div class="info-box brown-bg">
                    	<p>23</p>
						<div class="title">Trainers</div>						
					</div><!--/.info-box-->			
				</div><!--/.col-->	
                
				<div class="col-md-3 col-sm-12 col-xs-12">
					<div class="info-box green-bg">
                    	<p>610</p>
						<div class="title">Learners</div>						
					</div><!--/.info-box-->			
				</div><!--/.col-->
			</div>	
	</div><!--/.row-->
    
    <div class="row">
   	<div class="btn-group">
    <button type="button" class="btn btn-primary">Daily</button>
    <button type="button" class="btn btn-primary">Weekly</button>
    <button type="button" class="btn btn-primary">Monthly</button>
      </ul>
    </div>
    	
    </div>
            
 <div id="Section-First">
   
 </div>
 
<div class="mdlanalytics-total clearfix">
	<h3>TOTAL</h3>
    
     <div class="count"><p>643</p> 
     	<div class="icons">
        	<i class="fa fa-users"></i></i><span class="items">USER</span>
        </div>
     </div>
  
     <div class="count"><p>23</p>  
     	<div class="icons">
        	<i class="zmdi zmdi-book"></i><span class="items">CATEGORIES</span>
        </div>
     </div>
     
     <div class="count"><p>138</p> 
     	<div class="icons">
        	<i class="fa fa-book"></i><span class="items">COURSES</span>
        </div>
     </div>
     
     <div class="count"><p>1553</p>
     	<div class="icons">
        	<i class="fa fa-file-text-o"></i><span class="items">MODULES</span>
            </div>
     </div>
     
     <div class="count"><p>931.8<span style="font-size:16px";>MB</span></p>
     	<div class="icons">
        	<i class="fa fa-simplybuilt"></i><span class="items">SPACE</span>
        </div>
     </div>
</div> 
		
<div id="Section-Second">
   <div class="row">
        <div class = "Enrollments-block span5">
            <h3>Enrollments</h3>
            <div id="user-enrol" style="width: 400px; height: 400px;"></div>
        </div>
       
        <div class="participation span6">
            <h3>Participation</h3>
            <div id="participation-block" style="width: 500px; height: 500px;"></div>
        </div>
       
    	
    </div>
</div>
       
       
        
<div id="Section-third">
   <div class="row">
       <div class="register">
        	<h3>Registrations</h3>
  		<div id="countries" style="width:500px; height:400px;"></div>
        </div>
        
    </div>
</div>        
<!--    	<div class="enroll span5">
        	<h3>Enrollment per-course</h3><?php
//  			$formcontent = "";
//        $formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/dashboard.php'), 'method' => 'post'));
////        $formcontent .= html_writer::tag('p', 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'from_date', 'value' => $fromdate)), array('id' => 'from_date'));
////        $formcontent .= html_writer::tag('p', 'To Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'text', 'name' => 'to_date', 'value' => $todate)), array('id' => 'to_date'));
//        $formcontent .= html_writer::tag('p', 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'from_date', 'value' => $fromdate)), array('id' => 'from_date'));
//        $formcontent .= html_writer::tag('p', 'From Date (DD-MM-YYYY) : ' . html_writer::empty_tag('input', array('type' => 'date', 'name' => 'to_date', 'value' => $todate)), array('id' => 'to_date'));
//        $formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => 'submit'));
//        $formcontent .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'reset', 'value' => 'reset'));
//        echo $formcontent;
//        if (empty($reportobj1->charttype)) {
//            echo '<h4>Sorry! no record found</h4>';
//        }
//        ?>
        
			<div id="enrollmentpercourse" style="width: 400px;"></div>
    	</div>-->

	
<!--<div id="Section-Third">
        <h3>Participants</h3>-->
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
//        ?>
        <div id="enrollmentpercourse" style="width: 400px; height:400px;"></div>
    </div>-->
    
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
                    [<?php echo $reportobj5->axis->xaxis . ',' . $reportobj5->axis->yaxis; ?>],
<?php echo implode(',', $reportobj5->data); ?>

            ]);
//                    var options = {
//                    title: <?php echo "'" . $reportobj5->charttitle . "'"; ?>
//                    };
                    var chart = new google.visualization.<?php echo $reportobj5->charttype; ?>(document.getElementById('user-enrol'));
//                    chart.draw(data, options);
                    chart.draw(data, {});
            }
</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawStuff);
            function drawStuff() {
<?php if (!empty($reportobj->data)) { ?>
                var data = google.visualization.arrayToDataTable([
                        [     <?php echo $reportobj2->title; ?> ],
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
            var chart = new google.visualization.<?php echo $reportobj2->charttype; ?>(document.getElementById('participation-block'));
                    // Convert the Classic options to Material options.
                    chart.draw(data, options);
            };
</script>

<?php
echo $OUTPUT->footer();
