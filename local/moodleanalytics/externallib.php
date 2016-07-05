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

function get_tabular_reports() {
    $classes_array = array(
        'learner_progress' => "Learner progress",
        'student_performance' => "Student performance",
        'activity_progress' => "Activity progress",
        'overdue_users' => "Overdue users",
    );
    return $classes_array;
}

function get_tabular_reports_class($reportname = '') {
    $classes_array = array(
        'learner_progress' => new learner_progress(),
        'student_performance' => new student_performance(),
        'activity_progress' => new activity_progress(),
        'overdue_users' => new overdue_users(),
    );
    return $classes_array[$reportname];
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

        $sql_filter = "";
        $sql_join = "";
        $WHERE = "";
        if (!empty($params->courseid)) {
            $sql_filter = isset($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
        }
        $filterColumn = '';
        if (!empty($params->date_from) && !empty($params->to_from)) {
            $filterColumn = "ue.timecreated BETWEEN $params->date_from AND $params->to_from";
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
        $where = "";
        if (!empty($params->date_from) && !empty($params->to_from)) {
            $where = "AND ue.timecreated BETWEEN $params->date_from AND $params->to_from";
        }
        $sql_filter = "";
        if ($params->courseid) {
            $sql_filter = isset($params->courseid) ? " AND c.id IN ($params->courseid) " : "";
        }

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
        if (!empty($params->date_from) && !empty($params->to_from)) {
            $sql_filter = " AND gg.timecreated BETWEEN $params->date_from AND $params->to_from";
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
            $course = !empty($value->course) ? "''" . clean_param($value->course, PARAM_ALPHANUMEXT) . "''" : "'-'";
            $learner = !empty($value->learner) ? "'$value->learner'" : "'-'";
            $email = !empty($value->email) ? "'$value->email'" : "'-'";
            if (!empty($value->enablecompletion)) {
                $completedon = !empty($value->complete) ? "'" . userdate($value->complete, get_string('strftimedate', 'langconfig')) . "'" : "'-'";
            }else{
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
