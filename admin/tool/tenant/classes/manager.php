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
 * Class manager.
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

namespace tool_tenant;

use tool_tenant\event\tenant_created;
use tool_tenant\event\tenant_deleted;
use tool_tenant\event\tenant_updated;
use tool_tenant\event\tenant_user_created;
use tool_tenant\event\tenant_user_updated;

defined('MOODLE_INTERNAL') || die();

/**
 * Methods for managing the list of tenants
 *
 * Not external API.
 *
 * Use {@link \tool_tenant\tenancy} to get information about current tenant and its users
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class manager {

    /**
     * Returns list of tenants in the system
     *
     * @return tenant[]
     */
    public function get_tenants() : array {
        global $DB;
        $cache = \cache::make('tool_tenant', 'tenants');
        if (!($tenants = $cache->get('active'))) {
            $tenantrecords = $DB->get_records(tenant::TABLE, ['archived' => 0],
                'isdefault DESC, sortorder, id');
            $tenants = [];
            foreach ($tenantrecords as $tenantrecord) {
                $tenants[$tenantrecord->id] = new tenant(0, $tenantrecord);
            }
            if (empty($tenants)) {
                // Create default tenant.
                $tenant = $this->create_tenant((object)[
                    'name' => get_string('defaultname', 'tool_tenant'),
                    'isdefault' => 1]);
                $tenants[$tenant->get('id')] = $tenant;
            }
            $cache->set('active', $tenants);
        }
        return $tenants;
    }

    /**
     * Returns list of archived tenants in the system
     *
     * @return tenant[]
     */
    public function get_archived_tenants() {
        global $DB;
        $cache = \cache::make('tool_tenant', 'tenants');
        if (($archivedtenants = $cache->get('archived')) === false) {
            $tenantrecords = $DB->get_records(tenant::TABLE, ['archived' => 1], 'timearchived DESC');
            $archivedtenants = [];
            foreach ($tenantrecords as $tenantrecord) {
                $archivedtenants[$tenantrecord->id] = new tenant(0, $tenantrecord);
            }
            $cache->set('archived', $archivedtenants);
        }
        return $archivedtenants;
    }

    /**
     * Resets tenants list cache
     */
    protected function reset_tenants_cache() {
        \cache_helper::purge_by_event('tenantsmodified');
        \cache::make('tool_tenant', 'mytenant')->purge();
        \cache::make('tool_tenant', 'tenants')->purge();
    }

    /**
     * Retrieves an active tenant by id
     *
     * @param int $id
     * @param \moodle_url $exceptionlink (optional) link to use in exception message
     * @return tenant
     * @throws \moodle_exception
     */
    protected function get_tenant(int $id, \moodle_url $exceptionlink = null) : tenant {
        $tenants = $this->get_tenants();
        if (array_key_exists($id, $tenants)) {
            return $tenants[$id];
        }
        throw new \moodle_exception('tenantnotfound', 'tool_tenant',
            $exceptionlink ?: self::get_base_url());
    }

    /**
     * Creates a new tenant
     *
     * @param \stdClass $data
     */
    public function create_tenant(\stdClass $data) : tenant {
        global $DB;
        $sortorder = (int)$DB->get_field_sql('SELECT max(sortorder) FROM {tool_tenant} WHERE archived = 0', []);
        $data->sortorder = $sortorder + 1;
        $tenant = new tenant(0, $data);
        $tenant->create();
        if (!$tenant->get('isdefault')) {
            // Do not trigger event when default tenant is created, it is done automatically on the first request
            // and may affect core unittests.
            tenant_created::create_from_object($tenant)->trigger();
        }
        $this->reset_tenants_cache();
        return $tenant;
    }

    /**
     * Updates a tenant
     *
     * @param tenant $tenant
     * @param \stdClass $newdata
     * @return tenant same object that was passed to this method
     */
    protected function update_tenant_object(tenant $tenant, \stdClass $newdata) : tenant {
        $oldrecord = $tenant->to_record();
        foreach ($newdata as $key => $value) {
            if (tenant::has_property($key) && $key !== 'id') {
                $tenant->set($key, $value);
            }
        }
        $tenant->save();
        tenant_updated::create_from_object($tenant, $oldrecord)->trigger();
        $this->reset_tenants_cache();
        return $tenant;
    }

    /**
     * Archives a tenant
     *
     * @param int $id
     * @return tenant
     */
    public function archive_tenant(int $id) : tenant {
        $tenant = $this->get_tenant($id);
        return $this->update_tenant_object($tenant,
            (object)['archived' => 1, 'timearchived' => time()]);
    }

    /**
     * Restores archived tenant
     *
     * @param int $id
     * @return tenant
     * @throws \moodle_exception
     */
    public function restore_tenant(int $id) : tenant {
        global $USER;
        $tenant = new tenant($id);
        if (!$tenant->get('id') || !$tenant->get('archived')) {
            throw new \moodle_exception('tenantnotfound', 'tool_tenant', self::get_base_url());
        }
        $this->update_tenant_object($tenant, (object)['archived' => 0, 'timearchived' => null]);
        unset($USER->tenantid);
        return $tenant;
    }

    /**
     * Deletes archived tenant
     *
     * @param int $id
     * @return tenant
     * @throws \moodle_exception
     */
    public function delete_tenant(int $id) : tenant {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        $tenant = new tenant($id);
        if (!$tenant->get('id') || !$tenant->get('archived')) {
            throw new \moodle_exception('tenantnotfound', 'tool_tenant', self::get_base_url());
        }

        // Delete tenant users associations.
        $DB->delete_records('tool_tenant_user', ['tenantid' => $id]);

        // Delete tenant record, trigger event.
        $event = tenant_deleted::create_from_object($tenant);
        $tenant->delete();
        $event->trigger();

        $this->reset_tenants_cache();
        return $tenant;
    }

    /**
     * Allocate the user to the tenant
     *
     * @param int $userid
     * @param int $tenantid
     * @param string $component component that called this method
     * @param string $reason
     */
    public function allocate_user(int $userid, int $tenantid, string $component, string $reason) {
        if (isguestuser($userid)) {
            return;
        }
        $usertenant = tenant_user::create_for_user($userid);
        $oldrecord = $usertenant->to_record();
        $usertenant->set('tenantid', $tenantid);
        $usertenant->set('component', $component);
        $usertenant->set('reason', $reason);
        $usertenant->save();
        if ($oldrecord->id) {
            tenant_user_updated::create_from_object($usertenant, $oldrecord)->trigger();
        } else {
            tenant_user_created::create_from_object($usertenant)->trigger();
        }
        // Check to see if this user has been assigned a tenant role.
        $this->assign_tenant_user_role($userid, $tenantid);

        $cache = \cache::make('tool_tenant', 'mytenant');
        $cacheidx = 'tenantid-' . $userid;
        $cache->delete($cacheidx);
    }

    /**
     * Assigns a user the tenant user role (while deleting them from all other roles).
     *
     * @param  int $userid The user ID.
     * @param  int $tenantid The tenant ID.
     * @param  int $categoryid The category ID.
     */
    public function assign_tenant_user_role(int $userid, int $tenantid, int $categoryid = null) {
        // Remove from all tool_tenant roles.
        role_unassign_all(['userid' => $userid, 'component' => 'tool_tenant']);

        if (!isset($categoryid)) {
            $tenant = tenancy::get_tenants()[$tenantid];
            $categoryid = $tenant->categoryid;
        }

        // This checks to see if the user is in a tenant with no category id and if so just marks the user context
        // as dirty.
        if (!empty($categoryid)) {
            $context = \context_coursecat::instance($categoryid);
            // Assign the tenant user role.
            \role_assign(self::get_tenant_user_role(), $userid, $context->id, 'tool_tenant', $tenantid);
            \cache_helper::purge_by_event('changesincoursecat');
        }
    }

    /**
     * Base URL to view tenants list
     * @return \moodle_url
     */
    public static function get_base_url() : \moodle_url {
        $url = new \moodle_url('/admin/tool/tenant/index.php');
        return $url;
    }

    /**
     * URL to view a tenant
     * @param int $tenantid
     * @return \moodle_url
     */
    public static function get_view_url(int $tenantid) : \moodle_url {
        return new \moodle_url(self::get_base_url(), ['id' => $tenantid]);
    }

    /**
     * Returns role id for tenant admins, creates if it does not exist
     *
     * @return int
     */
    public static function get_tenant_admin_role() : int {
        global $CFG;
        self::create_tenant_roles();
        return (int)$CFG->tool_tenant_adminrole;
    }

    /**
     * Returns tenantmanager role, creates if it does not exist
     *
     * @return int
     */
    public static function get_tenant_manager_role() : int {
        global $CFG;
        self::create_tenant_roles();
        return (int)$CFG->tool_tenant_managerrole;
    }

    /**
     * Returns the tenantuser role, creates if it does not exist
     *
     * @return int
     */
    public static function get_tenant_user_role() : int {
        global $CFG;
        self::create_tenant_roles();
        return (int)$CFG->tool_tenant_userrole;
    }

    /**
     * List of default capabilities for the tenant admin.
     *
     * @return array A list of default capabilities.
     */
    protected static function get_tenant_admin_capabilities() : array {
        return array_keys(array_filter(role::get_tenant_admin_capabilities()));
    }

    /**
     * Changes core roles to work better in multitenant environment
     */
    public static function change_core_roles() {
        global $CFG;
        require_once($CFG->libdir . '/accesslib.php');

        $capability = 'moodle/category:viewcourselist';
        if (get_capability_info($capability)) {
            $roles = get_all_roles();
            foreach ($roles as $role) {
                $permission = in_array($role->shortname,
                    ['tool_tenant_user', 'tool_tenant_manager', 'manager', 'coursecreator']) ?
                    CAP_ALLOW : CAP_INHERIT;
                assign_capability($capability, $permission, $role->id, \context_system::instance());
            }
        }
    }

    /**
     * Creates a role (if it does not exist) and adds capabilities to it
     *
     * If a role with the same shortname already exists, it is UPDATED (name, description and capabitilies)
     * Note, this function does not check if capabilities exist so it can be called from install.php and update.php
     *
     * @param string $shortname
     * @param string $name
     * @param string $description
     * @param array $capabilities list of capabilities that need to be added to this role. If the role already
     *     exists, these capabilities will be added to it. No capabilities will be removed.
     * @param string $archetype role archetype, by default none
     * @param array $contextlevels assignable context levels, by default [CONTEXT_SYSTEM]
     * @return int id of the role
     */
    protected static function create_role(string $shortname, string $name,
              string $description, array $capabilities, string $archetype = '', ?array $contextlevels = null) : int {
        $context = \context_system::instance();
        $roleid = create_role($name, $shortname, $description, $archetype);

        // Add capabilities to the role.
        foreach ($capabilities as $capability) {
            if (get_capability_info($capability)) {
                assign_capability($capability, CAP_ALLOW, $roleid, $context->id, true);
            }
        }

        // Tenant roles can not be assigned manually.
        set_role_contextlevels($roleid, []);

        return $roleid;
    }

    /**
     * Assign the roles 'tool_tenant_admin' and 'tool_tenant_manager' to admin users.
     *
     * @param  int    $tenantid The tenant ID.
     * @param  array  $userids  IDs of the tenant admins
     */
    public function assign_tenant_admin_role(int $tenantid, array $userids) {
        // Make sure all admins have roles assigned.
        $adminrole = self::get_tenant_admin_role();
        $managerrole = self::get_tenant_manager_role();
        $systemcontext = \context_system::instance();
        foreach ($userids as $userid) {
            role_assign($adminrole, $userid, $systemcontext->id, 'tool_tenant', $tenantid);
            if ($catid = $this->get_tenant($tenantid)->get('categoryid')) {
                role_assign($managerrole, $userid, \context_coursecat::instance($catid)->id, 'tool_tenant', $tenantid);
            }
        }
        \cache_helper::purge_by_event('changesincoursecat');
    }

    /**
     * Adds plugin capabilities to the "Tenant administrator" role
     *
     * This function should only be called from the plugin's install.php
     *
     * @param string $pluginname
     */
    public static function add_plguin_capabilities_to_tenant_admin_role(string $pluginname) {
        global $CFG;
        if (empty($CFG->tool_tenant_adminrole)) {
            // The tenant administrator role has not been created yet. Nothing to do.
            return;
        }

        // Get list of capabilities that are whitelisted for this plugin.
        $capabilities = role::get_plugin_capabilities_for_tenant_admin_role($pluginname, true);
        if (!$capabilities) {
            // This plugin defines no safe capabilities for the "Tenant admninistrator" role. Nothing to do.
            return;
        }

        $roleid = $CFG->tool_tenant_adminrole;
        foreach ($capabilities as $capability => $allow) {
            if (get_capability_info($capability)) {
                assign_capability($capability, $allow, $roleid, \context_system::instance()->id, true);
            }
        }

        // Reset caches.
        accesslib_reset_role_cache();
    }

    /**
     * Lost of roles that tenantmanager can assign in course category context and below
     *
     * @return array
     */
    protected static function get_tenant_manager_roles_associations() : array {
        $roles = get_all_roles();
        $rv = ['view' => [], 'switch' => [], 'override' => [], 'assign' => []];
        foreach ($roles as $role) {
            if (in_array($role->shortname, ['student', 'teacher', 'editingteacher', 'coursecreator'])) {
                $rv['assign'][] = $role->id;
                $rv['view'][] = $role->id;
                $rv['override'][] = $role->id;
            }
            if (in_array($role->shortname, ['tool_tenant_manager'])) {
                $rv['view'][] = $role->id;
            }
            if (in_array($role->shortname, ['student', 'teacher', 'editingteacher', 'guest'])) {
                $rv['switch'][] = $role->id;
            }
            if (in_array($role->shortname, ['user', 'guest', 'frontpage'])) {
                $rv['view'][] = $role->id;
            }
            if (in_array($role->shortname, ['tool_tenant_user'])) {
                $rv['override'][] = $role->id;
            }
            if (in_array($role->shortname, ['manager'])) {
                $rv['view'][] = $role->id;
            }
        }
        return $rv;
    }

    /**
     * Add role assignments, overrides, etc
     *
     * @param int $roleid
     * @param array $roles
     */
    protected static function add_role_assignments(int $roleid, array $roles) {
        global $DB;
        $roles += ['assign' => [], 'override' => [], 'switch' => [], 'view' => []];
        foreach ($roles['assign'] as $otherroleid) {
            if (!$DB->record_exists('role_allow_assign', ['roleid' => $roleid, 'allowassign' => $otherroleid])) {
                core_role_set_assign_allowed($roleid, $otherroleid);
            }
        }
        foreach ($roles['override'] as $otherroleid) {
            if (!$DB->record_exists('role_allow_override', ['roleid' => $roleid, 'allowoverride' => $otherroleid])) {
                core_role_set_override_allowed($roleid, $otherroleid);
            }
        }
        foreach ($roles['switch'] as $otherroleid) {
            if (!$DB->record_exists('role_allow_switch', ['roleid' => $roleid, 'allowswitch' => $otherroleid])) {
                core_role_set_switch_allowed($roleid, $otherroleid);
            }
        }
        foreach ($roles['view'] as $otherroleid) {
            if (!$DB->record_exists('role_allow_view', ['roleid' => $roleid, 'allowview' => $otherroleid])) {
                core_role_set_view_allowed($roleid, $otherroleid);
            }
        }

    }

    /**
     * Creates tenant admin/manager/user role.
     *
     * Can be called from install.php and/or update.php or tool_tenant
     */
    public static function create_tenant_roles() {
        global $CFG;
        if (!empty($CFG->tool_tenant_adminrole) && !empty($CFG->tool_tenant_managerrole) && !empty($CFG->tool_tenant_userrole)) {
            // Already created.
            return;
        }

        // Tenant admin.
        $capabilities = self::get_tenant_admin_capabilities();
        $roleid = self::create_role('tool_tenant_admin', '',
            get_string('tenantadmindescription', 'tool_tenant'), $capabilities);
        set_config('tool_tenant_adminrole', $roleid);

        // Tenant manager.
        $caps = array_keys(get_default_capabilities('manager'));
        $caps[] = 'moodle/category:viewcourselist';
        $roleid = self::create_role('tool_tenant_manager', '',
            get_string('tenantmanagerdescription', 'tool_tenant'),
                $caps, 'manager',
            [CONTEXT_COURSECAT, CONTEXT_COURSE]);
        set_config('tool_tenant_managerrole', $roleid);
        self::add_role_assignments($roleid, self::get_tenant_manager_roles_associations());

        // Tenant user.
        $capabilities = ['moodle/category:viewcourselist'];
        $roleid = self::create_role('tool_tenant_user', '',
            get_string('tenantuserdescription', 'tool_tenant'), $capabilities);
        set_config('tool_tenant_userrole', $roleid);

        // Reset caches.
        accesslib_reset_role_cache();
    }
}
