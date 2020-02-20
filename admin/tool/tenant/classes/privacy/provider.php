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
 * Privacy Subsystem implementation for tool_tenant.
 *
 * @package    tool_tenant
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_tenant\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Class provider
 *
 * @package    tool_tenant
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'tool_tenant_user',
            [
                'id' => 'privacy:metadata:user:id',
                'userid' => 'privacy:metadata:user:userid',
                'tenantid' => 'privacy:metadata:user:tenantid',
                'component' => 'privacy:metadata:user:component',
                'reason' => 'privacy:metadata:user:reason',
                'timemodified' => 'privacy:metadata:user:timemodified',
                'timecreated' => 'privacy:metadata:user:timecreated',
                'usermodified' => 'privacy:metadata:user:usermodified',
            ],
            'privacy:metadata:user'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int         $userid The user to search.
     * @return  contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        $contextlist = new contextlist();

        // I've opted for the system context as the tenant may not always be in a category, and storing this information
        // when exported under the system directory makes more sense.

        $sql = "SELECT COUNT(1)
                  FROM {tool_tenant_user}
                 WHERE userid = :userid OR usermodified = :adminuser";

        if ($DB->count_records_sql($sql, ['userid' => $userid, 'adminuser' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        $context = null;
        foreach ($contextlist->get_contexts() as $currentcontext) {
            if ($currentcontext->contextlevel == CONTEXT_SYSTEM) {
                $context = $currentcontext;
                break;
            }
        }
        if (!isset($context)) {
            return;
        }

        $sql = "SELECT tu.id, t.id as tenantid, t.name, t.sitename, tu.userid, tu.timecreated, tu.timemodified, tu.usermodified
                    FROM {tool_tenant} t
                    JOIN {tool_tenant_user} tu ON tu.tenantid = t.id
                   WHERE tu.userid = :userid OR tu.usermodified = :adminid";

        $records = $DB->get_recordset_sql($sql, ['userid' => $user->id, 'adminid' => $user->id]);
        $stores = [];
        foreach ($records as $record) {
            if (!isset($stores[$record->tenantid])) {
                $stores[$record->tenantid] = [
                    'tenantid' => $record->tenantid,
                    'tenant_name' => $record->name,
                    'tenant_sitename' => $record->sitename,
                ];
            }
            $stores[$record->tenantid]['tenant_users'][$record->id] = [
                'tenant_user' => transform::user($record->userid),
                'time_added' => transform::datetime($record->timecreated),
                'time_last_modified' => transform::datetime($record->timemodified),
                'modified_by' => transform::user($record->usermodified)
            ];
        }
        $records->close();
        foreach ($stores as $store) {
            $directories = [get_string('tenants', 'tool_tenant'), $store['tenant_name'] . $store['tenantid']];
            writer::with_context($context)->export_data($directories, (object) $store);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        // Delete all users from tenants.
        $DB->delete_records('tool_tenant_user');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $context = null;
        foreach ($contextlist->get_contexts() as $currentcontext) {
            if ($currentcontext->contextlevel == CONTEXT_SYSTEM) {
                $context = $currentcontext;
                break;
            }
        }
        if (!isset($context)) {
            return;
        }

        // Only delete records for the users assigned to a tenant.
        $DB->delete_records('tool_tenant_user', ['userid' => $user->id]);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $sql = "SELECT userid, usermodified
                  FROM {tool_tenant_user}";
        $userlist->add_from_sql('userid', $sql, []);
        $userlist->add_from_sql('usermodified', $sql, []);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $DB->delete_records_list('tool_tenant_user', 'userid', $userlist->get_userids());
    }
}