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
}