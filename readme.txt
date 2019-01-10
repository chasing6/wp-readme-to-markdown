=== WP Readme to Markdown ===
Contributors: (this should be a list of wordpress.org userid's)
Tags: comments, spam
Requires at least: 4.9
Tested up to: 4.9
Stable tag: 4.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Converts your readme.txt files into markdown for posting on sites like Github.com.

== Description ==

This plugin is used in a dev environment to automatically convert WordPress's readme.txt file to a README.md markdown file (for Github and the like). **DO NOT USE THIS PLUGIN IN A PRODUCTION ENVIRONMENT!!!**

== Requirements ==

Your WP install must be in debug mode for this to work. Please add `define('WP_DEBUG', true);` to your wp-config.php file. You must also have proper read/write permissions in your plugin's folder

== Usage ==

= Adding a File for Conversion =

Adding a file to the convert queue is as easy as adding a single line of code! The plugin uses the hook `wp_rtm/add` to run the function. Paramaters include:
```php
/**
 * wp_rtm/add parameters:
 *
 * @param string $filepath    the main path to the plugin readme.txt file.
 * @param string $namespace   (optional) unique identifier used for this file, used for hooking later. Defaults to numeric key.
 * @param string $readme      (optional) path to readme.txt file relative to $filepath. Defaults to readme.txt
 * @param string $markdown    (optional) path to MARKDOWN.md file relative to $filepath. Defaults to README.md
 */
```

The simplest way to convert your readme.txt file is to add the following line into the main file of your plugin.

```php
do_action('wp_rtm/add', plugin_dir_path() );
```
In order to allow for hooking into the file directly, it is recommended that you add a namespace to the file queue:

```php
do_action( 'wp_rtm/add', plugin_dir_path(), 'plugin-name' );
```
**Note that if you queue up a file with the same namespace as a file already in the queue, the original file will be overwritten.**

Files are converted during the `plugins_loaded` action, which means that in order to generate the new files you must reload your browser.

== Changelog ==

= 0.2.1 =
- **Fixed** Bug causing file to always save.

= 0.2.0 =
- **Enhancement** Added `wp_rtm/files` filter.
- **Enhancement** Added `wp_rtm/file-{namespace}/post_save` action.
- **Enhancement** Added readme.txt and README.md files
