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
    $sql1 = "SELECT c.id, c.fullname, count( ue.id ) AS nums FROM {course c, {enrol e, {user_enrolments ue WHERE e.courseid = c.id AND ue.enrolid =e.id $sql GROUP BY c.id";
    return $DB->get_records_sql($sql1);
}

/* Reports for the selectbox
 * Returns reports array
 */

function get_coursereports() {
    $report_array = array(1 => 'Course progress', 2 => 'Activity attempt', 3 => 'Activity Status Report');
    return $report_array;
}

/* Returns report class to call
 *  @param reportid int
 */

function get_report_class($reportid) {
    $classes_array = array(1 => new course_progress(),
        2 => new activity_attempt(),
        3 => new activity_status(),
        4 => new registrations(),
        5 => new enrollmentspercourse(),
        6 => new coursesize(),
        7 => new courseenrollments(),
        8 => new teachingactivity()
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

function get_chart_types() {
    $chartoptions = array(1 => 'LineChart', 2 => 'ComboChart', 3 => 'BubbleChart');
    return $chartoptions;
}

class course_progress {

    function get_chart_types() {
        $chartoptions = array(1 => 'LineChart', 2 => 'ComboChart', 3 => 'BubbleChart');
        return $chartoptions;
    }

    function process_reportdata($reportobj, $params = array()) {
        //$courseid, $users, $charttype
        global $DB, $USER;
        $json_grades = array();
        $users_update = array();
        $feedback = array();
        $context = context_course::instance($params->courseid);
        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $params->courseid, 'page' => 1));

//first make sure we have proper final grades - this must be done before constructing of the grade tree
        grade_regrade_final_grades($params->courseid);
//Initialise the grader report object that produces the table
//the class grade_report_grader_ajax was removed as part of MDL-21562
        $report = new grade_report_grader($params->courseid, $gpr, $context);
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
                foreach ($params->users as $key => $username) {
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

            $USER->gradeediting[$params->courseid] = '';
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
        $reportobj->gradeheaders = $this->get_headers($params->users, $users_update);
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

    function process_reportdata($reportobj, $params = array()) {
        $quiz_array = $this->get_course_quiz($params->courseid);
        $reportobj->quiz_array = $quiz_array;
        $quizdetails = array();
        $reportobj->info = '';
        $notattemptedusers = array();
        if ($params->users && !empty($reportobj->quizid)) {
            $json_quiz_attempt = $this->get_user_quiz_attempts($reportobj->quizid, $params->users);
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
            $reportobj->gradeheaders = $this->get_headers($params->users, $notattemptedusers);
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

    function process_reportdata($reportobj, $params = array()) {
        global $DB;
        $context = context_course::instance($params->courseid);
        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'grader', 'courseid' => $params->courseid, 'page' => 1));
        $report = new grade_report_grader($params->courseid, $gpr, $context);
        $report->load_users();
        $report->load_final_grades();
        $resourceactivitycompletion = $this->get_activity_completion($params->courseid);
        $averageusergrades = $this->get_user_avggrades($report->grades);
        if (!empty($params->users)) {
            foreach ($params->users as $key => $username) {
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

                $reportobj->data = $this->get_data($params->users, $newaveragegrade, $resactivitycompletion, $feedback);
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
        $json_enrols = array();
        $enrollments = $this->get_enrollments_per_course();
        foreach ($enrollments as $enrollment) {
            $json_enrols[] = "['" . $enrollment->fullname . "', $enrollment->nums]";
        }

        $headers = $this->get_headers();
        $charttype = $this->get_chart_types();

        $reportobj->data = $json_enrols;
        $reportobj->headers = $headers;
        $reportobj->charttype = $charttype;
    }

    function get_enrollments_per_course() {
        global $USER, $CFG, $DB;
//        $sql = $this->get_teacher_sql($params, "c.id", "courses");
        $sql1 = "SELECT c.id, c.fullname, count( ue.id ) AS nums FROM {course} c, {enrol} e, {user_enrolments} ue WHERE e.courseid = c.id AND ue.enrolid =e.id GROUP BY c.id";
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
                . "FROM {course c "
                . "LEFT JOIN (SELECT c.instanceid AS course, sum( f.filesize ) as coursesize "
                . "FROM {files f, {context c "
                . "WHERE c.id = f.contextid GROUP BY c.instanceid) fs ON fs.course = c.id WHERE c.category > 0 ORDER BY c.timecreated ";
        $coursesizes = $DB->get_records_sql($coursesizesql);

        foreach ($coursesizes as $csize) {
            $csize->size = ($csize->size / (1024 * 1024));
            $json_coursesizes[] = "['" . $csize->coursename . "', $csize->size]";
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
            $json_courseenrollments[] = "[" . userdate($cenrol->startdate) . ',' .
                    userdate($cenrol->enrolstart) . ',' . $cenrol->course . ',' .
                    $cenrol->learner . ',' . $cenrol->email . ',' . $cenrol->enrol . ',' .
                    $cenrol->enrolstart . ',' . $cenrol->enrolend . ',' . $cenrol->complete . "]";
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
        if ($CFG->version < 2014051200) {
            $table = "{log}";
            $teachingact = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id,CONCAT(u.firstname, ' ', u.lastname) as teacher,
					count(ue.courseid) as courses,ff.videos,l1.urls,l0.evideos,
					l2.assignments,l3.quizes,l4.forums,l5.attendances
					FROM (" . $this->getUsersEnrolsSql(explode(",", $this->teacher_roles)) . ") as ue
						LEFT JOIN {user} u ON u.id = ue.userid
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {files} f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
                                                LEFT JOIN (SELECT l.userid, count(l.id) urls FROM $table l WHERE l.module = 'url' AND l.action = 'add' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM $table l WHERE l.module = 'page' AND l.action = 'add' GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM $table l WHERE l.module = 'assignment' AND l.action = 'add' GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM $table l WHERE l.module = 'quiz' AND l.action = 'add' GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM $table l WHERE l.module = 'forum' AND l.action = 'add' GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM $table l WHERE l.module = 'attendance' AND l.action = 'add' GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE u.deleted = 0 AND u.suspended = 0 GROUP BY ue.userid");
        } else {
            $table = "{logstore_standard_log}";
            $teachingact = $DB->get_records_sql("SELECT
					SQL_CALC_FOUND_ROWS u.id,CONCAT(u.firstname, ' ', u.lastname) as teacher,
					count(ue.courseid) as courses,f1.files,ff.videos,l1.urls,l0.evideos,l2.assignments,
					l3.quizes,l4.forums,l5.attendances FROM
						(" . $this->getUsersEnrolsSql(explode(",", $this->teacher_roles)) . ") as ue
						LEFT JOIN {user u ON u.id = ue.userid
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) files FROM {files f WHERE filearea = 'content' GROUP BY f.userid) as f1 ON f1.userid = u.id
						LEFT JOIN (SELECT f.userid, count(distinct(f.filename)) videos FROM {files f WHERE f.mimetype LIKE '%video%' GROUP BY f.userid) as ff ON ff.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) urls FROM $table l,{course_modules cm, {modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'url' AND l.action = 'created' GROUP BY l.userid) as l1 ON l1.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) evideos FROM $table l,{course_modules cm, {modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'page' AND l.action = 'created'GROUP BY l.userid) as l0 ON l0.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) assignments FROM $table l,{course_modules cm, {modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'assignment' AND l.action = 'created'GROUP BY l.userid) as l2 ON l2.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) quizes FROM $table l,{course_modules cm, {modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'quiz' AND l.action = 'created'GROUP BY l.userid) as l3 ON l3.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) forums FROM $table l,{course_modules cm, {modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'forum' AND l.action = 'created'GROUP BY l.userid) as l4 ON l4.userid = u.id
						LEFT JOIN (SELECT l.userid, count(l.id) attendances FROM $table l,{course_modules cm, {modules m  WHERE cm.id = l.objectid AND m.id = cm.module AND m.name = 'attendance' AND l.action = 'created'GROUP BY l.userid) as l5 ON l5.userid = u.id
						WHERE u.deleted = 0 AND u.suspended = 0 GROUP BY ue.userid");
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
        $gradeheaders = array("teacher", "courses", "ff.videos", "l1.urls", "l0.evideos", "l2.assignments", "l3.quizes", "l4.forums", "l5.attendances");
        return $gradeheaders;
    }

}
