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
      <?php settings_fields('instamojo-credentials'); ?>
      <?php do_settings_sections('instamojo-credentials'); ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">Username</th>
          <td><input type="text" name="instamojo-username" value="<?php echo get_option('instamojo-username'); ?>"></input></td>
        </tr>
        <tr valign="top">
          <th scope="row">Password</th>
          <td><input type="text" name="instamojo-password"></input></td>
        </tr>
      </table>
      <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  public function page_init()
  {
    register_setting(
      'instamojo-credentials',
      'instamojo-username',
      array($this, 'sanitize')
    );

    register_setting(
      'instamojo-credentials',
      'instamojo-password',
      array($this, 'sanitize')
    );

    register_setting(
      'instamojo-credentials',
      'instamojo-auth-token'
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
      array($this, 'password\_callback'),
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
    printf(
      '<input type="text" id="instamojo-username" name="instamojo_credentials[username]" value="%s" />',
      isset($this->options['username']) ? esc_attr($this->options['username']) : ''
    );
  }

  public function password_callback()
  {
    printf(
      '<input type="text" id="instamojo-password" name="instamojo_credentials[password]" value="%s" />',
      isset($this->options['password']) ? esc_attr($this->options['password']) : ''
    );
  }

}

if (!current_user_can('manage_options'))
{
  wp_die(__('You do not have sufficient permissions to access this page.'));
}
else
{
  $my_settings_page = new InstamojoSettingsPage();
}

?>