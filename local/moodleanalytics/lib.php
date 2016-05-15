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
    $report_array = array(1 => 'Course progress', 2 => 'Activity attempt', 3 => 'Activity Status Report');
    return $report_array;
}

/* Returns report class to call
 *  @param reportid int
 */

function get_report_class($reportid) {
    $classes_array = array(1 => new course_progress(),
        2 => new activity_attempt(),
        3 => new activity_status()
    );
    return $classes_array[$reportid];
}

class course_process {
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

    function process_reportdata($courseid, $users, $charttype) {
        $json_grades = array();
        $users_update = array();
        $feedback = array();

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

        $json_grades_array = array();
        foreach ($json_grades as $key => $grade_info) {
            $grade_info = TRIM($grade_info, ',');
            $json_grades_array[] = "[" . $grade_info . "]";
        }

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

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Activities';
        $axis->yaxis = 'Grades';
        return $axis;
    }

}

class activity_attempt {
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

    function process_reportdata($courseid, $users, $charttype) {
        $quizdetails = array();
        $info = '';
        $notattemptedusers = array();
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
            $attempts = $attempts ? format_quiz_attemptwise_grades($maxnumofattempts, $attempts) : array('');
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

}

class activity_status {
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

    function process_reportdata($courseid, $users, $charttype) {
        $resourceactivitycompletion = get_activity_completion($courseid);
        $averageusergrades = get_user_avggrades($report->grades);
        if (!empty($users)) {
            foreach ($users as $key => $username) {
                $feedback[$username] = random_value_for_feedback();
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

                foreach ($users as $thisuserkey => $thisusername) {
                    $chartdetails[] = "[" . "'" . $thisusername . "'" . "," . $newaveragegrade[$thisusername] . "," . $resactivitycompletion[$thisusername] . "," . $feedback[$thisusername] . "]";
                }
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

    function get_headers() {
        $gradeheaders = array();
        //        $gradeheaders[] = "'Test'";
        $gradeheaders[] = "'Grade'";
        $gradeheaders[] = "'Resource completion'";
        $gradeheaders[] = "'Feedback'";
        $report->gradeheaders = $gradeheaders;
    }

    function get_axis_names($reportname) {
        $axis = new stdClass();
        $axis->xaxis = 'Grades';
        $axis->yaxis = 'Resource Completion';
        return $axis;
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
