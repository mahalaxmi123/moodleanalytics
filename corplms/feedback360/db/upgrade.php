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
 * @author Ciaran Irvine <ciaran.irvine@corplmslms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@corplmslms.com>
 * @package corplms
 * @subpackage feedback360
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_corplms_feedback360_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.
    if ($oldversion < 2014061600) {
        require_once($CFG->dirroot.'/corplms/feedback360/lib.php');
        $usercount = $DB->count_records('user', array('deleted' => 1));
        if ($usercount > 0) {
            // This could take some time and use a lot of resources.
            set_time_limit(0);
            raise_memory_limit(MEMORY_EXTRA);
            $i = 0;
            $deletedusers = $DB->get_recordset('user', array('deleted' => 1), null, 'id, username, firstname, lastname, email, idnumber, picture, mnethostid');
            $context = context_system::instance();
            $pbar = new progress_bar('fixdeleteduserfeedback360', 500, true);
            $pbar->update($i, $usercount, "Fixing Feedback360 for deleted users - {$i}/{$usercount}.");
            foreach ($deletedusers as $user) {
                $event = \core\event\user_deleted::create(
                    array(
                        'objectid' => $user->id,
                        'context' => $context,
                        'other' => array(
                            'username' => $user->username,
                            'email' => $user->email,
                            'idnumber' => $user->idnumber,
                            'picture' => $user->picture,
                            'mnethostid' => $user->mnethostid
                        )
                ));
                feedback360_event_handler::feedback360_user_deleted($event);
                $i++;
                $pbar->update($i, $usercount, "Fixing Feedback360 for deleted users - {$i}/{$usercount}.");
            }
            $deletedusers->close();
        }
        upgrade_plugin_savepoint(true, 2014061600, 'corplms', 'feedback360');
    }
    return true;
}
