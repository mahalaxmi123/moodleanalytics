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
require_once($CFG->libdir . '/completionlib.php');

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
    $quizdetails = array();
    $maxnumofattempts = '';
    if (!empty($users)) {
        foreach ($users as $username) {
            $count = 1;
            $user = $DB->get_record('user', array('username' => $username));
            $quizattempts = quiz_get_user_attempts($quizid, $user->id, 'finished');
            if ($quizattempts) {
                foreach ($quizattempts as $quizattempt) {
                    $attempts[$username]['Attempt ' . $count] = $quizattempt->sumgrades;
                    $count++;
                }
            } else {
                $quizdetails['usernotattempted'][$username] = "$username has not taken this quiz yet.";
            }
        }
        foreach ($attempts as $attempt) {
            $currentnumofattempts[] = count($attempt);
            $maxnumofattempts = max($currentnumofattempts);
        }
        $attempts = format_quiz_attemptwise_grades($maxnumofattempts, $attempts);
    }
    return array_merge($quizdetails, $attempts);
}

function format_quiz_attemptwise_grades($max, $thisattempts) {
    foreach ($thisattempts as $thisattempt) {
        $count = count($thisattempt);
        if (!empty($max) && $max > $count) {
            $less = $max - $count;
            for ($i = 1; $i <= $less; $i++) {
                $count += 1;
                $thisattempt['Attempt ' . $count] = 0;
            }
        }
        $modifiedattempts[] = $thisattempt;
    }
    foreach ($modifiedattempts as $modattempts) {
        $numofattempts = count($modattempts);
        for ($num = 1; $num <= $numofattempts; $num++) {
            if (!empty($newattempts['Attempt ' . $num])) {
                $newattempts['Attempt ' . $num] .= $modattempts['Attempt ' . $num] . ',';
            } else {
                $newattempts['Attempt ' . $num] = ',' . $modattempts['Attempt ' . $num] . ',';
            }
        }
    }
    return $newattempts;
}

function get_course_quiz($courseid) {
    $quiz_array = array();
    if (!empty($courseid)) {
        $activities = get_array_of_activities($courseid);
        foreach ($activities as $actinfo) {
            if ($actinfo->mod == 'quiz') {
                $quiz_array[$actinfo->id] = $actinfo->name;
            }
        }
    }
    return $quiz_array;
}

function get_course_reports() {
    $report_array = array('Course progress', 'Activity attempt');
    return $report_array;
}

function get_axis_names($reportname) {
    $axis = new stdClass();
    switch ($reportname) {
        case 'Course progress':
            $axis->xaxis = 'Activities';
            $axis->yaxis = 'Grades';
            break;
        case 'Activity attempt':
            $axis->xaxis = 'Attempts';
            $axis->yaxis = 'Grades';
            break;
        default :
            break;
    }
    return $axis;
}

/* Returns the resource completion percentage of all users in a course
 * @param : courseid int
 * return resource completion array 
 */

function get_activity_completion($courseid) {
    global $DB;
    $resource_completion_array = array();
    $course = $DB->get_record('course', array('id' => $courseid));
    if (!$course) {
        return;
    }
    $completion = new completion_info($course);
    $activities = $completion->get_activities();
    $total_activities = COUNT($activities);
    $progress = $completion->get_progress_all();
    foreach ($progress as $userid => $userprogess_data) {
        $completedact_count = COUNT($userprogess_data->progress);
        if ($completedact_count) {
            $resource_completion_array[$userid] = ($completedact_count / $total_activities) * 100;
        } else {
            $resource_completion_array[$userid] = 0;
        }
    }
    return $resource_completion_array;
}

/* Returns users average grade in a course
 * @param : grade  array
 * Return : usersaveragegrades array
 */

function get_user_avggrades($grades) {
    $useravggrades = array();
    $activities = array();
    foreach ($grades as $grade => $gradeinfo) {
        foreach ($gradeinfo as $gradeval) {
            $activities[$gradeval->grade_item->itemname] = $gradeval->grade_item->itemname;
            if (!empty($useravggrades[$gradeval->userid])) {
                $useravggrades[$gradeval->userid] += $gradeval->finalgrade;
            } else {
                $useravggrades[$gradeval->userid] = $gradeval->finalgrade;
            }
        }
    }
    $total_activities = COUNT($activities);
    if ($total_activities) {
        foreach ($useravggrades as $userid => $gradetotal) {
            $useravggrades[$userid] = ($gradetotal / $total_activities);
        }
    } else {
        return;
    }
    return $useravggrades;
}
