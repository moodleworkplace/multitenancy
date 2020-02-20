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
 * File containing tests for tool_tenant\tenancy class.
 *
 * @package     tool_tenant
 * @category    test
 * @copyright   2018 Moodle Pty Ltd <support@moodle.com>
 * @author      2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license     Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the tool_tenant\tenancy class methods.
 *
 * @package    tool_tenant
 * @copyright  2018 Moodle Pty Ltd <support@moodle.com>
 * @author     2018 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    Moodle Workplace License, distribution is restricted, contact support@moodle.com
 */
class tool_tenant_tenancy_testcase extends advanced_testcase {

    /**
     * Set up
     */
    protected function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Returns the tenant generator
     *
     * @return tool_tenant_generator
     */
    protected function get_generator(): tool_tenant_generator {
        return $this->getDataGenerator()->get_plugin_generator('tool_tenant');
    }

    /**
     * Test for funciton get_tenant_id()
     */
    public function test_single_tenant() {
        global $USER, $DB;
        $this->setAdminUser();

        $tenants = (new \tool_tenant\manager())->get_tenants();
        $tenant = reset($tenants);
        $this->assertEquals($tenant->get('id'), \tool_tenant\tenancy::get_tenant_id());
        $this->assertEquals($tenant->get('id'), \tool_tenant\tenancy::get_default_tenant_id());

        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql('uu');
        $sql = 'SELECT uu.id FROM {user} uu ' . $join . ' WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params);
        $this->assertEquals([$USER->id], $users);
    }
    
    protected function generate_alias() {
        static $base = null;
        if ($base === null) {
            $base = rand(1000, 9999);
        }
        $base++;
        return 'randparam' . $base;
    }

    /**
     * Test for function get_users_sql()
     */
    public function test_get_users_sql() {
        global $DB, $USER;
        $this->setAdminUser();
        $adminuserid = $USER->id;

        $defaulttenantid = \tool_tenant\tenancy::get_default_tenant_id();
        $othertenantid = $this->get_generator()->create_tenant()->id;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        // User1 is not specifically allocated (neither is admin).
        // User2 is allocated to the default tenant.
        $this->get_generator()->allocate_user($user2->id, $defaulttenantid);
        // User3 and user4 are allocated to the other tenant.
        $this->get_generator()->allocate_user($user3->id, $othertenantid);
        $this->get_generator()->allocate_user($user4->id, $othertenantid);

        // For default tenant.
        $ualias = $this->generate_alias();
        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql($ualias, $defaulttenantid);
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} " . $join . ' WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params);
        $this->assertEquals([$adminuserid, $user1->id, $user2->id], $users);

        // Double join produces no errors.
        list($join2, $where2, $params2) = \tool_tenant\tenancy::get_users_sql($ualias, $defaulttenantid);
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} " . $join . $join2 .
                ' WHERE ' . $where . ' AND ' . $where2 . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params + $params2);
        $this->assertEquals([$adminuserid, $user1->id, $user2->id], $users);

        // For other tenant.
        $ualias = $this->generate_alias();
        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql($ualias, $othertenantid);
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} " . $join . ' WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params);
        $this->assertEquals([$user3->id, $user4->id], $users);

        // Double join produces no errors.
        list($join2, $where2, $params2) = \tool_tenant\tenancy::get_users_sql($ualias, $othertenantid);
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} " . $join . $join2 .
                ' WHERE ' . $where . ' AND ' . $where2 . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params + $params2);
        $this->assertEquals([$user3->id, $user4->id], $users);

        // Archive one tenant and users that were allocated to it now appear in the default tenant.
        (new \tool_tenant\manager())->archive_tenant($othertenantid);
        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql('u', $defaulttenantid);
        $sql = 'SELECT u.id FROM {user} u ' . $join . ' WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params);
        $this->assertEquals([$adminuserid, $user1->id, $user2->id, $user3->id, $user4->id], $users);

        // Restore it and users are back where they were.
        (new \tool_tenant\manager())->restore_tenant($othertenantid);
        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql('u', $defaulttenantid);
        $sql = 'SELECT u.id FROM {user} u ' . $join . ' WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params);
        $this->assertEquals([$adminuserid, $user1->id, $user2->id], $users);

        list($join, $where, $params) = \tool_tenant\tenancy::get_users_sql('uu', $othertenantid);
        $sql = 'SELECT uu.id FROM {user} uu ' . $join . ' WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, $params);
        $this->assertEquals([$user3->id, $user4->id], $users);
    }

    /**
     * Test for function get_users_sql()
     */
    public function test_get_tenant_id() {
        global $DB, $USER;
        $this->setAdminUser();

        $defaulttenantid = \tool_tenant\tenancy::get_default_tenant_id();
        $othertenantid = $this->get_generator()->create_tenant()->id;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        // User1 is not specifically allocated (neither is admin).
        // User2 is allocated to the default tenant.
        $this->get_generator()->allocate_user($user2->id, $defaulttenantid);
        // User3 and user4 are allocated to the other tenant.
        $this->get_generator()->allocate_user($user3->id, $othertenantid);
        $this->get_generator()->allocate_user($user4->id, $othertenantid);

        // For user who is not allocated.
        $this->assertEquals($defaulttenantid, \tool_tenant\tenancy::get_tenant_id($user1->id));

        // For user who is allocated to the default tenant.
        $this->assertEquals($defaulttenantid, \tool_tenant\tenancy::get_tenant_id($user2->id));

        // For user who is allocated to the other tenant.
        $this->assertEquals($othertenantid, \tool_tenant\tenancy::get_tenant_id($user3->id));

        // Archive one tenant and users that were allocated to it now appear in the default tenant.
        (new \tool_tenant\manager())->archive_tenant($othertenantid);
        $this->assertEquals($defaulttenantid, \tool_tenant\tenancy::get_tenant_id($user3->id));
        $this->assertEquals($defaulttenantid, \tool_tenant\tenancy::get_tenant_id($user4->id));
        $this->assertEquals($defaulttenantid, \tool_tenant\tenancy::get_tenant_id($user2->id));

        // Restore it and users are back where they were.
        (new \tool_tenant\manager())->restore_tenant($othertenantid);
        $this->assertEquals($othertenantid, \tool_tenant\tenancy::get_tenant_id($user3->id));
        $this->assertEquals($othertenantid, \tool_tenant\tenancy::get_tenant_id($user4->id));
        $this->assertEquals($defaulttenantid, \tool_tenant\tenancy::get_tenant_id($user2->id));
    }

    /**
     * Test for function get_users_sql() for the current user
     */
    public function test_get_tenant_id_self() {
        $defaulttenantid = \tool_tenant\tenancy::get_default_tenant_id();
        $othertenantid = $this->get_generator()->create_tenant()->id;
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->assertEquals($defaulttenantid, \tool_tenant\tenancy::get_tenant_id());

        $this->get_generator()->allocate_user($user1->id, $othertenantid);
        $this->assertEquals($othertenantid, \tool_tenant\tenancy::get_tenant_id());
    }

    /**
     * Test for function get_users_sql()
     */
    public function test_get_users_subquery() {
        global $DB, $USER, $CFG;
        $this->setAdminUser();
        $adminuserid = $USER->id;
        $guestuserid = $CFG->siteguest;

        $defaulttenantid = \tool_tenant\tenancy::get_default_tenant_id();
        $othertenantid = $this->get_generator()->create_tenant()->id;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        // User1 is not specifically allocated (neither is admin).
        // User2 is allocated to the default tenant.
        $this->get_generator()->allocate_user($user2->id, $defaulttenantid);
        // User3 and user4 are allocated to the other tenant.
        $this->get_generator()->allocate_user($user3->id, $othertenantid);
        $this->get_generator()->allocate_user($user4->id, $othertenantid);

        // For default tenant.
        $ualias = $this->generate_alias();
        $where = \tool_tenant\tenancy::get_users_subquery(false, false, "{$ualias}.id");
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} WHERE " . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$guestuserid, $adminuserid, $user1->id, $user2->id], $users);

        // Double usage produces no errors.
        $where2 = \tool_tenant\tenancy::get_users_subquery(false, false, "{$ualias}.id");
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} " .
            ' WHERE ' . $where . ' AND ' . $where2 . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$guestuserid, $adminuserid, $user1->id, $user2->id], $users);

        // For other tenant.
        $ualias = $this->generate_alias();
        $where = \tool_tenant\tenancy::get_users_subquery(false, false, "{$ualias}.id", $othertenantid);
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} WHERE $where ORDER BY id";
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$user3->id, $user4->id], $users);

        // Double usage produces no errors.
        $where2 = \tool_tenant\tenancy::get_users_subquery(false, false, "{$ualias}.id", $othertenantid);
        $sql = "SELECT {$ualias}.id FROM {user} {$ualias} " .
            ' WHERE ' . $where . ' AND ' . $where2 . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$user3->id, $user4->id], $users);

        // Archive one tenant and users that were allocated to it now appear in the default tenant.
        (new \tool_tenant\manager())->archive_tenant($othertenantid);
        $where = \tool_tenant\tenancy::get_users_subquery(false, false, "{$ualias}.id");
        $sql = 'SELECT u.id FROM {user} u WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$guestuserid, $adminuserid, $user1->id, $user2->id, $user3->id, $user4->id], $users);

        // Restore it and users are back where they were.
        (new \tool_tenant\manager())->restore_tenant($othertenantid);
        $where = \tool_tenant\tenancy::get_users_subquery(false, false, "u.id");
        $sql = 'SELECT u.id FROM {user} u WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$guestuserid, $adminuserid, $user1->id, $user2->id], $users);

        $where = \tool_tenant\tenancy::get_users_subquery(false, false, "uu.id", $othertenantid);
        $sql = 'SELECT uu.id FROM {user} uu WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$user3->id, $user4->id], $users);

        // Admin user can "see all".
        $this->setAdminUser();
        $where = \tool_tenant\tenancy::get_users_subquery(true, false, "u.id");
        $sql = 'SELECT u.id FROM {user} u WHERE ' . $where . ' ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$guestuserid, $adminuserid, $user1->id, $user2->id, $user3->id, $user4->id], $users);

        // Testing with "andpostfix".
        $where = \tool_tenant\tenancy::get_users_subquery(true, true, "u.id");
        $sql = 'SELECT u.id FROM {user} u WHERE ' . $where . ' u.deleted=0 ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$guestuserid, $adminuserid, $user1->id, $user2->id, $user3->id, $user4->id], $users);

        $where = \tool_tenant\tenancy::get_users_subquery(false, true, "u.id");
        $sql = 'SELECT u.id FROM {user} u WHERE ' . $where . ' u.deleted=0 ORDER BY id';
        $users = $DB->get_fieldset_sql($sql, []);
        $this->assertEquals([$guestuserid, $adminuserid, $user1->id, $user2->id], $users);
    }

}