# OpenEuropa Media Embed module

The OpenEuropa Media Embed module allows the embedding of Media entities into content in an agnostic (non-Drupal) way.

To this end, it comes with two main elements: the WYSIWYG embed button and the filter plugin. On top of that, it allows
the site administrators to define which available view displays are also available to be embedded.

## WYSIWYG button

Using the media embed WYSIWYG button, editors can select media entities that they wish to embed. Upon selecting the media entity, an oEmbed-based embed code is
inserted in the content. This code follows the oEmbed protocol and is therefore understandable by other clients as well. An example embed code:

```
<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/118a06e9-e7df-4b7b-8ab2-5f5addc2f0b3">
  <a href="https://data.ec.europa.eu/ewp/media/118a06e9-e7df-4b7b-8ab2-5f5addc2f0b3">sdasd</a>
</p>
```

Where `118a06e9-e7df-4b7b-8ab2-5f5addc2f0b3` is the UUID of the media entity.


Moreover, we have the [OpenEuropa oEmbed component][1] which will be used when a site expects its content to be read by external systems that
need to understand the embed codes. Essentially, it acts as an oEmbed provider for the media resources on the site.

## Text filter

The embed code provided by the WYSIWYG button is transformed into the rendered entity by the filter plugin `FilterMediaEmbed`.
Adding this to a text format will replace the embed tags with the rendered media entity.


## Embeddable view displays

Site administrators will need to define which available view modes are also available to be embedded via the tools described above.
This is done by selecting the available view displays on the display mode configuration page for each available media bundle.

## Usage

In order to use the functionalities of the module, follow the next steps:

1) Create a text format.
You can do so by navigating to `/admin/config/content/formats` and clicking the "Add text format button". More information
is available on the official [documentation][2].

2) Add the Media Embed button to your Active toolbar.
You can do that while creating your text format or by navigating to the text format configuration form (`/admin/config/content/formats/manage/TEXT_FORMAT_ID`).
Make sure you select CKEditor as the Text editor for your text format and move the "Media" button from the *available buttons* section to the Active toolbar.

3) Enable the "Embeds media entities using the oEmbed format" filter.
This filter needs to be enabled and placed last in the Filter processing order.
(**WARNING**: This is very important if you want the oEmbed specific urls to be converted into internal aliases)

4) Make view displays embeddable.
Once the previous steps are done, navigate to the display mode configuration of the bundle you wish to be embeddable and select which of
the available view displays will be available for embedding. E.g., in order to configure which of the view displays of the Image media type
are available for embedding, you will need to navigate to `/admin/structure/media/manage/image/display`.



[1]: https://github.com/openeuropa/oe_oembed
[2]: https://www.drupal.org/docs/user_guide/en/structure-text-format-config.html

