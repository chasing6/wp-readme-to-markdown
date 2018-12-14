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
 * Description:       Create a form driven polar chart.
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

    add_filter( 'wp_rtm/convert', array( __CLASS__, 'convert') );
  }

  static function add( $namespace, $filepath, $readme = 'readme.txt', $markdown = 'MARKDOWN.md'){
    // add the info to the namespace
    self::$files[ $namespace ] = array(
      'filepath'      => $filepath,
      'readme'        => $readme,
      'markdown'      => $markdown,
    );

  }

  static function convert(){
    return self::$files;
  }
}

add_action( 'plugins_loaded', function(){
  WP_Readme_To_Markdown::load();
} );

WP_Readme_To_Markdown::load();
do_action( 'wp_rtm/add', 'this-file', plugin_dir_path(__FILE__) );
function convert_rtm() {
  $files = apply_filters( 'wp_rtm/convert', array() );
  do_action( 'add_debug_info', $files);
}
add_action( 'wp_footer', 'convert_rtm' );
