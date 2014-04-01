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

  public function page_init()
  {
    register_setting(
      'instamojo_credentials-group',
      'instamojo_credentials'
    );
  }

  public function admin_tabs()
  {
    $current = isset($_GET['tab']) ? $_GET['tab'] : 'homepage';

    $tabs = array(
      'homepage'    => 'Instamojo Credentials',
      'shortcode'   => 'Shortcodes'
    );
    ?>
    <h2><?php _e('Instamojo Options') ?></h2>
    <h3 class="nav-tab-wrapper">
    <?php
    foreach ($tabs as $tab => $name)
    {
      $class = ($tab == $current) ? ' nav-tab-active' : '';
      ?>
      <a class="nav-tab<?php echo $class; ?>" href="?page=instamojo&tab=<?php echo $tab; ?>"><?php _e($name); ?></a>
    <?php
    }
    ?>
    </h3>
    <?php
  }

  public function create_admin_page()
  {
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'homepage';

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
      echo '<div class="updated"><p>You have already authenticated your account with us. If you wish to switch accounts then enter your details again.</p></div>';
    }
    else
    {
      echo '<div class="error"><p>Please authenticate your account first before you use the Instamojo Widget.</p></div>';
    }
    ?>
    <div class="wrap">
      <?php $this->admin_tabs(); ?>
      <?php
      switch ($tab)
      {
        case 'homepage':
      ?>
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
      <?php
          break;

        case 'shortcode':
          // Create Instamojo instance for interacting with API
          $instamojo = new Instamojo(APPLICATION_ID);
          $instamojo->setAuthToken($auth_token);
          $offerObject = $instamojo->listAllOffers();
          $offers = $offerObject['offers'];
      ?>
      <form method="" action="" id="instamojo-shortcode-generate">
        <table class="form-table">
          <tbody>
            <tr>
              <th>
                <label for="instamojo_offer"><?php _e('Instamojo Offer'); ?></label>
              </th>
              <td>
                <select id="instamojo_offer" name="instamojo-offer">
                  <option value="none" selected="selected">None</option>
                <?php
                  foreach ($offers as $offer) {
                ?>
                  <option value="<?php echo $offer['slug']; ?>"><?php echo $offer['title']; ?></option>
                <?php
                  }
                ?>
                </select>
              </td>
            </tr>
            <tr>
              <th>
                <label for="instamojo_style"><?php _e('Button Style'); ?></label>
              </th>
              <td>
                <select id="instamojo_style" name="button-style">
                  <option value="none" selected="selected">None</option>
                  <option value="light">Light</option>
                  <option value="dark">Dark</option>
                  <option value="flat">Flat Light</option>
                  <option value="flat-dark">Flat Dark</option>
                </select>
              </td>
            </tr>
            <tr>
              <th>
                <label for="instamojo_text"><?php _e('Button Text'); ?></label>
              </th>
              <td>
                <input type="text" id="instamojo_text" name="button-text" value="Checkout with Instamojo" />
              </td>
            </tr>
            <tr>
              <th>
                <label for="instamojo_shortcode_output"><?php _e('Shortcode'); ?></label>
              </th>
              <td>
                <textarea id="generatedShortcode" contenteditable></textarea>
              </td>
            </tr>
          </tbody>
        </table>
      </form>
      <script type="text/javascript">
        jQuery(document).ready(function() {
          generateShortcode();

          jQuery('#instamojo_offer, #instamojo_style, #instamojo_text').change(function() {
            generateShortcode();
          });

          function generateShortcode() {
            var $form = jQuery('#instamojo-shortcode-generate');
            var offer = $form.find('#instamojo_offer').val();
            var style = $form.find('#instamojo_style').val();
            var text = $form.find('#instamojo_text').val();

            var output = '[instamojo';

            if (offer !== 'none') {
              output = output + ' offer="' + offer + '"';
            }

            output = output + ' style="' + style + '"';

            output = output + ' text="' + text + '"';

            output = output + ']';

            jQuery('#generatedShortcode').text(output);
          }
        });
      </script>
      <?php
          break;

        default:
          break;
      }
      ?>
    </div>
    <?php
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