<?php

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
    $this->_options = get_option('instamojo-credentials');
    ?>
    <div class="wrap">
      <?php screen_icon(); ?>
      <h2>Instamojo Options</h2>
      <form method="post" action="options.php">
      <?php settings_fields('instamojo-credentials-group'); ?>
      <?php do_settings_sections('instamojo-admin'); ?>
      <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  public function page_init()
  {
    register_setting(
      'instamojo-credentials-group',
      'instamojo-credentials',
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
      $new_input['username'] = sanitize_text_field($input['username']);

    return $new_input;
  }

  /**
   * Print the Section text
   */
  public function print_section_info()
  {
    print 'Enter your settings below:';
  }

  public function username_callback()
  {
    $username = $this->_options['username'];
    echo '<input type="text" name="instamojo-credentials[username]" value="'.$username.'" />';
  }

  public function password_callback()
  {
    echo '<input type="password" name="instamojo-credentials[password]" />';
  }

}

if (is_admin())
{
  $my_settings_page = new InstamojoSettingsPage();
}

?>