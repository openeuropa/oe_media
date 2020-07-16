@api
Feature: Video iframe media.
  In order to show remote videos
  As a site editor
  I want to be able to create media entities with iframe as source.

  @javascript @cleanup:node @cleanup:media @cleanup:file
  Scenario: Video iframe media entities can be created and rendered.
    Given I am logged in as a user with the "create oe_media_demo content, create video_iframe media, access media overview" permissions
    When I go to "the Video iframe creation page"
    Then I should see the heading "Add Video iframe"
    And the available options in the "Ratio" select should be:
      | 16:9 |
      | 4:3  |
      | 3:2  |
      | 1:1  |
    When I fill in "Name" with "EBS"
    And I fill in "Iframe" with "<iframe src=\"http://web:8080/tests/fixtures/example.html\" width=\"800\" height=\"600\" frameborder=\"0\"><a href=\"#\">invalid</a></iframe><script type=\"text/javascript\">alert('no js')</script>"
    And I attach the file "example_1.jpeg" to "Iframe thumbnail"
    And I wait for AJAX to finish
    And I fill in "Alternative text" with "iframe thumbnail"
    And I press "Save"
    Then I should see the success message "Video iframe EBS has been created."
    And I should see the image "example_1.jpeg"

    When I go to "the demo content creation page"
    Then I should see the heading "Create OpenEuropa Media Demo"
    When I fill in "Title" with "My Node"
    And I fill in the "video iframe" reference field with "EBS"
    And I press "Save"
    Then I should see the heading "My Node"

    # Verify that the iframe has been embedded, with all the tags except "<iframe>" stripped.
    And the response should contain "<iframe src=\"http://web:8080/tests/fixtures/example.html\" width=\"800\" height=\"600\" frameborder=\"0\">invalid</iframe>alert('no js')"
