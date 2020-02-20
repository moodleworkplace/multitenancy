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
 * Class role
 *
 * @package     tool_tenant
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @author      2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * Class role, various methods to work with preset tenant roles
 *
 * @package     tool_tenant
 * @copyright   2019 Moodle Pty Ltd <support@moodle.com>
 * @author      2019 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role {

    /**
     * Return default names for the tenant roles that should be used if the name is not specified
     *
     * Used in function role_get_name() in lib/accesslib.php
     *
     * @param \stdClass $role
     * @return mixed
     */
    public static function get_default_role_name($role) {
        if ($role->id == manager::get_tenant_admin_role()) {
            return get_string('tenantadmin', 'tool_tenant');
        }
        if ($role->id == manager::get_tenant_manager_role()) {
            return get_string('tenantmanager', 'tool_tenant');
        }
        if ($role->id == manager::get_tenant_user_role()) {
            return get_string('tenantuser', 'tool_tenant');
        }
        return $role->shortname;
    }

    /**
     * Returns the class name to use for editing tenant roles
     *
     * This is used in admin/roles/define.php
     *
     * @return define_role_table|null
     */
    public static function get_definition_table(): ?define_role_table {
        $action = optional_param('action', 'view', PARAM_ALPHA);
        if ($action === 'view') {
            return null;
        }
        $roleid = optional_param('roleid', 0, PARAM_INT);
        $roles = [manager::get_tenant_admin_role(),
            manager::get_tenant_manager_role(),
            manager::get_tenant_user_role()];
        if (!in_array($roleid, $roles)) {
            return null;
        }

        $showadvanced = get_user_preferences('definerole_showadvanced', false);
        return new define_role_table($roleid, $showadvanced);
    }

    /**
     * The list of capabilities that are considered "safe" for the tenant administrator role
     *
     * This means that users who have these capabilities in the system context:
     * - can browse/search all users (for example in the user pickers) but only users in their tenant
     * - can not change any settings that would affect other tenants
     * - can not create any content that would be visible to other tenants
     *
     * Core capabilities may only be added to this function and we must ensure that the core
     * is modified respectfully.
     *
     * Other plugins may define the callback "tenant_admin_capabilities()" where they list
     * safe capabilities defined in this plugin.
     *
     * @return array A list of default capabilities.
     */
    public static function get_tenant_admin_capabilities() : array {
        $capabilities = [
            'moodle/site:configview' => CAP_ALLOW,
            'tool/tenant:browseusers' => CAP_ALLOW,
            'tool/tenant:managetheme' => CAP_ALLOW,
            'tool/tenant:manageusers' => CAP_ALLOW,
            'moodle/role:assign' => CAP_ALLOW,
            'moodle/site:viewuseridentity' => CAP_ALLOW,
            'moodle/site:doclinks' => CAP_ALLOW,
            'moodle/badges:awardbadge' => CAP_ALLOW,
            'moodle/badges:viewawarded' => CAP_ALLOW,
        ];

        foreach (\core_component::get_plugin_types() as $ptype => $unused) {
            $plugins = \core_component::get_plugin_list_with_class($ptype, 'tool_tenant');
            foreach ($plugins as $plugin => $classname) {
                $caps = component_class_callback($classname, 'get_tenant_admin_capabilities', []);
                if (empty($caps) || !is_array($caps)) {
                    continue;
                }
                $callback = $classname . '::get_tenant_admin_capabilities';
                foreach ($caps as $capability => $allow) {
                    if (!is_string($capability)) {
                        debugging("Can not read capability name in {$callback}()", DEBUG_DEVELOPER);
                        continue;
                    }
                    list($plugintype, $pluginname) = \core_component::normalize_component($plugin);
                    if (strpos($capability, "{$plugintype}/{$pluginname}:") !== 0) {
                        debugging("Capability '" . s($capability) .
                            "' returned in {$callback}() must belong to the plugin {$plugin}", DEBUG_DEVELOPER);
                        continue;
                    }
                    if ($allow !== CAP_ALLOW && $allow !== CAP_INHERIT) {
                        debugging("Capability '" . s($capability) . "' returned in {$callback}() " .
                            "must be either CAP_ALLOW or CAP_INHERIT", DEBUG_DEVELOPER);
                        continue;
                    }
                    if (!get_capability_info($capability) && during_initial_install()) {
                        // Capabilities may not have been loaded yet.
                        update_capabilities($plugin);
                    }
                    $capabilities[$capability] = $allow;
                }
            }
        }
        ksort($capabilities);
        return $capabilities;
    }

    /**
     * Returns list of capabilities whitelisted for one plugin
     *
     * @param string $pluginname
     * @param bool $onlyallowed
     * @return array associative array $capabilityname=>$permission
     */
    public static function get_plugin_capabilities_for_tenant_admin_role(string $pluginname, bool $onlyallowed = false): array {
        list($type, $name) = \core_component::normalize_component($pluginname);
        $capabilities = array_filter(self::get_tenant_admin_capabilities(),
            function($allow, $cap) use ($type, $name, $onlyallowed) {
                return (!$onlyallowed || $allow == CAP_ALLOW)
                    && strpos($cap, "$type/$name:") === 0;
            }, ARRAY_FILTER_USE_BOTH);
        return $capabilities;
    }
}
