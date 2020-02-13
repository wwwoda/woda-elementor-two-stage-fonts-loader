=== Woda Elementor Two Stage Fonts Loader ===
Contributors: @davidmondok
Tags: fonts, performance
Requires at least: 4.5
Tested up to: 5.3
Stable tag: 0.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

...

== Description ==

...

== Installation ==

1. Activate the plugin through the 'Plugins' menu in WordPress
2. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Changelog ==

= 0.1.0 =
* Provide basic features to implement font loading strategy

= 0.1.2 =
* Trigger error only if Query Monitor Plugin is activated

= 0.2.0 =
* Update GitHub updater to 4.9 to use Authorization HTTP header instead as using the `access_token` query parameter is deprecated and will be removed July 1st, 2020.
* Use constant GITHUB_ACCESS_TOKEN for updater if available
* Change fallback option key to woda_github_access_token

`<?php code(); // goes in backticks ?>`
