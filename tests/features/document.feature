@api
Feature: Document media entities.
  In order to show documents on the website
  As a site editor
  I want to be able to upload documents.

  @cleanup:node @cleanup:media
  Scenario: Documents can be uploaded and attached to nodes.
    Given I am logged in as a user with the "create oe_media_demo content, create document media" permissions
    And I go to "media/add/document"
    Then I should see the heading "Add Document"
    When I fill in "Name" with "My Document 1"
    And I attach the file "sample.pdf" to "File"
    And I press "Save"
    Then I should see the heading "My Document 1"

    When I go to "node/add/oe_media_demo"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "My Node"
    And I fill in the document reference field with "My Document 1"
    And I press "Save"
    Then I should see the heading "My Node"
    And I should see the link "sample.pdf"
