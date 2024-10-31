=== Plugin Name ===
Contributors: baba_mmx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WZHXKXW5M36D4
Tags: nextgengallery, nextgen gallery, nextgen, tags, cloud tag, filter, nextcellent
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 1.6.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends NextGen Gallery (prior to version 2.0) / NextCellent Gallery to simplify the image filtering, providing a simple way to filter gallery images with their own tags.

== Description ==

Extends NextGen Gallery (prior to version 2.0) / NextCellent Gallery to simplify the image filtering, providing a simple way to filter gallery images with their own tags.

**This plugin depends on NextGEN Gallery prior to version 2.0 or NextCellent Gallery.**

**This plugin will not work with newer versions of NextGEN Gallery and IT WILL NOT BE UPDATED TO WORK WITH THEM.**

**This plugin is minimally maintained:** please make a donation to encourage future developments using this link [Donate to this plugin »](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WZHXKXW5M36D4 "Donate to this plugin »").



= WARNING! NextGenGallery 2.0 update breaks all =
--------------------------------------------------------------------------------
--------------------------------------------------------------------------------
**NGG 2.0 has a completly different structure: Powertags will only work with NGG < 2.0**

Please refer to this [nextgen-gallery-200-conflicterror](http://wordpress.org/support/topic/nextgen-gallery-200-conflicterror-1?replies=5#post-4476789 "nextgen-gallery-200-conflicterror")
--------------------------------------------------------------------------------
--------------------------------------------------------------------------------


= Usage =

1. Add one or more tags to the gallery images
2. Insert the nggpowertags shortcode:`[nggpowertags gallery=X]` (where X is the gallery ID) and you have done.
3. Optionally you can add one or more attributes:

* **tagmenu_sep**: to define a character to separate the tags (default is ",")
* **tagmenu_format**: can be "sep", "list" or "select" or "empty" to hide the menu (default is "sep", which stands for separator)
* **all_word**: can be one of the translated strings *see Translation notes* (default is "All")
* **template**=caption or one of other ngg templates (the same as nggallery shortcode)
* **exclude**: *see "Include/Exclude notes"*
* **include**: *see "Include/Exclude notes"*
* **unique**: you can add an unique value per gallery

You can also set more than one gallery using a comma separated list of IDs, for example:
`[nggpowertags gallery=1,2,3]`


= Include/Exclude notes =

The include attribute has been set to have the precedence on the exclude attribute and can be also used to specify a custom ordered list of tags.
To better understand the precedence, I show you an example of a gallery (name it with ID=1) with 3 images tagged as follow:

* image1: "dubai", "cool"
* image2: "cool"
* image3: "dubai"

This shortcodes are equivalent:

`[nggpowertags gallery=1 include=cool exclude=dubai]`
`[nggpowertags gallery=1 include=cool]`

You'll end up with image1 and image2.

Using this shortcode:

`[nggpowertags gallery=1 exclude=dubai]`

You'll end up with the image2 only.


= Custom ordered tags =

With the "include" attribute you can easily change the tag order as you like: if you have "dubai" and "cool" tags, you end up with the alphabetical order (cool / dubai), but if you specify the attribute "include":
`[nggpowertags gallery=1 include=dubai,cool]`
you end up with your personal ordered tags.


= Permalinks =

In order to have working parmalinks with Powertags, you have first to enable the permalinks in WordPress (using for example %postname%) then on NextGen Gallery (../wp-admin/admin.php?page=nggallery-options), checking "Activate permalinks" options.
You can optionally change the gallery slug name, changing the "Gallery slug name" input field.

In this way, you end up with something like this:

`http://www.yoursitename.com/yourpagename/gallery-slug-name/tags/yourtag`


= Translation notes =

The default word used to describe all the images is set to "All".
Someone asked me to add a translatable string, so I've added some useful words:
`"All", "Any", "Show All", "Show All Categories", "All Categories", "All Tags"`
Actually these are translated only on my mother tongue, which is the italian.
If you need more translatable strings or more languages, please modify or create a new ".po" file and send it via mail to my email address (info@mauromascia.com).

*Note that, if you specify a different word (from the ones defined as translatable strings) in the "all_word" attribute, this will not be translated.*


= Style =

The main structure is this:
`
<div id="nggpowertags">
    <div class="nggpowertags-menu">...</div>
    <div class="ngg-galleryoverview">...</div>
</div>
`

If you need to move the menu down, you can put some CSS (in your style.css) like this:
`
div#nggpowertags {
    position: relative;
}
div.nggpowertags-menu {
    position: absolute;
    bottom: 0;
}
div.ngg-galleryoverview {
    padding-bottom: 30px;
}
`

The style of the active tag can be changed using the "active-tag" class under the "nggpowertags-menu" div.

If you need to hide the tag menu, you have to set the tagmenu_format to "empty":
`[nggpowertags gallery=1 tagmenu_format=empty]`




== Installation ==

1. Unzip the archive of the plugin or download it from the [official Wordpress plugin repository](http://wordpress.org/extend/plugins/nextgen-gallery-powertags/ "NextGen Gallery Powertags")
2. Upload the folder 'nextgen-gallery-powertags' to the Wordpress plugin directory (../wp-content/plugins/)
3. Activate the plugin through the 'Plugins' menu in WordPress (NextGen Gallery - of course - MUST be already enabled!)
4. Configure it under you posts/pages using the nggpowertags shortcode: `[nggpowertags gallery=X]` (where X is the gallery ID) and you have done.
5. Optionally you can add one or more attributes (see the **Description** page)


== Frequently Asked Questions ==

= Do I need to edit any files? =
Nop, once followed the installation instruction you have done.

= I need some plugin modifications. How can I tell you what I want? =
Nop, at the moment you can't and there will not be new stuff for this plugin.
This plugin is minimally maintained: please make a donation to encourage future developments.

= I would like to make a donation =
Yes thanks, donations are always welcome!
You can make a donation using this link: [Donate to this plugin »](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WZHXKXW5M36D4 "Donate to this plugin »")


== Screenshots ==

1. Display All images
2. Display the first tag clicked
3. Display the second tag clicked


== Changelog ==


= 1.6.5 =
* Add NextCellent Gallery as a required plugin.

= 1.6.1 =
* Fixed last commit

= 1.6.0 =
* Fixed ordered select

= 1.5.9 =
* NEW: Added ability to hide the tag menu (thanks to Petr).

= 1.5.8 =
* Better error handling.

= 1.5.7 =
* NEW: Added russian language (thanks to J-Skip).

= 1.5.6 =
* Fixed: Added a parent div above menu and content.

= 1.5.5 =
* Typo correction: changed "sel" to "sep" in the attribute options readme.

= 1.5.4 =
* Fixed: The tag slug must be used insted of the tag name in the taglist.

= 1.5.3 =
* Fixed: SVN commit error

= 1.5.2 =
* NEW: Added the "All" word (and others) translatable.

= 1.5.1 =
* NEW: Added the "select" option for the tagmenu_format attribute
* NEW: Added the "active-tag" class for the active tag, so you can easily add the style in your CSS stylesheet.

= 1.5.dev =
* NEW: Added the "include" attribute

= 1.4.1 =
* Fixed: ["Warning: Missing argument 2 for wpdb::prepare()"](http://wordpress.org/support/topic/error-107 "topic/error-107")
* Fixed: added the menu to the output, doesn't print it directly

= 1.4 =
* Fixed: [Clean permalinks issue](http://wordpress.org/support/topic/cleaner-permalinks "http://wordpress.org/support/topic/cleaner-permalinks")
* Fixed: ["Warning: in_array() expects parameter 2 to be array, string given"](http://wordpress.org/support/topic/error-107 "topic/error-107")

= 1.3 =
* Fixed: [Picture sort order issuey](http://wordpress.org/support/topic/sorting-broken-with-powertags "Fixed the picture sort order issue")
* NEW: [Multiple galleries selection](http://www.mauromascia.com/portfolio/wordpress-nextgen-gallery-powertags/#comment-741049320 "Adding multiple galleries selection")

= 1.2 =
* Fixed: "exclude" attribute when there are multiple tags per image

= 1.1 =
* NEW: "exclude" attribute to hide one or more tags
* Fixed: the ability of filtering by the gallery ID

= 1.0 =
* First release
