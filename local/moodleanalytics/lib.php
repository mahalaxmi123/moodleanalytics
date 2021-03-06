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
//    $report_array = array(14 => 'New Courses', 15 => 'Courses with zero activity', 16 => 'Unique Sessions', 17 => 'Scorm Stats', 18 => 'File Stats', 19 => 'Uploads', 22 => 'Scorm Attempts', 23 => 'Course Stats', 24 => 'Progress By Learner', 25 => 'Teaching Performance', 26 => 'Activity Stats', 27 => 'Course Progress', 28 => 'Learner Progress By Course', 29 => 'Quiz Grades', 31 => 'Quiz Attempts', 34 => 'Learner progress', 35 => 'Student performance', 36 => 'Activity progress', 37 => 'Overdue users');
    $report_array = array(
        'course_with_zero_activity' => 'Courses with zero activity',
        'learner_progress' => 'Learning progress',
        'activity_stats' => 'Activity Stats',
        'teachingactivity' => 'Teaching Activity',
        'student_performance' => 'Student performance',
        'forum_stats' => 'Forum stats',
        'scorm_stats' => 'Scorm Stats',
        'quizstats' => 'Quiz Stats',
        'file_stats' => 'File Stats',
        'course_stats' => 'Course Stats',
        'progress_by_learner' => 'Progress By Learner',
        'teaching_performance' => 'Teaching Performance',
        'quiz_attempts' => 'Quiz Attempts',
        'activity_forum' => 'Activity in forums',
        'course_progress' => 'Course Progress',
        'quiz_grades' => 'Quiz Grades',
        'forum_discussions' => 'Forum Discussions',
        'scorm_attempts' => 'Scorm Attempts',
        'uploads' => 'Uploads',
        'activity_progress' => 'Activity progress',
        'overdue_users' => 'Overdue users',
        'enrolment_method' => 'Enrollment Methods',
        'courseenrollments' => 'Course Enrollments',
        'learner_progress_by_course' => 'Learner Progress By Course',
    );
    return $report_array;
}

/* Returns report class to call
 *  @param reportid int
 */

function get_report_class($reportname = '') {
    $classes_array = array(
        'registrations' => new registrations(),
        'enrollmentspercourse' => new enrollmentspercourse(),
        'coursesize' => new coursesize(),
        'courseenrollments' => new courseenrollments(),
        'teachingactivity' => new teachingactivity(),
        'activeip' => new activeip(),
        'languageused' => new languageused(),
        'newregistrants' => new newregistrants(),
        'newcourses' => new newcourses(),
        'enrolments' => new enrolments(),
        'new_courses' => new new_courses(),
        'course_with_zero_activity' => new course_with_zero_activity(),
        'unique_sessions' => new unique_sessions(),
        'scorm_stats' => new scorm_stats(),
        'file_stats' => new file_stats(),
        'uploads' => new uploads(),
        'registrants' => new registrants(),
        'participations' => new participations(),
        'scorm_attempts' => new scorm_attempts(),
        'course_stats' => new course_stats(),
        'progress_by_learner' => new progress_by_learner(),
        'teaching_performance' => new teaching_performance(),
        'activity_stats' => new activity_stats(),
        'course_progress' => new course_progress(),
        'learner_progress_by_course' => new learner_progress_by_course(),
        'quiz_grades' => new quiz_grades(),
        'quizstats' => new quizstats(),
        'quiz_attempts' => new quiz_attempts(),
        'dashboardchart' => new dashboardchart(),
        'enrolments_analytics' => new enrolments_analytics(),
        'learner_progress' => new learner_progress(),
        'student_performance' => new student_performance(),
        'activity_progress' => new activity_progress(),
        'overdue_users' => new overdue_users(),
        'forum_stats' => new forum_stats(),
        'activity_forum' => new activity_forum(),
        'forum_discussions' => new forum_discussions(),
        'enrolment_method' => new enrolment_method()
    );
    return $classes_array[$reportname];
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

function getCourseUserGradeSql($grage = 'grade', $round = 0) {

    global $CFG;

    return "SELECT gi.courseid, g.userid, round(((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
				FROM
					{$CFG->prefix}grade_items gi,
					{$CFG->prefix}grade_grades g
				WHERE
					gi.itemtype = 'course' AND
					g.itemid = gi.id
				GROUP BY gi.courseid, g.userid";
}

function getQuizAttemptsSql($type = "attempts") {
    global $CFG;

    if ($type == "grade") {
        $sql = "avg((qa.sumgrades/q.sumgrades)*100) as $type";
    } elseif ($type == "duration") {
        $sql = "sum(qa.timefinish - qa.timestart) $type";
    } else {
        $sql = "count(distinct(qa.id)) $type";
    }

    return "SELECT qa.quiz, $sql
						FROM
							{$CFG->prefix}quiz q,
							{$CFG->prefix}quiz_attempts qa,
							(" . getUsersEnrolsSql() . ") ue
						WHERE
							qa.quiz = q.id AND
							q.course = ue.courseid AND
							qa.userid = ue.userid AND
							qa.timefinish > 0 AND
							qa.timestart > 0
						GROUP BY qa.quiz";
}

//class course_progress {
//
//    function get_chart_types() {
//        $chartoptions = 'ComboChart';
//        return $chartoptions;
//    }
//
//    function process_reportdata($reportobj, $courseid, $users, $charttype) {
//        global $DB, $USER;
//        $json_grades = array();
//        $users_update = array();
//        $feedback = array();
//        $context = context_course::instance($courseid);
//        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));
//
////first make sure we have proper final grades - this must be done before constructing of the grade tree
//        grade_regrade_final_grades($courseid);
////Initialise the grader report object that produces the table
////the class grade_report_grader_ajax was removed as part of MDL-21562
//        $report = new grade_report_grader($courseid, $gpr, $context);
////        $numusers = $report->get_numusers(true, true);
//
//        $activities = array();
//        if (!empty($report->gtree->top_element['children'])) {
//            foreach ($report->gtree->top_element['children'] as $children) {
//                $activities[$children['eid']] = $children['object']->itemname;
//            }
//        }
//// final grades MUST be loaded after the processing
//        $report->load_users();
//        $report->load_final_grades();
//
//        if (!empty($report) && !empty($report->grades)) {
//            foreach ($report->grades as $grades => $grade) {
//                foreach ($users as $key => $username) {
//                    $user = $DB->get_record('user', array('username' => $username));
//                    foreach ($grade as $gradeval) {
//                        if ($gradeval->grade_item->itemtype != 'course') {
//                            if (!empty($user->id) && ($gradeval->userid == $user->id)) {
//                                $users_update[$user->username] = $user->username;
//                                if (!empty($json_grades[$gradeval->grade_item->itemname])) {
//                                    $json_grades[$gradeval->grade_item->itemname] .= (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
//                                } else {
//                                    $json_grades[$gradeval->grade_item->itemname] = "'" . $gradeval->grade_item->itemname . "'" . ',' . (!empty($gradeval->finalgrade) ? $gradeval->finalgrade : 0.0 ) . ',';
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//
//            $USER->gradeediting[$courseid] = '';
//            $averagegrade = $report->get_right_avg_row();
//            $actavggrade = array();
//            foreach ($averagegrade as $avggradvalue) {
//                foreach ($avggradvalue->cells as $avggrade) {
//                    $actitemname = '';
//                    $attrclass = $avggrade->attributes['class'];
//                    if (isset($attrclass) && isset($activities[$attrclass])) {
//                        $actitemname = $activities[$attrclass];
//                    }
//                    if (!empty($avggrade->text)) {
//                        $actavggrade[$actitemname] = floatval($avggrade->text);
//                    }
//                }
//            }
//            foreach ($json_grades as $key => $value) {
//                $json_grades[$key] .= $actavggrade[$key] . ',';
//            }
//        }
//
//        $reportobj->data = $this->get_data($json_grades);
//        $reportobj->headers = $this->get_headers($users, $users_update);
//        $reportobj->act_avg_position = $this->get_act_avg_position($reportobj->headers);
//    }
//
//    function get_axis_names($reportname) {
//        $axis = new stdClass();
//        $axis->xaxis = 'Activities';
//        $axis->yaxis = 'Grades';
//        return $axis;
//    }
//
//    function get_data($json_grades) {
//        $json_grades_array = array();
//        foreach ($json_grades as $key => $grade_info) {
//            $grade_info = TRIM($grade_info, ',');
//            $json_grades_array[] = "[" . $grade_info . "]";
//        }
//        return $json_grades_array;
//    }
//
//    function get_headers($users, $users_update) {
//        $headers = array();
//        if (!empty($users)) {
//            if (!empty($users_update)) {
//                foreach ($users_update as $key => $userval) {
//                    if (!empty($userval)) {
//                        $header1 = new stdclass();
//                        $header1->type = "'string'";
//                        $header1->name = "'" . $userval . " - Grade '";
//                        $headers[] = $header1;
//                    }
//                }
//            } else {
//                $errors[] = 'User(s)';
//            }
//        }
//        $position = '';
//        if (empty($errors)) {
//            $header2 = new stdclass();
//            $header2->type = "'string'";
//            $header2->name = "'" . 'activities average' . "'";
//            $headers[] = $header2;
//        }
//
//        return $headers;
//    }
//
//    function get_act_avg_position($gradeheaders) {
//        $position = array_search("'" . 'activities average' . "'", $headers);
//        return $position;
//    }
//
//}
//
//class activity_attempt {
//
//    function get_chart_types() {
//        $chartoptions = 'ComboChart';
//        return $chartoptions;
//    }
//
//    function process_reportdata($reportobj, $courseid, $users, $charttype) {
//        $quiz_array = $this->get_course_quiz($courseid);
//        $reportobj->quiz_array = $quiz_array;
//        $quizdetails = array();
//        $reportobj->info = '';
//        $notattemptedusers = array();
//        if ($users && !empty($reportobj->quizid)) {
//            $json_quiz_attempt = $this->get_user_quiz_attempts($reportobj->quizid, $users);
//            if (array_key_exists('usernotattempted', $json_quiz_attempt)) {
//                $notattemptedposition = array_search('usernotattempted', array_keys($json_quiz_attempt));
//                $notattemptedmessage = array_slice($json_quiz_attempt, $notattemptedposition, 1);
//                foreach ($notattemptedmessage['usernotattempted'] as $key => $message) {
//                    $reportobj->info .= html_writer::div($message, 'alert alert-info');
//                    $notattemptedusers[] = $key;
//                }
//            }
//            unset($json_quiz_attempt['usernotattempted']);
//
//            $reportobj->data = $this->get_data($json_quiz_attempt);
//            $reportobj->headers = $this->get_headers($users, $notattemptedusers);
//        }
//    }
//
//    function get_course_quiz($courseid) {
//        $quiz_array = array();
//        if (!empty($courseid)) {
//            $activities = get_array_of_activities($courseid);
//            foreach ($activities as $actinfo) {
//                if ($actinfo->mod == 'quiz') {
//                    $quiz_array[$actinfo->id] = $actinfo->name;
//                }
//            }
//        }
//        return $quiz_array;
//    }
//
//    function get_user_quiz_attempts($quizid, $users) {
//        global $DB;
//        $attempts = array();
//        $quizdetails = array();
//        $maxnumofattempts = '';
//        if (!empty($users)) {
//            foreach ($users as $username) {
//                $count = 1;
//                $user = $DB->get_record('user', array('username' => $username));
//                $quizattempts = quiz_get_user_attempts($quizid, $user->id, 'finished');
//                if ($quizattempts) {
//                    foreach ($quizattempts as $quizattempt) {
//                        $attempts[$username]['Attempt ' . $count] = $quizattempt->sumgrades;
//                        $count++;
//                    }
//                } else {
//                    $quizdetails['usernotattempted'][$username] = "$username has not taken this quiz yet.";
//                }
//            }
//            foreach ($attempts as $attempt) {
//                $currentnumofattempts[] = count($attempt);
//                $maxnumofattempts = max($currentnumofattempts);
//            }
//            if (!empty($attempts)) {
//                $attempts = $this->format_quiz_attemptwise_grades($maxnumofattempts, $attempts);
//            }
//        }
//        return array_merge($quizdetails, $attempts);
//    }
//
//    function format_quiz_attemptwise_grades($max, $thisattempts) {
//        foreach ($thisattempts as $thisattempt) {
//            $count = count($thisattempt);
//            if (!empty($max) && $max > $count) {
//                $less = $max - $count;
//                for ($i = 1; $i <= $less; $i++) {
//                    $count += 1;
//                    $thisattempt['Attempt ' . $count] = 0;
//                }
//            }
//            $modifiedattempts[] = $thisattempt;
//        }
//        foreach ($modifiedattempts as $modattempts) {
//            $numofattempts = count($modattempts);
//            for ($num = 1; $num <= $numofattempts; $num++) {
//                if (!empty($newattempts['Attempt ' . $num])) {
//                    $newattempts['Attempt ' . $num] .= $modattempts['Attempt ' . $num] . ',';
//                } else {
//                    $newattempts['Attempt ' . $num] = ',' . $modattempts['Attempt ' . $num] . ',';
//                }
//            }
//        }
//        return $newattempts;
//    }
//
//    function get_axis_names($reportname) {
//        $axis = new stdClass();
//        $axis->xaxis = 'Attempts';
//        $axis->yaxis = 'Grades';
//        return $axis;
//    }
//
//    function get_headers($users, $notattemptedusers) {
//        $headers = array();
//        foreach ($users as $userkey => $uservalue) {
//            if (!empty($uservalue) && !in_array($uservalue, $notattemptedusers)) {
//                $header1 = new stdclass();
//                $header1->type = "'string'";
//                $header1->name = "'" . $uservalue . " - Grade '";
//                $headers[] = $header1;
//            }
//        }
//        return $headers;
//    }
//
//    function get_data($json_quiz_attempt) {
//        $quizdetails = array();
//        if (!empty($json_quiz_attempt)) {
//            foreach ($json_quiz_attempt as $quiz => $quizgrades) {
//                $quizdetails[] = "[" . "'" . $quiz . "'" . "," . trim($quizgrades, ',') . "]";
//            }
//        }
//        return $quizdetails;
//    }
//
//}
//
//class activity_status {
//
//    function get_chart_types() {
//        $chartoptions = 'BubbleChart';
//        return $chartoptions;
//    }
//
//    function process_reportdata($reportobj, $courseid, $users, $charttype) {
//        global $DB;
//        $context = context_course::instance($courseid);
//        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $courseid, 'page' => 1));
//        $report = new grade_report_grader($courseid, $gpr, $context);
//        $report->load_users();
//        $report->load_final_grades();
//        $resourceactivitycompletion = $this->get_activity_completion($courseid);
//        $averageusergrades = $this->get_user_avggrades($report->grades);
//        if (!empty($users)) {
//            foreach ($users as $key => $username) {
//                $feedback[$username] = $this->random_value_for_feedback();
//            }
//            if (!empty($resourceactivitycompletion) && $averageusergrades) {
//                foreach ($resourceactivitycompletion as $resuseridkey => $rescompletiongrade) {
//                    $resusers = $DB->get_record('user', array('id' => $resuseridkey));
//                    $resactivitycompletion[$resusers->username] = $rescompletiongrade;
//                }
//                foreach ($averageusergrades as $avguserid => $avgusergrades) {
//                    $usersforavggrade = $DB->get_record('user', array('id' => $avguserid));
//                    $newaveragegrade[$usersforavggrade->username] = $avgusergrades;
//                }
//
//                $reportobj->data = $this->get_data($users, $newaveragegrade, $resactivitycompletion, $feedback);
//                $reportobj->headers = $this->get_headers();
//            }
//        } else {
//            $errors[] = 'User(s)';
//        }
//    }
//
//    /* Returns the resource completion percentage of all users in a course
//     * @param : courseid int
//     * return resource completion array 
//     */
//
//    function get_activity_completion($courseid) {
//        global $DB;
//        $resource_completion_array = array();
//        $course = $DB->get_record('course', array('id' => $courseid));
//        if (!$course) {
//            return;
//        }
//        $completion = new completion_info($course);
//        $activities = $completion->get_activities();
//        $total_activities = COUNT($activities);
//        $progress = $completion->get_progress_all();
//        foreach ($progress as $userid => $userprogess_data) {
//            $completedact_count = COUNT($userprogess_data->progress);
//            if ($completedact_count) {
//                $resource_completion_array[$userid] = ($completedact_count / $total_activities) * 100;
//            } else {
//                $resource_completion_array[$userid] = 0;
//            }
//        }
//        return $resource_completion_array;
//    }
//
//    /* Returns users average grade in a course
//     * @param : grade  array
//     * Return : usersaveragegrades array
//     */
//
//    function get_user_avggrades($grades) {
//        $useravggrades = array();
//        $activities = array();
//        foreach ($grades as $grade => $gradeinfo) {
//            foreach ($gradeinfo as $gradeval) {
//                $activities[$gradeval->grade_item->itemname] = $gradeval->grade_item->itemname;
//                if (!empty($useravggrades[$gradeval->userid])) {
//                    $useravggrades[$gradeval->userid] += $gradeval->finalgrade;
//                } else {
//                    $useravggrades[$gradeval->userid] = $gradeval->finalgrade;
//                }
//            }
//        }
//        $total_activities = COUNT($activities);
//        if ($total_activities) {
//            foreach ($useravggrades as $userid => $gradetotal) {
//                $useravggrades[$userid] = ($gradetotal / $total_activities);
//            }
//        } else {
//            return;
//        }
//        return $useravggrades;
//    }
//
//    function random_value_for_feedback() {
//        $feedback = rand(1, 10);
//        return $feedback;
//    }
//
//    function get_data($users, $newaveragegrade, $resactivitycompletion, $feedback) {
//        foreach ($users as $thisuserkey => $thisusername) {
//            $chartdetails[] = "[" . "'" . $thisusername . "'" . "," . $newaveragegrade[$thisusername] . "," . $resactivitycompletion[$thisusername] . "," . $feedback[$thisusername] . "]";
//        }
//        return $chartdetails;
//    }
//
////    function get_headers() {
////        $gradeheaders = array();
////        //        $gradeheaders[] = "'Test'";
////        $gradeheaders[] = "'Grade'";
////        $gradeheaders[] = "'Resource completion'";
////        $gradeheaders[] = "'Feedback'";
////        return $gradeheaders;
////    }
//
//    function get_headers() {
//        $headers = array();
//        $header1 = new stdclass();
//        $header1->type = "'string'";
//        $header1->name = "'Test'";
//        $headers[] = $header1;
//        $header2 = new stdclass();
//        $header2->type = "'number'";
//        $header2->name = "'Grade'";
//        $headers[] = $header2;
//        $header3 = new stdclass();
//        $header3->type = "'number'";
//        $header3->name = "'Resource Completion'";
//        $headers[] = $header3;
//        $header4 = new stdclass();
//        $header4->type = "'number'";
//        $header4->name = "'Feedback'";
//        $headers[] = $header4;
//        return $headers;
//    }
//
//    function get_axis_names($reportname) {
//        $axis = new stdClass();
//        $axis->xaxis = 'Grades';
//        $axis->yaxis = 'Resource Completion';
//        return $axis;
//    }
//
//}

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
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $json_coursesizes = array();
        $coursesizes = array();
        $coursesizesql = "SELECT c.fullname as coursename , fs.coursesize as size, DATE_FORMAT( FROM_UNIXTIME( c.timecreated ) ,  '%m/%d/%Y' ) as timecreated "
                . "FROM {course} c "
                . "LEFT JOIN (SELECT c.instanceid AS course, sum( f.filesize ) as coursesize "
                . "FROM {files} f, {context} c "
                . "WHERE c.id = f.contextid GROUP BY c.instanceid) fs ON fs.course = c.id WHERE c.category > 0 ORDER BY c.timecreated ";
        $coursesizes = $DB->get_records_sql($coursesizesql);

        foreach ($coursesizes as $csize) {
            $csize->size = ($csize->size / (1024 * 1024));
            $json_coursesizes[] = '[' . '"' . $csize->coursename . '"' . ',' . $csize->size . ',' . '"' . $csize->timecreated . '"' . ']';
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
        $header1->name = "'Course'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Size in MB'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Created'";
        $headers[] = $header3;
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
        $json_data = array();
        $datearray = array();
        $_SESSION['current_month'] = $param[0];
        $newusersql = "SELECT * FROM mdl_user WHERE timecreated>0";
        $lists = $DB->get_records_sql($newusersql);
        $lastdate = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime($_SESSION['current_month'])), date('Y', strtotime($_SESSION['current_month'])));
        for ($i = 1; $i <= $lastdate; $i++) {
            $datearray[$i] = 0;
        }
        $presentdate = date('Y-m-d', strtotime($_SESSION['current_month']));
        $presentmonthyear = date('m-Y', strtotime($_SESSION['current_month']));
        $numberofuser = 0;
        foreach ($lists as $list) {
            $numberofuser++;
            if ($presentmonthyear != date('m-Y', $list->timecreated)) {
                continue;
            }
            for ($j = 1; $j <= count($datearray); $j++) {
                if (date('d', $list->timecreated) == $j) {
                    $datearray[$j] ++;
                    continue;
                }
            }
        }
        $monthyear = date("Y,m,", strtotime($presentdate));
        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
        $reportobj->totalusers = $numberofuser;
        $reportobj->messagestring = "Number of Students Enrol On ";
        for ($j = 1; $j <= count($datearray); $j++) {
            $json_data[] = '"' . $monthyear . $j . '"' . ",'" . $reportobj->messagestring . $j . ":'," . $datearray[$j];
        }
        $reportobj->data = $json_data;
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
        for ($i = 1; $i <= $lastdate; $i++) {
            $datearray[$i] = 0;
        }
        $presentdate = date('Y-m-d', strtotime($_SESSION['current_month']));
        $presentmonthyear = date('m-Y', strtotime($_SESSION['current_month']));
        $course = 0;
        foreach ($lists as $list) {
            $course++;
            if ($presentmonthyear != date('m-Y', $list->timecreated)) {
                continue;
            }
            for ($j = 1; $j <= count($datearray); $j++) {
                if (date('d', $list->timecreated) == $j) {
                    $datearray[$j] ++;
                    continue;
                }
            }
        }
        $monthyear = date("Y,m,", strtotime($presentdate));
        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
        $reportobj->totalcourses = $course;
        $reportobj->messagestring = "Number of Courses Form On ";
        for ($j = 1; $j <= count($datearray); $j++) {
            $json_data[] = '"' . $monthyear . $j . '"' . ",'" . $reportobj->messagestring . $j . ":'," . $datearray[$j];
        }
        $reportobj->data = $json_data;
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
            $data[] = '[' . '"' . $course->fullname . '"' . ',' . $complete . ',' . $notcomplete . ']';
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

class quizstats {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER;
        $json_quizstats = array();
        $json_quizstats = $this->get_quizstats_data($params);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_quizstats;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_quizstats_data($params) {
        global $USER, $CFG, $DB;
        $this->teacher_roles = '3,4';
        $data = $DB->get_records_sql("SELECT
				SQL_CALC_FOUND_ROWS c.id,
				c.fullname,
				count(q.id) as quizzes,
				sum(qs.duration) as duration,
				sum(qa.attempts) as attempts,
				avg(qg.grade) as grade,
				(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
									  FROM {role_assignments} AS ra
									  JOIN {user} AS u ON ra.userid = u.id
									  JOIN {context} AS ctx ON ctx.id = ra.contextid
									  WHERE ra.roleid IN ($this->teacher_roles)  AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher FROM
						{quiz} q
						LEFT JOIN {course} c ON c.id = q.course
						LEFT JOIN (" . $this->getQuizAttemptsSql("duration") . ") qs ON qs.quiz = q.id
						LEFT JOIN (" . $this->getQuizAttemptsSql() . ") qa ON qa.quiz = q.id
						LEFT JOIN (" . $this->getQuizAttemptsSql("grade") . ") qg ON qg.quiz = q.id
						WHERE  c.visible = 1 AND c.category > 0
						GROUP BY c.id");
        $json_data = array();
        foreach ($data as $key => $value) {
            $coursename = "'$value->fullname'";
            $teacher = !empty($value->teacher) ? "'$value->teacher'" : "'-'";
            $quizzes = !empty($value->quizzes) ? "$value->quizzes" : 0;
            $attempts = !empty($value->attempts) ? "$value->attempts" : 0;
            $averagegrades = !empty($value->grade) ? "$value->grade" : 0;
            $totaltimespent = !empty($value->duration) ? "$value->duration" : 0;
            $json_data[] = "[" . $coursename . ',' . $teacher . ',' . $quizzes . ',' . $attempts . ',' . $totaltimespent . ',' . $averagegrades . "]";
        }
        return $json_data;
    }

    function getQuizAttemptsSql($type = "attempts") {
        global $CFG;

        if ($type == "grade") {
            $sql = "avg((qa.sumgrades/q.sumgrades)*100) as $type";
        } elseif ($type == "duration") {
            $sql = "sum(qa.timefinish - qa.timestart) $type";
        } else {
            $sql = "count(distinct(qa.id)) $type";
        }

        return "SELECT qa.quiz, $sql FROM {quiz} q,{quiz_attempts} qa,
							(" . $this->getUsersEnrolsSql() . ") ue
						WHERE	qa.quiz = q.id AND
							q.course = ue.courseid AND
							qa.userid = ue.userid AND
							qa.timefinish > 0 AND
							qa.timestart > 0
						GROUP BY qa.quiz";
    }

    function getUsersEnrolsSql($roles = array(), $enrols = array()) {
        global $CFG;
        $this->learner_roles = '3';
        if (empty($roles)) {
            $roles = explode(",", $this->learner_roles);
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
						{user_enrolments} ue,
						{enrol} e,
						{role_assignments} ra,
						{context} ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Course Name'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Teacher'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'Quizzes'";
        $gradeheaders[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'Attempts'";
        $gradeheaders[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'Total time spent'";
        $gradeheaders[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'number'";
        $header6->name = "'Average grades'";
        $gradeheaders[] = $header6;
        return $gradeheaders;
    }

}

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
						LEFT JOIN (" . getCourseGradeSql() . ") as gc ON gc.courseid = c.id
						LEFT JOIN (" . getCourseLearnersSql() . ") e ON e.courseid = c.id
						LEFT JOIN (" . getCourseCompletedSql() . ") as cc ON cc.course = c.id
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

        $learnerprogress = $DB->get_records_sql($sql);
        return $learnerprogress;
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

class teaching_performance {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $teacherperformance = $this->get_teaching_performance($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($teacherperformance);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_teaching_performance($fromdate, $todate) {
        global $DB, $CFG;
        $sql = "SELECT
					SQL_CALC_FOUND_ROWS u.id,
					CONCAT(u.firstname, ' ', u.lastname) teacher,
					count(ue.courseid) as courses,
					sum(l.learners) as learners,
					sum(ls.learners) as activelearners,
					sum(c.completed) as completedlearners,
					AVG( g.grade ) AS grade
				FROM
					(" . getUsersEnrolsSql(explode(",", 3)) . ") as ue
					LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
					LEFT JOIN (" . getCourseLearnersSql() . ") l ON l.courseid = ue.courseid
					LEFT JOIN (" . getCourseLearnersSql('learners', strtotime('-30 days'), time()) . ") ls ON ls.courseid = ue.courseid
					LEFT JOIN (" . getCourseCompletedSql() . ") c ON c.course = ue.courseid
					LEFT JOIN (" . getCourseGradeSql() . ") g ON g.courseid = ue.courseid
                                        WHERE ue.timecreated BETWEEN $fromdate AND $todate GROUP BY u.id
                                        ORDER BY ue.timecreated DESC";

        $teacherperformance = $DB->get_records_sql($sql);
        return $teacherperformance;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($teacherperformance) {
        $chartdetails = array();
        foreach ($teacherperformance as $key => $value) {
            if ($value->courses == "") {
                $value->courses = 0;
            }
            if ($value->learners == "") {
                $value->learners = 0;
            }
            if ($value->activelearners == "") {
                $value->activelearners = 0;
            }
            if ($value->completedlearners == "") {
                $value->completedlearners = 0;
            }
            if ($value->grade == "") {
                $value->grade = 0;
            }
            $chartdetails[] = '[' . '"' . $value->teacher . '"' . ',' . $value->courses . ',' . $value->learners . ',' . $value->activelearners . ',' . $value->completedlearners . ',' . $value->grade . ']';
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Teacher'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'# of courses'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'# of students enrolled'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'# of active students within the last 30 days'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'# of students completed courses'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'number'";
        $header6->name = "'Average grade for all courses'";
        $headers[] = $header6;
        return $headers;
    }

}

class activity_stats {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $activitystats = $this->get_activity_stats($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($activitystats);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_activity_stats($fromdate, $todate) {
        global $DB, $CFG;
        $sql = "SELECT
		SQL_CALC_FOUND_ROWS cm.id,
		m.name AS module,
		m.name AS moduletype,
		cm.added,
		cm.completion,
		cmc.completed,
		gc.grade
		FROM {$CFG->prefix}course_modules cm
		LEFT JOIN {$CFG->prefix}modules m ON m.id = cm.module
		LEFT JOIN (SELECT coursemoduleid, COUNT(DISTINCT(id)) AS completed FROM `{$CFG->prefix}course_modules_completion` GROUP BY coursemoduleid) cmc ON cmc.coursemoduleid = cm.id
                LEFT JOIN (SELECT gi.iteminstance, gi.itemmodule, AVG( (g.finalgrade/g.rawgrademax)*100 ) AS grade FROM {$CFG->prefix}grade_items gi, {$CFG->prefix}grade_grades g WHERE gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.iteminstance, gi.itemmodule) as gc ON gc.itemmodule = m.name AND gc.iteminstance = cm.instance
		WHERE cm.visible = 1 AND cm.added BETWEEN $fromdate AND $todate GROUP BY cm.id";

        $activitystats = $DB->get_records_sql($sql);
        return $activitystats;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($activitystats) {
        $chartdetails = array();
        foreach ($activitystats as $key => $value) {
            if ($value->completed == "") {
                $value->completed = 0;
            }
            if ($value->grade == "") {
                $value->grade = 0;
            }
            $chartdetails[] = '[' . '"' . $value->module . '"' . ',' . '"' . $value->moduletype . '"' . ',' . $value->completed . ',' . $value->grade . ',' . '"' . date("Y-m-d", $value->added) . '"' . ']';
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Activity'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Type'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'# Of Learners Completed This Activity'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'Average Score'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'string'";
        $header5->name = "'Created'";
        $headers[] = $header5;
        return $headers;
    }

}

class course_progress {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $courseprogress = $this->get_course_progress($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($courseprogress);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_course_progress($fromdate, $todate) {
        global $DB, $CFG;
        $sql = "SELECT
					SQL_CALC_FOUND_ROWS ue.id, ue.userid,
					((cmca.cmcnuma / cma.cmnuma)*100 ) as assignments,
					((cmc.cmcnums / cmx.cmnumx)*100 ) as participations,
					cma.cmnuma as assigns,
					gc.grade,
					c.fullname as course,
					CONCAT( u.firstname, ' ', u.lastname ) AS learner
						FROM (" . getUsersEnrolsSql() . ") as ue
							LEFT JOIN {$CFG->prefix}user u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course c ON c.id = ue.courseid
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnums FROM {$CFG->prefix}course_modules cv WHERE cv.visible = 1 GROUP BY cv.course) as cm ON cm.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnumx FROM {$CFG->prefix}course_modules cv WHERE cv.visible = 1 and cv.completion = 1 GROUP BY cv.course) as cmx ON cmx.course = c.id
							LEFT JOIN (SELECT cv.course, count(cv.id) as cmnuma FROM {$CFG->prefix}course_modules cv WHERE cv.visible = 1 and cv.module = 1 GROUP BY cv.course) as cma ON cma.course = c.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
							LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnuma FROM {$CFG->prefix}course_modules cm, {$CFG->prefix}course_modules_completion cmc WHERE cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible  =  1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) as cmca ON cmca.course = c.id AND cmca.userid = u.id
							LEFT JOIN (" . getCourseUserGradeSql() . ") as gc ON gc.courseid = c.id AND gc.userid = u.id
								WHERE u.deleted = 0 AND u.suspended = 0 AND ue.timecreated BETWEEN $fromdate AND $todate GROUP BY ue.userid, ue.courseid";

        $courseprogress = $DB->get_records_sql($sql);
        return $courseprogress;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($courseprogress) {
        $chartdetails = array();
        foreach ($courseprogress as $key => $value) {
            if ($value->assignments == "") {
                $value->assignments = 0;
            }
            if ($value->participations == "") {
                $value->participations = 0;
            }
            if ($value->grade == "") {
                $value->grade = 0;
            }
            $chartdetails[] = '[' . '"' . $value->learner . '"' . ',' . '"' . $value->course . '"' . ',' . $value->participations . ',' . $value->assignments . ',' . $value->grade . ']';
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
        $header2->name = "'Course'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'Participations'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'Assignments'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'Current Score'";
        $headers[] = $header5;
        return $headers;
    }

}

class learner_progress_by_course {

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
			SQL_CALC_FOUND_ROWS ue.id,
			cri.gradepass,
			ue.userid,
			ue.timecreated as started,
			c.id as cid,
			c.fullname,
			git.average,
			AVG((g.finalgrade/g.rawgrademax)*100) AS `grade`,
			cmc.completed,
			CONCAT(u.firstname, ' ', u.lastname) AS student,
			c.enablecompletion,
			cc.timecompleted as complete
						FROM (" . getUsersEnrolsSql() . ") as ue
							LEFT JOIN {$CFG->prefix}user as u ON u.id = ue.userid
							LEFT JOIN {$CFG->prefix}course as c ON c.id = ue.courseid
							LEFT JOIN {$CFG->prefix}course_completions as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN {$CFG->prefix}course_completion_criteria as cri ON cri.course = ue.courseid AND cri.criteriatype = 6
							LEFT JOIN {$CFG->prefix}grade_items gi ON gi.courseid = c.id AND gi.itemtype = 'course'
							LEFT JOIN {$CFG->prefix}grade_grades g ON g.itemid = gi.id AND g.userid =u.id
							LEFT JOIN (" . getCourseGradeSql('average') . ") git ON git.courseid=c.id
							LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed FROM {$CFG->prefix}course_modules_completion cmc, {$CFG->prefix}course_modules cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
								WHERE u.deleted = 0 AND u.suspended = 0 AND ue.timecreated BETWEEN $fromdate AND $todate GROUP BY ue.userid, ue.courseid";

        $learnerprogress = $DB->get_records_sql($sql);
        return $learnerprogress;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($courseprogress) {
        $chartdetails = array();
        foreach ($courseprogress as $key => $value) {
            if ($value->completed == "") {
                $value->completed = 0;
            }
            if ($value->grade == "") {
                $value->grade = 0;
            }
            if ($value->enablecompletion == 0 && $value->complete == "") {
                $value->complete = "Completion not enabled";
            } elseif ($value->enablecompletion == 1 && $value->complete == "") {
                $value->complete = 'Incomplete';
            } else {
                $value->complete = 'Completed';
            }
            $chartdetails[] = '[' . '"' . $value->student . '"' . ',' . '"' . $value->fullname . '"' . ',' . '"' . date("Y-m-d", $value->started) . '"' . ',' . $value->completed . ',' . round($value->grade, 2) . ',' . '"' . $value->complete . '"' . ']';
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
        $header2->name = "'Course Name'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Started'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'Completed Activities'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'Score'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'string'";
        $header6->name = "'Status'";
        $headers[] = $header6;
        return $headers;
    }

}

class quiz_grades {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $quizgrades = $this->get_quiz_grades($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($quizgrades);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_quiz_grades($fromdate, $todate) {
        global $DB, $CFG;
        $sql = "SELECT
				SQL_CALC_FOUND_ROWS qa.id,
				q.name,
				q.course,
				c.fullname,
				qa.timestart,
				qa.timefinish,
				qa.state,
				(qa.timefinish - qa.timestart) as duration,
				(qa.sumgrades/q.sumgrades*100) as grade,
				CONCAT(u.firstname, ' ', u.lastname) learner
				FROM {$CFG->prefix}quiz_attempts qa
					LEFT JOIN {$CFG->prefix}quiz q ON q.id = qa.quiz
					LEFT JOIN {$CFG->prefix}user u ON u.id = qa.userid
					LEFT JOIN {$CFG->prefix}course c ON c.id = q.course
					LEFT JOIN {$CFG->prefix}context ctx ON ctx.instanceid = c.id
					LEFT JOIN {$CFG->prefix}role_assignments ra ON ra.contextid = ctx.id AND ra.userid = u.id
				WHERE ra.roleid  IN (5) and qa.timestart BETWEEN $fromdate AND $todate";

        $quizgrades = $DB->get_records_sql($sql);
        return $quizgrades;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($quizgrades) {
        $chartdetails = array();
        foreach ($quizgrades as $key => $value) {
            if ($value->grade == "") {
                $value->grade = 0;
            }
            if ($value->state == "inprogress") {
                $value->state = "In Progress";
                $value->duration = "00:00:00";
                $value->timefinish = "";
            } else {
                $value->duration = gmdate("H:i:s", $value->timefinish - $value->timestart);
                $value->timefinish = date("Y-m-d", $value->timefinish);
            }
            $chartdetails[] = '[' . '"' . $value->name . '"' . ',' . '"' . $value->learner . '"' . ',' . '"' . $value->fullname . '"' . ',' . '"' . $value->state . '"' . ',' . '"' . date("Y-m-d", $value->timestart) . '"' . ',' . '"' . $value->timefinish . '"' . ',' . '"' . $value->duration . '"' . ',' . round($value->grade) . ']';
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Quiz name'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Learner'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Course name'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'string'";
        $header4->name = "'Progress'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'string'";
        $header5->name = "'Started on'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'string'";
        $header6->name = "'Completed'";
        $headers[] = $header6;
        $header7 = new stdclass();
        $header7->type = "'string'";
        $header7->name = "'Time taken'";
        $headers[] = $header7;
        $header8 = new stdclass();
        $header8->type = "'number'";
        $header8->name = "'Average Grade'";
        $headers[] = $header8;
        return $headers;
    }

}

class quiz_attempts {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {

        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $quizattempts = $this->get_quiz_attempts($fromdate, $todate);

        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($params['fromdate'], $interval, $params['todate']);

        $reportobj->data = $this->get_data($quizattempts);
        $reportobj->headers = $this->get_headers();
        $reportobj->charttype = $this->get_chart_types();
    }

    function get_quiz_attempts($fromdate, $todate) {
        global $DB, $CFG;

        $sql = "SELECT
				SQL_CALC_FOUND_ROWS q.id,
				q.name,
				q.course,
				c.fullname,
				ql.questions,
				q.timemodified,
				q.timeopen,
				q.timeclose,
				qa.attempts,
				qs.duration,
				qg.grade
			FROM {$CFG->prefix}quiz q
				LEFT JOIN {$CFG->prefix}course c ON c.id = q.course
				LEFT JOIN (SELECT quizid, count(*) questions FROM {$CFG->prefix}quiz_slots GROUP BY quizid) ql ON ql.quizid = q.id
				LEFT JOIN (" . getQuizAttemptsSql() . ") qa ON qa.quiz = q.id
				LEFT JOIN (" . getQuizAttemptsSql("duration") . ") qs ON qs.quiz = q.id
				LEFT JOIN (" . getQuizAttemptsSql("grade") . ") qg ON qg.quiz = q.id
			WHERE q.course > 0 AND q.timemodified BETWEEN $fromdate AND $todate GROUP BY q.id ORDER BY q.timemodified DESC";

        $quizattempts = $DB->get_records_sql($sql);
        return $quizattempts;
    }

    function get_axis_names() {
        return '';
    }

    function get_data($quizattempts) {
        $chartdetails = array();
        foreach ($quizattempts as $key => $value) {
            if ($value->questions == "") {
                $value->questions = 0;
            }
            if ($value->attempts == "") {
                $value->attempts = 0;
            }
            if ($value->grade == "") {
                $value->grade = 0;
            }
//            if ($value->state == "inprogress") {
//                $value->state = "In Progress";
//                $value->duration = "00:00:00";
//                $value->timefinish = "";
//            } else {
            $value->duration = gmdate("H:i:s", $value->duration);
            $value->timemodified = date("Y-m-d", $value->timemodified);
//            }
            $chartdetails[] = '[' . '"' . $value->name . '"' . ',' . '"' . $value->course . '"' . ',' . $value->questions . ',' . $value->attempts . ',' . '"' . $value->duration . '"' . ',' . round($value->grade, 2) . ',' . '"' . $value->timemodified . '"' . ']';
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $headers = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Quiz name'";
        $headers[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Course name'";
        $headers[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'Total questions'";
        $headers[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'Attempts'";
        $headers[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'string'";
        $header5->name = "'Total time spent'";
        $headers[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'number'";
        $header6->name = "'Average grade'";
        $headers[] = $header6;
        $header7 = new stdclass();
        $header7->type = "'string'";
        $header7->name = "'Created'";
        $headers[] = $header7;
        return $headers;
    }

}

class dashboardchart {

    function get_chart_types() {
        $chartoptions = 'LineChart';
        return $chartoptions;
    }

    function process_reportdata_month($reportobj, $param = array()) {
        global $DB, $USER, $CFG;
        //echo $param['endtime'];
        $strendtime = $param['endtime'];
        $endtime = date('d-m-Y', $param['endtime']);
        // echo $endtime;
        $starttime = strtotime('-3month', $param['endtime']);

        $starttimemonth = date('m-Y', $starttime);
        $firstdateofstartmonth = "01-" . $starttimemonth;
        $strstarttime = ($firstdateofstartmonth);

        $lastdate = cal_days_in_month(CAL_GREGORIAN, date('m', strtotime('-3month', $param['endtime'])), date('Y', strtotime('-3month', $param['endtime'])));
        //echo $lastdate;
        //echo $fromtime;
        $enroluser = "SELECT id, DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%d-%M' ) as enrolment_date,DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%M' ) as enrolment_month,DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%m-%Y' ) as enrolment_year FROM mdl_user WHERE  timecreated BETWEEN '" . $strstarttime . "' AND  '" . $strendtime . "'";
        $enrol_user = $DB->get_records_sql($enroluser);
        $completeuser = "SELECT id, DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%d-%M' ) as completed_date,DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%M' ) as completed_month,DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%m-%Y' ) as completed_year FROM mdl_course_completions WHERE  timecompleted BETWEEN '" . $strstarttime . "' AND  '" . $strendtime . "'";
        // print_object($enrol_user);
        $complete_user = $DB->get_records_sql($completeuser);
        $montharray = array();

        $montharray[] = date('M', $param['endtime']);
        $montharray[] = date('M', strtotime('-1month', $param['endtime']));
        $montharray[] = date('M', strtotime('-2month', $param['endtime']));
        $montharray[] = date('M', strtotime('-3month', $param['endtime']));
        $yeararray[] = date('m-Y', $param['endtime']);
        $yeararray[] = date('m-Y', strtotime('-1month', $param['endtime']));
        $yeararray[] = date('m-Y', strtotime('-2month', $param['endtime']));
        $yeararray[] = date('m-Y', strtotime('-3month', $param['endtime']));

        $montharray = array_unique($montharray);
        $yeararray = array_unique($yeararray);

        $midlastofmonth = array();
        $arraytocombine = array();
        for ($i = 0; $i < count($montharray); $i++) {
            $lastdate = cal_days_in_month(CAL_GREGORIAN, substr($yeararray[$i], 0, 2), substr($yeararray[$i], 3));
            $midlastofmonth[] = "$lastdate" . "-" . substr($yeararray[$i], 0, 2);
            $midlastofmonth[] = "15-" . substr($yeararray[$i], 0, 2);
            $arraytocombine[] = "$lastdate" . "-" . $montharray[$i];
            $arraytocombine[] = "15-" . $montharray[$i];
        }

        $valuefinal = array();
        $valuefinalcomplete = array();
        // var_dump($midlastofmonth);
        foreach ($midlastofmonth as $key => $value) {
            // echo $value;

            $count = 0;
            $countcomplete = 0;
            foreach ($enrol_user as $enrol) {
                if (substr($value, 3) != substr($enrol->enrolment_year, 0, 2))
                    continue;
                if (substr($value, 0, 2) >= substr($enrol->enrolment_date, 0, 2)) {
                    $count++;
                }
            }
            foreach ($complete_user as $complete) {
                if (substr($value, 3) != substr($complete->complete_year, 0, 2))
                    continue;
                if (substr($value, 0, 2) >= substr($complete->complete_date, 0, 2)) {
                    $countcomplete++;
                }
            }
            $valuefinal[] = $count;
            $valuefinalcomplete[] = $countcomplete;
        }
        // var_dump($valuefinal);
        for ($i = 0; $i < count($valuefinal); $i++) {
            if ($i % 2 == 0) {
                $valuefinal[$i] = $valuefinal[$i] - $valuefinal[$i + 1];
            }
        }
        $noofenrolments = array_combine($arraytocombine, $valuefinal);
        //var_dump($noofenrolments);
        $json_noofenrolments = array();
        $i = 0;
        foreach ($noofenrolments as $key => $value) {
            $json_noofenrolments[] = "['" . $key . "'," . $value . "," . $valuefinalcomplete[$i] . ']';
            $i++;
        }
        krsort($json_noofenrolments);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
        $reportobj->data = $json_noofenrolments;
    }

    function process_reportdata_daily($reportobj, $param = array()) {
        global $DB, $USER, $CFG;
        $fulldayname = array('Sun' => 'Sunday', 'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday');
        //echo $param['endtime'];
        $strendtime = $param['endtime'];
        $endtime = date('d-m-Y', $param['endtime']);
        //   echo $endtime;
        $starttime = strtotime('-6day', $param['endtime']);

        $starttimemonth = date('d-m-Y', $starttime);

        $enroluser = "SELECT id, DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%d-%M' ) as enrolment_date,DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%M' ) as enrolment_month,DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%m-%Y' ) as enrolment_year FROM mdl_user WHERE  timecreated BETWEEN '" . $starttime . "' AND  '" . $strendtime . "'";
        $enrol_user = $DB->get_records_sql($enroluser);
        $completeuser = "SELECT id, DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%d-%M' ) as completed_date,DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%M' ) as completed_month,DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%m-%Y' ) as completed_year FROM mdl_course_completions WHERE  timecompleted BETWEEN '" . $starttime . "' AND  '" . $strendtime . "'";
        $complete_user = $DB->get_records_sql($completeuser);
        // print_object($enrol_user);
        $daymontharray = array();
        $datemontharray = array();
        $datemontharray[] = date('d', $param['endtime']);

        for ($i = 1; $i <= 6; $i++) {
            $datemontharray[] = date('d', strtotime('-' . $i . 'day', $param['endtime']));
        }
        //var_dump($datemontharray);
        $daymontharray[] = $fulldayname[date('D', $param['endtime'])];
        for ($i = 1; $i <= 6; $i++) {
            $daymontharray[] = $fulldayname[date('D', strtotime('-' . $i . 'day', $param['endtime']))];
        }
        //var_dump($daymontharray);
        $valuefinal = array();
        $valuefinalcomplete = array();
        foreach ($datemontharray as $key => $value) {
            //echo $value;

            $count = 0;
            $countcomplete = 0;
            foreach ($enrol_user as $enrol) {
                if ($value == substr($enrol->enrolment_date, 0, 2)) {
                    $count++;
                }
            }
            foreach ($complete_user as $complete) {
                if ($value == substr($complete->complete_date, 0, 2)) {
                    $countcomplete++;
                }
            }
            $valuefinal[] = $count;
            $valuefinalcomplete[] = $countcomplete;
        }
        $noofenrolments = array_combine($daymontharray, $valuefinal);
        //var_dump($noofenrolments);
        $json_noofenrolments = array();
        $i = 0;
        foreach ($noofenrolments as $key => $value) {
            $json_noofenrolments[] = "['" . $key . "'," . $value . ',' . $valuefinalcomplete[$i] . ']';
            $i++;
        }
        krsort($json_noofenrolments);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
        $reportobj->data = $json_noofenrolments;
    }

    function process_reportdata_week($reportobj, $param = array()) {

        global $DB, $USER, $CFG;

        $strendtime = $param['endtime'];
        $endtime = date('d-m-Y', $param['endtime']);

        $starttime = strtotime('-4week', $param['endtime']);

        $starttimemonth = date('d-m-Y', $starttime);
        $enroluser = "SELECT id, DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%m%d' ) as enrolment_date,DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%M' ) as enrolment_month,DATE_FORMAT( FROM_UNIXTIME( timecreated ) ,  '%m-%Y' ) as enrolment_year FROM mdl_user WHERE  timecreated BETWEEN '" . $starttime . "' AND  '" . $strendtime . "'";
        $enrol_user = $DB->get_records_sql($enroluser);
        $completeuser = "SELECT id, DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%m%d' ) as completed_date,DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%M' ) as completed_month,DATE_FORMAT( FROM_UNIXTIME( timecompleted ) ,  '%m-%Y' ) as completed_year FROM mdl_course_completions WHERE  timecompleted BETWEEN '" . $starttime . "' AND  '" . $strendtime . "'";
        $complete_user = $DB->get_records_sql($completeuser);
        $daymontharray = array();
        $datemontharray = array();
        $datemontharray[] = date('md', $param['endtime']);

        for ($i = 1; $i <= 3; $i++) {
            $datemontharray[] = date('md', strtotime('-' . $i . 'week', $param['endtime']));
        }
        $daymontharray[] = date('d-M', $param['endtime']);
        for ($i = 1; $i <= 3; $i++) {
            $daymontharray[] = date('d-M', strtotime('-' . $i . 'week', $param['endtime']));
        }
        // var_dump($daymontharray);
        for ($i = 0; $i < count($datemontharray); $i++) {

            $count = 0;
            $countcomplete = 0;
            foreach ($enrol_user as $enrol) {

                if ($i == (count($datemontharray) - 1)) {
                    if ($datemontharray[$i] >= $enrol->enrolment_date) {
                        $count++;
                    }
                } else {
                    if (($datemontharray[$i] >= $enrol->enrolment_date) && ($datemontharray[$i + 1] <= $enrol->enrolment_date)) {
                        $count++;
                    }
                }
            }
            foreach ($complete_user as $complete) {

                if ($i == (count($datemontharray) - 1)) {
                    if ($datemontharray[$i] >= $complete->complete_date) {
                        $countcomplete++;
                    }
                } else {
                    if (($datemontharray[$i] >= $complete->complete_date) && ($datemontharray[$i + 1] <= $complete->complete_date)) {
                        $countcomplete++;
                    }
                }
            }
            $valuefinal[] = $count;
            $valuefinalcomplete[] = $countcomplete;
        }
        //var_dump($valuefinal);

        $noofenrolments = array_combine($daymontharray, $valuefinal);
        // var_dump($noofenrolments);
        $json_noofenrolments = array();
        $i = 0;
        foreach ($noofenrolments as $key => $value) {
            $json_noofenrolments[] = "['" . $key . "'," . $value . ',' . $valuefinalcomplete[$i] . ']';
            $i++;
        }
        krsort($json_noofenrolments);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
        $reportobj->data = $json_noofenrolments;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        $header1 = new stdclass();
        $header1->type = "'string'";
        $header1->name = "'Time'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'User Enrol'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'Course Completion'";
        $gradeheaders[] = $header3;
        return $gradeheaders;
    }

}

class enrolments_analytics {

    function get_chart_types() {
        $chartoptions = 'ColumnChart';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $param) {
        global $DB, $USER, $CFG;
        $registrantssql = "SELECT auth, COUNT(auth) AS Count FROM  `mdl_user` GROUP BY auth";
        $enrolments = array('corplms_program' => 0, 'guest' => 0, 'manual' => 0, 'self' => 0);
        $colour = array('lightblue', 'cyan', 'pink', 'blue');
        $registrants = $DB->get_records_sql($registrantssql);
        $json_registrants = array();
        $typeenrol = array();
        $count = array();
        $registrant = array();

        foreach ($registrants as $list) {
            foreach ($enrolments as $key => $enrolment) {
                if ($key == $list->auth) {
                    $enrolments[$key] = $list->count;
                }
            }
            // $typeenrol[] = $list->auth;
            //$language[] = $language_codes[$var];
            //$count[] = ($list->count);
        }
        //$registrant = array_combine($enrolments, $count);
        $axis = $this->get_axis_names('ColumnChart');
        $charttype = $this->get_chart_types();
        $i = 0;
        foreach ($registrant as $key => $value) {
            
        }
        foreach ($enrolments as $key => $value) {
            $json_registrants[] = "['" . ucfirst($key) . "'" . ',' . $value . ",'" . $colour[$i++] . "'],";
        }

        $reportobj->data = $json_registrants;
        $reportobj->axis = $axis;
        $reportobj->charttype = $charttype;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $gradeheaders = array();
        return $gradeheaders;
    }

}

class learner_progress {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER;
        $json_learnerprogress = array();
        $json_learnerprogress = $this->get_learnerprogress_data($params);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_learnerprogress;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_learnerprogress_data($params) {
        global $USER, $CFG, $DB;
        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;

        $sql_filter = "";
        $sql_join = "";
        $WHERE = "";
        if (!empty($params->courseid)) {
            $sql_filter = isset($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
        }
        $filterColumn = '';
        if (!empty($fromdate) && !empty($todate)) {
            $filterColumn = "ue.timecreated BETWEEN $fromdate AND $todate";
        }
        if ($sql_filter || $filterColumn) {
            $WHERE = "WHERE $filterColumn $sql_filter";
        }

        $sql = "SELECT	SQL_CALC_FOUND_ROWS ue.id,
			ue.timecreated as enrolled,
			gc.grade,
			c.enablecompletion,
			cc.timecompleted as complete,
			u.id as uid, u.email,
			CONCAT(u.firstname, ' ', u.lastname) as name,
			ue.enrols,
			c.id as cid,
			c.fullname as course,
			c.timemodified as start_date
			FROM (" . $this->getUsersEnrolsSql() . ") as ue
			LEFT JOIN {user} as u ON u.id = ue.userid
			LEFT JOIN {course} as c ON c.id = ue.courseid
			LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
			LEFT JOIN (" . $this->getCourseUserGradeSql() . ") as gc ON gc.courseid = c.id AND gc.userid = u.id
			$WHERE";
        $data = $DB->get_records_sql($sql);
        $json_data = array();
        foreach ($data as $key => $value) {
            $name = "'" . $value->name . "'";
            $email = "'" . $value->email . "'";
            $coursename = "'" . clean_param($value->course, PARAM_ALPHANUMEXT) . "'";
            $enrolmethod = "'" . $value->enrols . "'";
            $grade = !empty($value->grade) ? $value->grade : 0;
            $status = isset($value->completed) ? "'Completed'" : "'Incompleted'";
            $enrolledon = isset($value->start_date) ? userdate($value->start_date, get_string('strftimedate', 'langconfig')) : '-';
            $complete = isset($value->completed) ? userdate($value->completed, get_string('strftimedate', 'langconfig')) : '-';
            $json_data[] = "[" . $name . ',' . $email . ',' . $coursename . ',' . $enrolmethod . ',' . $grade . ',' . $status . ',' . "'" . $enrolledon . "'" . ',' . "'" . $complete . "'" . "]";
        }
        return $json_data;
    }

    function getUsersEnrolsSql($roles = array(), $enrols = array()) {
        global $CFG;
        $this->learner_roles = '5';
        if (empty($roles)) {
            $roles = explode(",", $this->learner_roles);
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
						{user_enrolments} ue,
						{enrol} e,
						{role_assignments} ra,
						{context} ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
    }

    function getCourseUserGradeSql($grage = 'grade', $round = 0) {

        global $CFG;

        return "SELECT gi.courseid, g.userid, round(((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
				FROM
					{grade_items} gi,
					{grade_grades} g
				WHERE
					gi.itemtype = 'course' AND
					g.itemid = gi.id
				GROUP BY gi.courseid, g.userid";
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $headers = array();
        $headers_names = array("'Learner'", "'Course name'", "'Email'", "'Enrolled methods'", "'Score'", "'Status'", "'Enrolled on'", "'Completed'");
        $headers_type = array("'Learner'" => "'string'", "'Course name'" => "'string'", "'Email'" => "'string'", "'Enrolled methods'" => "'string'", "'Score'" => "'number'", "'Status'" => "'string'", "'Enrolled on'" => "'string'", "'Completed'" => "'string'",);
        foreach ($headers_names as $key => $header) {
            $head = new stdClass();
            $head->type = $headers_type[$header];
            $head->name = $header;
            $headers[] = $head;
        }
        return $headers;
    }

}

class student_performance {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER;
        $json_studentperformance = array();
        $json_studentperformance = $this->get_studentperformance_data($params);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_studentperformance;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_studentperformance_data($params) {
        global $DB;
        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $where = "";
        if (!empty($fromdate) && !empty($todate)) {
            $where = "AND ue.timecreated BETWEEN $fromdate AND $todate";
        }
        $sql_filter = "";
//        if ($params->courseid) {
//            $sql_filter = isset($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
//        }

        $sql = "SELECT
			SQL_CALC_FOUND_ROWS ue.id,
			cri.gradepass,
			ue.userid,
			ue.timecreated as started,
			c.id as cid,
			c.fullname,
			git.average,
			AVG((g.finalgrade/g.rawgrademax)*100) AS grade,
			cmc.completed,
			CONCAT(u.firstname, ' ', u.lastname) AS student,
			c.enablecompletion,
			cc.timecompleted as complete
						FROM (" . $this->getUsersEnrolsSql() . ") as ue
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = ue.userid
							LEFT JOIN {course_completion_criteria} as cri ON cri.course = ue.courseid AND cri.criteriatype = 6
							LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
							LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
							LEFT JOIN (" . $this->getCourseGradeSql('average') . ") git ON git.courseid=c.id
							LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id  AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
								WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter $where GROUP BY ue.userid, ue.courseid";
        $data = $DB->get_records_sql($sql);
        $json_data = array();
        foreach ($data as $key => $value) {
            $name = "'" . $value->student . "'";
            $coursename = "'" . clean_param($value->fullname, PARAM_ALPHANUMEXT) . "'";
            $started = "'" . userdate($value->started, get_string('strftimedate', 'langconfig')) . "'";
            $grade = !empty($value->grade) ? $value->grade : 0;
            $completedact = !empty($value->completed) ? $value->completed : 0;
            $status = isset($value->enablecompletion) ? (isset($value->complete) ? "'Completed'" : "'Incompleted'") : "'Completion not enabled'";
            $enrolledon = isset($value->start_date) ? userdate($value->start_date, get_string('strftimedate', 'langconfig')) : '-';
            $complete = isset($value->completed) ? userdate($value->completed, get_string('strftimedate', 'langconfig')) : '-';
            $json_data[] = "[" . $name . ',' . $coursename . ',' . $started . ',' . $grade . ',' . $grade . ',' . $completedact . ',' . $grade . ',' . $status . "]";
        }

        return $json_data;
    }

    function getCourseGradeSql($grage = 'grade', $round = 0) {
        global $CFG;

        return "SELECT gi.courseid, round(avg((g.finalgrade/g.rawgrademax)*100), $round) AS $grage
					FROM
						{grade_items} gi,
						{grade_grades} g
					WHERE
						gi.itemtype = 'course' AND
						g.itemid = gi.id
					GROUP BY gi.courseid";
    }

    function getUsersEnrolsSql($roles = array(), $enrols = array()) {
        global $CFG;
        $this->learner_roles = '5';
        if (empty($roles)) {
            $roles = explode(",", $this->learner_roles);
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
						{user_enrolments} ue,
						{enrol} e,
						{role_assignments} ra,
						{context} ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $headers = array();
        $headers_names = array("'Learner'", "'Course name'", "'Started'", "'Progress'", "'Letter'", "'Completed Activities'", "'Score'", "'Status'");
        $headers_type = array("'Learner'" => "'string'", "'Course name'" => "'string'", "'Started'" => "'string'", "'Progress'" => "'number'", "'Letter'" => "'number'", "'Completed Activities'" => "'number'", "'Score'" => "'number'", "'Status'" => "'string'");
        foreach ($headers_names as $key => $header) {
            $head = new stdClass();
            $head->type = $headers_type[$header];
            $head->name = $header;
            $headers[] = $head;
        }
        return $headers;
    }

}

class activity_progress {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER;
        $json_activityprogress = array();
        $json_activityprogress = $this->get_activityprogress_data($params);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_activityprogress;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_activityprogress_data($params) {
        global $DB, $CFG;
        $sql_filter = '';
        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        if (!empty($fromdate) && !empty($todate)) {
            $sql_filter = " AND gg.timecreated BETWEEN $fromdate AND $todate";
        }
        $sql = "SELECT	SQL_CALC_FOUND_ROWS gg.id,
					gi.itemname,
					gg.userid,
					u.email,
					CONCAT(u.firstname, ' ', u.lastname) as learner,
					gg.timemodified as graduated,
					(gg.finalgrade/gg.rawgrademax)*100 as grade,
					cm.completion,
					cmc.completionstate
						FROM {grade_grades} gg
							LEFT JOIN {grade_items} gi ON gi.id=gg.itemid
							LEFT JOIN {user} as u ON u.id = gg.userid
							LEFT JOIN {modules} m ON m.name = gi.itemmodule
							LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
							LEFT JOIN {course} as c ON c.id=cm.course
							LEFT JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
								WHERE gi.itemtype = 'mod' $sql_filter";

        $data = $DB->get_records_sql($sql);
        $json_data = array();
        foreach ($data as $key => $value) {
            $activity = !empty($value->itemname) ? "'$value->itemname'" : "'-'";
            $learner = !empty($value->learner) ? "'$value->learner'" : "'-'";
            $email = !empty($value->email) ? "'$value->email'" : "'-'";
            $completedon = !empty($value->graduated) ? "'" . userdate($value->graduated, get_string('strftimedate', 'langconfig')) . "'" : "'-'";
            $score = !empty($value->grade) ? "$value->grade" : 0;
            $status = !empty($value->completion) ? (!empty($value->completionstate) ? "'Completed'" : "'Not completed'") : "'Completion not enabled'";
            $json_data[] = "[" . $activity . ',' . $learner . ',' . $email . ',' . $completedon . ',' . $score . ',' . $status . "]";
        }
        return $json_data;
    }

    function getUsersEnrolsSql($roles = array(), $enrols = array()) {
        global $CFG;
        $this->learner_roles = '5';
        if (empty($roles)) {
            $roles = explode(",", $this->learner_roles);
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
						{user_enrolments} ue,
						{enrol} e,
						{role_assignments} ra,
						{context} ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $headers = array();
        $headers_names = array("'Activity'", "'Learner'", "'Email'", "'Completed On'", "'Score'", "'Status'");
        $headers_type = array("'Activity'" => "'string'", "'Learner'" => "'string'", "'Email'" => "'string'", "'Completed On'" => "'string'", "'Score'" => "'number'", "'Status'" => "'string'");
        foreach ($headers_names as $key => $header) {
            $head = new stdClass();
            $head->type = $headers_type[$header];
            $head->name = $header;
            $headers[] = $head;
        }
        return $headers;
    }

}

class overdue_users {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER;
        $json_overdueusers = array();
        $json_overdueusers = $this->get_overdueusers_data($params);

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_overdueusers;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_overdueusers_data($params) {
        global $DB;
        $sql_filter = '';
        if (!empty($params->date_from) && !empty($params->to_from)) {
            $sql_filter = " AND gg.timecreated BETWEEN $params->date_from AND $params->to_from";
        }
        $sql = "SELECT
					SQL_CALC_FOUND_ROWS ue.id,
					ue.timecreated as enrolled,
					cc.timecompleted as complete,
					(g.finalgrade/g.rawgrademax)*100 AS grade,
					u.id as uid,
					u.email,
					CONCAT(u.firstname, ' ', u.lastname) learner,
					c.id as cid,
					c.enablecompletion,
					c.fullname as course
						FROM (" . $this->getUsersEnrolsSql() . ") as ue
							LEFT JOIN {user} as u ON u.id = ue.userid
							LEFT JOIN {course} as c ON c.id = ue.courseid
							LEFT JOIN {course_completions} as cc ON cc.course = ue.courseid AND cc.userid = u.id
							LEFT JOIN {grade_items} gi ON gi.courseid = ue.courseid AND gi.itemtype = 'course'
							LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id
								WHERE u.deleted = 0 AND u.suspended = 0 $sql_filter";
        $data = $DB->get_records_sql($sql);
        $json_data = array();
        foreach ($data as $key => $value) {
            $course = !empty($value->course) ? "'" . clean_param($value->course, PARAM_ALPHANUMEXT) . "'" : "'-'";
            $learner = !empty($value->learner) ? "'$value->learner'" : "'-'";
            $email = !empty($value->email) ? "'$value->email'" : "'-'";
            if (!empty($value->enablecompletion)) {
                $completedon = !empty($value->complete) ? "'" . userdate($value->complete, get_string('strftimedate', 'langconfig')) . "'" : "'-'";
            } else {
                $completedon = "'-'";
            }
            $score = !empty($value->grade) ? "$value->grade" : 0;
            $status = !empty($value->enablecompletion) ? (!empty($value->complete) ? "'Completed'" : "'Not completed'") : "'Completion not enabled'";
            $enrolledon = !empty($value->enrolled) ? "'" . userdate($value->enrolled, get_string('strftimedate', 'langconfig')) . "'" : "'-'";
            $json_data[] = "[" . $learner . ',' . $course . ',' . $email . ',' . $enrolledon . ',' . $completedon . ',' . $score . ',' . $status . "]";
        }
        return $json_data;
    }

    function getUsersEnrolsSql($roles = array(), $enrols = array()) {
        global $CFG;
        $this->learner_roles = '5';
        if (empty($roles)) {
            $roles = explode(",", $this->learner_roles);
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
						{user_enrolments} ue,
						{enrol} e,
						{role_assignments} ra,
						{context} ctx
					WHERE
						e.id = ue.enrolid AND
						ctx.instanceid = e.courseid AND
						ra.contextid = ctx.id AND
						ue.userid = ra.userid $sql_filter
					GROUP BY e.courseid, ue.userid";
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        return $axis;
    }

    function get_headers() {
        $headers = array();
        $headers_names = array("'Learner'", "'Course name'", "'Email'", "'Enrolled on'", "'Completed On'", "'Score'", "'Status'");
        $headers_type = array("'Learner'" => "'string'", "'Course name'" => "'string'", "'Email'" => "'string'", "'Enrolled on'" => "'string'", "'Completed On'" => "'string'", "'Score'" => "'number'", "'Status'" => "'string'");
        foreach ($headers_names as $key => $header) {
            $head = new stdClass();
            $head->type = $headers_type[$header];
            $head->name = $header;
            $headers[] = $head;
        }
        return $headers;
    }

}

class forum_stats {

    var $teacher_roles = 3;

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $json_forumstats = array();
        // $activeips = array();
        $forumstatssql = "SELECT SQL_CALC_FOUND_ROWS c.id,
					c.fullname,
					d.discussions,
					p.posts,
					COUNT(*) AS total,
					(SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
					  FROM mdl_role_assignments AS ra
					  JOIN mdl_user AS u ON ra.userid = u.id
					  JOIN mdl_context AS ctx ON ctx.id = ra.contextid
					  WHERE ra.roleid IN ($this->teacher_roles) AND ctx.instanceid = c.id AND ctx.contextlevel = 50 LIMIT 1) AS teacher
						FROM mdl_course c
							LEFT JOIN mdl_forum f ON f.course = c.id
							LEFT JOIN (SELECT course, count(*) discussions FROM mdl_forum_discussions group by course) d ON d.course = c.id
							LEFT JOIN (SELECT fd.course, count(*) posts FROM mdl_forum_discussions fd, mdl_forum_posts fp WHERE fp.discussion = fd.id group by fd.course) p ON p.course = c.id
							WHERE c.visible = 1 AND c.timecreated BETWEEN " . $fromdate . " AND " . $todate . " GROUP BY f.course ";
        $forumstats = $DB->get_records_sql($forumstatssql);
        $headers = $this->get_headers();
        foreach ($forumstats as $forumstat) {
            if ($forumstat->id == SITEID)
                continue;
            $json_forumstats[] = '[' . '"' . CLEAN_PARAM($forumstat->fullname, PARAM_ALPHANUMEXT) . '"' . ',' . '"' . $this->check_value_teacher($forumstat->teacher) . '"' . ',' . $this->check_value($forumstat->total) . ',' . $this->check_value($forumstat->posts) . ',' . $this->check_value($forumstat->discussions) . ']';
        }

        $reportobj->data = $json_forumstats;
        $charttype = $this->get_chart_types();
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
        $header1->name = "'Course Name'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Teacher'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'No. of Forums'";
        $gradeheaders[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'No. of Forum Posts'";
        $gradeheaders[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'No. of Forum Discussions'";
        $gradeheaders[] = $header5;
        return $gradeheaders;
    }

    function check_value($string) {
        return CLEAN_PARAM(is_null($string) ? '0' : $string, PARAM_ALPHANUMEXT);
    }

    function check_value_teacher($string) {
        return str_replace("'", "", is_null($string) ? 'NOT ASSIGNED' : $string);
    }

}

class activity_forum {

    var $teacher_roles = 3;

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $json_forumactivity = array();
        // $activeips = array();
        $forumactivitysql = "SELECT SQL_CALC_FOUND_ROWS f.id as forum, c.id, c.fullname,f.name, f.type
						,(SELECT COUNT(id) FROM mdl_forum_discussions AS fd WHERE f.id = fd.forum) AS Discussions
						,(SELECT COUNT(DISTINCT fd.userid) FROM mdl_forum_discussions AS fd WHERE fd.forum = f.id) AS UniqueUsersDiscussions
						,(SELECT COUNT(fp.id) FROM mdl_forum_discussions fd JOIN mdl_forum_posts AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS Posts
						,(SELECT COUNT(DISTINCT fp.userid) FROM mdl_forum_discussions fd JOIN mdl_forum_posts AS fp ON fd.id = fp.discussion WHERE f.id = fd.forum) AS UniqueUsersPosts
						,(SELECT COUNT( ra.userid ) AS Students
						FROM mdl_role_assignments AS ra
						JOIN mdl_context AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid  IN (5)
						AND ctx.instanceid = c.id
						) AS StudentsCount
						,(SELECT COUNT( ra.userid ) AS Teachers
						FROM mdl_role_assignments AS ra
						JOIN mdl_context AS ctx ON ra.contextid = ctx.id
						WHERE ra.roleid IN (3)
						AND ctx.instanceid = c.id
						) AS teacherscount
						,(SELECT COUNT( ra.userid ) AS Users
						FROM mdl_role_assignments AS ra
						JOIN mdl_context AS ctx ON ra.contextid = ctx.id
						WHERE  ctx.instanceid = c.id
						) AS UserCount
						, (SELECT (UniqueUsersDiscussions / StudentsCount )) AS StudentDissUsage
						, (SELECT (UniqueUsersPosts /StudentsCount)) AS StudentPostUsage
						FROM mdl_forum AS f
						JOIN mdl_course AS c ON f.course = c.id
						WHERE c.id > 0";
        $forumactivitys = $DB->get_records_sql($forumactivitysql);
        // print_object($forumactivitys);
        foreach ($forumactivitys as $forumactivity) {
            if ($forumactivity->id == SITEID)
                continue;
            $json_forumactivity[] = '[' . '"' . str_replace("'", "", $forumactivity->fullname) . '"' . ',' . '"' . str_replace("'", "", $forumactivity->name) . '"' . ',' . '"' . str_replace("'", "", $forumactivity->type) . '"'
                    . ',' . $this->check_value($forumactivity->discussions) . ',' . $this->check_value($forumactivity->uniqueusersdiscussions)
                    . ',' . $this->check_value($forumactivity->posts) . ',' . $this->check_value($forumactivity->uniqueusersposts)
                    . ',' . $this->check_value($forumactivity->studentscount) . ',' . $this->check_value($forumactivity->teacherscount)
                    . ',' . $this->check_value($forumactivity->usercount) . ']';
        }

        $charttype = $this->get_chart_types();

        $reportobj->data = $json_forumactivity;
        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();
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
        $header1->name = "'Course Name'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Forum Name'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Forum Type'";
        $gradeheaders[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'No. of Forum Discussions'";
        $gradeheaders[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'No. of Unique User Discussion'";
        $gradeheaders[] = $header5;
        $header6 = new stdclass();
        $header6->type = "'number'";
        $header6->name = "'No. of Posts'";
        $gradeheaders[] = $header6;
        $header7 = new stdclass();
        $header7->type = "'number'";
        $header7->name = "'No. of Unique User Posts'";
        $gradeheaders[] = $header7;
        $header8 = new stdclass();
        $header8->type = "'number'";
        $header8->name = "'No. of Participating Learners'";
        $gradeheaders[] = $header8;
        $header9 = new stdclass();
        $header9->type = "'number'";
        $header9->name = "'No. of Participating Teachers'";
        $gradeheaders[] = $header9;
        $header10 = new stdclass();
        $header10->type = "'number'";
        $header10->name = "'No. of Unique Participating User'";
        $gradeheaders[] = $header10;
        return $gradeheaders;
    }

    function check_value($string) {
        return CLEAN_PARAM(is_null($string) ? '0' : $string, PARAM_ALPHANUMEXT);
    }

}

class forum_discussions {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $json_forumdiscussion = array();
        // $activeips = array();
        $forumdiscussionsql = "SELECT  fp.id as id, c.id as courseid,c.fullname AS course, CONCAT( u.firstname,  ' ', u.lastname ) AS user, f.name, COUNT( fp.id ) AS posts, fpl.created
FROM mdl_forum_discussions fd
LEFT JOIN mdl_course c ON c.id = fd.course
LEFT JOIN mdl_forum f ON f.id = fd.forum
LEFT JOIN mdl_forum_posts fp ON fp.discussion = fd.id
LEFT JOIN mdl_user u ON u.id = fp.userid
LEFT JOIN mdl_forum_posts AS fpl ON fpl.id = ( 
SELECT MAX( fdx.id ) 
FROM mdl_forum_posts fpx, mdl_forum_discussions fdx
WHERE fpx.discussion = fdx.id
AND fdx.forum = fd.forum
AND fpx.userid = fpl.userid ) 
WHERE f.id >0 AND fd.timemodified BETWEEN " . $fromdate . " AND " . $todate . " GROUP BY u.id, f.id ";
        $forumdiscussions = $DB->get_records_sql($forumdiscussionsql);
        $noofdiscussionsql = "select  course,count( course) as count from mdl_forum_discussions group by course";
        $noofdiscussions = $DB->get_records_sql($noofdiscussionsql);
        $headers = $this->get_headers();
        foreach ($forumdiscussions as $forumdiscussion) {
            $totaldiscussion = 0;
            foreach ($noofdiscussions as $noofdiscussion) {
                if ($forumdiscussion->courseid == $noofdiscussion->course) {
                    $totaldiscussion = $noofdiscussion->count;
                }
            }
            $json_forumdiscussion[] = '[' . '"' . $forumdiscussion->name . '"' . ',' . '"' . $forumdiscussion->user . '"' . ',' . '"' . $forumdiscussion->course . '"' . ',' . $totaldiscussion . ',' . $forumdiscussion->posts . ']';
        }

        $reportobj->data = $json_forumdiscussion;
        $charttype = $this->get_chart_types();
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
        $header1->name = "'Forum Name'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'string'";
        $header2->name = "'Learner'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'string'";
        $header3->name = "'Course'";
        $gradeheaders[] = $header3;
        $header4 = new stdclass();
        $header4->type = "'number'";
        $header4->name = "'No. of discussions'";
        $gradeheaders[] = $header4;
        $header5 = new stdclass();
        $header5->type = "'number'";
        $header5->name = "'No. of Posts'";
        $gradeheaders[] = $header5;
        return $gradeheaders;
    }

}

class enrolment_method {

    function get_chart_types() {
        $chartoptions = 'Table';
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        global $DB, $USER, $CFG;
        $fromdate = $params['fromdate']->format('U');
        $todate = $params['todate']->format('U') + DAYSECS;
        $json_enrolusers = array();

        // $activeips = array();
        $activeipsql = "SELECT  en.enrol , count(en.enrol) as number_of_user, count(distinct en.courseid) as number_of_courses FROM  mdl_enrol as en , mdl_user_enrolments as uen WHERE en.id = uen.enrolid AND uen.timestart BETWEEN " . $fromdate . " AND  " . $todate . " group by en.enrol";
        $activeips = $DB->get_records_sql($activeipsql);

        foreach ($activeips as $activeip) {
            $json_enrolusers[] = '[' . '"' . $activeip->enrol . '"' . ',' . $activeip->number_of_courses . ',' . $activeip->number_of_user . ']';
        }
        $charttype = $this->get_chart_types();
        $headers = $this->get_headers();

        $reportobj->data = $json_enrolusers;
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
        $header1->name = "'Enrolment Method'";
        $gradeheaders[] = $header1;
        $header2 = new stdclass();
        $header2->type = "'number'";
        $header2->name = "'Number of Course Use This Enrolment'";
        $gradeheaders[] = $header2;
        $header3 = new stdclass();
        $header3->type = "'number'";
        $header3->name = "'Number of Enroled Learners'";
        $gradeheaders[] = $header3;
        return $gradeheaders;
    }

}
