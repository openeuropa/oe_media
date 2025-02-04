@api
Feature: Webtools social feeds.
  In order to be able to showcase Webtools social feeds
  As a site editor
  I want to create and reference Webtools social feeds media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools social feeds.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_social_feed media" permission
    When I visit "the Webtools social feed creation page"
    Then I should see the heading "Add Webtools social feed - Deprecated"
    And I should see the text "Enter the snippet without the script tag. Snippets can be generated in Webtools wizard or in the newer WCLOUD wizard."
    When I press "Save"
    Then I should see the following error messages:
      | error messages                                 |
      | Name field is required                         |
      | Webtools social feed snippet field is required |

    When I fill in "Name" with "Spokepersons"
    And I fill in "Webtools social feed snippet" with "{\"service\": \"charts\"}"
    And I press "Save"
    Then I should see the following error message:
      | error messages                                    |
      | The service "social_feed" is no longer supported. |

    When I fill in "Webtools social feed snippet" with "{\"service\":\"smk\",\"type\":\"list\",\"slug\":\"ec-spokespersons\"}"
    And I press "Save"
    Then I should see the following error message:
      | error messages                                    |
      | The service "social_feed" is no longer supported. |
