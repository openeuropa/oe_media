@api
Feature: AV Portal video.
  In order to be able to showcase AV Portal videos
  As a site editor
  I want to create and reference AV Portal video media entities.

  @av_portal @cleanup:media
  Scenario: Create and reference an AV Portal video
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_video media" permission
    When I visit "the AV Portal video creation page"
    And I fill in "Media AV Portal Video" with "https://audiovisual.ec.europa.eu/en/video/I-162747"
    And I press "Save"
    And I visit "the demo content creation page"
    And I fill in "Title" with "My demo node"
    And I reference the AV Portal video "Midday press briefing from 25/10/2018"
    And I press "Save"
    Then I should see the AV Portal video "Midday press briefing from 25/10/2018"

  @javascript @av_portal @cleanup:media
  Scenario: The node adding form should contain an entity browser widget with the possibility to add new and reuse existing AV Portal video.
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_video media,access media_entity_browser entity browser pages" permission
    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press "Select entities" in the "media browser field"
    Then I should see entity browser modal window
    When I click "Add AV Portal Video"
    And I fill in "Media AV Portal Video" with "https://audiovisual.ec.europa.eu/en/video/I-162747"
    And I press the "Save entity" button
    And I press the "Save" button
    Then I should see the AV Portal video "Midday press briefing from 25/10/2018"

    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press "Select entities" in the "media browser field"
    Then I should see entity browser modal window
    When I click "View"
    And I select the "Midday press briefing from 25/10/2018" media entity in the entity browser modal window
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see the AV Portal video "Midday press briefing from 25/10/2018"

    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press "Select entities" in the "media browser field"
    Then I should see entity browser modal window
    When I click "Register AV Portal video"
    Then I should see the link "external link"

  @javascript @av_portal @cleanup:media
  Scenario: The entity browser should contain a widget that allows to search for videos in AV Portal.
    Given I am logged in as a user with the "create oe_media_demo content,create av_portal_video media,access media_entity_browser entity browser pages" permission
    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press "Select entities" in the "media browser field"
    Then I should see entity browser modal window
    When I click "Search videos in AV Portal"
    Then I should see " LIVE \"Subsidiarity - as a building principle of the European Union\" Conference in Bregenz, Austria - Welcome, keynote speech and interviews"
    When I select the avportal item with the title ' LIVE "Subsidiarity - as a building principle of the European Union" Conference in Bregenz, Austria - Welcome, keynote speech and interviews'
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see the AV Portal video ' LIVE "Subsidiarity - as a building principle of the European Union" Conference in Bregenz, Austria - Welcome, keynote speech and interviews'
