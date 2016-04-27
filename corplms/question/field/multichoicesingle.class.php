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
 * @author Valerii Kuznetsov <valerii.kuznetsov@corplmslms.com>
 * @author Alastair Munro <alastair.munro@corplmslms.com>
 * @package corplms
 * @subpackage corplms_question
 */

/**
 * Multiple answers question stores data not in generated db table fields but in separate table.
 * This is because number of chosen options is unknown,
 * To store this data (answers), element uses *_scale_data table.
 * Sets of elements joned in scales. It's needed for.
 *
 */

require_once('multichoice.class.php');

class question_multichoicesingle extends multichoice {

    public function __construct($storage, $subjectid = 0, $answerid = 0) {
        parent::__construct($storage, $subjectid, $answerid);
        $this->scaletype = self::SCALE_TYPE_MULTICHOICE;
    }


    public static function get_info() {
        return array('group' => question_manager::GROUP_QUESTION,
                     'type' => get_string('questiontypemultichoice', 'corplms_question'));
    }


    /*
     * Customfield specific settings elements
     *
     * @param MoodleQuickForm $form
     * @return question_multichoice2 $this
     */
    protected function add_field_specific_settings_elements(MoodleQuickForm $form, $readonly, $moduleinfo) {
        $this->add_choices_menu($form, $readonly);

        // Add select type.
        $list = array();
        $list[] = $form->createElement('radio', 'list', '', get_string('multichoiceradio', 'corplms_question'), 1);
        $list[] = $form->createElement('radio', 'list', '', get_string('multichoicemenu', 'corplms_question'), 2);
        $form->addGroup($list, 'listtype', get_string('displaysettings', 'corplms_question'), array('<br/>'), true);

        // Set a default.
        $form->setDefault('listtype[list]', self::DISPLAY_RADIO);

        return $this;
    }
}
