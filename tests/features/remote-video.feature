@api
Feature: Remote video media entities.
  In order to show remote videos on the website
  As a site editor
  I want to be able to reference remote videos.

  @cleanup:node @cleanup:media
  Scenario Outline: Remote videos can be referenced and attached to nodes.
    Given I am logged in as a user with the "create oe_media_demo content, create remote_video media" permissions
    When I go to "the remote video selection page"
    Then I should see the heading "Add Remote video"
    When I fill in "Remote video URL" with "<url>"
    And I press "Save"
    Then I should see the heading "<title>"

    When I go to "the demo content creation page"
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
      | https://www.dailymotion.com/video/x6pa0tr   | European Commission Fines Google |

  @javascript @cleanup:node @cleanup:media
  Scenario Outline: Remote videos can be added and referenced through the entity browser modal.
    Given I am logged in as a user with the "create oe_media_demo content, create remote_video media, access media_entity_browser entity browser pages" permissions
    When I go to "the demo content creation page"
    Then I should see the heading "Create OpenEuropa Media Demo"

    When I fill in "Title" with "Videos are awesome"
    And I click the fieldset "Media browser field"
    And I press "Select entities"
    Then I should see entity browser modal window
    And I wait for AJAX to finish

    When I click "Add Video"
    And I wait for AJAX to finish
    And I fill in "Remote video URL" with "<url>"
    And I press "Save entity"
    And I wait for AJAX to finish
    Then I should see the text "<title>"
    And I should see the button "Remove"
    When I press "Save"
    Then I should see the heading "Videos are awesome"
    And I should see the embedded video player for "<url>"

    # Reuse the existing image media into another node.
    When I go to "the demo content creation page"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "More videos"
    And I click the fieldset "Media browser field"
    And I press "Select entities"
    Then I should see entity browser modal window
    And I wait for AJAX to finish

    When I select the "<title>" media in the entity browser modal window
    And I press "Select entities"
    And I wait for AJAX to finish
    Then I should see the text "<title>"
    And I should see the button "Remove"
    When I press "Save"
    Then I should see the heading "More videos"
    And I should see the embedded video player for "<url>"

    Examples:
      | url                                         | title                            |
      | https://www.youtube.com/watch?v=1-g73ty9v04 | Energy, let's save it!           |
      | https://vimeo.com/7073899                   | Drupal Rap Video - Schipulcon09  |
      | https://www.dailymotion.com/video/x6pa0tr   | European Commission Fines Google |
