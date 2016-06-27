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
        9 => new uploads(),
        10 => new registrations(),
        11=> new enrollmentspercourse(),
        12=> new coursesize(),
        13=> new courseenrollments(),
        14=> new teachingactivity()
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
        $header1->name = "'Date'";
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
        $sum = 0;
        foreach ($uploaddetails as $key => $details) {
            $uploaddatas[$key]['count'] = count($details);
            foreach ($details as $values) {
                $sum = $sum + (int) $values->filesize;
            }
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
        $header1->name = "'Course'";
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
        $json_enrols = array();
        $enrollments = $this->get_enrollments_per_course();
        foreach ($enrollments as $enrollment) {
            $json_enrols[] = '[' . '"' . $enrollment->fullname . '"' . ',' .  $enrollment->nums . ']';
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
                . "FROM {course} c "
                . "LEFT JOIN (SELECT c.instanceid AS course, sum( f.filesize ) as coursesize "
                . "FROM {files} f, {context} c "
                . "WHERE c.id = f.contextid GROUP BY c.instanceid) fs ON fs.course = c.id WHERE c.category > 0 ORDER BY c.timecreated ";
        $coursesizes = $DB->get_records_sql($coursesizesql);

        foreach ($coursesizes as $csize) {
            $csize->size = ($csize->size / (1024 * 1024));
            $json_coursesizes[] = '['. '"' . $csize->coursename . '"' . ',' . $csize->size . ']';
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
