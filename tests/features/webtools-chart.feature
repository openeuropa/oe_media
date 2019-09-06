@api
Feature: Webtools chart.
  In order to be able to showcase webtools chart
  As a site editor
  I want to create and reference webtools chart media entities.

  @cleanup:media
  Scenario: Create and reference a Webtools chart.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_chart media" permission
    When I visit "the Webtools chart creation page"
    And I fill in "Name" with "Basic chart"
    And I fill in "Description" with "This is basic chart"
    And I fill in "Webtools chart snippet" with "{\"service\": \"charts\"}"
    And I press "Save"
    And I visit "the demo content creation page"
    And I reference the Webtools chart "Basic chart"
    And I fill in "Title" with "My demo node"
    And I press "Save"
    Then I should see the Webtools chart "Basic chart" on the page

  @cleanup:media
  Scenario: Validate Webtools chart fields.
    Given I am logged in as a user with the "create oe_media_demo content,create webtools_chart media" permission
    When I visit "the Webtools chart creation page"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                         |
      | Name field is required                 |
      | Description field is required          |
      | Webtools chart snippet field is required |
    And I fill in "Name" with "Basic chart"
    And I fill in "Description" with "This is basic chart"
    And I fill in "Webtools chart snippet" with "{\"service\": \"map\"}"
    And I press "Save"
    Then I should see the following error messages:
      | error messages                  |
      | Invalid webtools Chart snippet. |
