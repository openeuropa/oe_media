# Open Europa Media CircaBC

This module integrates with CircaBC to create Document media based on CircaBC
documents. It does so by adding a third option to the Document media file type field, next to Local and Remote.

## Installation

* Install the module
* Add the settings configuration that contains the URL of the CircaBC instance. For example:

```
$settings['circabc'] = [
  'url' => 'https://webgate.acceptance.ec.europa.eu/circabc-ewpp',
];
```

* Add the CircaBC ID field to the Document media form display. It will not be visible, but it's required on the form.
* Move the `File type` field to the top on the Document media form display.
