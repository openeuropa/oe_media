@api
Feature: Webtools social feeds.
  In order to be able to showcase webtools social feeds
  As a site editor
  I want to create and reference webtools social feeds media entities.

  @webtools_social_feeds @cleanup:media
  Scenario: Create and reference a Webtools social feeds
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_social_feeds media" permission
    When I visit "the Webtools social feeds creation page"
    And I fill in "Name" with "Basic social feeds"
    And I fill in "Webtools social feeds snippet" with "{\"service\": \"smk\"}"
    And I press "Save"
    And I visit "the demo content creation page"
    And I reference the Webtools social feeds "Basic social feeds"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the Webtools social feeds "Basic social feeds" on the page
