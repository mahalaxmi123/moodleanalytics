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

function monthname($date) {
    $monthNum = date('m', strtotime($date));
    $YearNum = date('Y', strtotime($date));
    $monthName = date("F", mktime(0, 0, 0, $monthNum, 10));
    return "$monthName $YearNum";
}

function get_dashboard_countries() {
    global $USER, $CFG, $DB;
    //$sql = get_teacher_sql($params, "id", "users");

    return $DB->get_records_sql("SELECT country, count(*) as users FROM {user} WHERE confirmed = 1 and deleted = 0 and suspended = 0 and country != '' GROUP BY country");
}

function get_enrollments_per_course($params) {
    global $USER, $CFG, $DB;
    $sql = get_teacher_sql($params, "c.id", "courses");
    $sql1 = "SELECT c.id, c.fullname, count( ue.id ) AS nums FROM {course} c, {enrol} e, {user_enrolments} ue WHERE e.courseid = c.id AND ue.enrolid =e.id $sql GROUP BY c.id";
    return $DB->get_records_sql($sql1);
}

/* Reports for the selectbox
 * Returns reports array
 */

function get_coursereports() {
    $report_array = array(14 => 'New Courses', 15 => 'Courses with zero activity', 16 => 'Unique Sessions', 17 => 'Scorm Stats', 18 => 'File Stats', 19 => 'Uploads', 22 => 'Scorm Attempts', 23 => 'Course Stats', 24 => 'Progress By Learner');
    return $report_array;
}

/* Returns report class to call
 *  @param reportid int
 */

function get_report_class($reportid) {
    $classes_array = array(
        4 => new registrations(),
        5 => new enrollmentspercourse(),
        6 => new coursesize(),
        7 => new courseenrollments(),
        8 => new teachingactivity(),
        9 => new activeip(),
        10 => new languageused(),
        11 => new newregistrants(),
        12 => new newcourses(),
        13 => new enrolments(),
        14 => new new_courses(),
        15 => new course_with_zero_activity(),
        16 => new unique_sessions(),
        17 => new scorm_stats(),
        18 => new file_stats(),
        19 => new uploads(),
        20 => new registrants(),
        21 => new participations(),
        22 => new scorm_attempts(),
        23 => new course_stats(),
        24 => new progress_by_learner()
    );
    return $classes_array[$reportid];
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

function getUsersEnrolsSql($roles = array(), $enrols = array()) {
    global $CFG;

    if (empty($roles)) {
        $roles = explode(",", 5);
    }

    $sql_filter = "";
    if ($roles and $roles[0] != 0) {
        $sql_roles = array();
        foreach ($roles as $role) {
            $sql_roles[] = "ra.roleid = $role";
        }
        $sql_filter .= " AND (" . implode(" OR ", $sql_roles) . ")";
    }
    if ($enrols) {
        $sql_enrols = array();
        foreach ($enrols as $enrol) {
            $sql_enrols[] = "e.enrol = '$enrol'";
        }
        $sql_filter .= " AND (" . implode(" OR ", $sql_enrols) . ")";
    }

    return "SELECT ue.id, ra.roleid, e.courseid, ue.userid, ue.timecreated, GROUP_CONCAT( DISTINCT e.enrol) AS enrols
					FROM
						{$CFG->prefix}user_enrolments ue,
						{$CFG->prefix}enrol e,
						{$CFG->prefix}role_assignments ra,
						{$CFG->prefix}context ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
}

function getCourseGradeSql($grage = 'grade', $round = 0) {
    global $CFG;

    return "SELECT gi.courseid, round(avg((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
					FROM
						{$CFG->prefix}grade_items gi,
						{$CFG->prefix}grade_grades g
					WHERE
						gi.itemtype = 'course' AND
						g.itemid = gi.id
					GROUP BY gi.courseid";
}

function getCourseLearnersSql($learners = 'learners', $timestart = 0, $timefinish = 0) {
    global $CFG;

    $sql = ($timestart and $timefinish) ? "ue.timecreated BETWEEN $timestart AND $timefinish" : "1";

    return "SELECT ue.courseid, COUNT(DISTINCT(ue.userid)) AS $learners
					FROM
						(" . getUsersEnrolsSql() . ") ue
					WHERE $sql GROUP BY ue.courseid";
}

function getCourseCompletedSql($completed = 'completed') {
    global $CFG;

    return "SELECT c.course, count(DISTINCT(c.userid)) AS $completed
					FROM
						{$CFG->prefix}course_completions c,
						(" . getUsersEnrolsSql() . ") ue
					WHERE
						c.timecompleted > 0 AND
						c.course = ue.courseid AND
						c.userid = ue.userid
					GROUP BY c.course";
}

class course_progress {

    function get_chart_types() {
        $chartoptions = 'ComboChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $courseid, $users, $charttype) {
        global $DB, $USER;
        $json_grades = array();
        $users_update = array();
        $feedback = array();
        $context = context_course::instance($courseid);
        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));

//first make sure we have proper final grades - this must be done before constructing of the grade tree
        grade_regrade_final_grades($courseid);
//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
        $report = new grade_report_grader($courseid, $gpr, $context);
//        $numusers = $report->get_numusers(true, true);

        $activities = array();
        if (!empty($report->gtree->top_element['children'])) {
            foreach ($report->gtree->top_element['children'] as $children) {
                $activities[$children['eid']] = $children['object']->itemname;
            }
        }
// final grades MUST be loaded after the processing
        $report->load_users();
        $report->load_final_grades();

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

        $reportobj->data = $this->get_data($json_grades);
        $reportobj->headers = $this->get_headers($users, $users_update);
        $reportobj->act_avg_position = $this->get_act_avg_position($reportobj->headers);
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Activities';
        $axis->yaxis = 'Grades';
        return $axis;
    }

    function get_data($json_grades) {
        $json_grades_array = array();
        foreach ($json_grades as $key => $grade_info) {
            $grade_info = TRIM($grade_info, ',');
            $json_grades_array[] = "[" . $grade_info . "]";
        }
        return $json_grades_array;
    }

    function get_headers($users, $users_update) {
        $headers = array();
        if (!empty($users)) {
            if (!empty($users_update)) {
                foreach ($users_update as $key => $userval) {
                    if (!empty($userval)) {
                        $header1 = new stdclass();
                        $header1->type = "'string'";
                        $header1->name = "'" . $userval . " - Grade '";
                        $headers[] = $header1;
                    }
                }
            } else {
                $errors[] = 'User(s)';
            }
        }
        $position = '';
        if (empty($errors)) {
            $header2 = new stdclass();
            $header2->type = "'string'";
            $header2->name = "'" . 'activities average' . "'";
            $headers[] = $header2;
        }

        return $headers;
    }

    function get_act_avg_position($gradeheaders) {
        $position = array_search("'" . 'activities average' . "'", $headers);
        return $position;
    }

}

class activity_attempt {

    function get_chart_types() {
        $chartoptions = 'ComboChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $courseid, $users, $charttype) {
        $quiz_array = $this->get_course_quiz($courseid);
        $reportobj->quiz_array = $quiz_array;
        $quizdetails = array();
        $reportobj->info = '';
        $notattemptedusers = array();
        if ($users && !empty($reportobj->quizid)) {
            $json_quiz_attempt = $this->get_user_quiz_attempts($reportobj->quizid, $users);
            if (array_key_exists('usernotattempted', $json_quiz_attempt)) {
                $notattemptedposition = array_search('usernotattempted', array_keys($json_quiz_attempt));
                $notattemptedmessage = array_slice($json_quiz_attempt, $notattemptedposition, 1);
                foreach ($notattemptedmessage['usernotattempted'] as $key => $message) {
                    $reportobj->info .= html_writer::div($message, 'alert alert-info');
                    $notattemptedusers[] = $key;
                }
            }
            unset($json_quiz_attempt['usernotattempted']);

            $reportobj->data = $this->get_data($json_quiz_attempt);
            $reportobj->headers = $this->get_headers($users, $notattemptedusers);
        }
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
            if (!empty($attempts)) {
                $attempts = $this->format_quiz_attemptwise_grades($maxnumofattempts, $attempts);
            }
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

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Attempts';
        $axis->yaxis = 'Grades';
        return $axis;
    }

    function get_headers($users, $notattemptedusers) {
        $headers = array();
        foreach ($users as $userkey => $uservalue) {
            if (!empty($uservalue) && !in_array($uservalue, $notattemptedusers)) {
                $header1 = new stdclass();
                $header1->type = "'string'";
                $header1->name = "'" . $uservalue . " - Grade '";
                $headers[] = $header1;
            }
        }
        return $headers;
    }

    function get_data($json_quiz_attempt) {
        $quizdetails = array();
        if (!empty($json_quiz_attempt)) {
            foreach ($json_quiz_attempt as $quiz => $quizgrades) {
                $quizdetails[] = "[" . "'" . $quiz . "'" . "," . trim($quizgrades, ',') . "]";
            }
        }
        return $quizdetails;
    }

}

class activity_status {

    function get_chart_types() {
        $chartoptions = 'BubbleChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $courseid, $users, $charttype) {
        global $DB;
        $context = context_course::instance($courseid);
        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));
        $report = new grade_report_grader($courseid, $gpr, $context);
        $report->load_users();
        $report->load_final_grades();
        $resourceactivitycompletion = $this->get_activity_completion($courseid);
        $averageusergrades = $this->get_user_avggrades($report->grades);
        if (!empty($users)) {
            foreach ($users as $key => $username) {
                $feedback[$username] = $this->random_value_for_feedback();
            }
            if (!empty($resourceactivitycompletion) && $averageusergrades) {
                foreach ($resourceactivitycompletion as $resuseridkey => $rescompletiongrade) {
                    $resusers = $DB->get_record('user', array('id' => $resuseridkey));
                    $resactivitycompletion[$resusers->username] = $rescompletiongrade;
                }
                foreach ($averageusergrades as $avguserid => $avgusergrades) {
                    $usersforavggrade = $DB->get_record('user', array('id' => $avguserid));
                    $newaveragegrade[$usersforavggrade->username] = $avgusergrades;
                }

                $reportobj->data = $this->get_data($users, $newaveragegrade, $resactivitycompletion, $feedback);
                $reportobj->headers = $this->get_headers();
            }
        } else {
            $errors[] = 'User(s)';
        }
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

    function random_value_for_feedback() {
        $feedback = rand(1, 10);
        return $feedback;
    }

    function get_data($users, $newaveragegrade, $resactivitycompletion, $feedback) {
        foreach ($users as $thisuserkey => $thisusername) {
            $chartdetails[] = "[" . "'" . $thisusername . "'" . "," . $newaveragegrade[$thisusername] . "," . $resactivitycompletion[$thisusername] . "," . $feedback[$thisusername] . "]";
        }
        return $chartdetails;
    }

//    function get_headers() {
//        $gradeheaders = array();
//        //        $gradeheaders[] = "'Test'";
//        $gradeheaders[] = "'Grade'";
//        $gradeheaders[] = "'Resource completion'";
//        $gradeheaders[] = "'Feedback'";
//        return $gradeheaders;
//    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Test'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Grade'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'Resource Completion'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'Feedback'";
        $headers[] = $header4;
        return $headers;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Grades';
        $axis->yaxis = 'Resource Completion';
        return $axis;
    }

}

class new_courses {

    function get_chart_types() {
        $chartoptions = 'LineChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        if ($params) {
            $fromdate = $params['fromdate']->format('U');
            $todate = $params['todate']->format('U') + DAYSECS;

            $courses = $this->get_new_courses($fromdate, $todate);
            $coursedetails = array();
            $daywisecourse = array();
            $count = 0;
            $interval = new DateInterval('P1D'); // 1 Day
            $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

            $range = [];
            foreach ($dateRange as $date) {
                $range[$date->format('Y-m-d')] = 0;
            }
            foreach ($courses as $course) {
                $coursedetails[$course->timecreated][] = $course;
            }
            $coursedetails = array_merge($range, $coursedetails);
            foreach ($coursedetails as $key => $numofcourses) {
                if ($numofcourses != 0) {
                    $totalcourses = count($numofcourses);
                } else {
                    $totalcourses = 0;
                }
                $daywisecourse[date_format(date_create($key), 'jS M')] = $totalcourses;
            }

            $reportobj->data = $this->get_data($daywisecourse);
            $reportobj->headers = $this->get_headers();
            $reportobj->charttype = $this->get_chart_types();
        }
    }

    function get_new_courses($fromdate, $todate) {
        global $DB;
//        $lastselecteddate = strtotime(date('Y-m-d h:m:s', strtotime("-$days days")));
        $sql = "select id, shortname, FROM_UNIXTIME(timecreated, '%Y-%m-%d') as timecreated from {course} where timecreated BETWEEN $fromdate AND $todate ORDER BY timecreated ASC";
        $courses = $DB->get_records_sql($sql);
        return $courses;
    }

    function get_axis_names() {
        $axis = new stdClass();
        $axis->xaxis = 'Days';
        $axis->yaxis = 'courses';
        return $axis;
    }

    function get_data($daywisecourse) {
        $chartdetails = array();
        foreach ($daywisecourse as $day => $coursecount) {
            $chartdetails[] = "[" . "'" . $day . "'" . "," . $coursecount . "]";
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

//    function get_headers() {
//        $gradeheaders = array();
////        $gradeheaders[] = "'Date'";
//        $gradeheaders[] = "'Courses'";
//        return $gradeheaders;
//    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Date'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Courses'";
        $headers[] = $header2;
        return $headers;
    }

}

class course_with_zero_activity {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj) {
        global $DB;
        $sql = "SELECT fullname, FROM_UNIXTIME(timecreated, '%Y-%m-%d') as timecreated FROM  {course}
                WHERE id NOT IN (SELECT DISTINCT course FROM {course_modules})
                AND id != 1";
        $noactcourses = $DB->get_records_sql($sql);
        $reportobj->data = $this->get_data($noactcourses);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_axis_names() {
        return '';
    }

    function get_data($courseswithnoactivities) {
//        $chartdetails = array();
        foreach ($courseswithnoactivities as $course) {
            $chartdetails[] = "[" . "'" . $course->fullname . "'" . "," . "'" . $course->timecreated . "'" . "]";
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

//    function get_headers() {
//        $gradeheaders = array();
////        $gradeheaders[] = "'Date'";
//        $gradeheaders[] = "'Creation Time'";
//        return $gradeheaders;
//    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Course'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Creation Date'";
        $headers[] = $header2;
        return $headers;
    }

}

class unique_sessions {

    function get_chart_types() {
        $chartoptions = 'LineChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $sessions = $this->get_unique_sessions($fromdate, $todate);
        $sessiondetails = array();
        $daywisesession = array();
        $count = 0;
        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $range = [];
        foreach ($dateRange as $date) {
            $range[$date->format('Y-m-d')] = 0;
        }

        foreach ($sessions as $session) {
            $sessiondetails[$session->date]['session'] = $session;
        }

        $sessiondetails = array_merge($range, $sessiondetails);
        foreach ($sessiondetails as $key => $numofsession) {
            if ($numofsession != 0) {
                $totalsession = $numofsession['session']->uniqusessions;
            } else {
                $totalsession = 0;
            }
            $daywisesession[date_format(date_create($key), 'jS M')] = $totalsession;
        }

        $reportobj->data = $this->get_data($daywisesession);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_unique_sessions($fromdate, $todate) {
        global $DB;
        $sql = "SELECT FROM_UNIXTIME( lastaccess,  '%Y-%m-%d' ) AS DATE, COUNT( id ) AS uniqusessions
                FROM mdl_user WHERE id
                IN (
                    SELECT DISTINCT (userid)
                    FROM mdl_role_assignments
                    WHERE roleid
                    IN ( 5 )
                )
                AND lastaccess
                BETWEEN $fromdate 
                AND $todate";
        $uniquesessions = $DB->get_records_sql($sql);
        return $uniquesessions;
    }

    function get_axis_names() {
        $axis = new stdClass();
        $axis->xaxis = 'Days';
        $axis->yaxis = 'Sessions';
        return $axis;
    }

    function get_data($daywisesessions) {
        $chartdetails = array();
        foreach ($daywisesessions as $day => $uniquesession) {
            $chartdetails[] = "[" . "'" . $day . "'" . "," . $uniquesession . "]";
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

//    function get_headers() {
//        $gradeheaders = array();
////        $gradeheaders[] = "'Date'";
//        $gradeheaders[] = "'Sessions'";
//        return $gradeheaders;
//    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Date'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Sessions'";
        $headers[] = $header2;
        return $headers;
    }

}

class scorm_stats {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $scormstats = $this->get_scorm_stats($fromdate, $todate);
        $coursewisescorm = array();

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $scormcourses = array();
        foreach ($scormstats as $scorm) {
            $scormcourses[$scorm->course][$scorm->teacher][] = $scorm;
        }

        foreach ($scormcourses as $key => $numofscorm) {
            foreach ($numofscorm as $scormkey => $scormvalue) {
                $coursewisescorm[$key][$scormkey] = count($scormvalue);
            }
        }

        $reportobj->data = $this->get_data($coursewisescorm);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_scorm_stats($fromdate, $todate) {
        global $DB;
        $sql = "SELECT s.id, c.id as courseid ,s.name as scorm, c.fullname as course, u.username as teacher
                FROM {scorm} s
                INNER JOIN {files} f ON f.filename = s.reference
                INNER JOIN {course} c ON c.id = s.course
                INNER JOIN {user} u ON u.id = f.userid
                AND f.timecreated
                BETWEEN $fromdate 
                AND $todate ORDER BY f.timecreated DESC";
        $scormstats = $DB->get_records_sql($sql);
        return $scormstats;
    }

    function get_axis_names() {
//        $axis = new stdClass();
//        $axis->xaxis = 'Days';
//        $axis->yaxis = 'Sessions';
        return '';
    }

    function get_data($coursewisescorm) {
        $chartdetails = array();
        foreach ($coursewisescorm as $course => $coursevalue) {
            foreach ($coursevalue as $teacher => $scormcount) {
                $chartdetails[] = "[" . "'" . $course . "'" . "," . "'" . $teacher . "'" . "," . $scormcount . "]";
            }
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

//    function get_headers() {
//        $gradeheaders = array();
//        $gradeheaders[] = "'Teacher'";
//        $gradeheaders[] = "'# of Scorms'";
//        return $gradeheaders;
//    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Course'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Teacher'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'# of Scorms'";
        $headers[] = $header3;
        return $headers;
    }

}

class file_stats {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $filestats = $this->get_file_stats($fromdate, $todate);
        $coursewisefile = array();

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $filecourses = array();
        foreach ($filestats as $file) {
            $filecourses[$file->course][$file->teacher][] = $file;
        }

        foreach ($filecourses as $key => $numoffile) {
            foreach ($numoffile as $filekey => $filevalue) {
                $coursewisefile[$key][$filekey] = count($filevalue);
            }
        }

        $reportobj->data = $this->get_data($coursewisefile);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_file_stats($fromdate, $todate) {
        global $DB;
        $sql = "SELECT r.id, c.fullname AS course, r.name AS file , 
                (
                    SELECT DISTINCT CONCAT( u.firstname,  ' ', u.lastname ) 
                    FROM {role_assignments} AS ra
                    JOIN {user} AS u ON ra.userid = u.id
                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ra.roleid
                    IN ( 3 ) 
                    AND ctx.instanceid = c.id
                    AND ctx.contextlevel =50
                    LIMIT 1
                ) AS teacher
                FROM {course} c
                LEFT JOIN {resource} r ON r.course = c.id
                WHERE c.id !=1
                AND r.timemodified
                BETWEEN $fromdate 
                AND $todate ORDER BY r.timemodified DESC";
        $filestats = $DB->get_records_sql($sql);
        return $filestats;
    }

    function get_axis_names() {
//        $axis = new stdClass();
//        $axis->xaxis = 'Days';
//        $axis->yaxis = 'Sessions';
        return '';
    }

    function get_data($coursewisefile) {
        $chartdetails = array();
        foreach ($coursewisefile as $course => $coursevalue) {
            foreach ($coursevalue as $teacher => $filecount) {
                $chartdetails[] = "[" . "'" . $course . "'" . "," . "'" . $teacher . "'" . "," . $filecount . "]";
            }
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

//    function get_headers() {
//        $gradeheaders = array();
//        $gradeheaders[] = "'Teacher'";
//        $gradeheaders[] = "'# of Files'";
//        return $gradeheaders;
//    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Course'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Teacher'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'# of Files'";
        $headers[] = $header3;
        return $headers;
    }

}

class uploads {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $uploadstats = $this->get_upload_stats($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $uploaddetails = array();
        foreach ($uploadstats as $upload) {
            $uploaddetails[$upload->component][] = $upload;
        }
        $uploaddatas = array();
        foreach ($uploaddetails as $key => $details) {
            $uploaddatas[$key]['count'] = count($details);
            $sum = 0;
            for ($i = 0; $i < count($details); $i++) {
                $sum = $sum + (int) $details[$i]->filesize;
            }
//            foreach ($details as $values) {
//                print_object($values);
//                $sum = $sum + (int) $values->filesize;
//            }
            $uploaddatas[$key]['filesize'] = $sum;
        }

        $reportobj->data = $this->get_data($uploaddatas);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_upload_stats($fromdate, $todate) {
        global $DB;
        $sql = "SELECT 
                SQL_CALC_FOUND_ROWS id, component, filesize
                FROM mdl_files
                WHERE id >0 
                AND timemodified
                BETWEEN $fromdate 
                AND $todate and filename != '.' ORDER BY timemodified DESC";
        $uploadstats = $DB->get_records_sql($sql);
        return $uploadstats;
    }

    function get_axis_names() {
//        $axis = new stdClass();
//        $axis->xaxis = 'Days';
//        $axis->yaxis = 'Sessions';
        return '';
    }

    function get_data($uploaddatas) {
        $chartdetails = array();
        foreach ($uploaddatas as $key => $uploadvalue) {
//            $chartdetails[] = "[" . "'" . $key . "'" . "," . "'" . $teacher . "'" . "," . "'" . $filecount . "'" . "]";
            $chartdetails[] = "[" . "'" . $key . "'" . "," . $uploadvalue['count'] . "," . $uploadvalue['filesize'] . "]";
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

//    function get_headers() {
//        $gradeheaders = array();
//        $gradeheaders[] = "'# of Files'";
//        $gradeheaders[] = "'File size'";
//        return $gradeheaders;
//    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Component'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'# of Files'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'File Size'";
        $headers[] = $header3;
        return $headers;
    }

}

class registrations {

    function get_chart_types() {
        $chartoptions = 'GeoChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER;
        $json_countries = array();
        $countries = $this->get_dashboard_countries();
        foreach ($countries as $country) {
            $json_countries[] = "['" . ucfirst($country->country) . "', $country->users]";
        }

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_countries;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_dashboard_countries() {
        global $USER, $CFG, $DB;
        $sql = "SELECT country, count(*) as users FROM {user} WHERE confirmed = 1 and deleted = 0 and suspended = 0 and country != '' GROUP BY country";
        $countries = $DB->get_records_sql($sql);
        return $countries;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Country';
        $axis->yaxis = 'Users';
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Country'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Users'";
        $gradeheaders[] = $header2;
        return $gradeheaders;
    }

}

class enrollmentspercourse {

    function get_chart_types() {
        $chartoptions = 'PieChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER;
        $fromdate = $params['timestart']->format('U');
        $todate = $params['timefinish']->format('U') + DAYSECS;
        $json_enrols = array();
        $enrollments = $this->get_enrollments_per_course($fromdate, $todate);
        foreach ($enrollments as $enrollment) {
            $json_enrols[] = '[' . '"' . $enrollment->fullname . '"' . ',' . $enrollment->nums . ']';
        }

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_enrols;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_enrollments_per_course($fromdate, $todate) {
        global $USER, $CFG, $DB;
//        $sql = $this->get_teacher_sql($params, "c.id", "courses");
        $sql1 = "SELECT c.id, c.fullname, count( ue.id ) AS nums FROM {course} c, {enrol} e, {user_enrolments} ue WHERE e.courseid = c.id AND ue.enrolid =e.id and ue.timecreated between $fromdate and $todate GROUP BY c.id";
        return $DB->get_records_sql($sql1);
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'fullname';
        $axis->yaxis = 'nums';
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'fullname'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'nums'";
        $gradeheaders[] = $header2;
        return $gradeheaders;
    }

}

class coursesize {

    function get_chart_types() {
        $chartoptions = 'BarChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $json_coursesizes = array();
        $coursesizes = array();
        $coursesizesql = "SELECT c.fullname as coursename , fs.coursesize as size "
                . "FROM {course} c "
                . "LEFT JOIN (SELECT c.instanceid AS course, sum( f.filesize ) as coursesize "
                . "FROM {files} f, {context} c "
                . "WHERE c.id = f.contextid GROUP BY c.instanceid) fs ON fs.course = c.id WHERE c.category > 0 ORDER BY c.timecreated ";
        $coursesizes = $DB->get_records_sql($coursesizesql);

        foreach ($coursesizes as $csize) {
            $csize->size = ($csize->size / (1024 * 1024));
            $json_coursesizes[] = '[' . '"' . $csize->coursename . '"' . ',' . $csize->size . ']';
        }

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_coursesizes;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Size in MB';
        $axis->yaxis = 'Course Name';
        return $axis;
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Course Name'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Size in MB'";
        $headers[] = $header2;
        return $headers;
    }

}

class courseenrollments {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $json_courseenrollments = array();
        $courseenrollments = array();
        $timesql = '';
        if (isset($params->timestart) && isset($params->timefinish)) {
            $timesql = "AND ue.timecreated BETWEEN $params->timestart AND $params->timefinish";
        }
        $this->learner_roles = 5;

        $sql = "SELECT
			SQL_CALC_FOUND_ROWS ue.id,IF(ue.timestart = 0, ue.timecreated, ue.timecreated) as enrolstart,
			ue.timeend as enrolend,	ccc.timeend,c.startdate,
			c.enablecompletion,cc.timecompleted as complete,
			CONCAT(u.firstname, ' ', u.lastname) as learner,
			u.email,
			ue.userid,
			e.courseid,
			e.enrol,
			c.fullname as course
			
						FROM
							{user_enrolments} ue
							LEFT JOIN {enrol} e ON e.id = ue.enrolid
							LEFT JOIN {context} ctx ON ctx.instanceid = e.courseid
							LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = e.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = e.courseid AND cc.userid = ue.userid
							LEFT JOIN {course_completion_criteria} as ccc ON ccc.course = e.courseid AND ccc.criteriatype = 2
								WHERE ra.roleid IN ($this->learner_roles) $timesql GROUP BY ue.id ";

//            "recordsTotal" => key($size),
//            "recordsFiltered" => key($size),
//            "data" => $data;
        $courseenrollments = $DB->get_records_sql($sql);
        foreach ($courseenrollments as $cenrol) {

//            $json_courseenrollments[] = '[' . '"' . userdate($cenrol->startdate) . '"' . ',' .
//                    '"' . userdate($cenrol->enrolstart) . '"' . ',' . '"' . $cenrol->course . '"' . ',' .
//                    '"' . $cenrol->learner . '"' . ',' . '"' . $cenrol->email . '"' . ',' . '"' . $cenrol->enrol . '"' . ',' .
//                    '"' . $cenrol->enrolstart . '"' . ',' . '"' . $cenrol->enrolend . '"' . ',' . '"' . $cenrol->complete . '"' . ']';

            $json_courseenrollments[] = '[' . '"' . userdate($cenrol->startdate, get_string('strftimedate', 'langconfig')) . '"' . ',' .
                    '"' . userdate($cenrol->enrolstart, get_string('strftimedate', 'langconfig')) . '"' . ',' . '"' . $cenrol->course . '"' . ',' .
                    '"' . $cenrol->learner . '"' . ',' . '"' . $cenrol->email . '"' . ',' . '"' . $cenrol->enrol . '"' . ',' .
                    '"' . userdate($cenrol->enrolstart, get_string('strftimedate', 'langconfig')) . '"' . ',' . '"' . userdate($cenrol->enrolend, get_string('strftimedate', 'langconfig')) . '"' . ',' . '"' . $cenrol->complete . '"' . ']';
//                    . ',' . $cenrol->complete ? 'Yes' : 'No' . "]";
        }
        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_courseenrollments;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $headers = array();
        $header4 = new stdclass();
        $header4->type = "'string'";
        $header4->name = "'startdate'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'string'";
        $header5->name = "'timeend'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'string'";
        $header6->name = "'Course'";
        $headers[] = $header6;
        $header7 = new stdclass();
        $header7->type = "'string'";
        $header7->name = "'Learner'";
        $headers[] = $header7;
        $header8 = new stdclass();
        $header8->type = "'string'";
        $header8->name = "'email'";
        $headers[] = $header8;
        $header9 = new stdclass();
        $header9->type = "'string'";
        $header9->name = "'Enrol method'";
        $headers[] = $header9;
        $header10 = new stdclass();
        $header10->type = "'string'";
        $header10->name = "'Enrol start'";
        $headers[] = $header10;
        $header11 = new stdclass();
        $header11->type = "'string'";
        $header11->name = "'Enrol end'";
        $headers[] = $header11;
        $header12 = new stdclass();
        $header12->type = "'string'";
        $header12->name = "'Completion status'";
        $headers[] = $header12;

        return $headers;
    }

}

class teachingactivity {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $json_teachingactivity = array();
        $teachingact = array();
        $teachers = $DB->get_records_sql("SELECT distinct c.id "
                . "FROM {course} as c, {role_assignments} AS ra, {user} AS u,"
                . " {context} AS ct WHERE c.id = ct.instanceid AND ra.roleid IN (2,3,4) "
                . "AND ra.userid = u.id AND ct.id = ra.contextid");
        $teachers_list = implode(',', array_keys($teachers));
        if ($CFG->version < 2014051200) {
            $table = "{log}";
            $teachingact = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id as userid ,COUNT(c.id) as course , CONCAT(u.firstname, ' ', u.lastname) as teacher,
					ff.videos,l1.urls,l0.evideos,
					l2.assignments,l3.quizes,l4.forums,l5.attendances
					FROM 	{user_enrolments} ue
						LEFT JOIN {user} u ON u.id = ue.userid
                                                LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                                                LEFT JOIN {context} ct ON ct.id = ra.contextid 
                                                LEFT JOIN {course} c on c.id = ct.instanceid
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {files} f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
                                                LEFT JOIN (SELECT l.userid, count(l.id) urls FROM $table l WHERE l.module = 'url' AND l.action = 'add' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM $table l WHERE l.module = 'page' AND l.action = 'add' GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM $table l WHERE l.module = 'assignment' AND l.action = 'add' GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM $table l WHERE l.module = 'quiz' AND l.action = 'add' GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM $table l WHERE l.module = 'forum' AND l.action = 'add' GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM $table l WHERE l.module = 'attendance' AND l.action = 'add' GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE u.deleted = 0 AND u.suspended = 0 AND ra.roleid IN (2,3,4) GROUP BY ue.userid");
        } else {
            $table = "{logstore_standard_log}";
            $teachingact = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id as userid,COUNT(c.id) course,CONCAT(u.firstname, ' ', u.lastname) as teacher,
					f1.files,ff.videos,l1.urls,l0.evideos,l2.assignments,
					l3.quizes,l4.forums,l5.attendances FROM
							{user_enrolments} ue
						LEFT JOIN {user} u ON u.id = ue.userid
                                                LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                                                LEFT JOIN {context} ct ON ct.id = ra.contextid 
                                                LEFT JOIN {course} c on c.id = ct.instanceid 
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) files FROM {files} f WHERE filearea = 'content' GROUP BY f.userid) as f1 ON f1.userid = u.id
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {files} f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) urls FROM $table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'url' AND l.action = 'created' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM $table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'page' AND l.action = 'created'GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM $table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'assignment' AND l.action = 'created'GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM $table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'quiz' AND l.action = 'created'GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM $table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'forum' AND l.action = 'created'GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM $table l,{course_modules} cm, {modules} m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'attendance' AND l.action = 'created'GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE u.deleted = 0 AND u.suspended = 0 AND ra.roleid IN (2,3,4) GROUP BY ue.userid");
        }
        foreach ($teachingact as $teachact) {
            $courses = $teachact->course != NULL ? $teachact->course : 0;
            $videos = $teachact->videos != NULL ? $teachact->videos : 0;
            $urls = $teachact->urls != NULL ? $teachact->urls : 0;
            $evideos = $teachact->evideos != NULL ? $teachact->evideos : 0;
            $forums = $teachact->forums != NULL ? $teachact->forums : 0;
            $assignments = $teachact->assignments != NULL ? $teachact->assignments : 0;
            $attendances = $teachact->attendances != NULL ? $teachact->attendances : 0;
            $quizes = $teachact->quizes != NULL ? $teachact->quizes : 0;
            $json_teachingactivity[] = "[" . "'" . $teachact->teacher . "'" . ',' .
                    $courses . ',' . $videos . ',' .
                    $urls . ',' . $evideos . ',' . $assignments . ',' .
                    $quizes . ',' . $forums . ',' . $attendances . "]";
        }

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_teachingactivity;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();

        return $axis;
    }

    function get_headers() {
        $headers = array();
        $header4 = new stdclass();
        $header4->type = "'string'";
        $header4->name = "'teacher'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'courses'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'number'";
        $header6->name = "'videos'";
        $headers[] = $header6;
        $header7 = new stdclass();
        $header7->type = "'number'";
        $header7->name = "'urls'";
        $headers[] = $header7;
        $header8 = new stdclass();
        $header8->type = "'number'";
        $header8->name = "'evideos'";
        $headers[] = $header8;
        $header9 = new stdclass();
        $header9->type = "'number'";
        $header9->name = "'assignments'";
        $headers[] = $header9;
        $header10 = new stdclass();
        $header10->type = "'number'";
        $header10->name = "'quizes'";
        $headers[] = $header10;
        $header11 = new stdclass();
        $header11->type = "'number'";
        $header11->name = "'forums'";
        $headers[] = $header11;
        $header12 = new stdclass();
        $header12->type = "'number'";
        $header12->name = "'attendances'";
        $headers[] = $header12;

        return $headers;
    }

}

class activeip {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $json_activeips = array();
        $activeips = array();
        $activeipsql = "SELECT id, username, lastip, currentlogin FROM mdl_user WHERE timecreated >0 and currentlogin>0
            ORDER BY lastlogin DESC";
        //$lists = $DB->get_records_sql($sql);
        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();
        $activeips = $DB->get_records_sql($activeipsql);

        foreach ($activeips as $activeip) {
            //$csize->size = ($csize->size/(1024*1024));
            //$json_coursesizes[] = "['" . $csize->coursename . "', $csize->size]";
            $json_activeips[] = "['" . $activeip->username . "'," . "'" . $activeip->lastip . "'" . ",'" . date('d-m-y', $activeip->currentlogin) . "'" . '],';
        }

        $reportobj->data = $json_activeips;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Username'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'IP Address'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Last Acceess'";
        $gradeheaders[] = $header3;
        return $gradeheaders;
    }

}

class languageused {

    function get_chart_types() {
        $chartoptions = 'PieChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $param) {
        global $DB, $USER, $CFG;
        $language_codes = array(
            'en' => 'English', 'aa' => 'Afar', 'ab' => 'Abkhazian', 'af' => 'Afrikaans',
            'am' => 'Amharic', 'ar' => 'Arabic', 'as' => 'Assamese', 'ay' => 'Aymara',
            'az' => 'Azerbaijani', 'ba' => 'Bashkir', 'be' => 'Byelorussian', 'bg' => 'Bulgarian',
            'bh' => 'Bihari', 'bi' => 'Bislama', 'bn' => 'Bengali/Bangla', 'bo' => 'Tibetan',
            'br' => 'Breton', 'ca' => 'Catalan', 'co' => 'Corsican', 'cs' => 'Czech', 'cy' => 'Welsh',
            'da' => 'Danish', 'de' => 'German', 'dz' => 'Bhutani', 'el' => 'Greek', 'eo' => 'Esperanto',
            'es' => 'Spanish', 'et' => 'Estonian', 'eu' => 'Basque', 'fa' => 'Persian', 'fi' => 'Finnish',
            'fj' => 'Fiji', 'fo' => 'Faeroese', 'fr' => 'French', 'fy' => 'Frisian', 'ga' => 'Irish',
            'gd' => 'Scots/Gaelic', 'gl' => 'Galician', 'gn' => 'Guarani', 'gu' => 'Gujarati',
            'ha' => 'Hausa', 'hi' => 'Hindi', 'hr' => 'Croatian', 'hu' => 'Hungarian', 'hy' => 'Armenian',
            'ia' => 'Interlingua', 'ie' => 'Interlingue', 'ik' => 'Inupiak', 'in' => 'Indonesian',
            'is' => 'Icelandic', 'it' => 'Italian', 'iw' => 'Hebrew', 'ja' => 'Japanese',
            'ji' => 'Yiddish', 'jw' => 'Javanese', 'ka' => 'Georgian', 'kk' => 'Kazakh', 'kl' => 'Greenlandic',
            'km' => 'Cambodian', 'kn' => 'Kannada', 'ko' => 'Korean', 'ks' => 'Kashmiri', 'ku' => 'Kurdish',
            'ky' => 'Kirghiz', 'la' => 'Latin', 'ln' => 'Lingala', 'lo' => 'Laothian', 'lt' => 'Lithuanian',
            'lv' => 'Latvian/Lettish', 'mg' => 'Malagasy', 'mi' => 'Maori', 'mk' => 'Macedonian',
            'ml' => 'Malayalam', 'mn' => 'Mongolian', 'mo' => 'Moldavian', 'mr' => 'Marathi', 'ms' => 'Malay',
            'mt' => 'Maltese', 'my' => 'Burmese', 'na' => 'Nauru', 'ne' => 'Nepali', 'nl' => 'Dutch',
            'no' => 'Norwegian', 'oc' => 'Occitan', 'om' => '(Afan)/Oromoor/Oriya', 'pa' => 'Punjabi',
            'pl' => 'Polish', 'ps' => 'Pashto/Pushto', 'pt' => 'Portuguese', 'qu' => 'Quechua', 'rm' => 'Rhaeto-Romance',
            'rn' => 'Kirundi', 'ro' => 'Romanian', 'ru' => 'Russian', 'rw' => 'Kinyarwanda', 'sa' => 'Sanskrit',
            'sd' => 'Sindhi', 'sg' => 'Sangro', 'sh' => 'Serbo-Croatian', 'si' => 'Singhalese', 'sk' => 'Slovak',
            'sl' => 'Slovenian', 'sm' => 'Samoan', 'sn' => 'Shona', 'so' => 'Somali', 'sq' => 'Albanian',
            'sr' => 'Serbian', 'ss' => 'Siswati', 'st' => 'Sesotho', 'su' => 'Sundanese', 'sv' => 'Swedish',
            'sw' => 'Swahili', 'ta' => 'Tamil', 'te' => 'Tegulu', 'tg' => 'Tajik', 'th' => 'Thai',
            'ti' => 'Tigrinya', 'tk' => 'Turkmen', 'tl' => 'Tagalog', 'tn' => 'Setswana', 'to' => 'Tonga',
            'tr' => 'Turkish', 'ts' => 'Tsonga', 'tt' => 'Tatar', 'tw' => 'Twi', 'uk' => 'Ukrainian',
            'ur' => 'Urdu', 'uz' => 'Uzbek', 'vi' => 'Vietnamese', 'vo' => 'Volapuk',
            'wo' => 'Wolof', 'xh' => 'Xhosa', 'yo' => 'Yoruba', 'zh' => 'Chinese', 'zu' => 'Zulu',);
        $languageusedsql = "SELECT lang, COUNT(lang) AS Count FROM  `mdl_user` GROUP BY lang";
        $languageuseds = $DB->get_records_sql($languageusedsql);
        $json_languageused = array();
        $language = array();
        $count = array();
        $languageused = array();

        foreach ($languageuseds as $list) {
            $var = $list->lang;
            $language[] = $language_codes[$var];
            $count[] = ($list->count);
        }
        $languageused = array_combine($language, $count);
        $axis = $this->get_axis_names('PieChart');
        $charttype = $this->get_chart_types();
        $title = $this->get_chart_title();

        foreach ($languageused as $key => $value) {
            $json_languageused[] = "['" . $key . "'" . ',' . $value . '],';
        }

        $reportobj->data = $json_languageused;
        $reportobj->axis = $axis;
        $reportobj->charttype = $charttype;
        $reportobj->charttitle = $title;
    }

    function get_chart_title() {
        $charttitle = 'Language Used';
        return $charttitle;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = "'Language'";
        $axis->yaxis = "'User'";
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        return $gradeheaders;
    }

}

class newregistrants {

    function get_chart_types() {
        $chartoptions = 'AnnotationChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $param = array()) {
        global $DB, $USER, $CFG;
        $_SESSION['current_month'] = $param[0];
        $newusersql = "SELECT * FROM mdl_user WHERE timecreated>0";
        $lists = $DB->get_records_sql($newusersql);
        $lastdate = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($_SESSION['current_month'])), date('Y', strtotime($_SESSION['current_month'])));
//$eventsbyday = get_records_between('mdl_event', $_SESSION['current_month'], $lastdate);

        $presentdate = date('Y-m-d', strtotime($_SESSION['current_month']));
        $presentmonth = date('m', strtotime($_SESSION['current_month']));
        $presentyear = date('Y', strtotime($_SESSION['current_month']));
        $nfirst = 0;
        $nmid = 0;
        $nlast = 0;
        $numberofuser = 0;
        foreach ($lists as $list) {
            $numberofuser++;
            if (($presentmonth != date('m', $list->timecreated)) || ($presentmonth != date('m', $list->timecreated))) {
                continue;
            }
            if (date('d', $list->timecreated) == 1) {
                $nfirst++;
                continue;
            }
            if (date('d', $list->timecreated) <= 15) {
                $nmid++;
            } else {
                $nlast++;
            }
        }
//$firstdate = $date("Y,m",)    
        $monthyear = date("Y,m,", strtotime($presentdate));
        $lastdate = date("Y,m,t", strtotime($presentdate));
        $firstdatestring = '"' . $monthyear . ',15"';
        $middatestring = '"' . $monthyear . ',1"';
        $lastdatestring = '"' . $lastdate . '"';


        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
        $reportobj->totalusers = $numberofuser;
        $reportobj->nfirst = $nfirst;
        $reportobj->nmid = $nmid;
        $reportobj->nlast = $nlast;
        $reportobj->firstdatestring = $firstdatestring;
        $reportobj->middatestring = $middatestring;
        $reportobj->lastdatestring = $lastdatestring;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Size in MB';
        $axis->yaxis = 'Course Name';
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'date'";
        $header1->name = "'Date'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Number Of Courses'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Title'";
        $gradeheaders[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'string'";
        $header4->name = "'Strength'";
        $gradeheaders[] = $header4;
        return $gradeheaders;
    }

}

class newcourses {

    function get_chart_types() {
        $chartoptions = 'AnnotationChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $param = array()) {
        global $DB, $USER, $CFG;
        $_SESSION['current_month'] = $param[0];
        $newcoursesql = "SELECT * FROM mdl_course WHERE startdate>0";
        $lists = $DB->get_records_sql($newcoursesql);
        $lastdate = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($_SESSION['current_month'])), date('Y', strtotime($_SESSION['current_month'])));
//$eventsbyday = get_records_between('mdl_event', $_SESSION['current_month'], $lastdate);

        $presentdate = date('Y-m-d', strtotime($_SESSION['current_month']));
        $presentmonth = date('m', strtotime($_SESSION['current_month']));
        $presentyear = date('Y', strtotime($_SESSION['current_month']));
        $nfirst = 0;
        $nmid = 0;
        $nlast = 0;
        $course = 0;
        foreach ($lists as $list) {
            $course++;
            if (($presentmonth != date('m', $list->timecreated)) || ($presentmonth != date('m', $list->timecreated))) {
                continue;
            }
            if (date('d', $list->timecreated) == 1) {
                $nfirst++;
                continue;
            }
            if (date('d', $list->timecreated) <= 15) {
                $nmid++;
            } else {
                $nlast++;
            }
        }
        $monthyear = date("Y,m,", strtotime($presentdate));
        $lastdate = date("Y,m,t", strtotime($presentdate));
        $firstdatestring = '"' . $monthyear . ',15"';
        $middatestring = '"' . $monthyear . ',1"';
        $lastdatestring = '"' . $lastdate . '"';




        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        // $reportobj->data = $json_languageused;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
        $reportobj->totalcourses = $course;
        $reportobj->nfirst = $nfirst;
        $reportobj->nmid = $nmid;
        $reportobj->nlast = $nlast;
        $reportobj->firstdatestring = $firstdatestring;
        $reportobj->middatestring = $middatestring;
        $reportobj->lastdatestring = $lastdatestring;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'date'";
        $header1->name = "'Date'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Number Of Users'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Title'";
        $gradeheaders[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'string'";
        $header4->name = "'Strength'";
        $gradeheaders[] = $header4;
        return $gradeheaders;
    }

}

class enrolments {

    function get_chart_types() {
        $chartoptions = 'PieChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $param) {
        global $DB, $USER, $CFG;

        $sql = "SELECT uen.id , en.enrol FROM  mdl_enrol as en , mdl_user_enrolments as uen WHERE en.id = uen.enrolid";
        $lists = $DB->get_records_sql($sql);
        $json_enrol = array();

        $manual = 0;
        $self = 0;
        $guest = 0;
        $noofenrolments = 0;

        foreach ($lists as $list) {
            $noofenrolments++;
            if ($list->enrol == 'manual') {
                $manual++;
            } elseif ($list->enrol == 'self') {
                $self++;
            } elseif ($list->enrol == 'guest') {
                $guest++;
            }
        }
        $json_enrol[] = "['" . 'Manual' . "'," . $manual . "]";
        $json_enrol[] = "['" . 'Self' . "'," . $self . "]";
        $json_enrol[] = "['" . 'Guest' . "'," . $guest . "]";
        $axis = $this->get_axis_names('PieChart');
        $charttype = $this->get_chart_types();
        $title = $this->get_chart_title();
        $header = $this->get_headers();

        $reportobj->axis = $axis;
        $reportobj->headers = $header;
        $reportobj->charttype = $charttype;
        $reportobj->charttitle = $title;
        $reportobj->data = $json_enrol;
    }

    function get_chart_title() {
        $charttitle = 'Enroled User';
        return $charttitle;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = "'Enrolments Methods'";
        $axis->yaxis = "'Number of Enrolments'";
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Manual'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Self'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Guest'";
        $gradeheaders[] = $header3;
        return $gradeheaders;
    }

}

class registrants {

    function get_chart_types() {
        $chartoptions = 'PieChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $param) {
        global $DB, $USER, $CFG;
        $registrantssql = "SELECT auth, COUNT(auth) AS Count FROM  `mdl_user` GROUP BY auth";
        $registrants = $DB->get_records_sql($registrantssql);
        $json_registrants = array();
        $typeenrol = array();
        $count = array();
        $registrant = array();

        foreach ($registrants as $list) {
            $typeenrol[] = $list->auth;
            //$language[] = $language_codes[$var];
            $count[] = ($list->count);
        }
        $registrant = array_combine($typeenrol, $count);
        $axis = $this->get_axis_names('PieChart');
        $charttype = $this->get_chart_types();
        $title = $this->get_chart_title();

        foreach ($registrant as $key => $value) {
            $json_registrants[] = "['" . ucfirst($key) . "'" . ',' . $value . '],';
        }

        $reportobj->data = $json_registrants;
        $reportobj->axis = $axis;
        $reportobj->charttype = $charttype;
        $reportobj->charttitle = $title;
    }

    function get_chart_title() {
        $charttitle = 'Registrants';
        return $charttitle;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = "'Enrollment_type'";
        $axis->yaxis = "'User'";
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        return $gradeheaders;
    }

}

class participations {

    function get_chart_types() {
        $chartoptions = 'ColumnChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj2, $param) {
        global $DB, $USER, $CFG;
        $courses = $DB->get_records('course', array());
        $data = array();
        foreach ($courses as $course) {
            $complete = 0;
            $notcomplete = 0;
            if (($course->id) == SITEID)
                continue;
            $coursecontext = context_course::instance($course->id);
            $courseusers = get_enrolled_users($coursecontext);
            $completeobj = new completion_info($course);
            foreach ($courseusers as $courseuser) {

                if ($completeobj->is_course_complete($courseuser->id)) {
                    $complete++;
                } else {
                    $notcomplete++;
                }
            }
            $data[] = "['" . $course->fullname . "'," . $complete . ',' . $notcomplete . ",' ']";
        }
        $charttype = $this->get_chart_types();
        $charttitle = $this->get_chart_title();
        $reportobj2->data = $data;
        $reportobj2->title = $charttitle;
        $reportobj2->charttype = $charttype;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_chart_title() {
        $charttitle = "'Course Name'" . ',' . "'Completed'" . ',' . "'Not Completed'";
        return $charttitle;
    }

    function get_headers() {
        $gradeheaders = array();
        return $gradeheaders;
    }

}

/* function newregistrants_get_chart_types($chartanme) {
  $chartoptions = $chartname;
  return $chartoptions;
  }

  function newregistrants_process_reportdata() {
  global $DB, $USER, $CFG;
  $sql = "SELECT * FROM mdl_user WHERE timecreated>0";
  $lists = $DB->get_records_sql($sql);
  return $lists;
  }

  function newregistrants_get_axis_names($reportname) {
  $axis = new stdClass();
  $axis->xaxis = 'Size in MB';
  $axis->yaxis = 'Course Name';
  return $axis;
  }

  function newregistrants_get_headers() {
  $gradeheaders = array();
  $header1 = new stdclass();
  $header1->type = "'date'";
  $header1->name = "'Date'";
  $gradeheaders[] = $header1;
  $header2 = new stdclass();
  $header2->type = "'number'";
  $header2->name = "'Number Of Courses'";
  $gradeheaders[] = $header2;
  $header3 = new stdclass();
  $header3->type = "'string'";
  $header3->name = "'Last Acceess'";
  $gradeheaders[] = $header3;
  $header4 = new stdclass();
  $header4->type = "'string'";
  $header4->name = "'Strength'";
  $gradeheaders[] = $header4;
  return $gradeheaders;
  } */

class scorm_attempts {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $scormattemptstats = $this->get_scorm_attempts($fromdate, $todate);
        $scormwiseattempts = array();

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($scormattemptstats);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_scorm_attempts($fromdate, $todate) {
        global $DB;
        $sql = "SELECT
			SQL_CALC_FOUND_ROWS u.id+st.scormid+st.timemodified as id,
			CONCAT(u.firstname,' ',u.lastname) as user,
			st.userid,
			st.scormid,
			sc.name,
			c.fullname,
			count(DISTINCT(st.attempt)) as attempts,
			cmc.completionstate,
			cmc.timemodified as completiondate,
			sv.starttime,
			sm.duration,
			sm.timemodified as lastaccess,
			round(sg.score, 0) as score
					FROM mdl_scorm_scoes_track AS st
					LEFT JOIN mdl_user AS u ON st.userid=u.id
					LEFT JOIN mdl_scorm AS sc ON sc.id=st.scormid
					LEFT JOIN mdl_course c ON c.id = sc.course
					LEFT JOIN mdl_modules m ON m.name = 'scorm'
					LEFT JOIN mdl_course_modules cm ON cm.module = m.id AND cm.instance = sc.id
					LEFT JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id

					LEFT JOIN (SELECT userid, timemodified, scormid, SEC_TO_TIME( SUM( TIME_TO_SEC( value ) ) ) AS duration FROM mdl_scorm_scoes_track where element = 'cmi.core.total_time' GROUP BY userid, scormid) AS sm ON sm.scormid =st.scormid and sm.userid=st.userid
					LEFT JOIN (SELECT userid, MIN(value) as starttime, scormid FROM mdl_scorm_scoes_track where element = 'x.start.time' GROUP BY userid, scormid) AS sv ON sv.scormid =st.scormid and sv.userid=st.userid
					LEFT JOIN (SELECT gi.iteminstance, (gg.finalgrade/gg.rawgrademax)*100 AS score, gg.userid FROM mdl_grade_items gi, mdl_grade_grades gg WHERE gi.itemmodule='scorm' and gg.itemid=gi.id  GROUP BY gi.iteminstance, gg.userid) AS sg ON sg.iteminstance =st.scormid and sg.userid=st.userid
					WHERE sc.id > 0 and sv.starttime BETWEEN $fromdate 
                                        AND $todate GROUP BY st.userid, st.scormid ORDER BY sv.starttime DESC";

        $scormattemptstats = $DB->get_records_sql($sql);
        return $scormattemptstats;
    }

    function get_axis_names() {
//        $axis = new stdClass();
//        $axis->xaxis = 'Days';
//        $axis->yaxis = 'Sessions';
        return '';
    }

    function get_data($scormattemptstats) {
        $chartdetails = array();
        foreach ($scormattemptstats as $key => $value) {
            if ($value->score == "") {
                $value->score = 0;
            }
            $chartdetails[] = '[' . '"' . $value->user . '"' . ',' . '"' . $value->name . '"' . ',' . '"' . $value->fullname . '"' . ',' . $value->attempts . ',' . '"' . $value->duration . '"' . ',' . '"' . date("Y-m-d H:i:s", $value->starttime) . '"' . ',' . '"' . date("Y-m-d H:i:s", $value->completiondate) . '"' . ',' . $value->score . ']';
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Learner'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'SCORM Activity Name'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Course name'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'Attempts'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'string'";
        $header5->name = "'Total Time Spent'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'string'";
        $header6->name = "'Started On'";
        $headers[] = $header6;
        $header7 = new stdclass();
        $header7->type = "'string'";
        $header7->name = "'Completed On'";
        $headers[] = $header7;
        $header8 = new stdclass();
        $header8->type = "'number'";
        $header8->name = "'Average Grade'";
        $headers[] = $header8;
        return $headers;
    }
}


class course_stats {
        
    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $coursestats = $this->get_course_stats($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($coursestats);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_course_stats($fromdate, $todate) {
        global $DB, $CFG;
        $sql = "SELECT
				SQL_CALC_FOUND_ROWS c.id,
				c.fullname as course,
				c.timecreated as created,
				c.enablecompletion,
				e.learners,
				cc.completed,
				gc.grade,
				cm.modules
					FROM {$CFG->prefix}course as c
						LEFT JOIN (SELECT course, count( id ) AS modules FROM {$CFG->prefix}course_modules WHERE visible = 1 GROUP BY course) cm ON cm.course = c.id
						LEFT JOIN (".getCourseGradeSql().") as gc ON gc.courseid = c.id
						LEFT JOIN (".getCourseLearnersSql().") e ON e.courseid = c.id
						LEFT JOIN (".getCourseCompletedSql().") as cc ON cc.course = c.id
							WHERE c.visible=1 AND c.category > 0 AND c.timecreated BETWEEN $fromdate AND $todate
                                                        ORDER BY c.timecreated DESC";

        $coursestats = $DB->get_records_sql($sql);
        return $coursestats;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($coursestats) {
        $chartdetails = array();
        foreach ($coursestats as $key => $value) {
            if ($value->grade == "") {
                $value->grade = 0;
            }
            if ($value->completed == "") {
                $value->completed = 0;
            }
            if ($value->learners == "") {
                $value->learners = 0;
            }
            if ($value->modules == "") {
                $value->modules = 0;
            }
            $chartdetails[] = '[' . '"' . $value->course . '"' . ',' . $value->learners . ',' . $value->modules . ',' . $value->completed . ',' . $value->grade . ',' . '"' . date("Y-m-d", $value->created) . '"' . ']';
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Course name'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'# of Enrolled Learners'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'# of Modules'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'# of Learners Completed Course'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'Score'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'string'";
        $header6->name = "'Date Created'";
        $headers[] = $header6;
        return $headers;
    }
}


class progress_by_learner {   
         
    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $learnerprogress = $this->get_learner_progress($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($learnerprogress);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_learner_progress($fromdate, $todate) {
        global $DB, $CFG;
        $sql = "SELECT
				SQL_CALC_FOUND_ROWS DISTINCT u.id,
				CONCAT(u.firstname, ' ', u.lastname) as learner,
				u.email,
				u.timecreated as registered,
				ue.courses,
				round(gc.grade, 2) as grade,
				cm.completed_courses,
				cmc.completed_activities
				FROM mdl_role_assignments ra, mdl_user as u
					LEFT JOIN (SELECT ue.userid, count(DISTINCT e.courseid) as courses FROM mdl_user_enrolments ue, mdl_enrol e WHERE e.id = ue.enrolid AND ue.status = 0 and e.status = 0 
                               GROUP BY ue.userid) as ue ON ue.userid = u.id
					LEFT JOIN (SELECT userid, count(id) as completed_courses FROM mdl_course_completions cc WHERE timecompleted > 0 GROUP BY userid) as cm ON cm.userid = u.id
					LEFT JOIN (SELECT g.userid, AVG( (g.finalgrade/g.rawgrademax)*100) AS grade FROM mdl_grade_items gi, mdl_grade_grades g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) as gc ON gc.userid = u.id
					LEFT JOIN (SELECT cmc.userid, count(cmc.id) as completed_activities FROM mdl_course_modules cm, mdl_course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 GROUP BY cmc.userid) as cmc ON cmc.userid = u.id
					WHERE ra.roleid IN (5) AND u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0 AND u.timecreated BETWEEN $fromdate AND $todate
                                        ORDER BY u.timecreated DESC";

        $coursestats = $DB->get_records_sql($sql);
        return $coursestats;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($learnerprogress) {
        $chartdetails = array();
        foreach ($learnerprogress as $key => $value) {
            if ($value->grade == "") {
                $value->grade = 0;
            }
            if ($value->courses == "") {
                $value->courses = 0;
            }
            if ($value->completed_courses == "") {
                $value->completed_courses = 0;
            }
            if ($value->completed_activities == "") {
                $value->completed_activities = 0;
            }
            $chartdetails[] = '[' . '"' . $value->learner . '"' . ',' . '"' . $value->email . '"' . ',' . '"' . date("Y-m-d", $value->registered) . '"' . ',' . $value->courses . ',' . $value->completed_activities . ',' . $value->completed_courses . ',' . $value->grade . ']';
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Learner'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Email'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Registered'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'# of courses'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'Completed Activities'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'number'";
        $header6->name = "'Completed Courses'";
        $headers[] = $header6;
        $header7 = new stdclass();
        $header7->type = "'number'";
        $header7->name = "'Score'";
        $headers[] = $header7;
        return $headers;
    }
}