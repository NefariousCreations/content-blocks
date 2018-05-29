<?php
/**
 * Plugin Name: Content Blocks by Nefarious Creations
 * Plugin URI: https://nefariouscreations.com.au
 * Description: Content Blocks Custom Content Type & Widget
 * Version: 1.0.1
 * Author: Nefarious Creations
 * Author URI: https://nefariouscreations.com.au
 */

/**
 * Load Plugin Update Checker
 */
require 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
  'https://github.com/NefariousCreations/content-blocks/',
  __FILE__, //Full path to the main plugin file or functions.php.
  'content-blocks'
);

$myUpdateChecker->setBranch('master');

//------------------------------------------------------------------------------

/**
 * Content Blocks Custom Post Type
 */
add_action( 'init', function () {

  /**
   * Labels
   */
  $labels = array(
    'name'                => 'Content Blocks',
    'singular_name'       => 'Content Block',
    'menu_name'           => 'Content Blocks',
    'name_admin_bar'      => 'Content Block',
    'add_new'             => 'Add New',
    'add_new_item'        => 'Add New Content Block',
    'new_item'            => 'New Content Block',
    'edit_item'           => 'Edit Content Block',
    'view_item'           => 'View Content Block',
    'all_items'           => 'All Content Blocks',
    'archives'            => 'Content Block Archives',
    'search_items'        => 'Search Content Blocks',
    'parent_item_colon'   => 'Parent Content Block:',
    'not_found'           => 'No Content Blocks found.',
    'not_found_in_trash'  => 'No Content Blocks found in Trash.',
  );

  /**
   * Settings
   * @link https://codex.wordpress.org/Function_Reference/register_post_type
   */
  $args = array(
    'capability_type'     => 'page',
    'has_archive'         => false,
    'hierarchical'        => true,

    'public'              => false,
    'publicly_queryable'  => false,
    'exclude_from_search' => true,

    'show_ui'             => true,
    'show_in_menu'        => true,
    'query_var'           => true,

    'rewrite'             => array( 'slug' => 'content-block', 'with_front' => false ),

    'supports'            => array( 'title', 'editor', 'revisions' ),

    'labels'              => $labels,
    'show_in_rest'        => true,
    'menu_position'       => 50,
    'menu_icon'           => 'dashicons-format-aside',
  );

  /**
   * Register Post Type
   */
  register_post_type( 'content-blocks', $args );

}, 1 );

/**
 * Flush rewrite rules
 * Description: Refreshing wp to display new custom post type and any changes.
 * Important note. disable this after development is finished.
 */
add_action( 'after_switch_theme', function () {
  flush_rewrite_rules();
}, 2);

/**
 * Content Blocks Widget
 */
class content_blocks_widget extends WP_Widget {

  /**
   * Holds widget settings defaults, populated in constructor.
   */
  protected $defaults;

  /**
   * Set the default widget options and construct the widget.
   */
  function __construct() {

    $this->defaults = array(
      'title'           => '',
      'display_title'   => '',
      'page_id'         => '',
      'show_title'      => 1,
    );

    $widget_ops = array(
      'classname'   => 'content_blocks_widget',
      'description' => __( 'Displays a content block', 'sage' ),
    );

    parent::__construct( 'content_blocks_widget', __( 'Content Blocks', 'sage' ), $widget_ops );

  }

  /**
   * Create the widget content.
   */
  function widget( $args, $instance ) {

    global $wp_query;

    // Merge content block widget settings with default widget settings
    $instance = wp_parse_args( (array) $instance, $this->defaults );

    // Before Widget Wrap
    $widget_content  = $args['before_widget'];

    // Content Block
    $wp_query = new WP_Query( array('post_type' => 'content-blocks', 'page_id' => $instance['page_id'] ) );

    if ( have_posts() ) : while ( have_posts() ) : the_post();

      // Widget Title Open Wrap
      $widget_content .= $args['before_title'];
      // Display Widget Set Title
      $widget_content .= apply_filters('widget_title', $instance['display_title'], $instance, $this->id_base);
      // Widget Title Close Wrap
      $widget_content .= $args['after_title'];
      
      // Widget Content
      $widget_content .= '<div class="entry-content">';

      // Individually apply content filters to avoid conflicting with content builders
      $content_block_content = get_the_content();
      $content_block_content = wptexturize($content_block_content);
      $content_block_content = convert_smilies($content_block_content);
      $content_block_content = convert_chars($content_block_content);
      $content_block_content = wpautop($content_block_content);
      $content_block_content = shortcode_unautop($content_block_content);
      $content_block_content = prepend_attachment($content_block_content);

      // Do the shortcode and add to widget content
      $widget_content .= do_shortcode($content_block_content);

      $widget_content .= '</div>';

      endwhile;
    endif;

    // Restore original query
    wp_reset_query();

    // After Widget Wrap
    $widget_content .= $args['after_widget'];

    // Echo The Widget
    echo $widget_content;

  }

  /**
   * Update a particular instance of the widget.
   */
  function update( $new_instance, $old_instance ) {

    // If the content block has been selected get the title
    if ($new_instance['page_id']) {

      global $wp_query;

      $wp_query = new WP_Query( array('post_type' => 'content-blocks', 'page_id' => $new_instance['page_id'] ) );

      if ( have_posts() ) : while ( have_posts() ) : the_post();

        $new_instance['title'] = get_the_title();

        endwhile;
      endif;

      // Restore original query
      wp_reset_query();

    }

    // Set the instances for all checkboxes
    $new_instance['show_title'] = isset($new_instance['show_title']) ? $new_instance['show_title'] : 0;

    return $new_instance;
  }

  /**
   * Create the admin settings form.
   */
  function form( $instance ) {

    // Merge with defaults
    $instance = wp_parse_args( (array) $instance, $this->defaults );
    ?>

    <!--Set a widget title text field-->
    <!--This field is created only to satisfy the $new-instance need for a field to be present-->
    <!--The title is set from the content block title-->
    <p style="display: none;">
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'sage' ); ?>:</label>
      <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
    </p>

    <!--Set a widget display_title text field-->
    <p>
      <label for="<?php echo $this->get_field_id( 'display_title' ); ?>"><?php _e( 'Display Title', 'sage' ); ?>:</label>
      <input type="text" id="<?php echo $this->get_field_id( 'display_title' ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_title' ) ); ?>" value="<?php echo esc_attr( $instance['display_title'] ); ?>" class="widefat" />
    </p>

    <!--Content block selection dropdown-->
    <p>
      <label for="<?php echo $this->get_field_id( 'page_id' ); ?>"><?php _e( 'Content Block', 'sage' ); ?>:</label>
      <?php
        wp_dropdown_pages( array(
          'name'      => esc_attr( $this->get_field_name( 'page_id' ) ),
          'id'        => $this->get_field_id( 'page_id' ),
          'selected'  => $instance['page_id'],
          'post_type' => 'content-blocks'
        ) );
      ?>
    </p>

    <hr class="div">

    <!--Show the content block or widget title checkbox-->
    <p>
      <input id="<?php echo esc_attr( $this->get_field_id( 'show_title' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_title' ) ); ?>" value="1"<?php checked( $instance['show_title'] ); ?> />
      <label for="<?php echo esc_attr( $this->get_field_id( 'show_title' ) ); ?>"><?php _e( 'Show Title', 'sage' ); ?></label>
    </p>

    <?php

  }

}

/**
 * Register & Load The Widget
 */
add_action( 'widgets_init', function () {
  register_widget( 'content_blocks_widget' );
});