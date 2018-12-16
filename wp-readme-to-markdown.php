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
 * Version:           0.2.0
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

  /**
   * Loads up the hooks for main functionality
   *
   * @since 1.0.0
   */

  static function load(){
    add_action( 'wp_rtm/add', array( __CLASS__, 'add'), 10, 4 );
    add_action( 'plugins_loaded', array( __CLASS__, 'convert_files') );
  }

  /**
   * Adds a specific file to the convert queue
   *
   * @since 1.0.0
   *
   * @param string $filepath    the main path to the plugin, use plugin_dir_path(__FILE__) in most cases.
   * @param string $namespace   (optional) unique identifier used for this file, used for hooking later
   * @param string $readme      (optional) path to readme.txt file relative to $filepath
   * @param string $markdown    (optional) path to MARKDOWN.md file relative to $filepath
   */

  static function add( $filepath, $namespace = NULL, $readme = 'readme.txt', $markdown = 'README.md'){

    if ( is_null( $namespace) ){
      self::$files[ $namespace ] = array(
        'filepath'      => $filepath,
        'readme'        => $readme,
        'markdown'      => $markdown,
      );
    } else {
      self::$files[] = array(
        'filepath'      => $filepath,
        'readme'        => $readme,
        'markdown'      => $markdown,
      );
    }
  }

  /**
   * Converts all the files in $files from txt to md
   *
   * @since 1.0.0
   */

  static function convert_files(){

    /**
     * Filter to edit the file queue before paths are built
     *
     * @since 1.0.0
     */
    self::$files = apply_filters( 'wp_rtm/files', self::$files );

    // build the file paths for each file in the queue
    self::build_file_paths();

    // actually convert the file!
    foreach ( self::$files as $namespace => $file ){

      if( file_exists( $file['txt_path'] ) ){
        $txt_contents = file_get_contents( $file['txt_path'] );

        // convert txt to markdown
        $markdown = \WPReadme2Markdown\Converter::convert( $txt_contents );

        // write/create the file
        if ( ! file_exists( $file['md_path']) || self::check_contents( $file['md_path'], $markdown ) ) {
          self::save_markdown_file( $namespace, $markdown );
        }
      }
    }
  }

  /**
   * Converts all the files in $files from txt to md
   *
   * @since 1.0.0
   */
  private function build_file_paths(){
    foreach ( self::$files as $ns => $file ){
      self::$files[$ns]['txt_path'] = $file['filepath'] . $file['readme'];
      self::$files[$ns]['md_path'] = $file['filepath'] . $file['markdown'];
    }
  }

  private function check_contents( $md_path, $contents ){
    // if the file doesn't exist yet, write one
    if (! file_exists($md_path) ){
      return true;
    }

    $old_contents = file_get_contents($md_path);

    if ( $old_contents != $contents ){
      return true;
    }
    return false;
  }

  private function save_markdown_file( $namespace, $contents ){
    $markdown_file = fopen( self::$files[$namespace]['md_path'], 'w');

    // allow a filter if a namespace was given
    if ( is_string( $namespace ) ) {
      $contents = apply_filters( 'wp_rtm/file_' . $namespace . '/pre_save/md_contents', $contents );
    }

    fwrite($markdown_file, $contents);
    fclose($markdown_file);

    // allow a post_save action if a namespace was given
    if ( is_string( $namespace ) ) {
      do_action( 'wp_rtm/file_' . $namespace . '/post_save', $contents );
    }

  }

}

WP_Readme_To_Markdown::load();

// let's convert this plugin's file!
do_action( 'wp_rtm/add', plugin_dir_path(__FILE__), 'wp_rtm' );

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
