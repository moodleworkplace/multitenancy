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
 * Class tenant
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_tenant;

defined('MOODLE_INTERNAL') || die();

use core\output\inplace_editable;

/**
 * Class tenant
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant extends \core\persistent {

    /** The table name. */
    const TABLE = 'tool_tenant';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'name' => array(
                'type' => PARAM_TEXT,
                'description' => 'The tenant name.',
            ),
            'sitename' => array(
                'type' => PARAM_TEXT,
                'description' => 'The tenant site name.',
                'default' => null,
                'null' => NULL_ALLOWED,
            ),
            'siteshortname' => array(
                'type' => PARAM_TEXT,
                'description' => 'The tenant site short name.',
                'default' => null,
                'null' => NULL_ALLOWED,
            ),
            'idnumber' => array(
                'type' => PARAM_RAW,
                'description' => 'An id number used for external services.',
                'default' => null,
                'null' => NULL_ALLOWED,
            ),
            'archived' => array(
                'type' => PARAM_INT,
                'description' => 'Is archived.',
                'default' => 0,
            ),
            'timearchived' => array(
                'type' => PARAM_INT,
                'description' => 'Time the tenant was archived.',
                'default' => null,
                'null' => NULL_ALLOWED,
            ),
            'isdefault' => array(
                'type' => PARAM_INT,
                'description' => 'Is default tenant',
                'default' => 0,
            ),
            'sortorder' => array(
                'type' => PARAM_INT,
                'description' => 'Sort order',
                'default' => 0,
            ),
            'categoryid' => array(
                'type' => PARAM_INT,
                'description' => 'Category ID this tenant is linked to',
                'default' => null,
                'null' => NULL_ALLOWED,
            ),
            'cssconfig' => array(
                'type' => PARAM_RAW,
                'description' => 'The CSS config for this tenant.',
                'default' => null,
                'null' => NULL_ALLOWED,
            ),
            'useloginurlid' => array(
                'type' => PARAM_BOOL,
                'description' => 'Use login URL id',
                'default' => 1,
            ),
            'useloginurlidnumber' => array(
                'type' => PARAM_BOOL,
                'description' => 'Use login URL idnumber',
                'default' => 1,
            ),
        );
    }

    /**
     * Tenant name ready for display
     * @return string
     */
    public function get_formatted_name() : string {
        return format_string($this->get('name'), true,
            ['context' => \context_system::instance(), 'escape' => false]);
    }
}

