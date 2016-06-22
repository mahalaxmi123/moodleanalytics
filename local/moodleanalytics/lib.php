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

/* Reports for the selectbox
 * Returns reports array
 */

function get_coursereports() {
    $report_array = array(1 => 'Course progress', 2 => 'Activity attempt', 3 => 'Activity Status Report', 4 => 'New Courses', 5 => 'Courses with zero activity', 6 => 'Unique Sessions');
    return $report_array;
}

/* Returns report class to call
 *  @param reportid int
 */

function get_report_class($reportid) {
    $classes_array = array(1 => new course_progress(),
        2 => new activity_attempt(),
        3 => new activity_status(),
        4 => new new_courses(),
        5 => new course_with_zero_activity(),
        6 => new unique_sessions()
    );
    return $classes_array[$reportid];
}

/* Returns array of days filter to call
 *  Report for the days filter
 */

function get_days_filter() {
    $days_filter = array(3 => 'Last 3 days',
        7 => 'Last 7 days',
        10 => 'Last 10 days'
    );
    return $days_filter;
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

function get_chart_types() {
    $chartoptions = array(1 => 'LineChart', 2 => 'ComboChart', 3 => 'BubbleChart', 4 => 'Table');
    return $chartoptions;
}

class course_progress {

    function get_chart_types() {
        $chartoptions = array(1 => 'LineChart', 2 => 'ComboChart', 3 => 'BubbleChart');
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
        $reportobj->gradeheaders = $this->get_headers($users, $users_update);
        $reportobj->act_avg_position = $this->get_act_avg_position($reportobj->gradeheaders);
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
        $gradeheaders = array();
        if (!empty($users)) {
            if (!empty($users_update)) {
                foreach ($users_update as $key => $userval) {
                    if (!empty($userval)) {
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
        }

        return $gradeheaders;
    }

    function get_act_avg_position($gradeheaders) {
        $position = array_search("'" . 'activities average' . "'", $gradeheaders);
        return $position;
    }

}

class activity_attempt {

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
            $reportobj->gradeheaders = $this->get_headers($users, $notattemptedusers);
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
        $gradeheaders = array();
        foreach ($users as $userkey => $uservalue) {
            if (!empty($uservalue) && !in_array($uservalue, $notattemptedusers)) {
                $gradeheaders[] = "'" . $uservalue . " - Grade '";
            }
        }
        return $gradeheaders;
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
                $reportobj->gradeheaders = $this->get_headers();
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

    function get_headers() {
        $gradeheaders = array();
        //        $gradeheaders[] = "'Test'";
        $gradeheaders[] = "'Grade'";
        $gradeheaders[] = "'Resource completion'";
        $gradeheaders[] = "'Feedback'";
        return $gradeheaders;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Grades';
        $axis->yaxis = 'Resource Completion';
        return $axis;
    }

}

class new_courses {

    function process_reportdata($reportobj, $from_date, $to_date) {
        if ($from_date && $to_date) {

            $fromdate = $from_date->format('U');
            $todate = $to_date->format('U') + DAYSECS;
            $courses = $this->get_new_courses($fromdate, $todate);
            $coursedetails = array();
            $daywisecourse = array();
            $count = 0;
            $interval = new DateInterval('P1D'); // 1 Day
            $dateRange = new DatePeriod($from_date, $interval, $to_date);

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
            $reportobj->gradeheaders = $this->get_headers();
        }
    }

    function get_new_courses($fromdate, $todate) {
        global $DB;
//        $lastselecteddate = strtotime(date('Y-m-d h:m:s', strtotime("-$days days")));
        $sql = "select id, shortname, FROM_UNIXTIME(timecreated, '%Y-%m-%d') as timecreated from {course} where timecreated BETWEEN $fromdate AND $todate ORDER BY timecreated ASC";
        $courses = $DB->get_records_sql($sql);
        return $courses;
    }

    function get_axis_names($reportname) {
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

    function get_headers() {
        $gradeheaders = array();
//        $gradeheaders[] = "'Date'";
        $gradeheaders[] = "'Courses'";
        return $gradeheaders;
    }

}

class course_with_zero_activity {

    function process_reportdata($reportobj) {
        global $DB;
        $sql = "SELECT fullname, FROM_UNIXTIME(timecreated, '%Y-%m-%d') as timecreated FROM  {course}
                WHERE id NOT IN (SELECT DISTINCT course FROM {course_modules})
                AND id != 1";
        $noactcourses = $DB->get_records_sql($sql);
        $reportobj->data = $this->get_data($noactcourses);
        $reportobj->gradeheaders = $this->get_headers();
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

    function get_headers() {
        $gradeheaders = array();
//        $gradeheaders[] = "'Date'";
        $gradeheaders[] = "'Creation Time'";
        return $gradeheaders;
    }

}

class unique_sessions {

    function process_reportdata($reportobj, $from_date, $to_date) {

        $fromdate = $from_date->format('U');
        $todate = $to_date->format('U') + DAYSECS;
        $sessions = $this->get_unique_sessions($fromdate, $todate);
        $sessiondetails = array();
        $daywisesession = array();
        $count = 0;
        $interval = new DateInterval('P1D'); // 1 Day
        $dateRange = new DatePeriod($from_date, $interval, $to_date);

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
        $reportobj->gradeheaders = $this->get_headers();
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

    function get_headers() {
        $gradeheaders = array();
//        $gradeheaders[] = "'Date'";
        $gradeheaders[] = "'Sessions'";
        return $gradeheaders;
    }

}
