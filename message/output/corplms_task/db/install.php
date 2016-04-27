<?php
/*
 * This file is part of Corplms LMS
 *
 * Copyright (C) 2010 onwards Corplms Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Piers Harding <piers@catalyst.net.nz>
 * @author Luis Rodrigues
 * @package corplms
 * @subpackage message
 */

require_once($CFG->dirroot.'/corplms/message/messagelib.php');
function xmldb_message_corplms_task_install() {
    global $DB;

    $result = true;

    $provider = new stdClass();
    $provider->name  = 'corplms_task';
    //Avoid duplicate processors
    if (!$DB->get_record('message_processors', array('name' => $provider->name))) {
        $DB->insert_record('message_processors', $provider);
    }

    // set default permitted
    tm_set_preference_defaults();

    return $result;
}
