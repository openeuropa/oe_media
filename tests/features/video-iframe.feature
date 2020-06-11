@api
Feature: Video iframe media.
  In order to show remote videos
  As a site editor
  I want to be able to create medias with iframe as source.

  @cleanup:node @cleanup:media @media-enable-standalone-url
  Scenario: Video iframe medias can be created.
    Given I am logged in as a user with the "create oe_media_demo content, create video_iframe media" permissions
    When I go to "the Video iframe creation page"
    Then I should see the heading "Add Video iframe"
    And the available options in the "Ratio" select should be:
      | 16:9 |
      | 4:3  |
      | 3:2  |
      | 1:1  |
    When I fill in "Name" with "EBS"
    And I fill in "Iframe" with "<iframe src=\"http://web:8080/tests/fixtures/example.html\" width=\"800\" height=\"600\" frameborder=\"0\"><a href=\"#\">invalid</a></iframe><script type=\"text/javascript\">alert('no js')</script>"
    And I press "Save"
    Then I should see the heading "EBS"
    # Verify that the iframe has been embedded, with all the tags except "<iframe>" stripped.
    And the response should contain "<iframe src=\"http://web:8080/tests/fixtures/example.html\" width=\"800\" height=\"600\" frameborder=\"0\">invalid</iframe>alert('no js')"
