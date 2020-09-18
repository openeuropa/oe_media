@api
Feature: Document media entities.
  In order to show documents on the website
  As a site editor
  I want to be able to upload documents and reference Document media entities.

  @cleanup:node @cleanup:media @cleanup:file @media-enable-standalone-url
  Scenario: Documents can be uploaded and attached to nodes.
    Given I am logged in as a user with the "administer nodes, create oe_media_demo content, edit own oe_media_demo content, view own unpublished content, create document media" permissions
    When I go to "the document creation page"
    Then I should see the heading "Add Document"
    When I fill in "Name" with "My Document 1"
    And I attach the file "sample.pdf" to "File"
    And I press "Save"
    Then I should see the heading "My Document 1"

    When I go to "the demo content creation page"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "My Node"
    And I fill in the document reference field with "My Document 1"
    And I press "Save"
    Then I should see the heading "My Node"
    And I should see the link "sample.pdf"

    # Log out and check that we can see the media.
    When I log out
    And I go to the "My Document 1" media page
    Then I should see the heading "My Document 1"

    # Log back in and unpublish the node.
    When I am logged in as a user with the "administer nodes, edit any oe_media_demo content" permission
    And I go to the "My node" node page
    When I click "Edit"
    And I uncheck "Published"
    And I press "Save"
    Then I should see "My Node has been updated"

    # Log back out and check that the media has no access.
    When I log out
    And I go to the "My Document 1" media page
    Then I should see "Access denied"

  @javascript @cleanup:media @cleanup:file
  Scenario: The entity browser should allow the selection and creation of new Document Media entities
    Given I am logged in as a user with the "create oe_media_demo content,create document media,access media_entity_browser entity browser pages" permissions
    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press "Select entities" in the "media browser field"
    Then I should see entity browser modal window
    When I click "Add File"
    And I fill in "Name" with "Media document"
    And I attach the file "sample.pdf" to "File"
    And I press the "Save entity" button
    And I press the "Save" button
    Then I should see the link "sample.pdf"

    When I visit "the demo content creation page"
    And I fill in "Title" with "Media demo"
    And I click the fieldset "Media browser field"
    And I press "Select entities" in the "media browser field"
    Then I should see entity browser modal window
    When I click "View"
    And I select the "Media document" media entity in the entity browser modal window
    And I press the "Select entities" button
    And I press the "Save" button
    Then I should see the link "sample.pdf"
