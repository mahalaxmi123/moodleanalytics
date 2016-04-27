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
 * @package corplms
 * @subpackage corplms_core
 */

// Example of an upgrade output item:
// echo $OUTPUT->heading('Disable Moodle autoupdates in Corplms');
// echo $OUTPUT->notification($success, 'notifysuccess');
// print_upgrade_separator();

defined('MOODLE_INTERNAL') || die();
global $OUTPUT, $DB, $CFG, $CORPLMS;

require_once ("$CFG->dirroot/corplms/core/db/utils.php");

$dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.
$success = get_string('success');

// Double-check version numbers when upgrading a Corplms installation.
if (isset($CFG->corplms_release)){
    if (substr($CFG->corplms_release, 0, 3) == '1.0' || substr($CFG->corplms_release, 0, 3) == '1.1') {
        $a = new stdClass();
        $a->currentversion = $CFG->corplms_release;
        $a->attemptedversion = $CORPLMS->release;
        $a->required = get_string('corplmsrequiredupgradeversion', 'corplms_core');
        throw new moodle_exception('corplmsunsupportedupgradepath', 'corplms_core', '', $a);
    } else if (substr($CFG->corplms_release, 0, 3) == '2.4') {
        corplms_fix_existing_capabilities();
    }
}

// Check unique idnumbers in corplms tables.
if ($CFG->version < 2013051402.00) {
    echo $OUTPUT->heading(get_string('corplmsupgradecheckduplicateidnumbers', 'corplms_core'));
    $duplicates = corplms_get_nonunique_idnumbers();
    if (!empty($duplicates)) {
        $duplicatestr = '';
        foreach ($duplicates as $duplicate) {
            $duplicatestr .= get_string('idnumberduplicates', 'corplms_core', $duplicate) . '<br/>';
        }
        throw new moodle_exception('corplmsuniqueidnumbercheckfail', 'corplms_core', '', $duplicatestr);
    } else {
        echo $OUTPUT->notification($success, 'notifysuccess');
        print_upgrade_separator();
    }
}

// Fix Facetoface error on upgrade from 2.2 or 2.4.
if (isset($CFG->corplms_release)){
    // Need to remove any plus bump as version_compare does not understand it.
    $oldversion = str_replace('+', '', $corplmsinfo->existingcorplmsversion);
    $newversion = str_replace('+', '', $corplmsinfo->newcorplmsversion);
    //If current version < 2.5.0 and attempted version >= 2.5.10 then add the invalidatecache field to course_completions.
    if (version_compare($oldversion, '2.5.0', '<') && version_compare($newversion, '2.5.10', '>=')) {
        // Define field invalidatecache to be added to course_completions.
        $table = new xmldb_table('course_completions');
        $field = new xmldb_field('invalidatecache', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }
}
