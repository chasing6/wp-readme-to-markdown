<?php
/**
 * Auto generates a MARKDOWN.md file
 *
 * @link              http://innovatedentalmarketing.com
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       WP Readme to Markdown
 * Plugin URI:        https://github.com/chasing6/wp-readme-to-markdown
 * Description:       Converts the readme.txt file into markdown for github, etc.
 * Version:           0.1.0
 * Author:            Scott McCoy
 * Author URI:        https://github.com/chasing6/
 * Text Domain:       wp-rtm
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/chasing6/wp-readme-to-markdown
 */


// include the composer autoloader

require_once( plugin_dir_path(__FILE__) . 'vendor/autoload.php');

class WP_Readme_To_Markdown{
  /**
	 * Array of files to convert
	 *
	 * @var array
	 */
  static $files = array();

  static function load(){
    add_action( 'wp_rtm/add', array( __CLASS__, 'add'), 10, 4 );
    add_action( 'plugins_loaded', array( __CLASS__, 'convert_files') );
  }

  static function add( $namespace, $filepath, $readme = 'readme.txt', $markdown = 'MARKDOWN.md'){
    // add the info to the namespace
    self::$files[ $namespace ] = array(
      'filepath'      => $filepath,
      'readme'        => $readme,
      'markdown'      => $markdown,
    );

  }

  static function convert_files(){

    $files = apply_filters( 'wp_rtm/files', self::$files );

    // TODO: add a way to append or prepend the markdown file

    // TODO: actually convert the file!
    do_action( 'add_debug_info', $files, 'Readme to Markdown Conversions');
  }

}

WP_Readme_To_Markdown::load();

// let's convert this plugin's file!
do_action( 'wp_rtm/add', 'wp-rtm', plugin_dir_path(__FILE__) );

// make sure this is the first plugin loaded,
// so that we can use the wp_rtm/add action filter reliably;
function load_wp_rtm_first() {
	// ensure path to this file is via main wp plugin path
	$wp_path_to_this_file = preg_replace('/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR."/$2", __FILE__);
	$this_plugin = plugin_basename(trim($wp_path_to_this_file));
	$active_plugins = get_option('active_plugins');
	$this_plugin_key = array_search($this_plugin, $active_plugins);
	if ($this_plugin_key) { // if it's 0 it's the first plugin already, no need to continue
		array_splice($active_plugins, $this_plugin_key, 1);
		array_unshift($active_plugins, $this_plugin);
		update_option('active_plugins', $active_plugins);
	}
}
add_action("activated_plugin", "load_wp_rtm_first");
