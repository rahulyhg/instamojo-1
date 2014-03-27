<?php

define('ENCRYPTION_KEY', '!@#$%^&*');
define('APPLICATION_ID', 'acd73b5ac8ccd76be2dafc46e082d415');

include_once(__DIR__.'/lib/Instamojo.php');

/**
 * Instamojo Settings Page
 */
class InstamojoSettingsPage
{
  private $_options;

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_plugin_page'));
    add_action('admin_init', array($this, 'page_init'));
    add_action('updated_option', array($this, 'auth_token'));
  }

  public function add_plugin_page()
  {
    add_options_page(
      'Instamojo Options',
      'Instamojo',
      'manage_options',
      'instamojo-admin',
      array($this, 'create_admin_page')
    );
  }

  public function create_admin_page()
  {
    $this->_options = get_option('instamojo_credentials');
    if (isset($this->_options['auth_token']))
    {
      echo '<div class="update-nag"><p>You have already authenticated your account with us. If you wish to switch accounts then enter your details again.</p></div>';
    }
    ?>
    <div class="wrap">
      <h2>Instamojo Options</h2>
      <form method="post" action="options.php">
      <?php settings_fields('instamojo_credentials-group'); ?>
      <?php do_settings_sections('instamojo-admin'); ?>
      <?php submit_button('Authenticate'); ?>
      </form>
    </div>
    <?php
  }

  public function page_init()
  {
    register_setting(
      'instamojo_credentials-group',
      'instamojo_credentials',
      array($this, 'sanitize')
    );

    add_settings_section(
      'credentials',
      'Instamojo Credentials',
      array($this, 'print_section_info'),
      'instamojo-admin'
    );

    add_settings_field(
      'instamojo-username',
      'Username',
      array($this, 'username_callback'),
      'instamojo-admin',
      'credentials'
    );

    add_settings_field(
      'instamojo-password',
      'Password',
      array($this, 'password_callback'),
      'instamojo-admin',
      'credentials'
    );
  }


  /**
   * Sanitize each setting field as needed
   *
   * @param array $input Contains all settings fields as array keys
   */
  public function sanitize($input)
  {
    $new_input = array();

    if(isset($input['username']))
    {
      $new_input['username'] = sanitize_text_field($input['username']);
    }

    if(isset($input['password']))
    {
      $new_input['password'] = $input['password'];
    }

    return $new_input;
  }

  /**
   * Print the Section text
   */
  public function print_section_info()
  {
    echo '<p>Enter your login credentials below:</p>';
  }

  public function username_callback()
  {
    echo '<input type="text" name="instamojo_credentials[username]" />';
  }

  public function password_callback()
  {
    echo '<input type="password" name="instamojo_credentials[password]" />';
  }

  public function auth_token()
  {
    $options = get_option('instamojo_credentials');
    if (!$options['auth_token'])
    {
      $instance = new Instamojo($options['username'], $options['password'], APPLICATION_ID);
      $auth = $instance->apiAuth();
      $options['auth_token'] = $auth['token'];
      unset($options['username']);
      unset($options['password']);
      update_option('instamojo_credentials', $options);
      return $this;
    }
  }

}

if (is_admin())
{
  $my_settings_page = new InstamojoSettingsPage();
}

?>