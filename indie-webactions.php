<?php
/**
 * Plugin Name: Indie WebActions
 * Plugin URI: https://github.com/dshanske/indie-webactions
 * Description: Web Actions Handler
 * Version: 0.1.0
 * Author: David Shanske
 * Author URI: http://david.shanske.com
 * Text Domain: Web Actions
 */

function indie_webactions_activation() {
  if (version_compare(phpversion(), 5.3, '<')) {
    die("The minimum PHP version required for this plugin is 5.3");
  }
}

register_activation_hook(__FILE__, 'indie_webactions_activation');

// Autoload
require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php');
use Mf2;

add_action('init', array('Web_Actions', 'init'), 12);


class Web_Actions {
  /**
   * Initialize the plugin.
   */
  public static function init() {
    add_filter('comment_reply_link', array('Web_Actions', 'comment_reply_link'), null, 4);
    add_action('comment_form_before', array('Web_Actions', 'comment_form_before'), 0);
    add_action('comment_form_after', array('Web_Actions', 'after'), 0);
    add_filter('query_vars', array('Web_Actions', 'query_var'));
    add_action('parse_query', array('Web_Actions', 'parse_query'));
    if (!taxonomy_exists('kind') ) {
      add_filter('the_content', array('Web_Actions', 'the_content'));
    }
  }

  /**
   * add webaction to the reply links in the comment section
   *
   * @param string $link the html representation of the comment link
   * @param array $args associative array of options
   * @param int $comment ID of comment being replied to
   * @param int $post ID of post that comment is going to be displayed on
   */
  public static function comment_reply_link( $link, $args, $comment, $post ) {
    $permalink = get_permalink($post->ID);

    return "<indie-action do='reply' with='".esc_url( add_query_arg( 'replytocom', $comment->comment_ID, $permalink ) )."'>$link</indie-action>";
  }

  /**
   * surround comment form with a reply action
   */
  public static function comment_form_before() {
    $post = get_queried_object();
    $permalink = get_permalink($post->ID);

    echo "<indie-action do='reply' with='$permalink'>";
  }

  /**
   * generic webaction "closer"
   */
  public static function after() {
    echo "</indie-action>";
  }

  public static function query_var($vars) {
    $vars[] = 'indie-action';
    return $vars;
  }

  public static function parse_query($wp) {
    $data = array_merge_recursive( $_POST, $_GET );
    // check if it is an action request or not
    if (!array_key_exists('indie-action', $wp->query_vars)) {
      return;
    }
    // If Not logged in, reject input
    if (!is_user_logged_in() ) {
      auth_redirect();
    }
    if (!current_user_can('publish_posts') ) {
      header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
      status_header(403);  
      _e ('User Does Not Have Permission to Publish', 'Web Actions');
      exit;
    }
    $action = $wp->query_vars['indie-action'];
    if ( taxonomy_exists('kind') ) {
      $actions = array('like', 'favorite', 'bookmark', 'repost');
    }
    else {
      $actions = array('bookmark');
    }
    if (!in_array($action, $actions)) {
      header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
      status_header(400);
      _e ('Invalid Action', 'Web Actions');
      exit;
    }
    if (!isset($data['url']) || isset($data['fill']) ) {
      header('Content-Type: text/html; charset=' . get_option('blog_charset'));
      Web_Actions::post_form($action);
      exit;
    }
    if (filter_var($data['url'], FILTER_VALIDATE_URL) === false) {
      status_header(400);
      _e ('The URL is Invalid', 'Web Actions');
      exit;
    }
    $args = array (
      'post_content' => ' ',
      'post_status'    => 'private', // Defaults to private
      'post_type' => 'post',
      'post_title' => current_time('Gis') // Post Title is the time
    );
    // If public is past, the post status is publish
    if (isset($data['public']) ) {
      $args['post_status'] = 'publish';
    }
    if (isset($data['content']) ) {
      $args['post_content'] = wp_kses_post( trim($data['content']) );
    }
    // tags will map to a category if exists, otherwise a tag
    if (isset($data['tags'])) {
      foreach ($data['tags'] as $mp_cat) {
        $wp_cat = get_category_by_slug($mp_cat);
        if ($wp_cat) {
          $args['post_category'][] = $wp_cat->term_id;
        } else {
          $args['tags_input'][] = $mp_cat;
        }
      }
    }
    $args = apply_filters('pre_kind_action', $args);
    $post_id = wp_insert_post($args, true);  
    if (is_wp_error($post_id) ) {
        status_header(400);
        echo $post_id->get_error_message();
        exit;
    }
    $cite = array();
    $cite[0] = array();
    $cite[0]['url'] = esc_url($data['url']);
    $cite[0]['name'] = sanitize_text_field( trim($data['title']) );
    if (isset($data['text']) ) {
      $cite[0]['content'] = wp_kses_post( trim($data['text']) );
    }
    if (isset($data['lat'])||isset($data['lon']) ) {
      update_post_meta($post_id, 'geo_latitude', sanitize_text_field(trim($data['lat'])) );
      update_post_meta($post_id, 'geo_longitude', sanitize_text_field(trim($data['lon'])) );
    }
    update_post_meta($post_id, 'mf2_cite', $cite); 
    if( taxonomy_exists('kind') ) {
        wp_set_object_terms($post_id, $action, 'kind');
    }
    else {
        switch($action) {
          case 'bookmark':
            set_post_format($post_id, 'link');
            break;
          default:
            set_post_format($post_id, 'aside');
        }
    }
    do_action('after_kind_action', $post_id);
    // Return just the link to the new post
    status_header (200);
    echo get_permalink($post_id);
    // Optionally instead redirect to the new post
    // wp_redirect(get_permalink($post_id));
    exit;
  }
  public static function the_content($content) {
    return $content;
  }

  public function post_form($kind) {
    echo '<title>';
    _e ('Quick Post', 'Web Actions');
    echo '</title>';
    echo '<h1>';
    _e ('Quick Post', 'Web Actions');
    echo ' - ' . $kind . '</h1>';
    echo '<form action="'. site_url()  . '/?indie-action=' . $kind . '" method="post">';

    echo '<p>';
    _e ('URL:', 'Web Actions'); 
    echo '<input type="url" name="url" size="70" /></p>';
    echo '<p>';
    _e('Name:', 'Web Actions');
    echo '<input type="text" name="title" size="70" /></p>';

    echo '<p>';
    _e('Author Name:', 'Web Actions');
    echo '<input type="text" name="author" size="70" /></p>';

    echo '<p>';
    _e('Publisher:', 'Web Actions');
    echo '<input type="text" name="publisher" size="70" /></p>';

    echo '<p>';
    _e('Tags(Comma separated):', 'Web Actions');
    echo '<input type="text" name="tags" size="70" /></p>';

    echo '<p>';
    _e('Content/Excerpt:', 'Web Actions');
    echo '<textarea name="text" rows="3" cols="70" ></textarea></p>';

    echo '<p><input type="submit" /></p>';
    echo '</form>';
  }

}
