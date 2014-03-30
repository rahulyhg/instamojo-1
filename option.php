<?php

include_once(__DIR__.'/constants.php');
include_once(__DIR__.'/lib/Instamojo.php');

/**
 * Instamojo Settings Page
 */
class Instamojo_Settings_Page
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
      'instamojo',
      array($this, 'create_admin_page')
    );
  }

  public function create_admin_page()
  {
    $this->_options = get_option('instamojo_credentials');
    $auth_token = $this->_options['auth_token'];

    if (isset($_POST['submit']))
    {
      if (function_exists('current_user_can') && !current_user_can('manage_options'))
      {
        die(__('Cheatin&#8217; uh?'));
      }

      $instamojo_credentials = $_POST['instamojo_credentials'];
      if (isset($instamojo_credentials))
      {
        if (isset($auth_token))
        {
          $this->revoke_token($auth_token);
        }
        $instance = new Instamojo(APPLICATION_ID, $instamojo_credentials['username'], $instamojo_credentials['password']);
        $auth = $instance->apiAuth();
        $instamojo_credentials['auth_token'] = $auth['token'];
        unset($instamojo_credentials['password']);
        update_option('instamojo_credentials', $instamojo_credentials);
      }
    }

    if (isset($_POST['revoke']))
    {
      if (isset($auth_token))
      {
        $this->revoke_token($auth_token);
      }
    }

    if ($auth_token)
    {
      echo '<div class="update-nag"><p>You have already authenticated your account with us. If you wish to switch accounts then enter your details again.</p></div>';
    }
    else
    {
      echo '<div class="error"><p>Please authenticate your account first before you use the Instamojo Widget.</p></div>';
    }
    ?>
    <div class="wrap">
      <h2><?php _e('Instamojo Options'); ?></h2>

      <h3><?php _e('Instamojo Credentials'); ?></h3>
      <form method="post" action="" id="instamojo-conf">
        <table class="form-table">
          <tbody>
            <tr>
              <th>
                <label for="instamojo-username"><?php _e('Username'); ?></label>
              </th>
              <td>
                <input type="text" id="instamojo-username" name="instamojo_credentials[username]" />
              </td>
            </tr>
            <tr>
              <th>
                <label for="instamojo-password"><?php _e('Password'); ?></label>
              </th>
              <td>
                <input type="password" id="instamojo-password" name="instamojo_credentials[password]" />
              </td>
            </tr>
          </tbody>
        </table>
        <p class="submit">
          <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Authenticate'); ?>" />
        </p>
      </form>

      <h3><?php _e('Revoke Your Authentication Token'); ?></h3>
      <form method="post" action="" id="instamojo-token-revoke">
        <p class="submit">
          <input type="submit" name="revoke" id="revoke-button" class="button button-secondary" value="<?php _e('Revoke Token'); ?>" <?php if (!$this->_options['auth_token']) echo 'disabled'; ?> />
        </p>
      </form>
    </div>
    <?php
  }

  public function page_init()
  {
    register_setting(
      'instamojo_credentials-group',
      'instamojo_credentials'
    );
  }

  public function username_callback()
  {
    echo '<input type="text" name="instamojo_credentials[username]" />';
  }

  public function password_callback()
  {
    echo '<input type="password" name="instamojo_credentials[password]" />';
  }

  private function revoke_token($auth_token)
  {
    $instance = new Instamojo(APPLICATION_ID);
    $instance->setAuthToken($auth_token);
    $instance->deleteAuthToken();
    delete_option('instamojo_credentials');
    unset($instance);
  }
}

if (is_admin())
{
  $my_settings_page = new Instamojo_Settings_Page();
}

?>