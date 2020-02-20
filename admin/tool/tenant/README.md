# Multitenancy #

THIS IS A MODIFIED VERSION OF tool_tenant PLUGIN FROM MOODLE WORKPLACE INTENDED FOR TESTING
PURPOSES ONLY.

This plugin adds multi-tenancy feature to Moodle sites. Please note that core modifications are
required for this plugin to work and it can not be used outside of Moodle Workplace suite.

## Contributed plugins that can work both with and without Multitenancy plugin

In order to support multitenancy plugins must include the "tenant limitation condition" to any SQL
query that queries data from the {user} table. If the plugin has functionality that allows to interact with
an individual users, the visibility of individual users must also be checked.

### Limiting users in a list

Normal query:

    $sql = "SELECT u.* FROM {user} u WHERE u.deleted = 0";

Modified query:

    // Add tenant condition.
    /** @uses \tool_tenant\tenancy::get_users_subquery */
    $tenantcondition = component_class_callback('tool_tenant\\tenancy', 'get_users_subquery',
        [true, true, 'u.id'], '');
    $sql = "SELECT u.* FROM {user} u WHERE $tenantcondition u.deleted = 0"

How it works:
- If tool_tenant plugin is not present, the class will not be found and the value of $tenantcondition
  will be empty string.
- If user has capability to access all tenants or the site is not multitenant, this method will also
  return empty string
- If user belongs to a tenant and is not allowed to view other tenants the $tenantcondition will return
  an expression that can be used in WHERE clause, it will be followed by "AND" so it can work together with
  the rest of the WHERE clause.

The method tool_tenant\tenancy::get_users_subquery() takes four parameters, see the phpdocs to the method
for more details. Make sure to add unittests that test each of the following situations:
1. Multitenancy plugin is not installed
2. Multitenancy plugin is installed but site is not multitenant
3. Multitenancy plugin is installed and current user is allowed to switch between tenants (i.e. global admin)
4. Multitenancy plugin is installed, there are several tenants, current user belongs to one of the tenants
   and can not switch between tenants

### Checking user's visibility

It is often also necessary to check "visibility" of an individual user, for example, the plugin should
use the get_users_subquery() method in a user selector that allows to pick users to call an AJAX
script but the web service that returns the result of this call should also check that the userid
that is passed to it is not hidden from the current user because of multitenancy. Example:

        /** @uses \tool_tenant\tenancy::is_user_hidden_by_tenancy */
        if (component_class_callback('tool_tenant\\tenancy', 'is_user_hidden_by_tenancy',
                [$otheruserid, $USER->id], false)) {
            throw moodle_exception('nopermissions');
        }

If the multitenancy plugin is not present this callback will return false. Note that this method can
only be used in addition to the regular capability check (such as capability to enrol, view members, notes, etc).

### Checking access inside courses

When user is already enrolled into a course and this user interacts with other enrolled users, multitenancy checks
are no longer needed. Instead, __separate group mode__ must be used to separate users inside the courses.

This means that __activity modules plugins__ do not need to implement anything special for multitenancy support.
However they must ensure that they fully support separate group mode. When groups support is not possible for the
activity type, the plugin authors normally recommend to create one instance per group and restrict access to each
instance based on the group.

Methods to check activity group mode (that takes into account forced course group mode):

    /*1*/ groups_get_activity_groupmode($cm, $course=null)
    /*2*/ $cm->effectivegroupmode // For instances of cm_info, also works with $PAGE->cm

Any user listing inside the activity must respect:
- separate group mode and capability 'moodle/site:accessallgroups'
- whether user can access activity (based on "Access restriction" rules)

Method for filtering users list based on access restrictions:

    $info = new \core_availability\info_module($cm);
    $users = $info->filter_user_list($users);

Method for checking if individual user can access the activity (not to be used inside loops):

    \core_availability\info_module::is_user_visible($cm, $userid)

As for the __enrolment plugins__, they most likely need to be modified to support multitenancy, for example, manual enrolment
method in core has been modified to search only among site users that belong to the same tenant as the current user.

See also documentation about shared courses: https://docs.moodle.org/en/Programs#Shared_courses

### Whitelisting capability as "safe" for the "Tenant administrator" role

In Moodle Workplace administrators within the tenants have special role "Tenant administrator" in the system
context that is assigned to them automatically. Global administrator is able to modify which capabilities
are allowed for this role, however, to avoid misconfiguration, the global administrator can only chose
among the "whitelisted" capabilities.

Each plugin can list its capabilities as safe for the "Tenant administrator" role. This means that:
- this capability can be granted in the system context
- person who has this capability is not able to change any settings that would affect other tenants
- person who has this capability is not able to view, modify or affect any users in other tenants

In order to whitelist a capability the plugin has to create a class \PLUGINNAME\tool_tenant (located in the
PLUGINDIR/classes/tool_tenant.php) with a static method get_tenant_admin_capabilities(). Example:

    public static function get_tenant_admin_capabilities() {
        return [
            'tool/xyz:write' => CAP_ALLOW,
            'tool/xyz:read' => CAP_INHERIT,
        ];
    }

In the example above both capabilities are allowed for the "Tenant administrator" role, and the capability
'write' will be automatically added to the role but only if this plugin is installed at the same time
when tool_tenant plugin is installed.

In many cases the contributed plugin will be installed after the Moodle Workplace is intalled, in this case
we recommend to add install script that adds the necessary capabilities to the "tool_tenant_admin" role.
Example (db/install.php):

    function xmldb_tool_xyz_install() {
        update_capabilities('tool_xyz'); // See MDL-65668 about why this is needed.
        component_class_callback('tool_tenant\\tenancy', 'add_capabilities_to_tenant_admin_role', ['tool_xyz']);
        return true;
    }
