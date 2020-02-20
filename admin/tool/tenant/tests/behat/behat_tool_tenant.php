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
 * tool_tenant steps definitions.
 *
 * @package    tool_tenant
 * @category   test
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use \Behat\Gherkin\Node\TableNode;

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Steps definitions for tool_tenant.
 *
 * @package    tool_tenant
 * @category   test
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_tenant extends behat_base {

    /**
     * Returns the tenant generator
     * @return tool_tenant_generator
     */
    protected function get_generator() : tool_tenant_generator {
        $datagenerator = testing_util::get_data_generator();
        return $datagenerator->get_plugin_generator('tool_tenant');
    }

    /**
     * Generates tenants with given names
     *
     * @Given /^the following tenants exist:$/
     *
     * @param TableNode $data
     */
    public function the_following_tenants_exist(TableNode $data) {
        $generator = $this->get_generator();

        // Every test that uses this step will use the same setup as the default workplace installation.
        \tool_tenant\manager::change_core_roles();

        foreach ($data->getHash() as $elementdata) {
            $tenant = (object)array_diff_key($elementdata, ['category' => 1]);
            if (!empty($elementdata['category'])) {
                $tenant->categoryid = $this->get_category_id($elementdata['category']);
            }
            if (!empty($elementdata['idnumber'])) {
                $tenant->idnumber = $elementdata['idnumber'];
            }
            $generator->create_tenant($tenant);
        }
    }

    /**
     * Gets the user id from it's username.
     * @throws Exception
     * @param string $username
     * @return int
     */
    protected function get_user_id($username) {
        global $DB;

        if (!$id = $DB->get_field('user', 'id', array('username' => $username))) {
            throw new Exception('The specified user with username "' . $username . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the tenant id from it's name.
     * @throws Exception
     * @param string $tenantname
     * @return int
     */
    protected function get_tenant_id($tenantname) {
        global $DB;

        if (!$id = $DB->get_field('tool_tenant', 'id', array('name' => $tenantname))) {
            throw new Exception('The specified tenant with name "' . $tenantname . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the category id from it's name.
     *
     * @throws Exception
     * @param string $categoryname
     * @return int
     */
    protected function get_category_id($categoryname) {
        global $DB;

        if (!$id = $DB->get_field('course_categories', 'id', array('name' => $categoryname))) {
            throw new Exception('The specified category with name "' . $categoryname . '" does not exist');
        }
        return $id;
    }

    /**
     * Allocates users to tenants
     *
     * @Given /^the following users allocations to tenants exist:$/
     *
     * @param TableNode $data
     */
    public function the_following_user_allocations_to_tenants_exist(TableNode $data) {
        $generator = $this->get_generator();

        foreach ($data->getHash() as $elementdata) {
            $generator->allocate_user($this->get_user_id($elementdata['user']), $this->get_tenant_id($elementdata['tenant']));
        }
    }

    /**
     * Create a course from backup file with completion enabled
     *
     * @param int $categoryid
     * @param string $fullname
     * @param string $shortname
     */
    protected function create_course($categoryid, $fullname, $shortname) {
        global $CFG;

        $data = array('backupfile' => $CFG->dirroot . '/admin/tool/tenant/tests/fixtures/backup.mbz',
            'summary' => '', 'category' => $categoryid, 'fullname' => $fullname, 'shortname' => $shortname,
            'enablecompletion' => true, 'format' => 'topics');
        $mode = tool_uploadcourse_processor::MODE_CREATE_NEW;
        $updatemode = tool_uploadcourse_processor::UPDATE_ALL_WITH_DATA_ONLY;
        $co = new tool_uploadcourse_course($mode, $updatemode, $data);
        $co->prepare();
        $co->proceed();
    }

    /**
     * Quickly create several tenants, users and courses
     *
     * @Given /^"(?P<tenant_number>\d+)" tenants exist with "(?P<users_number>\d+)" users and "(?P<courses_number>\d+)" courses in each$/
     *
     * @param int $tenants
     * @param int $users
     * @param int $courses
     */
    public function tenants_exist_with_users_and_courses_in_each($tenants, $users, $courses) {
        // Create categories and tenants.
        $categories = [['name', 'category', 'idnumber']];
        $tenantsrows = [['name', 'category', 'idnumber']];
        for ($i = 1; $i <= $tenants; $i++) {
            $categories[] = ["Category{$i}", '0', "CAT{$i}"];
            $tenantsrows[] = ['Tenant' . $i, "Category{$i}", 'tenant' . $i];
        }
        $this->execute("behat_data_generators::the_following_entities_exist", ["categories", new TableNode($categories)]);
        $this->execute("behat_tool_tenant::the_following_tenants_exist", new TableNode($tenantsrows));

        // Create and allocate users.
        $usersdata = [['username', 'firstname', 'lastname', 'email']];
        $usertenants = [['user', 'tenant']];

        for ($i = 1; $i <= $tenants; $i++) {
            $usersdata[] = ["tenantadmin{$i}", 'Tenantadmin', $i, "tenantadmin{$i}@invalid.com"];
            $usertenants[] = ["tenantadmin{$i}", "Tenant{$i}"];
            for ($j = 1; $j <= $users - 1; $j++) {
                $usersdata[] = ["user{$i}{$j}", 'User', $i . $j, "user{$i}{$j}@invalid.com"];
                $usertenants[] = ["user{$i}{$j}", "Tenant{$i}"];
            }
        }

        $this->execute("behat_data_generators::the_following_entities_exist", ["users", new TableNode($usersdata)]);
        $this->execute("behat_tool_tenant::the_following_user_allocations_to_tenants_exist", new TableNode($usertenants));

        // Assign admins and create courses.
        $manager = new \tool_tenant\manager();
        for ($i = 1; $i <= $tenants; $i++) {
            $tenantid = $this->get_tenant_id("Tenant{$i}");
            $userid = $this->get_user_id("tenantadmin{$i}");
            $categoryid = $this->get_category_id("Category{$i}");
            // Assign admins to tenants, set category.
            $manager->assign_tenant_admin_role($tenantid, [$userid]);
            // Create courses.
            for ($j = 1; $j <= $courses; $j++) {
                $this->create_course($categoryid, "Course{$i}{$j}", "C{$i}{$j}");
            }
        }
    }
}
