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
 * Class define_role_table
 *
 * @package     tool_tenant
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @author      2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

namespace tool_tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * Class define_role_table
 *
 * @package     tool_tenant
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @author      2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class define_role_table extends \core_role_define_role_table_basic {
    /** @var bool table is in the "show advanced" mode */
    protected $showadvanced;
    /** @var bool this is a tenant admin role */
    protected $isadmin;
    /** @var array cache for the available admin capabilities */
    protected $admincapabilities = null;

    /**
     * define_role_table constructor.
     *
     * @param int $roleid
     * @param bool $showadvanced
     */
    public function __construct($roleid, $showadvanced) {
        $this->isadmin = ($roleid == manager::get_tenant_admin_role());
        parent::__construct(\context_system::instance(), $roleid);
        $this->showadvanced = $showadvanced;
        if ($showadvanced) {
            $this->displaypermissions = $this->allpermissions;
        } else {
            $this->displaypermissions = [CAP_ALLOW => $this->allpermissions[CAP_ALLOW]];
        }
    }

    /**
     * Switch between advanced/basic view for advanced button
     */
    protected function print_show_hide_advanced_button() {
        if (!$this->showadvanced) {
            return parent::print_show_hide_advanced_button();
        } else {
            return \core_role_define_role_table_advanced::print_show_hide_advanced_button();
        }
    }

    /**
     * Switch between advanced/basic view for permission cells
     *
     * @param \stdClass $capability
     * @return string
     */
    protected function add_permission_cells($capability) {
        if (!$this->showadvanced) {
            return parent::add_permission_cells($capability);
        } else {
            return \core_role_define_role_table_advanced::add_permission_cells($capability);
        }
    }

    /**
     * Never allow to change assignable contexts
     *
     * @return string
     */
    protected function get_assignable_levels_control() {
        $output = get_string('nomanualassignment', 'tool_tenant');
        foreach ($this->allcontextlevels as $cl => $clname) {
            $output .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'contextlevel' . $cl, 'value' => "0"]);
        }
        return $output;
    }

    /**
     * Input control for archetype
     *
     * @param string $id
     * @return string
     */
    protected function get_archetype_field($id) {
        if ($this->isadmin) {
            return get_string('none') .
                \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'archetype', 'value' => ""]);
        } else {
            return parent::get_archetype_field($id);
        }
    }

    /**
     * Input control for shortname
     *
     * @param string $id
     * @return string
     */
    protected function get_shortname_field($id) {
        return s($this->role->shortname) .
            \html_writer::empty_tag('input',
                ['type' => 'hidden', 'id' => $id, 'name' => $id, 'value' => $this->role->shortname]);
    }

    /**
     * Returns an array of roles with the allowed type.
     *
     * @param string $type Must be one of: assign, switch, override or view.
     * @return string
     */
    protected function get_allow_role_control($type) {
        // Admin role can not override or switch to any other role.
        if ($this->isadmin && ($type === 'override' || $type === 'switch')) {
            return get_string('none').
                \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'allow'.$type.'[]', 'value' => ""]);
        } else {
            return parent::get_allow_role_control($type);
        }
    }

    /**
     * Update $this->permissions based on submitted data, while making a list of
     * changed capabilities in $this->changed.
     */
    public function read_submitted_permissions() {
        parent::read_submitted_permissions();

        // Allow name to be empty.
        $name = optional_param('name', null, PARAM_TEXT);
        if ($name !== null && !strlen($name) && isset($this->errors['name'])) {
            $this->role->name = '';
            unset($this->errors['name']);
        }
    }

    /**
     * For subclasses to override. Allows certain capabilties
     * to be left out of the table.
     *
     * @param object $capability the capability this row relates to.
     * @return boolean. If true, this row is omitted from the table.
     */
    protected function skip_row($capability) {
        if ($this->isadmin) {
            if ($this->admincapabilities === null) {
                $this->admincapabilities = role::get_tenant_admin_capabilities();
            }
            return (!array_key_exists($capability->name, $this->admincapabilities));
        } else {
            return (int)($capability->contextlevel) < CONTEXT_COURSECAT;
        }
    }

    /**
     * Display the table.
     */
    public function display() {
        global $OUTPUT;
        parent::display();
        if ($this->isadmin) {
            echo $OUTPUT->box(get_string('tenantadmincapabilitieslimit', 'tool_tenant',
                get_docs_url('Tenant administrator role')));
        } else {
            echo $OUTPUT->box(get_string('tenantcategorycapabilitieslimit', 'tool_tenant'));
        }
    }
}
