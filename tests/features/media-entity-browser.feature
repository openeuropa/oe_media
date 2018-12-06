@api
Feature: Media entity browser.
  In order to select the existing medias
  As a site editor
  I want to be able to use filters.

  @javascript
  Scenario: The node adding form should contain entity browser widget with possibility to view and filter all existing medias.
    Given I am logged in as a user with the "create oe_media_demo content,access media_entity_browser entity browser pages" permission
    And I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    When I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "View"
    Then I should see search existing medias by entity browser filter
