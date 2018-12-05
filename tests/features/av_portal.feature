@api
Feature: AV Portal.
  In order to be able to showcase AV Portal Videos
  As a site editor
  I want to create and reference AV Portal media entities.

  @av_portal @cleanup:media
  Scenario: Create and reference an AV Portal video
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_video media" permission
    And I visit "media/add/av_portal_video"
    And I fill in "Media AV Portal Video" with "https://ec.europa.eu/avservices/video/player.cfm?sitelang=en&ref=I-162747"
    And I press "Save"
    And I visit "node/add/oe_media_demo"
    And I fill in "Title" with "My demo node"
    And I reference the AV Portal media "Midday press briefing from 25/10/2018"
    And I press "Save"
    Then I should see the AV Portal video "Midday press briefing from 25/10/2018"
