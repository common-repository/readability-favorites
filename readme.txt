=== Readability Favorites ===
Contributors: kanedo
Author URI: http://blog.kanedo.net
Author: Gabriel Bretschner <info@kanedo.net>
Donate Link: https://flattr.com/t/550258
Tags: Readability, Favorites, Links, weekly, daily, Linkpost
Requires at least: 3.0
Tested up to: 3.6.1
Stable Tag: 1.4

This plugin generates a blog post of your favorite Readability links. 

== Description ==

You want to publish and share all these links you found last week and saved in Readability? Now you can!

You can decide wether it should post automatically every day, every week, or you can generate this by hand.


The only thing you have to do is connect to Readability and the rest will be done for you.

This plugin uses the OAuth PHP Library ([http://code.google.com/p/oauth-php/](http://code.google.com/p/oauth-php/)) which is under the MIT License

== Installation ==

* copy the readability-favorites directory into your plugins directory
* activate it in the WP Backend
* go to the settings page (Settings-> Readability Favorites)
* configure the plugin and connect with Readability
* Enjoy!

== Requirements ==

In order to install this plugin you need the [cUrl PHP extension](http://php.net/curl) .

== Screenshots ==

1. The settings page. Here you can configure the plugin
2. The second screen
3. The third screen
4. The fourth screen

== Changelog ==
= 1.4 =

* all new options page
* new cron job url
* triggering manually via url now only works if schedule is set to "manual"
* you can now deauthorize readability
* plugin now shows with which account you are currently logged in
* post preview now shows the featured image if set
* you get a mesage if plugin is not authorized

= 1.3 =

* fixes a bug which causes the plugin to forget its authorization
* added "Open Links in new Window"

= 1.2.3.1 =

* fixes an array_key_exists warning
* added additional notes regarding issues with a Google Analytics Plugin

= 1.2.1 =

* replaced cURL with WP_Http
* fixed translation

= 1.2 =

* Added a custom post title and a featured image

= 1.1 =

* Added a preview of the next upcoming post so you can check whether everything is allright.


== Frequently Asked Questions ==

= When I try to authorize the plugin I get redirected to the settings page of the google Analytics Plugin (Plugin Author YOAST) =

I'm sorry for your inconvenience. This is a known issue but this is really not my fault. The problem is, the other plugin grabs all requests with an oauth_token parameter (which is necessary for all OAuth Plugins) and redirects to itself. I filed a Bug at the developer. One workaround could be: Deactivate the GA Plugin, authorize with Readability and activate it again. Everything else should be fine. Hopefully.