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
 * Class tenancy.
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_tenant;

defined('MOODLE_INTERNAL') || die();

/**
 * To be used to get information about current tenant and its users
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenancy {

    /** @var int */
    protected static $forcetenantid = 0;

    /**
     * Gets the list of tenants
     *
     * @return \stdClass[]
     */
    public static function get_tenants() : array {
        global $DB;
        $cache = \cache::make('tool_tenant', 'tenants');
        if (!($tenants = $cache->get('list'))) {
            $tenants = $DB->get_records('tool_tenant', ['archived' => 0],
                'isdefault DESC, sortorder, id', 'id, name, idnumber, isdefault, sitename, categoryid, '.
                'siteshortname, useloginurlid, useloginurlidnumber, timemodified');
            $first = reset($tenants);
            if (!$tenants || !$first->isdefault) {
                // Create default tenant.
                $tenant = (new manager())->create_tenant((object)[
                    'name' => get_string('defaultname', 'tool_tenant'),
                    'isdefault' => 1]);
                $tenants = [$tenant->get('id') => $tenant->to_record()] + $tenants;
            }
            $cache->set('list', $tenants);
        }
        return $tenants;
    }

    /**
     * Check if site is configured to have multiple tenants
     *
     * @return bool
     */
    public static function is_site_multi_tenant() : bool {
        $tenants = self::get_tenants();
        return count($tenants) > 1;
    }

    /**
     * Does an SQL query to retrieve tenant id for the given user
     *
     * @param int $userid
     * @return int
     */
    protected static function get_tenant_id_int(int $userid) : int {
        global $DB;
        $tenantid = $DB->get_field_sql("SELECT t.id
            FROM {tool_tenant_user} tu
            JOIN {tool_tenant} t ON tu.tenantid = t.id AND t.archived = 0
            WHERE tu.userid = ?", [$userid]);
        if ($tenantid) {
            return $tenantid;
        }
        return self::get_default_tenant_id();
    }

    /**
     * Id of the tenant user belongs to
     *
     * @param int $userid userid, if omitted current user
     * @return int
     */
    public static function get_tenant_id(?int $userid = null) : int {
        global $USER;

        if (!self::is_site_multi_tenant()) {
            return self::get_default_tenant_id();
        }

        // User is logged in.
        $userid = $userid ?: ($USER ? $USER->id : 0);
        $cache = \cache::make('tool_tenant', 'mytenant');

        // First make sure that the tenant for the current user is in the cache.
        $cacheidx = 'tenantid-' . $USER->id;
        if (!($mytenantid = $cache->get($cacheidx))) {
            $mytenantid = self::get_tenant_id_int($USER->id);
            $cache->set($cacheidx, $mytenantid);
        }

        // Requesting tenant for the current user.
        if ($userid == $USER->id) {
            return $mytenantid;
        }

        // Requesting tenant for another user.
        $otherusers = $cache->get('otherusers-'.$mytenantid);
        $otherusers = is_array($otherusers) ? $otherusers : [];
        if (in_array($userid, $otherusers)) {
            return $mytenantid;
        }

        $usertenantid = self::get_tenant_id_int($userid);
        if ($usertenantid == $mytenantid) {
            $otherusers[] = $userid;
            $cache->set('otherusers-'.$mytenantid, $otherusers);
        }
        return $usertenantid;
    }

    /**
     * In some cases we already know that some other user belongs to the same tenant, mark it as such to reduce queries elsewhere.
     *
     * @param int $userid
     */
    public static function mark_user_as_same_tenant(int $userid) {
        if (!self::is_site_multi_tenant()) {
            return;
        }
        $cache = \cache::make('tool_tenant', 'mytenant');
        $mytenantid = self::get_tenant_id();

        $otherusers = $cache->get('otherusers-'.$mytenantid);
        $otherusers = is_array($otherusers) ? $otherusers : [];
        if (!in_array($userid, $otherusers)) {
            $otherusers[] = $userid;
            $cache->set('otherusers-'.$mytenantid, $otherusers);
        }
    }

    /**
     * Find a tenant that has a given id and also has 'useloginurlid' enabled
     *
     * @param int $value
     * @return int
     */
    protected static function find_tenant_by_id(int $value) {
        $tenants = self::get_tenants();
        foreach ($tenants as $tenant) {
            if ($tenant->useloginurlid && $tenant->id == $value) {
                return $tenant->id;
            }
        }
        return 0;
    }

    /**
     * Find a tenant that has a given idnumber and also has 'useloginurlidnumber' enabled
     *
     * @param string $value
     * @return int
     */
    protected static function find_tenant_by_idnumber(string $value) {
        $tenants = self::get_tenants();
        foreach ($tenants as $tenant) {
            if ($tenant->useloginurlidnumber && $tenant->idnumber === $value) {
                return $tenant->id;
            }
        }
        return 0;
    }

    /**
     * Helps to build SQL to retrieve users that belong to the current tenant
     *
     * Example of usage:
     *
     * $ualias = \tool_wp\db::generate_alias();
     * list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql($ualias);
     * $sql = "SELECT {$ualias}.* FROM {user} {$ualias} " . $join . ' WHERE ' . $where;
     * $DB->get_records_sql($sql, $params);
     *
     * This query never returns deleted users or guest user.
     *
     * @param string $usertablealias
     * @param int $tenantid tenant id, by default tenant of the current user
     * @return array array of three elements [$join, $where, $params]
     */
    public static function get_users_sql(string $usertablealias = 'u', int $tenantid = 0) : array {
        global $CFG;
        static $cnt = 0;
        $cnt++;
        $pg = self::generate_param_name();
        $params = [$pg => (int)$CFG->siteguest];
        $where = " {$usertablealias}.deleted = 0 AND {$usertablealias}.id <> :{$pg} ";
        $join = '';

        $tenants = self::get_tenants();
        if (count($tenants) > 1) {
            $param = self::generate_param_name();
            $tu = self::generate_alias();
            $t = self::generate_alias();
            $params[$param] = $tenantid ?: self::get_tenant_id();
            if ($params[$param] == self::get_default_tenant_id()) {
                $join = " LEFT JOIN {tool_tenant_user} {$tu} ON {$tu}.userid = {$usertablealias}.id " .
                    "LEFT JOIN {tool_tenant} {$t} ON {$t}.id = {$tu}.tenantid AND {$t}.archived = 0";
                $where .= " AND ({$t}.id IS NULL OR {$t}.id = :{$param}) ";
            } else {
                $join = " JOIN {tool_tenant_user} {$tu} ON {$tu}.userid = {$usertablealias}.id AND {$tu}.tenantid = :{$param} ";
            }
        }
        return [$join, $where, $params];
    }

    /**
     * Allows to temporarily "fix" the tenant id in get_users_subquery() calls
     *
     * @param int $tenantid
     */
    public static function force_tenantid_for_users_subquery(int $tenantid = 0) {
        self::$forcetenantid = $tenantid;
    }

    /**
     * Builds SQL to use in WHERE clause to filter users that belong to the specific tenant
     *
     * Note 1: guest user is assumed to belong to the default tenant
     * Note 2: this function does not exclude deleted or suspended users
     *
     * @param bool $canseeall do not add tenant check if user has capability 'tool/tenant:manage'
     * @param bool $andpostfix append " AND " to the end of the query
     * @param string $useridfield field to join with
     * @param int $tenantid id of the tenant to filter or 0 for the current tenant
     * @return string
     */
    public static function get_users_subquery(bool $canseeall = true, bool $andpostfix = true,
                                              string $useridfield = 'u.id', int $tenantid = 0) : string {
        if (!self::is_site_multi_tenant()) {
            return $andpostfix ? '' : '1=1';
        }
        if (!self::$forcetenantid && $canseeall && self::can_view_users_in_all_tenants()) {
            return $andpostfix ? '' : '1=1';
        }
        $tenantid = $tenantid ?: (self::$forcetenantid ?: self::get_tenant_id());
        $defaulttenantid = self::get_default_tenant_id();
        $tu = self::generate_alias();
        if ($tenantid != $defaulttenantid) {
            $query = " {$useridfield} IN (SELECT {$tu}.userid FROM {tool_tenant_user} {$tu}
                WHERE {$tu}.tenantid = {$tenantid})";
        } else {
            $query = " {$useridfield} NOT IN (SELECT {$tu}.userid FROM {tool_tenant_user} {$tu}
                WHERE {$tu}.tenantid <> $defaulttenantid)";
        }

        return $query . ($andpostfix ? ' AND' : '') . ' ';
    }

    /**
     * Returns if user should not be visible to the current user at all because of multitenancy
     *
     * To use in core hacks:
     * component_class_callback('tool_tenant\\tenancy', 'is_user_hidden_by_tenancy', [$user]);
     *
     * @param int|\stdClass $user
     * @param int|null $currentuserid by default current user
     * @return bool
     */
    public static function is_user_hidden_by_tenancy($user, $currentuserid = null): bool {
        if (self::can_view_users_in_all_tenants()) {
            return false;
        }
        return self::get_tenant_id($currentuserid) != self::get_tenant_id(is_object($user) ? $user->id : $user);
    }

    /**
     * Returns the default tenant in the system, all unallocated users belong to this tenant
     *
     * @return int
     */
    public static function get_default_tenant_id() : int {
        $tenants = self::get_tenants();
        $tenantid = key($tenants);
        return $tenantid;
    }

    /**
     * Adds plugin capabilities to the "Tenant administrator" role
     *
     * This function should only be called from the plugin's install.php
     *
     * @param string $pluginname
     */
    public static function add_plugin_capabilities_to_tenant_admin_role(string $pluginname) {
        manager::add_plugin_capabilities_to_tenant_admin_role($pluginname);
    }

    /**
     * Checks if current user can access all tenants
     *
     * This function does not actually exist in tool_tenant
     */
    protected static function can_view_users_in_all_tenants() {
        return has_any_capability(['tool/tenant:manage', 'tool/tenant:allocate'],
            \context_system::instance());
    }
    /**
     * Generates unique table/column alias that must be used in conditions SQL
     *
     * This function does not actually exist in tool_tenant!
     *
     * @return string
     */
    protected static function generate_alias() : string {
        static $cnt = 0;
        return 'wpdba' . ($cnt++);
    }
    /**
     * Generates unique parameter name that must be used in conditions SQL
     *
     * This function does not actually exist in tool_tenant!
     *
     * @return string
     */
    protected static function generate_param_name() : string {
        static $cnt = 0;
        return 'wpdbp' . ($cnt++);
    }
}
