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
 * @subpackage program
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot . '/corplms/program/lib.php');

$action = required_param('action', PARAM_INT);
$selectedexceptioncount = required_param('selectedexceptioncount', PARAM_INT);


$html = html_writer::start_tag('div');
$html .= html_writer::start_tag('div');
if ($action == SELECTIONACTION_NONE) {
    echo get_string('pleaseselectoption', 'corplms_program');
    die();
}
else if ($action == SELECTIONACTION_AUTO_TIME_ALLOWANCE) {
    $html .= get_string('choseautomaticallydetermine', 'corplms_program');
}
else if ($action == SELECTIONACTION_OVERRIDE_EXCEPTION) {
    $html .= get_string('choseoverrideexception', 'corplms_program');
}
else if ($action == SELECTIONACTION_DISMISS_EXCEPTION) {
    $html .= get_string('chosedismissexception', 'corplms_program');
}
$html .= html_writer::end_tag('div');

$html .= html_writer::tag('div', get_string('thiswillaffect', 'corplms_program', $selectedexceptioncount));

$html .= html_writer::tag('div', get_string('thisactioncannotbeundone', 'corplms_program'));

$html .= html_writer::end_tag('div');

echo $html;
