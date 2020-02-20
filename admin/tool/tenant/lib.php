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
 * Plugin callbacks.
 *
 * @package     tool_tenant
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of callback control_view_profile
 *
 * Prevents users from different tenants to view each others profiles
 *
 * @param stdClass $user The other user's details.
 * @param stdClass $course if provided, only check permissions in this course.
 * @param context $usercontext The user context if available.
 * @return int
 */
//function tool_tenant_control_view_profile($user, $course, $usercontext) {
//    global $USER;
//    if (\tool_tenant\permission::can_view_tenants_list()) {
//        // Always allow to view user profiles.
//        return core_user::VIEWPROFILE_FORCE_ALLOW;
//    }
//    $tenantid = \tool_tenant\tenancy::get_tenant_id($user->id);
//    if ($user->id != $USER->id &&
//            \tool_tenant\tenancy::get_tenant_id() != $tenantid) {
//        return core_user::VIEWPROFILE_PREVENT;
//    }
//    if (\tool_tenant\permission::can_browse_users($tenantid)) {
//        // This is a tenant admin. Always allow to view user profiles.
//        return core_user::VIEWPROFILE_FORCE_ALLOW;
//    }
//    return core_user::VIEWPROFILE_DO_NOT_PREVENT;
//}

/**
 * Callback executed from setup.php, available from Moodle 3.8 in core
 */
function tool_tenant_after_config() {
    global $SITE, $COURSE, $CFG;
    if (during_initial_install() || isset($CFG->upgraderunning)) {
        return;
    }

    // Prepare the current tenant.
    try {
        $tenantid = \tool_tenant\tenancy::get_tenant_id();
    } catch (\Exception $e) {
        // We are probably inside the plugin installation.
        return;
    }
    if (isset($SITE)) {
        $tenants = \tool_tenant\tenancy::get_tenants();
        $tenant = $tenants[$tenantid];
        $SITE->fullname = $tenant->sitename ?: $SITE->fullname;
        $SITE->shortname = $tenant->siteshortname ?: $SITE->shortname;

        if (isset($COURSE->id) && $COURSE->id == $SITE->id) {
            $COURSE->fullname = $tenant->sitename ?: $SITE->fullname;
            $COURSE->shortname = $tenant->siteshortname ?: $SITE->shortname;
        }
    }
}
