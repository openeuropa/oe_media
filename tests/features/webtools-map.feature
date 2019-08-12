@api
Feature: Webtools map.
  In order to be able to showcase webtools map
  As a site editor
  I want to create and reference webtools map media entities.

  @webtools_map @cleanup:media
  Scenario: Create and reference a Webtools map
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_map media" permission
    When I visit "the Webtools map creation page"
    And I fill in "Name" with "World map"
    And I fill in "Webtools map snippet" with "{\"service\": \"map\"}"
    And I press "Save"
    And I visit "the demo content creation page"
    And I reference the Webtools map "World map"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the Webtools map
