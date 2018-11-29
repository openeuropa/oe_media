@api
Feature: Media AV portal with entity browser.
  In order to be able to showcase the media and entity browser features for managing AV Portal video
  As a site editor
  I want to see the entity browser widget for adding and reusing AV Portal video media entities.

  @javascript @av_portal
  Scenario: The node adding form should contain entity browser widget with possibility to add new and reuse existing AV Portal video.
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_video media,access media_entity_browser entity browser pages" permission
    And I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    When I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "Add AV Portal Video"
    And I fill in "Media AV Portal Video" with "https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=I-162747"
    And I press the "Save entity" button
    And I press the "Save" button
    Then I should see the AV Portal video "Midday press briefing from 25/10/2018"

    When I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    When I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "View"
    And I select the "Midday press briefing from 25/10/2018" media entity in the entity browser modal window
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see the AV Portal video "Midday press briefing from 25/10/2018"
    # Cleanup of the media entity.
    And I remove the media "Midday press briefing from 25/10/2018"

    When I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    When I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "Register AV Portal video"
    Then I should see the link "external link"



