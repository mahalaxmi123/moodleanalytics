<?php
/*
 * This file is part of Corplms LMS
 *
 * Copyright (C) 2014 onwards Corplms Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@corplmslms.com>
 * @package corplms_core
 */

namespace corplms_core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The menu item visibility event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string shortname: Short name of menu item.
 * }
 *
 * @author Oleg Demeshev <oleg.demeshev@corplmslms.com>
 * @package corplms_core
 */
class menuitem_visibility extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param $itemid
     * @param $hide visibility status
     * @return menuitem_visibility
     */
    public static function create_from_item($itemid, $hide) {
        $data = array(
            'objectid' => $itemid,
            'context' => \context_system::instance(),
            'other' => array(
                'status' => ($hide ? 'hide' : 'show'),
            ),
        );
        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        return $event;
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'corplms_navigation';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventmenuitemupdated', 'corplms_core');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "Menu item with id '{$this->objectid}' changed visibility to {$this->other['status']}";
    }

    /**
     * Return name of the legacy event, which is replaced by this event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'menuitem_visibility';
    }

    /**
     * Return menuitem_visibility legacy event data.
     *
     * @return \stdClass menu item data.
     */
    protected function get_legacy_eventdata() {
        $node = new \stdClass();
        $node->id = $this->objectid;
        return $node;
    }

    /**
     * Returns array of parameters to be passed to legacy add_to_log() function.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $id = $this->other['status'] . 'id';
        return array(SITEID, 'corplms_core', 'menu item updated', "index.php?{$id}={$this->objectid}", $this->objectid);
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $id = $this->other['status'] . 'id';
        return new \moodle_url('/corplms/core/menu/index.php', array($id => $this->objectid));
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call menuitem_visibility::create() directly, use menuitem_visibility::create_from_item() instead.');
        }
        parent::validate_data();
    }
}
