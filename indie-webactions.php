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

// OpenGraph
require_once( plugin_dir_path( __FILE__ ) . 'Parser.php');

add_action('init', array('Web_Actions', 'init'), 12);


class Web_Actions {
  /**
   * Initialize the plugin.
   */
  public static function init() {
    // Offer the option of this being handled by the theme
    if(!current_theme_supports('web-actions')) {
      add_filter('comment_reply_link', array('Web_Actions', 'comment_reply_link'), null, 4);
      add_action('comment_form_before', array('Web_Actions', 'comment_form_before'), 0);
      add_action('comment_form_after', array('Web_Actions', 'after'), 0);
    }
    add_filter('query_vars', array('Web_Actions', 'query_var'));
    add_action('parse_query', array('Web_Actions', 'parse_query'));
    // If Post Kinds isn't activated show a basic bookmark display
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
  public static function kind_actions() {
    $actions = array('like', 'favorite', 'bookmark', 'repost', 'note', 'reply');
    return apply_filters('supported_webactions', $actions);
  }
 public static function unkind_actions() {
    $actions = array('bookmark', 'note');
    return apply_filters('supported_webactions', $actions);
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
      $actions = Web_Actions::kind_actions();
    }
    else {
      $actions = Web_Actions::unkind_actions();
    }
    if (!in_array($action, $actions)) {
      header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
      status_header(400);
      _e ('Unsupported or Disabled Action', 'Web Actions');
      exit;
    }
    if ( !isset($data['url'])&&!isset($data['postform']) ) {
      Web_Actions::form_header();
      Web_Actions::post_form($action, $data['test']);
      Web_Actions::form_footer();
      exit;
    }
    if ( isset($data['url']) && (filter_var($data['url'], FILTER_VALIDATE_URL) === false) ) {
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
    // Set categories if exists
    if (isset($data['tags'])) {
      $tags = array_map('trim', explode(',', $data['tags']));
      foreach ($tags as $cat) {
        $wp_cat = get_category_by_slug($cat);
        if ($wp_cat) {
          $args['post_category'][] = $wp_cat->term_id;
        } else {
          $args['tags_input'][] = $cat;
        }
      }
    }
    $response = Web_Actions::parse($data['url']);
    $args['post_title'] =  $response['name'] ?: current_time('Gis');
    if (isset($data['test']) ) {
      header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
      status_header(200);
      $return = Web_Actions::parse($data['url']);
      var_dump($return);
      exit;
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
    $cite[0]['name'] = sanitize_text_field( trim($data['title']) ) ?: $response['name'];
    $cite[0]['content'] = wp_kses_post( trim($data['text']) ) ?: $response['content'];
    if (isset($data['lat'])||isset($data['lon']) ) {
      update_post_meta($post_id, 'geo_latitude', sanitize_text_field(trim($data['lat'])) );
      update_post_meta($post_id, 'geo_longitude', sanitize_text_field(trim($data['lon'])) );
    }
    $cite[0]['publication'] = $cite[0]['publication'] ?: $response['site'];
    $cite = array_filter($cite);
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
    // redirect to the new post
    wp_redirect(get_permalink($post_id));
    exit;
  }
  // Under Development
  public static function parse($url) {
    $response = wp_remote_get($url);
    if (is_wp_error($response) ) {
        return $response;
    }
    $data = array();
    $body = wp_remote_retrieve_body($response);
    $meta = \ogp\Parser::parse($body);
    $domain = parse_url($url, PHP_URL_HOST);
    $data['name'] = $meta['og:title'] ?: $meta['twitter:title'];
    $data['content'] = $meta['og:description'] ?: $meta['twitter:description'];
    $data['author'] = $meta['article:author'];
    $data['site'] = $meta['og:site'] ?: $meta['twitter:site'];
    $data['image'] = $meta['og:image'] ?: $meta['twitter:image'];
    $data['entry'] = $entry;
    $data['meta'] = $meta;
    return array_filter($data);
  }

  public static function the_content($content) { 
    $cite = get_post_meta(get_the_ID(), 'mf2_cite', true); 
    if($cite) {
      $c = '<p>' . __('Bookmarked', 'Web Actions') . ' - ' . '<a href="' . $cite[0]['url'] . '">' . $cite[0]['name'] . '</a></p>';
      $content = $c . $content;
    }
    return $content;
  }
  public function form_header() {
    header('Content-Type: text/html; charset=' . get_option('blog_charset'));  
    ?>
      <!DOCTYPE html>
      <html <?php language_attributes(); ?>>
        <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <link rel="profile" href="http://microformats.org/profile/specs" />
        <link rel="profile" href="http://microformats.org/profile/hatom" />
        <link rel='stylesheet' id='indie-webaction-css'  href='<?php echo plugin_dir_url( __FILE__ ) . 'css/webaction.css'; ?>' type='text/css' media='all' />

        <?php do_action('indie_webaction_form_head'); ?> 
        <title><?php echo get_bloginfo('name'); ?>  - <?php _e ('Quick Post', 'Web Actions'); ?></title> 
       </head>
        <body>
        <header> 
           <h3><a href="<?php echo site_url(); ?>"><?php echo get_bloginfo('name');?></a>
           <a href="<?php echo admin_url(); ?>">(<?php _e('Dashboard', 'Web Actions'); ?>)</a></h3>
           <hr />
           <h1> <?php _e ('Quick Post', 'Web Actions'); ?></h1>
        </header>
      <?php
  }
  public static function form_footer() {
    ?>
      </body>
      </html>
    <?php
  }
  public static function post_form($action, $test=null) {
    ?>
      <form action="<?php echo site_url();?>/?indie-action=<?php echo $action;?>" method="post">
      <?php
        switch ($action) {
          case 'note':
            ?>
                <p>
                <textarea name="content" rows="3" cols="50" maxlength="140" ></textarea> 
              </p>
            <?php
            break;
          case 'reply':
          ?>
                <p> <?php _e ('Reply:' , 'Web Actions'); ?>
                <textarea name="content" rows="3" cols="50" ></textarea>
              </p>
          <?php
          default:
      ?>
          <p>
            <?php _e ('URL:', 'Web Actions'); ?>
            <input type="url" name="url" size="70" />
          </p>
          <p>
            <?php _e('Name:', 'Web Actions'); ?>
            <input type="text" name="title" size="70" />
          </p>
          <p>
            <?php _e('Author Name:', 'Web Actions'); ?>
            <input type="text" name="author" size="35" />
          </p>
          <p>
            <?php _e('Site Name/Publication:', 'Web Actions'); ?>
            <input type="text" name="publisher" size="35" />
          </p>
          <p>
           <?php _e('Content/Excerpt:', 'Web Actions'); ?>
           <textarea name="text" rows="3" cols="50" ></textarea>
         </p>
     <?php }
    ?>
          <p>
            <?php _e('Tags(Comma separated):', 'Web Actions'); ?>
            <input type="text" name="tags" size="35" />
          </p>
          <p>
            <?php _e('Public Post:', 'Web Actions'); ?>
            <input type="checkbox" name="public" />
          </p>
         <input type="hidden" name="postform" value="1" />
    <?php
        if ($test!=null) {
          echo '<input type="hidden" name="test" value="1" />';
        }
        do_action('indie_webaction_form_fields');  
    ?>
         <p><input type="submit" />
         </p>
      </form>
    <?php
  }

}
