<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/user/renderer.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/grader/lib.php');

function get_teacher_sql($params, $column, $type) {
    $sql = '';
    if (isset($params->userid) and $params->userid) {
        if ($type == "users") {
            $courses = $this->get_teacher_courses($params, true);
            $users = $this->get_teacher_leaners($params, true, $courses);
            $sql = "AND $column IN($users)";
        } elseif ($type == "courses") {
            $courses = $this->get_teacher_courses($params, true);
            $sql = "AND $column IN($courses)";
        }
    }
    return $sql;
}

function get_dashboard_countries() {
    global $USER, $CFG, $DB;
    //$sql = get_teacher_sql($params, "id", "users");

    return $DB->get_records_sql("SELECT country, count(*) as users FROM {user} WHERE confirmed = 1 and deleted = 0 and suspended = 0 and country != '' GROUP BY country");
}

function get_enrollments_per_course($params) {
    global $USER, $CFG, $DB;
    $sql = get_teacher_sql($params, "c.id", "courses");
    $sql1 = "SELECT c.id, c.fullname, count( ue.id ) AS nums FROM {$CFG->prefix}course c, {$CFG->prefix}enrol e, {$CFG->prefix}user_enrolments ue WHERE e.courseid = c.id AND ue.enrolid =e.id $sql GROUP BY c.id";
    return $DB->get_records_sql($sql1);
}

/* Return course users
 * @param : courseid int
 */

function get_course_users($courseid) {
    global $DB;
    $users_list = array();
    if (!empty($courseid)) {
        $role = $DB->get_record('role', array('shortname' => 'student'));
        $users = get_role_users($role->id, CONTEXT_COURSE::instance($courseid));
//        $users = get_enrolled_users(CONTEXT_COURSE::instance($courseid));
        foreach ($users as $user) {
            $users_list[$user->username] = $user->username;
        }
    }
    return $users_list;
}

function get_user_quiz_attempts($quizid, $users) {
    global $DB;
    $attempts = array();
    $count = 1;
    if (!empty($users)) {
        foreach ($users as $username) {
            $user = $DB->get_record('user', array('username' => $username));
            $quizattempts = quiz_get_user_attempts($quizid, $user->id, 'finished');
            foreach ($quizattempts as $quizattempts) {
                if (!empty($attempts['Attempt ' . $count])) {
                    $attempts['Attempt ' . $count] .= (!empty($quizattempts->sumgrades) ? $quizattempts->sumgrades : 0.0) . ',';
                } else {
                    $attempts['Attempt ' . $count] = "'" . (!empty($quizattempts->sumgrades) ? $quizattempts->sumgrades : 0.0) . ',';
                }
                $count++;
            }
        }
        $count++;
    }
    return $attempts;
}

function get_course_quiz($courseid) {
    $quiz_array = array();
    if (!empty($courseid)) {
        $activities = get_array_of_activities($courseid);
        foreach ($activities as $actinfo) {
            if ($actinfo->mod == 'quiz') {
                $quiz_array[] = $actinfo->id;
            }
        }
    }
    return $quiz_array;
}

function get_course_reports() {
    $report_array = array('Course progress', 'Activity attempt');
    return $report_array;
}
