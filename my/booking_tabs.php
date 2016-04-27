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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package corplms
 * @subpackage my
 */

if (empty($userid)) {
    print_error('error:cannotcallscriptthatway', 'corplms_core');
}
if (!isset($currenttab)) {
    $currenttab = '';
}

$tabs = array();
$row = array();
$activated = array();
$inactive = array();

$row[] = new tabobject('futurebookings', "$CFG->wwwroot/my/bookings.php?userid=$userid",
                           get_string('tab:futurebookings', 'corplms_core'));
$row[] = new tabobject('pastbookings', "$CFG->wwwroot/my/pastbookings.php?userid=$userid",
                           get_string('tab:pastbookings', 'corplms_core'));
$tabs[] = $row;

$activated[] = $currenttab;
print_tabs($tabs, $currenttab);
