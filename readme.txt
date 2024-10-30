=== BSGallery ===
Contributors: mnezerka
Donate link: http://blue.pavoucek.cz
Tags: gallery, image, images, album, photo, photos, picture, pictures
Requires at least: 2.5
Tested up to: 4.0
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wordpress plugin that allows to insert image galleries and single images in posts using shorttags ([gallery] replacement).

== Description ==

Plugin provides several shorttags for displaying image galleries and single images attached to post as attachments. It also allows to group post attachments into several sets that can be used to generate galleries for subset of all attached images. I wrote this plugin since I wasn't satisfied with standard [gallery] shorttag - I had to do many workarounds and hacks to have it working according my needs. Shutter reloaded is integrated, but plugin can be used without it (with lightbox etc.). 

== Installation ==

1. Install and activate the plugin through the 'Plugins' menu in WordPress
2. Set basic parameters in 'Appearance' -> 'BSGallery'
3. Upload some images as attachments to your post
4. Use shorttags [bsgallery] or [bsimage] to insert images into post content
5. That's it! :)

== Screenshots ==

== Frequently Asked Questions ==

== Changelog ==

= 1.4 =

* Bugfix - only files of mimetype "image/*" are shown 

= 1.3 =

* Added new parameter for bsgallery shortcode - "template". It is used to choose various HTML representations of generated gallery.

= 1.2 =

* All images found in post content are modified automatically (class adn title attributes) and could be catched by appropriate javascript imaging engine (lightbox, etc.). 

= 1.1 =

* Removed relation to shutter, implemented general concept of lightbox-like tool integration (user defined css class and rel for generated image links) .

= 1.0 =

* Initial release 


== Known Issues / Bugs ==

== Uninstall ==

1. Deactivate the plugin
2. That's it! :)
