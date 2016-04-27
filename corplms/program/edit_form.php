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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package corplms
 * @subpackage program
 */

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

class program_edit_form extends moodleform {

    function definition() {
        global $CFG, $USER, $OUTPUT, $COHORT_VISIBILITY;

        $mform =& $this->_form;
        $action = $this->_customdata['action'];
        $category = $this->_customdata['category'];
        $editoroptions = $this->_customdata['editoroptions'];
        $program = (isset($this->_customdata['program'])) ? $this->_customdata['program'] : false;
        $nojs = (isset($this->_customdata['nojs'])) ? $this->_customdata['nojs'] : 0 ;
        $iscertif = (isset($this->_customdata['iscertif'])) ? $this->_customdata['iscertif'] : 0;

        $systemcontext = context_system::instance();
        $categorycontext = context_coursecat::instance($category->id);
        $config = get_config('moodlecourse');

        if ($program) {
            $programcontext = context_program::instance($program->id);
        }

        // Add some hidden fields
        if ($action != 'add') {
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'iscertif', $iscertif);
        $mform->setType('iscertif', PARAM_INT);

        if ($action == 'delete') {
            // Only show delete confirmation
            $mform->addElement('html', get_string('checkprogramdelete', 'corplms_program', $program->fullname));
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'deleteyes', get_string('yes'));
            $buttonarray[] = $mform->createElement('submit', 'deleteno', get_string('no'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
            return;
        }

/// form definition with new program defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header','programdetails', get_string('programdetails', 'corplms_program'));

        if ($action == 'edit') {
            $mform->addElement('html', html_writer::start_tag('p', array('class' => 'instructions')) . get_string('instructions:programdetails', 'corplms_program') . html_writer::end_tag('p'));
        }

        // Must have create program capability in both categories in order to move program
        if (has_capability('corplms/program:createprogram', $categorycontext)) {
            $displaylist = array();
            $attributes = array();
            $attributes['class'] = 'corplms-limited-width';
            $displaylist = coursecat::make_categories_list('corplms/program:createprogram');
            $mform->addElement('select', 'category', get_string('category', 'corplms_program'), $displaylist, $attributes);
            $mform->setType('category', PARAM_INT);
        } else {
            $mform->addElement('hidden', 'category', null);
            $mform->setType('category', PARAM_INT);
        }

        if ($action == 'view') {
            $mform->hardFreeze('category');
        } else if ($program and !has_capability('moodle/course:changecategory', $categorycontext)) {
        // Use the course permissions to decide if a user can change a program's category
        // (as programs are treated like courses in this respect)
            $mform->hardFreeze('category');
            $mform->setConstant('category', $category->id);
        } else {
            $mform->addHelpButton('category', 'programcategory', 'corplms_program');
            $mform->setDefault('category', $category->id);
        }

        $mform->addElement('text','fullname', get_string('fullname', 'corplms_program'),'maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        if ($action == 'view') {
            $mform->hardFreeze('fullname');
        } else {
            $mform->addHelpButton('fullname', 'programfullname', 'corplms_program');
            if ($iscertif) {
                $mform->setDefault('fullname', get_string('defaultcertprogramfullname', 'corplms_certification'));
            } else {
                $mform->setDefault('fullname', get_string('defaultprogramfullname', 'corplms_program'));
            }
            $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');

        }

        $mform->addElement('text','shortname', get_string('shortname', 'corplms_program'),'maxlength="100" size="20"');
        $mform->setType('shortname', PARAM_TEXT);
        if ($action=='view') {
            $mform->hardFreeze('shortname');
        } else {
            $mform->addHelpButton('shortname', 'programshortname', 'corplms_program');
            if ($iscertif) {
                $mform->setDefault('shortname', get_string('defaultcertprogramshortname', 'corplms_certification'));
            } else {
                $mform->setDefault('shortname', get_string('defaultprogramshortname', 'corplms_program'));
            }
            $mform->addRule('shortname', get_string('missingshortname', 'corplms_program'), 'required', null, 'client');
        }

        $mform->addElement('text','idnumber', get_string('idnumberprogram', 'corplms_program'),'maxlength="100"  size="10"');
        $mform->setType('idnumber', PARAM_TEXT);
        if ($action == 'view') {
            $mform->hardFreeze('idnumber');
        } else {
            $mform->addHelpButton('idnumber', 'programidnumber', 'corplms_program');
        }

        $mform->addElement('date_selector', 'availablefrom', get_string('availablefrom', 'corplms_program'), array('optional' => true));
        $mform->setType('availablefrom', PARAM_INT);
        if ($action == 'view') {
            $mform->hardFreeze('availablefrom');
        } else {
            $mform->addHelpButton('availablefrom', 'programavailability', 'corplms_program');
        }

        $mform->addElement('date_selector', 'availableuntil', get_string('availableuntil', 'corplms_program'), array('optional' => true));
        $mform->setType('availableuntil', PARAM_INT);
        if ($action == 'view') {
            $mform->hardFreeze('availableuntil');
        } else {
            $mform->addHelpButton('availableuntil', 'programavailability', 'corplms_program');

        }

        $mform->addElement('editor', 'summary_editor', get_string('description', 'corplms_program'), null, $editoroptions);
        if ($action == 'view') {
            $mform->hardFreeze('summary_editor');
        } else {
            $mform->addHelpButton('summary_editor', 'summary', 'corplms_program');
            $mform->setType('summary_editor', PARAM_RAW);
        }

        if ($overviewfilesoptions = prog_program_overviewfiles_options($program)) {
            $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('programoverviewfiles', 'corplms_program'), null, $overviewfilesoptions);
            if ($action == 'view') {
                $mform->hardFreeze('overviewfiles_filemanager');
            } else {
                $mform->addHelpButton('overviewfiles_filemanager', 'programoverviewfiles', 'corplms_program');
            }
        }

        $mform->addElement('editor', 'endnote_editor', get_string('endnote', 'corplms_program'), null, $editoroptions);
        if ($action == 'view') {
            $mform->hardFreeze('endnote_editor');
        } else {
            $mform->addHelpButton('endnote_editor', 'endnote', 'corplms_program');
            $mform->setType('endnote_editor', PARAM_RAW);
        }

        // Conditionally add "visible" setting or audience dialog for visible learning.
        if (empty($CFG->audiencevisibility)) {
            if ($action == 'view') {
                $mform->addElement('static', 'visibledisplay', get_string('visible', 'corplms_program'), $program->visible ? get_string('yes') : get_string('no'));
            } else {
                $mform->addElement('advcheckbox','visible', get_string('visible', 'corplms_program'), null, null, array(0, 1));
                $mform->addHelpButton('visible', 'programvisibility', 'corplms_program');
                $mform->setDefault('visible', $config->visible);
                $mform->setType('visible', PARAM_BOOL);
            }
        } else {
            // Define instance type.
            $instancetype = COHORT_ASSN_ITEMTYPE_PROGRAM;
            if (!empty($program->certifid)) {
                $instancetype = COHORT_ASSN_ITEMTYPE_CERTIF;
            }
            if ($action == 'view') {
                $mform->addElement('header', 'visiblecohortshdr', get_string('audiencevisibility', 'corplms_cohort'));
                $mform->addElement('static', 'visibledisplay', get_string('audiencevisibility', 'corplms_cohort'), $COHORT_VISIBILITY[$program->audiencevisible]);
                $cohorts = corplms_cohort_get_visible_learning($program->id, $instancetype);
                if (!empty($cohorts)) {
                    $cohortsclass = new corplms_cohort_visible_learning_cohorts();
                    $cohortsclass->build_visible_learning_table($program->id, $instancetype, true);
                    $mform->addElement('html', $cohortsclass->display(true, 'visible'));
                }
                $mform->setExpanded('visiblecohortshdr');
            } else {
                // Only show the Audiences Visibility functionality to users with the appropriate permissions.
                if (has_capability('corplms/coursecatalog:manageaudiencevisibility', $systemcontext)) {
                    $mform->addElement('header', 'visiblecohortshdr', get_string('audiencevisibility', 'corplms_cohort'));
                    $mform->addElement('select', 'audiencevisible', get_string('visibility', 'corplms_cohort'), $COHORT_VISIBILITY);
                    $mform->addHelpButton('audiencevisible', 'visiblelearning', 'corplms_cohort');

                    if (empty($program->id)) {
                        $mform->setDefault('audiencevisible', $config->visiblelearning);
                        $cohorts = '';
                    } else {
                        $cohorts = corplms_cohort_get_visible_learning($program->id, $instancetype);
                        $cohorts = !empty($cohorts) ? implode(',', array_keys($cohorts)) : '';
                    }

                    $mform->addElement('hidden', 'cohortsvisible', $cohorts);
                    $mform->setType('cohortsvisible', PARAM_SEQUENCE);
                    $cohortsclass = new corplms_cohort_visible_learning_cohorts();
                    $instanceid = !empty($program->id) ? $program->id : 0;
                    $cohortsclass->build_visible_learning_table($instanceid, $instancetype);
                    $mform->addElement('html', $cohortsclass->display(true, 'visible'));

                    $mform->addElement('button', 'cohortsaddvisible', get_string('cohortsaddvisible', 'corplms_cohort'));
                    $mform->setExpanded('visiblecohortshdr');
                }
            }
        }

        //replacement for old corplms/core/icon classes
        $programicon = ($program && !empty($program->icon)) ? $program->icon : 'default';
        corplms_add_icon_picker($mform, $action, 'program', $programicon, $nojs, false);

        // Customfield support.
        if (!$program) {
            $program = new stdClass();
        }
        if (empty($program->id)) {
            $program->id = 0;
        }
        if (in_array($action, array('add', 'edit'))) {
            customfield_definition($mform, $program, 'program', 0, 'prog');
        } else {
            $customfields = customfield_get_fields($program, 'prog', 'program');
            $mform->addElement('header', 'customfields', get_string('customfields', 'corplms_customfield'));
            foreach ($customfields as $cftitle => $cfvalue) {
                $mform->addElement('static', null, $cftitle, $cfvalue);
            }
            $mform->setExpanded('customfields');
        }

        if ($action == 'add') {
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'), 'class="savechanges-overview program-savechanges"');
            $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('cancel', 'corplms_program'), 'class="program-cancel"');
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        } else if ($action == 'edit') {
            $buttonarray = array();
            $buttonarray[] = $mform->createElement('submit', 'savechanges', get_string('savechanges'), 'class="savechanges-overview program-savechanges"');
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        }

        if ($action == 'view' && $program && has_capability('corplms/program:configuredetails', $program->get_context())) {
            $button = $OUTPUT->single_button(new moodle_url('/corplms/program/edit.php',
                array('id' => $program->id, 'action' => 'edit')), get_string('editprogramdetails', 'corplms_program'), 'get');
            $mform->addElement('static', 'progdetailsbutton', '', $button);
        }
    }

    function validation($data, $files) {

        $mform = $this->_form;
        $errors = array();

        if ($data['availablefrom'] != 0 && $data['availableuntil'] != 0) {
            if ($data['availablefrom'] > $data['availableuntil']) {
                $errors['availableuntil'] = get_string('error:availibileuntilearlierthanfrom', 'corplms_program');
            }
        }

        $id = isset($data['id']) ? $data['id'] : 0;
        if (!empty($data['idnumber']) && corplms_idnumber_exists('prog', $data['idnumber'], $id)) {
            $errors['idnumber'] = get_string('idnumberexists', 'corplms_core');
        }

        // Validate any custom fields, this requires the ID to be set.
        $data['id'] = $id;
        $errors += customfield_validation((object)$data, 'program', 'prog');

        return $errors;
    }

    public function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        $progid = $mform->elementExists('id') ? $mform->getElementValue('id') : 0;
        if ($program = $DB->get_record('prog', array('id' => $progid))) {
            customfield_definition_after_data($mform, $program, 'program', 0, 'prog');
        }
    }

}

// Define a form class to display the program content in a non-editable form
class program_content_nonedit_form extends moodleform {

    function definition() {
        global $CFG, $USER, $OUTPUT;

        $mform =& $this->_form;

        $program = $this->_customdata['program'];
        $content = $program->get_content();

        // form definition
        //--------------------------------------------------------------------------------

       $mform->addElement('header','programcontent', get_string('programcontent', 'corplms_program'));

       // Get the total time allowed for this certification/program
       if ($program->certifid) {
           $this->display_course_sets($mform, $content, CERTIFPATH_CERT, get_string('oricertpath', 'corplms_certification'));
           $this->display_course_sets($mform, $content, CERTIFPATH_RECERT, get_string('recertpath', 'corplms_certification'));
       } else {
           $this->display_course_sets($mform, $content, CERTIFPATH_STD, '');
       }

        // Check capabilities.
        if (has_capability('corplms/program:configurecontent', $program->get_context())) {
            $button = $OUTPUT->single_button(new moodle_url($this->_form->getAttribute('action'), array('id' => $program->id)),
                get_string('editprogramcontent', 'corplms_program'), 'get');
            $mform->addElement('static', 'progcontentbutton', '', $button);
        }

    }

    /**
     * Display course sets for view
     */
    function display_course_sets(&$mform, $content, $certifpath, $formlabel) {

        $coursesets = $content->get_course_sets_path($certifpath);

        $formlabel && $mform->addElement('static', 'pathtitle_'.$certifpath, $formlabel.':', '');
        if (count($coursesets)) {
            foreach ($coursesets as $courseset) {
                $elementname = $courseset->get_set_prefix();
                $formlabel = $courseset->display_form_label();
                $formelement = $courseset->display_form_element();
                $mform->addElement('static', $elementname, $formlabel, $formelement);
            }

            $this->display_time_allowed($mform, $content, $certifpath);

        } else {
            $mform->addElement('static', 'progcontent', '', get_string('nocontent', 'corplms_program'));
       }
    }


    /**
     * Display the total time allowed for this program
     */
    function display_time_allowed(&$mform, $content, $certifpath) {

        $total_time_allowed = $content->get_total_time_allowance($certifpath);

        // Only display the time allowance if it is greater than zero
        if ($total_time_allowed > 0) {
            // Break the time allowed details down into human readable form
            $timeallowance = program_utilities::duration_explode($total_time_allowed);
            $timeallowedstr = html_writer::start_tag('p', array('class' => 'timeallowed'));
            $timeallowedstr .= get_string('allowtimeforprogram', 'corplms_program', $timeallowance);
            $timeallowedstr .= html_writer::end_tag('p');
            $mform->addElement('static', 'timeallowance_'.$certifpath, '', $timeallowedstr);
        }
    }
}

// Define a form class to display the program assignments
class program_assignments_nonedit_form extends moodleform {

    function definition() {
        global $CFG, $USER, $OUTPUT;

        $mform =& $this->_form;

        $program = $this->_customdata['program'];
        $assignments = $program->get_assignments();

// form definition
//--------------------------------------------------------------------------------
        $mform->addElement('header','programassignments', get_string('programassignments', 'corplms_program'));

        $elementname = 'assignments';
        $formlabel = $assignments->display_form_label();
        $formelement = $assignments->display_form_element();

        $mform->addElement('static', $elementname, $formlabel, $formelement);

        // Check capabilities
        if (has_capability('corplms/program:configureassignments', $program->get_context())) {
            $button = $OUTPUT->single_button(new moodle_url($this->_form->getAttribute('action'), array('id' => $program->id)),
                get_string('editprogramassignments', 'corplms_program'), 'get');

            $mform->addElement('static', 'progassignbutton', '', $button);
        }
    }


}


// Define a form class to display the program messages
class program_messages_nonedit_form extends moodleform {

    function definition() {
        global $CFG, $USER, $OUTPUT;

        $mform =& $this->_form;

        $program = $this->_customdata['program'];
        $messagesmanager = $program->get_messagesmanager();

// form definition
//--------------------------------------------------------------------------------
        $mform->addElement('header','programmessages', get_string('programmessages', 'corplms_program'));

        $elementname = 'messages';
        $formlabel = $messagesmanager->display_form_label();
        $formelement = $messagesmanager->display_form_element();

        $mform->addElement('static', $elementname, $formlabel, $formelement);

        // Check capabilities
        if (has_capability('corplms/program:configuremessages', $program->get_context())) {
            $button = $OUTPUT->single_button(new moodle_url($this->_form->getAttribute('action'), array('id' => $program->id)),
                get_string('editprogrammessages', 'corplms_program'), 'get');

            $mform->addElement('static', 'progmessagebutton', '', $button);
        }
    }
}



// display the certification details on Overview tab
class program_certifications_nonedit_form extends moodleform {

    function definition() {
        global $CFG, $USER, $DB, $CERTIFRECERT, $OUTPUT;

        $mform =& $this->_form;

        $program = $this->_customdata['program'];

        // form definition
        //--------------------------------------------------------------------------------
        $mform->addElement('header','hdrcertification', get_string('certification', 'corplms_certification'));

        $certification = $DB->get_record('certif', array('id' => $program->certifid));
        if (!$certification || $certification->activeperiod == 0) {
            $mform->addElement('static', 'el1', '' , get_string('nocertifdetailsfound', 'corplms_certification'));
        } else {
            $parts = explode(' ', $certification->activeperiod);
            $mform->addElement('static', 'el2', get_string('editdetailsactive', 'corplms_certification'),
                            $parts[0] . ' ' . mb_strtolower(get_string($parts[1].'s', 'corplms_certification'), 'UTF-8'));
            $parts = explode(' ', $certification->windowperiod);
            $mform->addElement('static', 'el3', get_string('editdetailswindow', 'corplms_certification'),
                            $parts[0] . ' ' . mb_strtolower(get_string($parts[1].'s', 'corplms_certification'), 'UTF-8'));
            $mform->addElement('static', 'el4', get_string('editdetailsrcopt', 'corplms_certification'),
                            $CERTIFRECERT[$certification->recertifydatetype]);
        }

        // Check capabilities
        if (has_capability('corplms/certification:configurecertification', $program->get_context())) {
            $button = $OUTPUT->single_button(new moodle_url($this->_form->getAttribute('action'),
                 array('id' => $program->id)), get_string('editcertification', 'corplms_certification'), 'get');
            $mform->addElement('static', 'certificationbutton', '', $button);
        }
    }
}

// Define a form class to display the program messages
class program_delete_form extends moodleform {

    function definition() {
        global $CFG, $USER;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'delete');
        $mform->setType('action', PARAM_TEXT);

// form definition
//--------------------------------------------------------------------------------
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'delete', get_string('deleteprogrambutton', 'corplms_program'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

    }

}
