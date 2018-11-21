@api
Feature: Media document bundle.
  In order to be able to showcase the media and entity browser features for managing files
  As a site editor
  I want to be able to work with File based Media entities

  @javascript
  Scenario: The entity browser should allow the selection and creation of new File Media entities
    Given I am logged in as a user with the "create oe_media_demo content,create document media,access media_entity_browser entity browser pages" permissions
    
    When I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "Add File"
    And I fill in "Name" with "Media document"
    And I attach the file "sample.pdf" to "File"
    And I press the "Save entity" button
    And I press the "Save" button
    Then I should see the link "sample.pdf"

    When I visit "node/add/oe_media_demo"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press the "Select entities" button
    Then I should see entity browser modal window
    When I click "View"
    And I select the "Media document" media entity in the entity browser modal window
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see the link "sample.pdf"
    # Cleanup of the media entity.
    And I remove the media "Media document"