@api
Feature: Webtools social feeds.
  In order to be able to showcase webtools social feeds
  As a site editor
  I want to create and reference webtools social feeds media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools social feeds.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_social_feeds media" permission
    When I visit "the Webtools social feeds creation page"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                                  |
      | Name field is required                          |
      | Description field is required                   |
      | Webtools social feeds snippet field is required |

    When I fill in "Name" with "Spokepersons"
    And I fill in "Description" with "This is a feed of EC spokepersons"
    And I fill in "Webtools social feeds snippet" with "{\"service\": \"charts\"}"
    And I press "Save"
    Then I should see the error message "Invalid webtools Social feeds snippet."

    When I fill in "Webtools social feeds snippet" with "{\"service\":\"smk\",\"type\":\"list\",\"slug\":\"ec-spokespersons\"}"
    And I press "Save"
    Then I should see the success message "Webtools social feeds Spokepersons has been created."

    When I visit "the demo content creation page"
    And I reference the Webtools social feeds "Spokepersons"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the success message "OpenEuropa Media Demo My demo node has been created."
    And I should see the Webtools social feeds "Spokepersons" on the page
