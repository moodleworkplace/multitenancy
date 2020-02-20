@tool @tool_tenant @javascript
Feature: Test multitenancy core patches
  As an admin
  I want to be able to create, update, archive and delete tenants

  Background:
    Given "2" tenants exist with "5" users and "3" courses in each

  Scenario: User inside a tenant can only enrol users from the same tenant
    When I log in as "tenantadmin1"
    And I am on "Course11" course homepage
    And I navigate to course participants
    And I press "Enrol users"
    And I click on ".form-autocomplete-downarrow" "css_element" in the ".modal-dialog" "css_element"
    And I should not see "User 2"
    And I should not see "Tenantadmin2"
    And I should not see "Admin user"
    And I click on "User 11" "text" in the ".modal-dialog .form-autocomplete-suggestions" "css_element"
    And I click on "Tenantadmin 1" "text" in the ".modal-dialog .form-autocomplete-suggestions" "css_element"
    And I press key "27" in the field "Select users"
    And I click on "Enrol users" "button" in the "Enrol users" "dialogue"
    Then I should see "Student" in the "User 11" "table_row"
    And I should not see "Tenant user"
    And I should see "Student" in the "Tenantadmin 1" "table_row"
    And I should see "Tenant manager" in the "Tenantadmin 1" "table_row"
    And I navigate to "Users > Enrolment methods" in current page administration
    And I should see "2" in the "Manual enrolments" "table_row"
    And I click on "Enrol users" "link" in the "Manual enrolments" "table_row"
    And "optgroup[label='Not enrolled users (3)']" "css_element" should exist in the "#addselect" "css_element"
    And "optgroup[label='Enrolled users (2)']" "css_element" should exist in the "#removeselect" "css_element"
    And the "Not enrolled users" select box should contain "User 12 (user12@invalid.com)"
    And the "Not enrolled users" select box should contain "User 13 (user13@invalid.com)"
    And the "Not enrolled users" select box should contain "User 14 (user14@invalid.com)"
    And the "Enrolled users" select box should contain "Tenantadmin 1 (tenantadmin1@invalid.com)"
    And the "Enrolled users" select box should contain "User 11 (user11@invalid.com)"
    And I should not see "User 2"
    And I set the field "Not enrolled users" to "User 12"
    And I press "Add"
    And "optgroup[label='Not enrolled users (2)']" "css_element" should exist in the "#addselect" "css_element"
    And "optgroup[label='Enrolled users (3)']" "css_element" should exist in the "#removeselect" "css_element"
    And the "Enrolled users" select box should contain "User 12 (user12@invalid.com)"
    And I follow "Participants"
    And I should see "Student" in the "User 12" "table_row"
    And I log out

  @_file_upload
  Scenario: User inside a tenant can only award badges to users from the same tenant
    When I log in as "admin"
    And I navigate to "Badges > Add a new badge" in site administration
    And I set the following fields to these values:
      | Name | Site Badge 1 |
      | Description | Site badge 1 description |
      | issuername | Tester of site badge |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I set the field "type" to "Manual issue by role"
    And I set the field "Tenant administrator" to "1"
    And I press "Save"
    And I press "Enable access"
    And I press "Continue"
    And I log out
    When I log in as "tenantadmin1"
    And I navigate to "Badges > Manage badges" in site administration
    And I click on "0" "link" in the "Site Badge 1" "table_row"
    And I press "Award badge"
    Then I should see "Tenantadmin 1"
    And I should see "User 11"
    And I should see "User 12"
    And I should see "User 13"
    And I should see "User 14"
    And I should not see "Tenantadmin 2"
    And I should not see "User 21"
    And I should not see "User 22"
    And I should not see "User 23"
    And I should not see "User 24"
    And I set the field "potentialrecipients[]" to "User 11 (user11@invalid.com)"
    And I press "Award badge"
    And I follow "Site Badge 1"
    And I follow "Recipients (1)"
    And I should see "User 11"
    And I log out
    And I log in as "tenantadmin2"
    And I navigate to "Badges > Manage badges" in site administration
    And I should see "0" in the "Site Badge 1" "table_row"
    And I follow "Site Badge 1"
    And I should see "This badge has not been earned yet."
    And I follow "Recipients (0)"
    And I should not see "User 11"

  Scenario: Admin can modify tenant roles but can not add capabilities that are not whitelisted to Tenant administrator role
    When I log in as "admin"
    And I navigate to "Users > Permissions > Define roles" in site administration
    And I follow "Tenant administrator"
    Then "tool_tenant_admin" "text" should exist in the "Short name" "form_row"
    And "Tenant administrator" "text" should exist in the "Custom full name" "form_row"
    And "None" "text" should exist in the "Allow role overrides" "form_row"
    And "None" "text" should exist in the "Allow role switches" "form_row"
    And "Allow" "text" should exist in the "moodle/site:configview" "table_row"
    And I press "Edit"
    And "This role can not be assigned manually in any context" "text" should exist in the "//div[contains(@class, 'fitem row') and contains(.,'Context types where this role may be assigned')]" "xpath_element"
    And "select" "css_element" should exist in the "Allow role assignments" "form_row"
    And "select" "css_element" should not exist in the "Allow role overrides" "form_row"
    And "select" "css_element" should not exist in the "Allow role switches" "form_row"
    And "select" "css_element" should exist in the "Allow role to view" "form_row"
    # Custom full name is actually empty.
    And the field "Custom full name" does not match value "e"
    # Core capability that can be set for this role:
    And I should see "moodle/site:configview"
    # Core capability that can never be set for this role:
    And I should not see "moodle/user:create"
    And I should see "Capabilities that are not compatible with Multi-tenancy are not listed here"

  Scenario: Admin can not manually assign any tenant roles because these roles can only be assigned automatically when allocating to tenant
    When I log in as "admin"
    And I navigate to "Users > Permissions > Assign system roles" in site administration
    Then I should see "Manager"
    And I should see "Course creator"
    And "Manager" "link" should exist in the "admintable" "table"
    And "Course creator" "link" should exist in the "admintable" "table"
    And "Tenant admnistrator" "link" should not exist in the "admintable" "table"
    And "Tenant" "text" should not exist in the "admintable" "table"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I click on "assignroles" action for "Miscellaneous" in management category listing
    And I should see "Assign roles in Category: Miscellaneous"
    And "Manager" "link" should exist in the "admintable" "table"
    And "Course creator" "link" should exist in the "admintable" "table"
    And "Tenant admnistrator" "link" should not exist in the "admintable" "table"
    And "Tenant" "text" should not exist in the "admintable" "table"
    And I log out
