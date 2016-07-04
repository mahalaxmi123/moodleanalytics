<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if ($hassiteconfig) {
    if (!$ADMIN->locate('moodleanalytics')) {
        $ADMIN->add('root', new admin_category('moodleanalytics', new lang_string('moodleanalytics', 'local_moodleanalytics')), 'users');
    }
    $ADMIN->add('moodleanalytics', new admin_externalpage('analytics', get_string('analytics', 'local_moodleanalytics'), $CFG->wwwroot . '/local/moodleanalytics/index.php', array('moodle/site:approvecourse')));
    $ADMIN->add('moodleanalytics', new admin_externalpage('course', get_string('course', 'local_moodleanalytics'), $CFG->wwwroot . '/local/moodleanalytics/course.php', array('moodle/site:approvecourse')));
    $ADMIN->add('moodleanalytics', new admin_externalpage('coursereport', get_string('coursereport', 'local_moodleanalytics'), $CFG->wwwroot . '/local/moodleanalytics/coursereport.php', array('moodle/site:approvecourse')));
    $ADMIN->add('moodleanalytics', new admin_externalpage('dashboard', get_string('dashboard', 'local_moodleanalytics'), $CFG->wwwroot . '/local/moodleanalytics/dashboard.php', array('moodle/site:approvecourse')));
    $ADMIN->add('moodleanalytics', new admin_externalpage('load', get_string('load', 'local_moodleanalytics'), $CFG->wwwroot . '/local/moodleanalytics/performance.php', array('moodle/site:approvecourse')));
    $ADMIN->add('moodleanalytics', new admin_externalpage('tabularreports', get_string('tabularreports', 'local_moodleanalytics'), $CFG->wwwroot . '/local/moodleanalytics/tabularreports.php', array('moodle/site:approvecourse')));
}