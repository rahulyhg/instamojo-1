<?php

/**
 * Shortcodes
 * Use the shortcode generator from the Settings Page
 *
 * [instamojo offer="your-offer-slug" style="light" text="Checkout my offer"]
 */

add_shortcode('instamojo', 'instamojo_button');

function instamojo_button($attributes, $content = null)
{
  extract(shortcode_atts(array(
    'offer' => null,
    'style' => 'none',
    'text'  => 'Checkout with Instamojo'
  ), $attributes));

  $instamojo_credentials = get_option('instamojo_credentials');

  wp_register_script('widgetjs', 'https://d2xwmjc4uy2hr5.cloudfront.net/im-embed/im-embed.min.js', 'jquery', null, true);
  wp_enqueue_script('widgetjs');

  if ($offer)
  {
    return '<a href="https://www.instamojo.com/'.$instamojo_credentials['username'].'/'.$offer.'/" rel="im-checkout" data-style="'.$style.'" data-text="'.$text.'"></a>';
  }
}

?>