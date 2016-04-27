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
 * @package corplms
 * @subpackage hierarchy
 */

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/corplms/hierarchy/lib.php');

class framework_edit_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG, $TEXTAREA_OPTIONS;

        $mform =& $this->_form;
        $prefix  = $this->_customdata['prefix'];

        $strgeneral  = get_string('general');

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'visible');
        $mform->setType('visible', PARAM_INT);
        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);
        $mform->addElement('hidden', 'hidecustomfields');
        $mform->setType('hidecustomfields', PARAM_INT);
        $mform->addElement('hidden', 'prefix', $prefix);
        $mform->setType('prefix', PARAM_ALPHA);

        $mform->addElement('text', 'fullname', get_string('name'), 'maxlength="254" size="50"');
        $mform->addRule('fullname', get_string($prefix.'missingnameframework', 'corplms_hierarchy'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        if (HIERARCHY_DISPLAY_SHORTNAMES) {
            $mform->addElement('text', 'shortname', get_string('shortnameframework', 'corplms_hierarchy'), 'maxlength="100" size="20"');
            $mform->addHelpButton('shortname', $prefix.'frameworkshortname', 'corplms_hierarchy');
            $mform->addRule('shortname', get_string('missingshortnameframework', 'corplms_hierarchy'), 'required', null, 'client');
            $mform->setType('shortname', PARAM_MULTILANG);
        }

        $mform->addElement('text', 'idnumber', get_string($prefix.'frameworkidnumber', 'corplms_hierarchy'), 'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', $prefix.'frameworkidnumber', 'corplms_hierarchy');
        $mform->setType('idnumber', PARAM_TEXT);

        $mform->addElement('editor', 'description_editor', get_string($prefix.'frameworkdescription', 'corplms_hierarchy'), null, $TEXTAREA_OPTIONS);
        $mform->addHelpButton('description_editor', $prefix.'frameworkdescription', 'corplms_hierarchy');
        $mform->setType('description_editor', PARAM_CLEANHTML);

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = array();
        $data = (object)$data;

        if (!empty($data->idnumber)) {
            $prefix = hierarchy::get_short_prefix($data->prefix);
            if (corplms_idnumber_exists($prefix . '_framework', $data->idnumber, $data->id)) {
                $errors['idnumber'] = get_string('idnumberexists', 'corplms_core');
            }
        }

        return $errors;
    }
}
