@api
Feature: Remote video media entities.
  In order to show remote videos on the website
  As a site editor
  I want to be able to reference remote videos.

  @cleanup:node @cleanup:media
  Scenario Outline: Remote videos can be referenced and attached to nodes.
    Given I am logged in as a user with the "create oe_media_demo content, create remote_video media" permissions
    And I go to "media/add/remote_video"
    Then I should see the heading "Add Remote video"
    When I fill in "Remote video URL" with "<url>"
    And I press "Save"
    Then I should see the heading "<title>"

    When I go to "node/add/oe_media_demo"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "My Node"
    And I fill in the "remote video" reference field with "<title>"
    And I press "Save"
    Then I should see the heading "My Node"
    And I should see the embedded video player for "<url>"

    Examples:
      | url                                         | title                            |
      | https://www.youtube.com/watch?v=1-g73ty9v04 | Energy, let's save it!           |
      | https://vimeo.com/7073899                   | Drupal Rap Video - Schipulcon09  |
      | http://www.dailymotion.com/video/x6pa0tr    | European Commission Fines Google |
