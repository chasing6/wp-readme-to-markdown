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
   * Array of built files
   *
   * @var array
   */

   static $built_files = array();

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

    if ( is_string( $namespace) ){
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

    // Only allow admin users to gen files
    if ( current_user_can('administrator') ) {
      //return;
    }

    /**
     * Filter to edit the file queue before paths are built
     *
     * @since 1.0.0
     */
    self::$files = apply_filters( 'wp_rtm/files', self::$files );

    // actually convert the file!
    foreach ( self::$files as $ns => $file ){

      self::run_convert( $ns );

      if ( self::file_changes( $ns ) ){
        self::save_file( $ns );
      }
    }
  }

  /**
   * Checks to see if the contents of readme.txt is different than what is already
   * in the README.md file
   *
   * @since 1.0.0
   *
   * @param string $md_path   the path to the md file
   * @param string $contents  the contents of the txt file
   */
  private static function check_contents( $md_path, $contents ){
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

  private static function file_changes( $ns ){

    //return true;
    if ( self::$built_files[$ns]['converted'] != self::$built_files[$ns]['md_contents'] ) {
      return true;
    } else {
      return false;
    }
  }

  private static function save_file( $ns ){

    $attr = self::$built_files[$ns];

    if ( is_string( $ns ) ){
      do_action( 'wp_rtm/file/' . $ns . '/before_save_md', $ns, self::$built_files[$ns] );
    }

    // open the file
    $file = fopen( self::$built_files[$ns]['md_path'], 'w');
    fwrite($file, self::$built_files[$ns]['converted'] );
    fclose($file);


    if ( is_string( $ns ) ){
      do_action( 'wp_rtm/file/' . $ns . '/after_save_md', $ns, self::$built_files[$ns] );
    }

  }

  private static function run_convert( $ns ){
    self::build_file_paths( $ns );
    self::get_file_contents( $ns );
    self::convert_to_md( $ns );
  }

  private static function build_file_paths( $ns ){
    $file = self::$files[$ns];

    $build = array(
      'txt_path'  => $file['filepath'] . $file['readme'],
      'md_path'   => $file['filepath'] . $file['markdown'],
    );

    self::$built_files[$ns] = $build;
  }

  private static function get_file_contents( $ns ){

    $file = self::$built_files[$ns];

    if ( file_exists( $file['txt_path']) ){
      $file['txt_contents'] = file_get_contents( $file['txt_path'] );
    } else {
      $file['txt_contents'] = 'No file found at ' . $file['text_path'];
    }

    if ( file_exists( $file['md_path']) ){
      $file['md_contents'] = file_get_contents( $file['md_path'] );
    } else {
      $file['md_contents'] = 'No markdown File';
    }

    self::$built_files[$ns] = $file;

  }

  private static function convert_to_md( $ns ){

    $file = self::$built_files[$ns];

    // TODO: Add prepend file hook

    $raw = \WPReadme2Markdown\Converter::convert( $file['txt_contents'] );

    $hooked = self::markdown_toc($raw);

    // convert txt to markdown
    $file['converted'] = $hooked;

    self::$built_files[$ns] = $file;

  }

  private static function markdown_toc($file_path) {
    //$file = file_get_contents($file_path);

    $file = $file_path;

    // ensure using only "\n" as line-break
    $source = str_replace(["\r\n", "\r"], "\n", $file);

    // look for markdown TOC items
    preg_match_all(
      '/^(?:=|-|#).*$/m',
      $source,
      $matches,
      PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE
    );

    // preprocess: iterate matched lines to create an array of items
    // where each item is an array(level, text)
    $file_size = strlen($source);
    foreach ($matches[0] as $item) {
      $found_mark = substr($item[0], 0, 1);
      if ($found_mark == '#') {
        // text is the found item
        $item_text = $item[0];
        $item_level = strrpos($item_text, '#') + 1;
        $item_text = substr($item_text, $item_level);
      } else {
        // text is the previous line (empty if <hr>)
        $item_offset = $item[1];
        $prev_line_offset = strrpos($source, "\n", -($file_size - $item_offset + 2));
        $item_text =
          substr($source, $prev_line_offset, $item_offset - $prev_line_offset - 1);
        $item_text = trim($item_text);
        $item_level = $found_mark == '=' ? 1 : 2;
      }
      if (!trim($item_text) OR strpos($item_text, '|') !== FALSE) {
        // item is an horizontal separator or a table header, don't mind
        continue;
      }
      $raw_toc[] = ['level' => $item_level, 'text' => trim($item_text)];
    }
    //var_dump( $raw_toc);

    return $file;
  }
}

WP_Readme_To_Markdown::load();

// let's convert this plugin's file!
do_action( 'wp_rtm/add', plugin_dir_path(__FILE__), 'wp_rtm', 'readme.txt', 'README.MD' );

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
add_action( 'activated_plugin', 'load_wp_rtm_first' );
