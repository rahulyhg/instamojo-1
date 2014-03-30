<?php

include_once(__DIR__.'/constants.php');
include_once(__DIR__.'/lib/Instamojo.php');

/**
 * Instamojo Widget
 * It extends the WordPress Widget class
 */
class Instamojo_Widget extends WP_Widget
{

  /**
   *  Default constructor
   */
  function __construct()
  {
    // Load any other optional scripts
    add_action('load-widgets.php', array(&$this, 'my_custom_load'));

    // Name and class of widget
    $widget_options = array(
      'classname' => 'instamojo-widget',
      'description' => 'Display Instamojo offers in your blog.');

    // Id, width and height of the widget
    $control_options = array(
      'id_base' => 'instamojo-widget',
      'width' => 300,
      'height' => 200);

    // Initialize the widget.
    $this->WP_Widget('instamojo-widget', 'Instamojo',  $widget_options, $control_options);
  }

  /**
   *  Called in the constructor.
   */
  function my_custom_load()
  {

  }

  /**
   *  Implements the widget() function as required by WordPress
   *  This is responsible for how the widget looks in your WordPress site
   */
  function widget($args, $instance)
  {
    wp_register_style('widgetcss', plugin_dir_url(__FILE__).'assets/css/imojo.css');
    wp_enqueue_style('widgetcss');

    $instamojo_credentials = get_option('instamojo_credentials');

    extract($args);
    if (!isset($instance['title']))
    {
      $instance['title'] = '';
    }
    $title = apply_filters('widget_title', $instance['title']);
    echo $before_widget;

    // If title is not given make it My Instamojo Product
    if ($instance['title']) {
      echo $before_title.$instance['title'].$after_title;
    }
    else {
      echo $before_title.'My Instamojo Product'.$after_title;
    }

    $button_html = '<div class="btn-container"><a href="https://www.instamojo.com/'.$instamojo_credentials['username'].'/'.$instance['instamojo_offer'].'" ';
    if ($instance['button_style'] != 'none') {
      $button_html .= 'class="im-checkout-btn btn--'.$instance['button_style'].'" ';
    }
    $button_html .= 'target="_blank">Buy Now</a></div>';
    echo $button_html;
    echo $after_widget;
  }

  /**
   *  Implements the update() function as required by WordPress
   *  This works when you fill data in the widget form input from the WordPress admin
   */
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    return $instance;
  }

  /**
   *  Implements the form() function as required by WordPress
   *  This is responsible for how the form in the WordPress admin looks
   */
  function form($instance)
  {
    $defaults = array('title' => '', 'instamojo_offer' => '', 'button_style' => 'none');
    $instance = wp_parse_args((array)$instance, $defaults);

    $instamojo_credentials = get_option('instamojo_credentials');
    if (!$instamojo_credentials['auth_token'])
    {
      ?>
      <p>Please authenticate your account first.</p>
      <?php
    }
    else
    {
      // Create Instamojo instance for interacting with API
      $instamojo = new Instamojo(APPLICATION_ID);
      $instamojo->setAuthToken($instamojo_credentials['auth_token']);
      $offerObject = $instamojo->listAllOffers();
      $offers = $offerObject['offers'];
      ?>
      <p>
        <label for="<?php echo $this->get_field_id('title');?>">Widget Title:</label>
        <input class="widefat" id="<?php echo $this->get_field_id('title');?>"
          name="<?php echo $this->get_field_name('title');?>"
          value="<?php echo $instance['title'];?>" />
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('instamojo_offer'); ?>">Instamojo Offer:</label>
        <select id="<?php echo $this->get_field_id('instamojo_offer'); ?>" name="<?php echo $this->get_field_name('instamojo_offer'); ?>">
          <option value="none" <?php if($instance['instamojo_offer'] == '') echo 'selected="selected"'; ?>>None</option>
        <?php
          foreach ($offers as $offer) {
        ?>
          <option value="<?php echo $offer['slug']; ?>" <?php if($instance['instamojo_offer'] == $offer['slug']) echo 'selected="selected"'; ?>><?php echo $offer['title']; ?></option>
        <?php
          }
        ?>
        </select>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('button_style'); ?>">Button Style</label>
        <select id="<?php echo $this->get_field_id('button_style'); ?>" name="<?php echo $this->get_field_name('button_style'); ?>">
          <option value="light" <?php if($instance['button_style'] == 'light') echo 'selected="selected"'; ?>>Light</option>
          <option value="dark" <?php if($instance['button_style'] == 'dark') echo 'selected="selected"'; ?>>Dark</option>
          <option value="flat" <?php if($instance['button_style'] == 'flat') echo 'selected="selected"'; ?>>Flat Light</option>
          <option value="flat-dark" <?php if($instance['button_style'] == 'flat-dark') echo 'selected="selected"'; ?>>Flat Dark</option>
          <option value="none" <?php if($instance['button_style'] == 'none') echo 'selected="selected"'; ?>>None</option>
        </select>
      </p>
    <?php
    }
  }
}
?>
