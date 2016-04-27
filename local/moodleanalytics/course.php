<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/user/renderer.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/grader/lib.php');

$courseid = required_param('id', PARAM_INT);        // course id
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/accesscard/index.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/accesscard/index.php');

// basic access checks
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('nocourseid');
}

$reportname = get_string('course', 'gradereport_grader');
echo $OUTPUT->header();
// return tracking object
$gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));

//first make sure we have proper final grades - this must be done before constructing of the grade tree
grade_regrade_final_grades($courseid);
//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
$report = new grade_report_grader($courseid, $gpr, $context, $page, $sortitemid);
$numusers = $report->get_numusers(true, true);

// final grades MUST be loaded after the processing
$report->load_users();
$report->load_final_grades();
echo $OUTPUT->footer();
