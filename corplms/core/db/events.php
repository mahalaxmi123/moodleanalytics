<?php
/*
 * This file is part of Corplms LMS
 *
 * Copyright (C) 2010 onwards Corplms Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @package corplms
 * @subpackage corplms_core
 */

/**
 * this file should be used for all the custom event definitions and handers.
 * event names should all start with corplms_.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}


$observers = array (
    array(
        'eventname' => '\corplms_core\event\module_completion',
        'callback' => 'corplms_core_event_handler::criteria_course_calc',
        'includefile' => '/corplms/core/lib.php'
    ),
    array(
        'eventname' => '\corplms_core\event\user_enrolment',
        'callback' => 'corplms_core_event_handler::user_enrolment',
        'includefile' => '/corplms/core/lib.php'
    ),
);
