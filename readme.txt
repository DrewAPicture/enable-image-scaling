=== Enable Image Scaling on Upload ===
Contributors: DrewAPicture
Donate link: http://wordpressfoundation.org/
Tags: uploads, media, images, scale
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 1.0.3.1

This enables you to set maximum height and width dimensions for images uploaded through WordPress.

== Description ==

Have problems with large images clogging up your uploads directory and taking up more space than necessary? Now you set a set of dimensions that all images will be scaled down to when uploaded through WordPress.

<strong>Complete 1.0 Rewrite!</strong>

* Supports the new Media Manager workflow introduced in 3.5
* <strong>3.5 Only:</strong> Set custom height and width dimensions instead of resizing to the "large size" dimensions
* Fully backward-compatible to WordPress 3.3

Now translated to Dutch! (thanks: <a href="http://wordpress.org/support/profile/theohoutman">Theo Houtman</a>)

If you have a bug fix or wish to contribute, check out the <a href="https://github.com/DrewAPicture/enable-image-scaling" target="_new">GitHub repository</a>. Pull requests welcome.

== Installation ==

1. Upload the unzipped `enable-scaling-on-upload` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. If desired, visit the Settings > Media settings to enable or disable scaling and set custom dimensions

== Frequently Asked Questions ==

= There used to be a checkbox on the upload screen, what happened to it? =

Since the ability to set custom dimensions was added for 3.5, the options were moved into the Settings > Media screen. There, you can enable or disable the feature globally.

== Screenshots ==

1. New custom dimension and global enable settings in Settings > Media.

2. Image Scaling option displayed on the new uploader screen.

== Changelog ==

= 1.0.3.1 =

* Fix Dutch translation filename
* Regenerate POT file with strings

= 1.0.3 = 

* Add Dutch translation (props <a href="http://wordpress.org/support/profile/theohoutman">Theo Houtman</a>)
* Fix some issues with strings not getting translated
* Other code refactoring

= 1.0.2 =

* Windows Server can't handle anonymous functions

= 1.0.1 =

* Small fixes

= 1.0 =

* Complete rewrite, allows custom dimensions 3.5+

= 0.4 =

* Awesomely forgot a sentence in readme.txt.

= 0.3 =

* Reworded readme description
* Fixed typos.

= 0.2 =

* Fix version number in plugin file

= 0.1 =

* Initial upload.

== Upgrade Notice ==

= 1.0.3.1 =

* Fix Dutch translation filename
* Regenerate POT file with strings

= 1.0.3 =

* New Dutch translation
* Fixes non-translated strings
* Other fixes and code refactoring

= 1.0.2 = 

* Windows Server can't handle anonymous functions

= 1.0.1 =

* Small fixes

= 1.0 =

* Complete rewrite, now allows custom dimensions and global disable

= 0.4 =

* Adds a couple words to readme description.

= 0.3 =

* Semanitc changes in readme and plugin files.

= 0.2 =

* Adds proper verison number to plugin file.

= 0.1 =

* This is the first version of this plugin
