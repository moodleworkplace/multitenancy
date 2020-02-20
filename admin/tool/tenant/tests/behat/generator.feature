@tool @tool_tenant @javascript
Feature: Tenants generator
  As a developer
  I want to be able to use generator for the tenants

  Scenario: Usage of tenants generator
    Given the following "users" exist:
      | username | firstname | lastname | email                 |
      | user1    | User      | 1        | user1@address.invalid |
      | user2    | User      | 2        | user2@address.invalid |
      | user3    | User      | 3        | user3@address.invalid |
    And the following "categories" exist:
      | name        | category | idnumber |
      | CatNike     | 0        | CAT1     |
    And the following tenants exist:
      | name   | sitename | siteshortname | category |
      | Nike   | Nike     | Nike          | CatNike  |
      | Adidas | Adidas   | Adidas        |          |
      | Empty  | Empty    | Empty         |          |
    And the following users allocations to tenants exist:
      | user     | tenant |
      | user1    | Nike   |
      | user2    | Adidas |
    And the following "courses" exist:
      | fullname | shortname | category      |
      | Course N | CN        | CAT1          |
      | Course M | CM        | 0             |
    When I log in as "admin"
    And I should see "Acceptance test site"
    And I should not see "Nike"
    And I should not see "Adidas"
    And I log out
    When I log in as "user1"
    And I should not see "Acceptance test site"
    And I should see "Nike"
    And I should not see "Adidas"
    And I am on site homepage
    And I should see "Course N"
    And I should not see "Course M"
    And I log out
    When I log in as "user2"
    And I should not see "Acceptance test site"
    And I should not see "Nike"
    And I should see "Adidas"
    And I am on site homepage
    And I should not see "Course N"
    And I should not see "Course M"
    And I log out

  Scenario: Tenants generator to bulk create tenants and users
    # This step will create users tenantadmin1, user11..user14, tenantadmin2, user21..user24
    Given "2" tenants exist with "5" users and "2" courses in each
    When I log in as "tenantadmin1"
    And I am on course index
    And I should see "Course12"
    And I should see "Course11"
    And I should not see "Course2"
    And I navigate to "Manage this category" in current page administration
    And I should see "Course and category management"
    And I should not see "Category2"
    And I should not see "Course2"
    And I log out
