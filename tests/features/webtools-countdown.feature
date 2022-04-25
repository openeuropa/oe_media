@api
Feature: Webtools countdown.
  In order to be able to showcase Webtools countdown
  As a site editor
  I want to create and reference Webtools countdown media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools countdown.
    Given I am logged in as a user with the "create oe_media_demo content, create webtools_countdown media" permission
    When I visit "the Webtools countdown creation page"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                               |
      | Name field is required                       |
      | Webtools countdown snippet field is required |

    When I fill in "Name" with "Event Countdown"
    And I fill in "Webtools countdown snippet" with "{\"service\": \"map\"}"
    And I press "Save"
    Then I should see the error message "Invalid Webtools Countdown snippet."
    When I fill in "Webtools countdown snippet" with "{\"service\":\"cdown\",\"date\":\"30/04/2052\",\"timezone\":\"Etc/Universal\",\"title\":\"Event countdown\",\"end\":true,\"show\":{\"day\":true,\"time\":true}}"
    And I press "Save"
    Then I should see the success message "Webtools countdown Event Countdown has been created."

    When I visit "the demo content creation page"
    And I reference the Webtools countdown "Event Countdown"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the success message "OpenEuropa Media Demo My demo node has been created."
    And I should see the Webtools countdown "Event Countdown" on the page
