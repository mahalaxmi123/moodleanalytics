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
require_once($CFG->dirroot.'/corplms/hierarchy/lib.php');
require_once($CFG->dirroot.'/corplms/hierarchy/prefix/goal/lib.php');
require_once($CFG->dirroot.'/corplms/hierarchy/item/edit_form.php');

class goal_edit_form extends item_edit_form {

    // Load data for the form.
    public function definition_hierarchy_specific() {
        global $DB;

        $mform =& $this->_form;
        $item = $this->_customdata['item'];

        // Get the name of the framework's scale. (Note this code expects there.
        // To be only one scale per framework, even though the DB structure.
        // Allows there to be multiple since we're using a go-between table).
        $scaledesc = $DB->get_field_sql("
            SELECT s.name
            FROM
                {{$this->hierarchy->shortprefix}_scale} s,
                {{$this->hierarchy->shortprefix}_scale_assignments} a
            WHERE
                a.frameworkid = ?
                and a.scaleid = s.id
        ", array($item->frameworkid));

        $mform->addElement('static', 'scalename', get_string('scale'), ($scaledesc) ? $scaledesc : get_string('none'));
        $mform->addHelpButton('scalename', 'goalscale', 'corplms_hierarchy');

    }
}

class goal_edit_personal_form extends moodleform {

    // Define the form.
    public function definition() {
        global $DB, $TEXTAREA_OPTIONS;

        // Javascript include.
        local_js(array(
            CORPLMS_JS_DIALOG,
            CORPLMS_JS_UI,
            CORPLMS_JS_ICON_PREVIEW
        ));

        $mform =& $this->_form;

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'goalpersonalid');
        $mform->setType('goalpersonalid', PARAM_INT);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        // Name.
        $mform->addElement('text', 'name', get_string('name'), 'maxlength="1024" size="50"');
        $mform->addRule('name', get_string('goalmissingname', 'corplms_hierarchy'), 'required', null);
        $mform->setType('name', PARAM_MULTILANG);

        // Description.
        $mform->addElement('editor', 'description_editor', get_string('description', 'corplms_hierarchy'),
                null, $TEXTAREA_OPTIONS);
        $mform->addHelpButton('description_editor', 'goaldescription', 'corplms_hierarchy');
        $mform->setType('description_editor', PARAM_CLEANHTML);

        // Scale.
        $scales = $DB->get_records('goal_scale', array());
        $scaledesc = array(0 => get_string('none'));
        foreach ($scales as $scale) {
            $scaledesc[$scale->id] = format_string($scale->name);
        }
        $mform->addElement('select', 'scaleid', get_string('scale'), ($scaledesc) ? $scaledesc : get_string('none'));
        $mform->addHelpButton('scaleid', 'goalscale', 'corplms_hierarchy');

        // Target date.
        $mform->addElement('date_selector', 'targetdate', get_string('goaltargetdate', 'corplms_hierarchy'), array('optional' => true));
        $mform->addHelpButton('targetdate', 'goaltargetdate', 'corplms_hierarchy');
        $mform->setType('targetdate', PARAM_INT);

        $this->add_action_buttons();
    }

    public function set_data($data) {
        global $TEXTAREA_OPTIONS, $CFG;

        $options = $TEXTAREA_OPTIONS;

        if (!empty($data->description)) {
            // Same again for the description.
            $data->descriptionformat = FORMAT_HTML;
            $data = file_prepare_standard_editor($data, 'description', $options, $options['context'],
                    'corplms_hierarchy', 'goal', $data->id);
        }

        // Everything else should be fine, set the data.
        parent::set_data($data);
    }

    public function validation($fromform, $files) {
        global $DB;
        $errors = array();
        $fromform = (object)$fromform;

        // Check user exists.
        if (!$DB->record_exists('user', array('id' => $fromform->userid))) {
            $errors['user'] = get_string('userdoesnotexist', "corplms_core");
        }

        // Check scale exists.
        if (!empty($fromform->scaleid) && !$DB->record_exists('goal_scale', array('id' => $fromform->scaleid))) {
            $errors['scale'] = get_string('invalidgoalscale', "corplms_hierarchy");
        }

        // Check target date is in the future.
        if (!empty($fromform->targetdate) && $fromform->targetdate < time()) {
            $errors['targetdate'] = get_string('error:invaliddatepast', 'corplms_hierarchy');
        }

        return $errors;
    }
}
