@api
Feature: Webtools map.
  In order to be able to showcase Webtools map
  As a site editor
  I want to create and reference Webtools map media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools map.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_map media" permission
    When I visit "the Webtools map creation page"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                         |
      | Name field is required                 |
      | Description field is required          |
      | Webtools map snippet field is required |

    When I fill in "Name" with "World map"
    And I fill in "Description" with "This is world map"
    And I fill in "Webtools map snippet" with "{\"service\": \"charts\"}"
    And I press "Save"
    Then I should see the error message "Invalid Webtools Map snippet."

    When I fill in "Webtools map snippet" with "{\"service\": \"map\"}"
    And I press "Save"
    Then I should see the success message "Webtools map World map has been created."

    When I visit "the demo content creation page"
    And I reference the Webtools map "World map"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the success message "OpenEuropa Media Demo My demo node has been created."
    And I should see the Webtools map "World map" on the page
