<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('../../config.php');
require_once('lib.php');

global $SESSION;
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/moodleanalytics/ajax.php');

$functionname = optional_param('fname', '', PARAM_ALPHAEXT); //function name as argument

if ($functionname) {

    switch ($functionname) {

        case "get_course_users":
            $courseid = optional_param('id', 0, PARAM_INT);
            $result = get_course_users($courseid);
            $result = json_encode($result);
            break;
        case "get_course_quiz":
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $result = get_course_quiz($courseid);
            $result = json_encode($result);
            break;
        default:
            $result = false;
    }
} else {
    $result = false;
}
echo $result;
