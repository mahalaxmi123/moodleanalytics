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
 * @author Simon Coggins <simon.coggins@corplmslms.com>
 * @package corplms
 * @subpackage corplms_hierarchy
 */

require_once($CFG->dirroot.'/lib/formslib.php');

class competencytemplate_edit_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG, $TEXTAREA_OPTIONS;

        $mform =& $this->_form;
        $strgeneral  = get_string('general');

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'visible');
        $mform->setType('visible', PARAM_INT);
        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);

        /// Print the required moodle fields first
        $mform->addElement('header', 'moodle', $strgeneral);
        $mform->addHelpButton('moodle', 'competencytemplategeneral', 'corplms_hierarchy');

        $mform->addElement('text', 'fullname', get_string('fullnametemplate', 'corplms_hierarchy'), 'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'competencytemplatefullname', 'corplms_hierarchy');
        $mform->addRule('fullname', get_string('missingfullnametemplate', 'corplms_hierarchy'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname', get_string('shortnametemplate', 'corplms_hierarchy'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'competencytemplateshortname', 'corplms_hierarchy');
        $mform->addRule('shortname', get_string('missingshortnametemplate', 'corplms_hierarchy'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);

        $mform->addElement('editor', 'description_editor', get_string('description'), null, $TEXTAREA_OPTIONS);
        $mform->addHelpButton('description_editor', 'text', null);
        $mform->setType('description_editor', PARAM_CLEANHTML);

        $this->add_action_buttons();
    }
}
