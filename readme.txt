=== Plugin Name ===
Contributors: bostondv
Donate link: http://pomelodesign.com/donate/
Tags: geo-redirect, multilanguage, multidomain, polylang, geolocation, redirection, geographical, redirect, localization
Requires at least: 3.8
Tested up to: 4.2
Stable tag: 1.0.6
License: MIT
License URI: http://opensource.org/licenses/MIT

Allows you to redirect visitors or switch language according to their country.

== Description ==

Allows you to redirect visitors or switch language according to their country.

== Installation ==

1. Unzip `wp-geo-redirect.zip`
2. Upload `wp-geo-redirect` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 1.1.0 =

* Removes Polylang specific integration.

= 1.0.6 =

* Compare only last two characters of language codes so that a geo redirect doesn't happen for locales of the same country code

= 1.0.5 =

* Fixes undefined variable warning

= 1.0.4 =

* Fixes incorrect country redirect

= 1.0.3 =

* Improves redirection handling when a polylang translation doesn't exist

= 1.0.2 =

* Adds "geo_redirect_skip_redirect" filter

= 1.0.1 =

* Fixes admin save issues
* Code optimization

= 1.0.0 =

* First version released.
