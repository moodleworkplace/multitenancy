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
 * Plugin strings are defined here.
 *
 * @package     tool_tenant
 * @category    string
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die();

$string['accent'] = 'Accent';
$string['accent_help'] = 'The colour used to indicate which item is active in the navigation drawer.';
$string['activetenants'] = 'Active tenants';
$string['addtenant'] = 'Add tenant';
$string['adduser'] = 'New user';
$string['admin'] = 'Administrator';
$string['administrators'] = 'Administrators';
$string['advanced'] = 'Advanced';
$string['allocateusers'] = 'Allocate users';
$string['archivedtenants'] = 'Archived tenants';
$string['archivetenant'] = 'Archive tenant';
$string['assigntenantadmins'] = 'Assign Tenant administrator role';
$string['basicinformation'] = 'Basic information';
$string['brand'] = 'Navigation bar';
$string['brand_help'] = 'The colour used for the top navigation bar.';
$string['button'] = 'Primary buttons';
$string['button_help'] = 'The colour of main action buttons.';
$string['cachedef_mytenant'] = 'Information about the current tenant';
$string['cachedef_tenants'] = 'Tenants list';
$string['cannotarchivetenant'] = 'Cannot archive the default tenant.';
$string['category'] = 'Course category';
$string['category_help'] = 'Tenant administrators will be automatically assigned \'Tenant manager\' role in this course category, they be able to create courses, assign roles, etc.
All tenant users will be automatically assigned \'Tenant user\' role in this category.<br><br>Only categories on the top level can be selected as Tenant categories.<br>
If \'New category\' is selected, a category with the same name as the Tenant name will be created.';
$string['categorynameexist'] = 'Category with the name \'{$a}\' already exists on top level. Please select this category or choose another name';
$string['categorytaken'] = 'This category is assigned to another tenant.';
$string['chooseexistingcategory'] = 'Choose an existing category';
$string['colours'] = 'Colours';
$string['confirmallocateusers'] = 'Are you sure you want to allocate the selected users to the selected tenant?';
$string['confirmarchivetenant'] = 'Are you sure you want to archive the tenant \'{$a}\'? All users allocated to this tenant will be moved to the default tenant.';
$string['confirmassigntenantadmins'] = 'Are you sure you want to assign tenant administrator role to the selected users?';
$string['confirmdeletetenant'] = 'Are you sure you want to delete the tenant \'{$a}\'? This action cannot be undone.';
$string['confirmdeleteuser'] = 'Are you sure you want to delete this user? This action cannot be undone.';
$string['confirmdeleteusers'] = 'Are you sure you want to delete the selected users? This action cannot be undone.';
$string['confirmrestoretenant'] = 'Are you sure you want to restore the tenant \'{$a}\'?';
$string['confirmsuspenduser'] = 'Are you sure you want to suspend this user?';
$string['confirmsuspendusers'] = 'Are you sure you want to suspend the selected users?';
$string['confirmunassigntenantadmins'] = 'Are you sure you want to unassign tenant administrator role from the selected users?';
$string['confirmunsuspenduser'] = 'Are you sure you want to unsuspend this user?';
$string['confirmunsuspendusers'] = 'Are you sure you want to unsuspend the selected users?';
$string['createnewcategory'] = 'Create a new category';
$string['customcss'] = 'Custom CSS';
$string['defaultname'] = 'Default tenant';
$string['deletetenant'] = 'Delete tenant';
$string['deleteuser'] = 'Delete user';
$string['deleteusers'] = 'Delete users';
$string['drawer'] = 'Drawer';
$string['drawer_help'] = 'The colour used for the navigation drawer background.';
$string['drawertext'] = 'Drawer text';
$string['drawertext_help'] = 'The colour used for navigation drawer links and icons when not active.';
$string['editdetails'] = 'Edit details';
$string['edittenant'] = 'Edit tenant \'{$a}\'';
$string['edittenantname'] = 'Edit name';
$string['edituser'] = 'Edit user account';
$string['edituserwithname'] = 'Edit user \'{$a}\'';
$string['enrolinseparategroups'] = 'This course may be shared with other tenants but users from different tenants will be allocated to separate groups';
$string['enrolwithoutgroups'] = 'Users from other tenants may be enrolled in this course and be able to see each other because this course is not in separate groups mode';
$string['errorcannotallocate'] = 'Cannot allocate to tenant';
$string['errorinvalidtenant'] = 'Invalid tenant \'{$a}\'';
$string['eventtenantcreated'] = 'Tenant created';
$string['eventtenantdeleted'] = 'Tenant deleted';
$string['eventtenantupdated'] = 'Tenant updated';
$string['eventtenantusercreated'] = 'User allocated to a tenant';
$string['eventtenantuserupdated'] = 'User allocation to a tenant was changed';
$string['favicon'] = 'Favicon';
$string['footer'] = 'Footer';
$string['footer_help'] = 'The colour used for the footer background.';
$string['footertext'] = 'Footer text';
$string['headerlogo'] = 'Header logo';
$string['idnumber'] = 'ID number';
$string['idnumber_help'] = 'The ID number of a tenant is only used when matching against external systems or in Upload user tool and is not displayed anywhere on the site. If the tenant has an official code name it may be entered, otherwise the field can be left blank.';
$string['images'] = 'Images';
$string['invalidcolour'] = 'This colour code is not in the right format. Please use the #000 or #000000 format.';
$string['loginbackground'] = 'Login background image';
$string['loginlogo'] = 'Login logo';
$string['loginurl'] = 'Login URL';
$string['loginurl_help'] = 'Selected URLs will be displayed to tenant administrators and they can pass them to their users in order to have tenant theming applied from the very beginning. If \'ID number\' is not specified the idnumber link will not be available even if selected. Note that during tenant creation the tenant id is not available.';
$string['management'] = 'Management';
$string['managetenants'] = 'Manage tenants';
$string['managetheme'] = 'Tenant personalisation';
$string['managethemewpmenu'] = 'Tenant';
$string['movebetweentenants'] = 'Move between tenants';
$string['movetenant'] = 'Move tenant \'{$a}\'';
$string['name'] = 'Tenant name';
$string['newname'] = 'New tenant \'{$a}\'';
$string['newnamefor'] = 'New name for \'{$a}\'';
$string['nocategory'] = 'No category';
$string['nomanualassignment'] = 'This role can not be assigned manually in any context';
$string['notspecified'] = 'Not specified';
$string['organisationadmintab'] = 'Organisation';
$string['pluginname'] = 'Multi-tenancy';
$string['primary'] = 'Links';
$string['primary_help'] = 'The colour used for links and interactive elements.';
$string['privacy:metadata:user'] = 'Allocation of users to tenants';
$string['privacy:metadata:user:component'] = 'Component responsible for allocation';
$string['privacy:metadata:user:id'] = 'ID';
$string['privacy:metadata:user:reason'] = 'Reason for allocation';
$string['privacy:metadata:user:tenantid'] = 'Tenant';
$string['privacy:metadata:user:timecreated'] = 'Time allocated';
$string['privacy:metadata:user:timemodified'] = 'Time modified';
$string['privacy:metadata:user:userid'] = 'User';
$string['privacy:metadata:user:usermodified'] = 'User who modified the record';
$string['reg_wptenants'] = 'Number of tenants ({$a})';
$string['restoretenant'] = 'Restore tenant';
$string['selectuser'] = 'Select user \'{$a}\'';
$string['separatetenantsingroups'] = 'In courses shared between tenants, add users from each tenant to separate groups';
$string['sitename'] = 'Site name';
$string['sitename_help'] = 'Allows to overwrite the the default name of the site for users from this tenant';
$string['siteshortname'] = 'Site short name';
$string['siteshortname_help'] = 'Allows to overwrite the the default short name of the site for users from this tenant';
$string['suspenduser'] = 'Suspend user';
$string['suspendusers'] = 'Suspend users';
$string['tenant:allocate'] = 'Allocate users to all tenants';
$string['tenant:browseusers'] = 'Browse users in the current tenant';
$string['tenant:manage'] = 'Manage the addition and editing of tenants';
$string['tenant:managetheme'] = 'Manage theme settings for the current tenant';
$string['tenant:manageusers'] = 'Add and edit users for the current tenant';
$string['tenantadmin'] = 'Tenant administrator';
$string['tenantadmincapabilitieslimit'] = 'Capabilities that are not compatible with Multi-tenancy are not listed here. <a href="{$a}">More info</a>';
$string['tenantadmindescription'] = 'The workplace tenant administrator role. Assigned automatically to the tenant admin in the system context.';
$string['tenantadministration'] = 'Tenant administration';
$string['tenantadministrator'] = 'This user is a tenant administrator';
$string['tenantcategorycapabilitieslimit'] = 'Only capabilities that can be set in the course category context are listed here';
$string['tenantdetails'] = 'Details';
$string['tenantlimit'] = 'Tenant limit';
$string['tenantlimit1'] = '1 (Multi-tenancy disabled)';
$string['tenantlimit_desc'] = 'Maximum number of tenants allowed in the system, both active and archived tenants are counted.';
$string['tenantlimitenabled'] = 'Enable tenant limit';
$string['tenantlimitenabled_desc'] = 'If this is enabled it\'s possible to limit number of tenants on this site.';
$string['tenantlimitreached'] = 'Tenant limit reached';
$string['tenantlimitreached1'] = 'Multi-tenancy feature is not enabled on this site';
$string['tenantlimitreachedmult'] = 'You can only create {$a} tenants on this site. Please note that archived tenants are also counted towards this limit';
$string['tenantlimitunlimited'] = 'Unlimited';
$string['tenantmanager'] = 'Tenant manager';
$string['tenantmanagerdescription'] = 'The workplace tenant manager role. Assigned automatically to the tenant admin in the context on their course category.';
$string['tenantnotfound'] = 'Tenant not found';
$string['tenants'] = 'Tenants';
$string['tenantuser'] = 'Tenant user';
$string['tenantuserdescription'] = 'The workplace tenant user role. Assigned automatically to all tenant users in the context on their course category.';
$string['themesettingssaved'] = 'Theme settings were saved. It may take several minutes before changes are visible on the site.';
$string['unassigntenantadmins'] = 'Unassign Tenant administrator role';
$string['unsuspenduser'] = 'Unsuspend user';
$string['unsuspendusers'] = 'Unsuspend users';
$string['userdeletedfail'] = '{$a} user(s) were not found or could not be deleted';
$string['userdeletedsuccess'] = '{$a} user(s) were deleted';
$string['usermovetotenant'] = '{$a->count} users were moved to tenant: {$a->tenant}';
$string['usersassignedtenantadminfail'] = 'Tenant administrator role was not assigned to {$a} user(s)';
$string['usersassignedtenantadminsuccess'] = 'Tenant administrator role was assigned to {$a} user(s)';
$string['userscount'] = 'Users';
$string['userssuspendedfail'] = '{$a} user(s) were not found or could not be suspended';
$string['userssuspendedsuccess'] = '{$a} user(s) were suspended';
$string['usersunassignedtenantadminfail'] = 'Tenant administrator role was not unassigned from {$a} users';
$string['usersunassignedtenantadminsuccess'] = 'Tenant administrator role was unassigned from {$a} users';
$string['usersunsuspendedfail'] = '{$a} user(s) were not found or could not be unsuspended';
$string['usersunsuspendedsuccess'] = '{$a} user(s) were unsuspended';
$string['usersuspendedsuccess'] = 'User suspended successfully';
$string['userunsuspendedsuccess'] = 'User unsuspended successfully';
$string['viewusers'] = 'Manage tenant \'{$a}\'';
