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
 * @subpackage corplms_customfield
 */

class customfield_define_text extends customfield_define_base {

    function define_form_specific(&$form) {
        /// Default data
        $form->addElement('text', 'defaultdata', get_string('defaultdata', 'corplms_customfield'), 'size="50"');
        $form->setType('defaultdata', PARAM_MULTILANG);
        $form->addHelpButton('defaultdata', 'customfielddefaultdatatext', 'corplms_customfield');

        /// Param 1 for text type is the size of the field
        $form->addElement('text', 'param1', get_string('fieldsize', 'corplms_customfield'), 'size="6"');
        $form->setDefault('param1', 30);
        $form->setType('param1', PARAM_INT);
        $form->addHelpButton('param1', 'customfieldfieldsizetext', 'corplms_customfield');

        /// Param 2 for text type is the maxlength of the field
        $form->addElement('text', 'param2', get_string('fieldmaxlength', 'corplms_customfield'), 'size="6"');
        $form->setDefault('param2', 2048);
        $form->setType('param2', PARAM_INT);
        $form->addHelpButton('param2', 'customfieldmaxlengthtext', 'corplms_customfield');
    }

}
