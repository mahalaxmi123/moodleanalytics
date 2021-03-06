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
 * @author Jon Sharp <jon.sharp@catalyst-eu.net>
 * @author David Curry <david.curry@corplmslms.com>
 * @package corplms
 * @subpackage certification
 *
 *
 * Events handler: http://docs.moodle.org/dev/Events_API
 *
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\corplms_program\event\program_assigned',
        'callback' => 'certification_event_handler::assigned',
        'includefile' => 'corplms/certification/lib.php',
    ),
    array(
        'eventname' => '\corplms_program\event\program_unassigned',
        'callback' => 'certification_event_handler::unassigned',
        'includefile' => 'corplms/certification/lib.php',
    ),
    array(
        'eventname' => '\corplms_program\event\program_completed',
        'callback' => 'certification_event_handler::completed',
        'includefile' => 'corplms/certification/lib.php',
    ),
);
