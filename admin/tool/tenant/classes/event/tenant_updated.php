<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin event classes are defined here.
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_tenant\event;

use core\event\base;
use tool_tenant\manager;
use tool_tenant\tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * The tenant_updated event class.
 *
 * @package    tool_tenant
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant_updated extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'tool_tenant';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Creates an instance of this event given the tenant object
     *
     * @param tenant $tenant the instance of tenant with updated properties
     * @param \stdClass $oldrecord the copy of the record before update
     * @return base
     */
    public static function create_from_object(tenant $tenant, \stdClass $oldrecord) {
        $params = [
            'context' => \context_system::instance(),
            'objectid' => $tenant->get('id'),
            'other' => []
        ];
        if (!$oldrecord->archived && $tenant->get('archived')) {
            $params['other']['isarchived'] = true;
        }
        if ($oldrecord->archived && !$tenant->get('archived')) {
            $params['other']['isrestored'] = true;
        }
        $event = static::create($params);
        $event->add_record_snapshot(tenant::TABLE, $tenant->to_record());
        return $event;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtenantupdated', 'tool_tenant');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if (!empty($this->other['isarchived'])) {
            return "The user with id '$this->userid' archived the tenant with id '$this->objectid'";
        } else if (!empty($this->other['isrestored'])) {
            return "The user with id '$this->userid' restored the archived tenant with id '$this->objectid'";
        } else {
            return "The user with id '$this->userid' updated the tenant with id '$this->objectid'";
        }
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return manager::get_view_url($this->objectid);
    }

    /**
     * This is used when restoring course logs where it is required that we
     * map the objectid to it's new value in the new course.
     *
     * @return int|string
     */
    public static function get_objectid_mapping() {
        return base::NOT_MAPPED;
    }

    /**
     * This is used when restoring course logs where it is required that we
     * map the information in 'other' to it's new value in the new course.
     *
     * @return array|bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
