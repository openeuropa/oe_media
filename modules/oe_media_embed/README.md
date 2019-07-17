# OpenEuropa Media Embed module

The OpenEuropa Media Embed module allows the embedding of Media entities into content in an agnostic (non-Drupal) way.

To this end, it comes with two main elements: the WYSIWYG embed button and the filter plugin.

Using the media embed WYSIWYG button, editors can select media entities that they wish to embed. Upon selecting the media entity, an oEmbed-based embed code is
inserted in the content. This code follows the oEmbed protocol and is therefore understandable by other clients as well. An example embed code:

```
<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/118a06e9-e7df-4b7b-8ab2-5f5addc2f0b3">
  <a href="https://data.ec.europa.eu/ewp/media/118a06e9-e7df-4b7b-8ab2-5f5addc2f0b3">sdasd</a>
</p>
```

Where `118a06e9-e7df-4b7b-8ab2-5f5addc2f0b3` is the UUID of the media entity.

This embed code is transformed into the rendered entity by the filter plugin `FilterMediaEmbed`. Adding this to a text format will replace the embed tags with the rendered media entity.

Moreover, we have the [OpenEuropa oEmbed component](https://github.com/openeuropa/oe_oembed) which will be used when a site expects its content to be read by external systems that 
need to understand the embed codes. Essentially, it acts as an oEmbed provider for the media resources on the site.
