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

class competencyscalevalue_edit_form extends moodleform {

    // Define the form
    function definition() {
        global $TEXTAREA_OPTIONS;
        $mform =& $this->_form;
        $scaleid = $this->_customdata['scaleid'];
        $id = $this->_customdata['id'];

        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'scaleid');
        $mform->setType('scaleid', PARAM_INT);
        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);
        $mform->addElement('hidden', 'prefix', 'competency');
        $mform->setType('prefix', PARAM_ALPHA);

        /// Print the required moodle fields first
        $mform->addElement('header', 'moodle', get_string('general'));

        $mform->addElement('static', 'scalename', get_string('competencyscale', 'corplms_hierarchy'));
        $mform->addHelpButton('scalename', 'competencyscaleassign', 'corplms_hierarchy');

        $mform->addElement('text', 'name', get_string('competencyscalevaluename', 'corplms_hierarchy'), 'maxlength="255" size="20"');
        $mform->addHelpButton('name', 'competencyscalevaluename', 'corplms_hierarchy');
        $mform->addRule('name', get_string('missingscalevaluename', 'corplms_hierarchy'), 'required', null, 'client');
        $mform->setType('name', PARAM_MULTILANG);

        $mform->addElement('text', 'idnumber', get_string('competencyscalevalueidnumber', 'corplms_hierarchy'), 'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'competencyscalevalueidnumber', 'corplms_hierarchy');
        $mform->setType('idnumber', PARAM_TEXT);

        $mform->addElement('text', 'numericscore', get_string('competencyscalevaluenumericalvalue', 'corplms_hierarchy'),
            'maxlength="12"  size="10"');
        $mform->addHelpButton('numericscore', 'competencyscalevaluenumericalvalue', 'corplms_hierarchy');
        $mform->setType('numericscore', PARAM_RAW);

        if (competency_scale_is_used($scaleid)) {
           $note = html_writer::tag('span', get_string('proficientvaluefrozen', 'corplms_hierarchy'), array('class' => 'notifyproblem'));
           $freeze = true;
        } else if ($id != 0 && competency_scale_only_proficient_value($scaleid) == $id) {
           $note = html_writer::tag('span', get_string('proficientvaluefrozenonlyprof', 'corplms_hierarchy'), array('class' => 'notifyproblem'));
            $freeze = true;
        } else {
            $note = '';
            $freeze = false;
        }
        $mform->addElement('advcheckbox', 'proficient', get_string('competencyscaleproficient', 'corplms_hierarchy'), $note);
        $mform->addHelpButton('proficient', 'competencyscaleproficient', 'corplms_hierarchy');
        if ($freeze) {
            $mform->hardFreeze('proficient');
        }

        $mform->addElement('editor', 'description_editor', get_string('description'), null, $TEXTAREA_OPTIONS);
        $mform->addHelpButton('description_editor', 'competencyscalevaluedescription', 'corplms_hierarchy');
        $mform->setType('description_editor', PARAM_CLEANHTML);

        $this->add_action_buttons();
    }

    /**
     * Carries out validation of submitted form values
     *
     * @param array $valuenew array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    function validation($valuenew, $files) {
        global $DB;

        $err = array();
        $valuenew = (object)$valuenew;

        // Check the numericscore field was either empty or a number
        if (strlen($valuenew->numericscore)) {
            // Convert to float
            $valuenew->numericscore = unformat_float($valuenew->numericscore, true);

            // Is a number
            if (is_numeric($valuenew->numericscore)) {
                if ($valuenew->numericscore < -99999.99999 OR $valuenew->numericscore > 99999.99999) {
                    $err['numericscore'] = get_string('invalidscalenumericalvalue', 'corplms_hierarchy');
                }
            } else {
                $err['numericscore'] = get_string('invalidnumeric', 'corplms_hierarchy');
            }
        } else {
            $valuenew->numericscore = null;
        }

        // Check that we're not removing the last proficient value from this scale
        if ($valuenew->proficient == 0) {
            if (!$DB->record_exists_select('comp_scale_values', "scaleid = ? AND proficient = 1 AND id != ?", array($valuenew->scaleid, $valuenew->id))) {
                $err['proficient'] = get_string('error:onescalevaluemustbeproficient', 'corplms_hierarchy');
            }
        }

        if (count($err) > 0) {
            return $err;
        }

        return true;
    }
}
