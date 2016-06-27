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
    $report_array = array(4 => 'New Courses', 5 => 'Courses with zero activity', 6 => 'Unique Sessions', 7 => 'Scorm Stats', 8 => 'File Stats', 9 => 'Uploads');
    return $report_array;
}

/* Returns report class to call
 *  @param reportid int
 */

function get_report_class($reportid) {
    $classes_array = array(
        4 => new new_courses(),
        5 => new course_with_zero_activity(),
        6 => new unique_sessions(),
        7 => new scorm_stats(),
        8 => new file_stats(),
        9 => new uploads()
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
            $reportobj->gradeheaders = $this->get_headers();
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

    function get_headers() {
        $gradeheaders = array();
//        $gradeheaders[] = "'Date'";
        $gradeheaders[] = "'Courses'";
        return $gradeheaders;
    }

//    function get_headers() {
//        $headers = array();
//        $header1 = new stdclass();
//        $header1->type = "'string'";
//        $header1->name = "'Date'";
//        $headers[] = $header1;
//        $header2 = new stdclass();
//        $header2->type = "'number'";
//        $header2->name = "'Courses'";
//        $headers[] = $header2;
//        return $headers;
//    }

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
        $reportobj->gradeheaders = $this->get_headers();
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

    function get_headers() {
        $gradeheaders = array();
//        $gradeheaders[] = "'Date'";
        $gradeheaders[] = "'Creation Time'";
        return $gradeheaders;
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
        $reportobj->gradeheaders = $this->get_headers();
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

    function get_headers() {
        $gradeheaders = array();
//        $gradeheaders[] = "'Date'";
        $gradeheaders[] = "'Sessions'";
        return $gradeheaders;
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
        $reportobj->gradeheaders = $this->get_headers();
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
                $chartdetails[] = "[" . "'" . $course . "'" . "," . "'" . $teacher . "'" . "," . "'" . $scormcount . "'" . "]";
            }
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $gradeheaders = array();
        $gradeheaders[] = "'Teacher'";
        $gradeheaders[] = "'# of Scorms'";
        return $gradeheaders;
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
        $reportobj->gradeheaders = $this->get_headers();
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
                $chartdetails[] = "[" . "'" . $course . "'" . "," . "'" . $teacher . "'" . "," . "'" . $filecount . "'" . "]";
            }
        }
        return !empty($chartdetails) ? $chartdetails : '';
    }

    function get_headers() {
        $gradeheaders = array();
        $gradeheaders[] = "'Teacher'";
        $gradeheaders[] = "'# of Files'";
        return $gradeheaders;
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
        $sum = 0;
        foreach ($uploaddetails as $key => $details) {
            $uploaddatas[$key]['count'] = count($details);
            foreach ($details as $values) {
                $sum = $sum + (int) $values->filesize;
            }
            $uploaddatas[$key]['filesize'] = $sum;
        }

        $reportobj->data = $this->get_data($uploaddatas);
        $reportobj->gradeheaders = $this->get_headers();
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
                AND $todate ORDER BY timemodified DESC";
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

    function get_headers() {
        $gradeheaders = array();
        $gradeheaders[] = "'# of Files'";
        $gradeheaders[] = "'File size'";
        return $gradeheaders;
    }

}
