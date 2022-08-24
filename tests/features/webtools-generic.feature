@api
Feature: Webtools generic.
  In order to be able to showcase generic Webtools widget
  As a site editor
  I want to create and reference Webtools generic media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools generic.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_generic media" permission
    When I visit "the Webtools generic creation page"
    Then I should see the text "Enter the snippet without the script tag. Snippets can be generated in Webtools wizard or in the newer WCLOUD wizard."
    When I press "Save"
    Then I should see the following error messages:
      | error messages                     |
      | Name field is required             |
      | Webtools snippet field is required |

    When I fill in "Name" with "Share button"
    And I fill in "Webtools snippet" with "{\"service\": \"map\"}"
    And I press "Save"
    Then I should see the error message "This service is supported by a dedicated asset type or feature, please use that instead."

    When I fill in "Webtools snippet" with "{\"service\": \"share\",\"icon\": true,\"selection\": false,\"shortenurl\": true}"
    And I press "Save"
    Then I should see the success message "Webtools generic Share button has been created."

    When I visit "the demo content creation page"
    And I reference the Webtools generic "Share button"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the success message "OpenEuropa Media Demo My demo node has been created."
    And I should see the Webtools generic "Share button" on the page
