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
 * tool_tenant data generator.
 *
 * @package    tool_tenant
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die();

/**
 * tool_tenant data generator class.
 *
 * @package    tool_tenant
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class tool_tenant_generator extends component_generator_base {

    /**
     * Number of instances created
     * @var int
     */
    protected $instancecount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->instancecount = 0;
    }

    /**
     * Creates new tenant
     *
     * @param array|stdClass $record
     * @return stdClass
     */
    public function create_tenant($record = null) : \stdClass {
        \tool_tenant\tenancy::get_tenants();
        $record = $record ? (array)$record : [];
        if (!array_key_exists('name', $record)) {
            $record['name'] = 'New tenant ' . (++$this->instancecount);
        }
        return (new \tool_tenant\manager())->create_tenant((object)$record)->to_record();
    }

    /**
     * Allocates a user to a tenant
     *
     * @param int $userid
     * @param int $tenantid
     */
    public function allocate_user(int $userid, int $tenantid) {
        (new \tool_tenant\manager())->allocate_user($userid, $tenantid, 'tool_tenant', 'testing');
    }
}
