@api
Feature: Webtools OP publication list.
  In order to be able to showcase Webtools OP publication list
  As a site editor
  I want to create and reference Webtools OP publication list media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools OP publication list.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_op_publication_list media, edit own webtools_op_publication_list media" permission
    When I visit "the Webtools OP publication list creation page"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                                    |
      | Name field is required                            |
      | Webtools OP Publication list ID field is required |

    When I fill in "Name" with "Basic OP publication list"
    And I fill in "Webtools OP Publication list ID" with "ID"
    And I press "Save"
    Then I should see the error message "Webtools OP Publication list ID must be a number."

    When I fill in "Webtools OP Publication list ID" with "12.34"
    And I press "Save"
    Then I should see the error message "Webtools OP Publication list ID is not a valid number."

    When I fill in "Webtools OP Publication list ID" with "1234"
    And I press "Save"
    Then I should see the success message "Webtools op publication list Basic OP publication list has been created."

    When I go to the "Basic OP publication list" media edit page
    Then the "Webtools OP Publication list ID" field should contain "1234"

    When I visit "the demo content creation page"
    And I reference the Webtools OP publication list "Basic OP publication list"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the success message "OpenEuropa Media Demo My demo node has been created."
    And I should see the Webtools op publication list "Basic OP publication list" on the page
