@api
Feature: Webtools shorthand.
  In order to be able to showcase Webtools shorthand
  As a site editor
  I want to create and reference Webtools shorthand media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools shorthand.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_shorthand media" permission
    When I visit "the Webtools shorthand creation page"
    Then I should see the text "Enter the snippet without the script tag. Snippets can be generated in Webtools wizard or in the newer WCLOUD wizard."
    When I press "Save"
    Then I should see the following error messages:
      | error messages                               |
      | Name field is required                       |
      | Webtools shorthand snippet field is required |

    When I fill in "Name" with "My story"
    And I fill in "Webtools shorthand snippet" with "{\"service\": \"map\"}"
    And I press "Save"
    Then I should see the error message "Invalid Webtools Shorthand snippet."

    When I fill in "Webtools shorthand snippet" with "{\"service\": \"shorthand\"}"
    And I press "Save"
    Then I should see the success message "Webtools shorthand My story has been created."

    When I visit "the demo content creation page"
    And I reference the Webtools shorthand "My story"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the success message "OpenEuropa Media Demo My demo node has been created."
    And I should see the Webtools shorthand "My story" on the page
