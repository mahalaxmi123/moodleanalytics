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
 * @author David Curry <david.curry@corplmslms.com>
 * @package corplms
 * @subpackage hierarchy
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
        'eventname' => '\core\event\user_deleted',
        'callback' => 'hierarchy_event_handler::user_deleted',
        'includefile' => '/corplms/hierarchy/lib.php',
    ),
);
