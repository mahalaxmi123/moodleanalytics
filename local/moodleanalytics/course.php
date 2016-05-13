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
$resourceactivitycompletion = get_activity_completion($courseid);
// return tracking object
if (!empty($submit) && !empty($courseid) && !empty($users) && !empty($charttype)) {
    $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));

//first make sure we have proper final grades - this must be done before constructing of the grade tree
    grade_regrade_final_grades($courseid);
//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
    $report = new grade_report_grader($courseid, $gpr, $context);
    $numusers = $report->get_numusers(true, true);

    $activities = array();
    if (!empty($report->gtree->top_element['children'])) {
        foreach ($report->gtree->top_element['children'] as $children) {
            $activities[$children['eid']] = $children['object']->itemname;
        }
    }
// final grades MUST be loaded after the processing
    $report->load_users();
    $report->load_final_grades();
} elseif (!empty($submit)) {
    if (empty($reportid)) {
        $errors[] = 'Report Name';
    }
    if (empty($courseid)) {
        $errors[] = 'Course';
    }
    if (empty($charttype)) {
        $errors[] = 'Chart Type';
    }
    if (empty($users)) {
        $errors[] = 'User(s)';
    }
} else {
    echo html_writer::div('Please select the filters to proceed.', 'alert alert-info');
}

$json_grades = array();
$users_update = array();
if (!empty($report) && !empty($report->grades)) {
    foreach ($report->grades as $grades => $grade) {
        foreach ($users as $key => $username) {
            $user = $DB->get_record('user', array('username' => $username));
            foreach ($grade as $gradeval) {
                if ($gradeval->grade_item->itemtype != 'course') {
                    if (!empty($user->id) && ($gradeval->userid == $user->id)) {
                        $users_update[$user->username] = $user->username;
                        if (!empty($json_grades[$gradeval->grade_item->itemname])) {
                            $json_grades[$gradeval->grade_item->itemname] .= (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
                        } else {
                            $json_grades[$gradeval->grade_item->itemname] = "'" . $gradeval->grade_item->itemname . "'" . ',' . (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
                        }
                    }
                }
            }
        }
    }
    
    $averageusergrades = get_user_avggrades($report->grades);
    
    $USER->gradeediting[$courseid] = '';
    $averagegrade = $report->get_right_avg_row();
    $actavggrade = array();
    foreach ($averagegrade as $avggradvalue) {
        foreach ($avggradvalue->cells as $avggrade) {
            $actitemname = '';
            $attrclass = $avggrade->attributes['class'];
            if (isset($attrclass) && isset($activities[$attrclass])) {
                $actitemname = $activities[$attrclass];
            }
            if (!empty($avggrade->text)) {
                $actavggrade[$actitemname] = floatval($avggrade->text);
            }
        }
    }
    foreach ($json_grades as $key => $value) {
        $json_grades[$key] .= $actavggrade[$key] . ',';
    }
}

$json_grades_array = array();
foreach ($json_grades as $key => $grade_info) {
    $grade_info = TRIM($grade_info, ',');
    $json_grades_array[] = "[" . $grade_info . "]";
}

$report_array = get_course_reports();
$quizdetails = array();
$info = '';
$notattemptedusers = array();
if ($reportid == 2) {
    if ($users && !empty($quizid)) {
        $json_quiz_attempt = get_user_quiz_attempts($quizid, $users);
        if (array_key_exists('usernotattempted', $json_quiz_attempt)) {
            $notattemptedposition = array_search('usernotattempted', array_keys($json_quiz_attempt));
            $notattemptedmessage = array_slice($json_quiz_attempt, $notattemptedposition, 1);
            foreach ($notattemptedmessage['usernotattempted'] as $key => $message) {
                $info .= html_writer::div($message, 'alert alert-info');
                $notattemptedusers[] = $key;
            }
        } 
        unset($json_quiz_attempt['usernotattempted']);
        if (!empty($json_quiz_attempt)) {
            foreach ($json_quiz_attempt as $quiz => $quizgrades) {
                $quizdetails[] = "[" . "'" . $quiz . "'" . "," . trim($quizgrades, ',') . "]";
            }
        } else {
            $info .= html_writer::div('User has not attempted the quiz yet.', 'alert alert-info');
        }
        foreach ($users as $userkey => $uservalue) {
            if (!empty($uservalue) && !in_array($uservalue, $notattemptedusers)) {
                $gradeheaders[] = "'" . $uservalue . " - Grade '";
            }
        }
    }
}

if ($reportid != 2) {
    $gradeheaders = array();
    if (!empty($users)) {
        if (!empty($users_update)) {
            foreach ($users_update as $key => $userval) {
                if (!empty($userval) && !in_array($userval, $notattemptedusers)) {
                    $gradeheaders[] = "'" . $userval . " - Grade '";
                }
            }
        } else {
            $errors[] = 'User(s)';
        }
    }
    $position = '';
    if (empty($errors)) {
        $gradeheaders[] = "'" . 'activities average' . "'";
        $position = array_search("'" . 'activities average' . "'", $gradeheaders);
    }
}
//$chartoptions = array('BarChart', 'GeoChart', 'ColumnChart', 'Histogram', 'PieChart', 'LineChart');
$chartoptions = array(1 => 'LineChart', 2 => 'ComboChart');
$courselist = get_courses();
$userlist = get_course_users($courseid);
$quiz_array = get_course_quiz($courseid);
$courses = array();
foreach ($courselist as $course) {
    if ($course->id != SITEID) {
        $courses[$course->id] = $course->fullname;
    }
}
$axis = new stdClass();
if (!empty($reportid) & $reportid >= 1) {
    $axis = get_axis_names($report_array[$reportid]);
}
$formcontent = html_writer::start_tag('div');
if (!empty($errors)) {
    $error = implode(", ", $errors);
    $formcontent .= html_writer::div("Please select $error", 'alert alert-danger');
}
$formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/course.php'), 'method' => 'post'));
//if(!empty($reportid)) {
//    $formcontent .= html_writer::tag('input', '', array('type'=>'hidden', 'id'=>'report', 'value'=>$reportid));
//}
$formcontent .= 'Report Name : ' . html_writer::select($report_array, 'reportid', $reportid, array('' => 'Select report'),array('id' => 'reportdropdown'));
$formcontent .= 'Course : ' . html_writer::select($courses, 'id', $courseid, array('' => 'Select course'), array('id' => 'coursedropdown', 'class' => 'coursedropdown'));
$formcontent .= 'Activity Name : ' . html_writer::select($quiz_array, 'quizid', $quizid, array('' => 'Select quiz'), array('id' => 'quizdropdown'));
$formcontent .= 'Chart Type : ' . html_writer::select($chartoptions, 'type', $charttype);
$formcontent .= '</br>';
$formcontent .= 'User(s) (You can select <strong>single / multiple</strong> users here): ' . html_writer::select($userlist, 'username[]', $users, array('' => 'Select User(s)'), array('id' => 'userdropdown', 'multiple' => 'multiple'));
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
        'packages':['corechart','geochart','line']
        }]
}"></script>
<div>
    <div class="box45 pull-left">
        <h3><?php echo isset($report_array[$reportid]) ? $report_array[$reportid] : ''; ?></h3>
        <h5><?php echo isset($info) ? $info : ''; ?></h5>
        <div id="course-grade" style="width:1000px; height:800px;"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawChart);
            function drawChart() {
            var data = new google.visualization.DataTable();
                    data.addColumn('string', 'Data');
<?php foreach ($gradeheaders as $gradehead) { ?>
                data.addColumn('number',<?php echo $gradehead; ?>);
<?php } if ($reportid == 1) { ?>
                data.addRows([<?php echo implode(',', $json_grades_array); ?>]);
<?php } else { ?>
                data.addRows([<?php echo implode(',', $quizdetails); ?>]);
<?php } ?>
            var chart = new google.visualization.<?php echo $chartoptions[$charttype]; ?>(document.getElementById('course-grade'));
                    var options = {
                    hAxis: {
                    title: '<?php echo isset($axis->xaxis) ? $axis->xaxis : ''; ?>',
                    },
                            vAxis: {
                            title: '<?php echo isset($axis->yaxis) ? $axis->yaxis : ''; ?>',
                            },
<?php if ($chartoptions[$charttype] == 'ComboChart') { ?>
                        seriesType: 'bars',
    <?php if ($reportid != 2) { ?>
                            series: {<?php
        echo (isset($position) ? $position : '');
        ?>: {type: 'line', color : 'black'}},
                                    //                                    trendlines : {<?php echo $position; ?>:{
                                    //                                type: 'exponential',
                                    //                                        color: 'green',
                                    //                                        visibleInLegend: true,
                                    //                                        pointVisible : true,
                                    //                                        pointSize : 10,
                                    //                                }},

        <?php
    }
}
?>
                    };
<?php if (empty($errors)) { ?>
                chart.draw(data, options);
<?php } ?>
            }
</script>
<?php
echo $OUTPUT->footer();
