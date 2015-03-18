<?php

include_once(dirname(__FILE__).'/constants.php');
include_once(dirname(__FILE__).'/lib/Instamojo.php');

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
    add_action('admin_notices', array($this, 'plugin_notices'));
    add_action('load-settings_page_instamojo', array($this, 'add_contextual_help'));
  }

  // Responsible for adding the setting link to the WordPress menu list
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

  // Register all settings in here
  public function page_init()
  {
    register_setting(
      'instamojo_credentials-group',
      'instamojo_credentials'
    );
  }

  // Add contextual help to the plugin page
  public function add_contextual_help()
  {
    $screen = get_current_screen();

    $screen->add_help_tab(array(
      'id' => 'getting-started',
      'title' => __('Getting Started'),
      'content' => '<p>'.__('To use this plugin, you require an <a href="https://www.instamojo.com/accounts/register/" target="_new">Instamojo account</a>.').'</p><p>'.__('If you already have an account with Instamojo and have created offers there, then you can authenticate you account by filling up the credentials. Enter your Instamojo Username and Password, and then click the <b>Authenticate</b> button.').'</p>'
    ));
    $screen->add_help_tab(array(
      'id' => 'usage',
      'title' => __('Usage'),
      'content' => '<p>'.__('After authenticating your account, you can now use the Instamojo Widget which is available from <b>Appearance > Widgets</b>.').'</p><p>You can also use the shortcode generator available in the options page. This shortcode can be used to embed buttons into posts and pages.</p>'
    ));
    $screen->add_help_tab(array(
      'id' => 'revoke',
      'title' => __('Revoking your token'),
      'content' => '<p>'.__('If you do not wish to use the plugin anymore and wish to revoke your token associated with this application, you can click the <b>Revoke Token</b> button.').'</p>'
    ));

    $screen->set_help_sidebar('<ul><li><a href="https://www.instamojo.com" target="_new">Instamojo Website</a><li><li><a href="https://www.instamojo.com/developers" target="_new">Instamojo API</a><li></ul>');
  }

  // Notices generated to help the user
  public function plugin_notices()
  {
    // Retrieve all stored options from the database
    if(isset($_POST['submit']) or isset($_POST['revoke'])){
      return;
    }
    $this->_options = get_option('instamojo_credentials');

    // Get the Auth Token from the options
    $auth_token = $this->_options['auth_token'];

    if (!$auth_token)
    {
      // Display notice if Auth Token is already stored
      echo '<div class="error"><p>Please authenticate your account first before you use the Instamojo Widget.</p></div>';
    }
  }

  // Handle all tabs for the settings page
  public function admin_tabs()
  {
    $current = isset($_GET['tab']) ? $_GET['tab'] : 'homepage';

    // All tabs
    $tabs = array(
      'homepage'    => 'Instamojo Credentials',
      'shortcode'   => 'Shortcode Generator'
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

  // The setting page
  public function create_admin_page()
  {
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'homepage';

    // Retrieve all stored options from the database
    $this->_options = get_option('instamojo_credentials');

    // Get the Auth Token from the options
    $auth_token = $this->_options['auth_token'];

    // Check if the submit button was pressed and the form was submitted
    if (isset($_POST['submit']))
    {
      if (function_exists('current_user_can') && !current_user_can('manage_options'))
      {
        // Die if current user cannot manage options
        die(__('Cheatin&#8217; uh?'));
      }

      // Get the POST data out
      $instamojo_credentials = $_POST['instamojo_credentials'];

      // Check if any data was sent
      if (isset($instamojo_credentials))
      {
        if (isset($auth_token))
        {
          // Revoke token if Auth Token already exists
          $this->revoke_token($auth_token);
        }

        // Create new instance to interact with Instamojo API
        $instance = new Instamojo(APPLICATION_ID, $instamojo_credentials['username'], $instamojo_credentials['password']);
        try
        {
          $auth = $instance->apiAuth();
          $instamojo_credentials['auth_token'] = $auth['token'];
          unset($instamojo_credentials['password']);

          // Update options with Username and Auth Token
          update_option('instamojo_credentials', $instamojo_credentials);
        }
        catch (Exception $e)
        {
          wp_cache_set('message', 'Seems like the credentials you entered were incorrect. Please try authenticating again.', 'instamojo-plugin');
        }
      }
    }

    // Check if request for revoking Auth Token was sent
    if (isset($_POST['revoke']))
    {
      if (isset($auth_token))
      {
        // Revoke token if Auth Token already exists
        $this->revoke_token($auth_token);
        unset($auth_token);
        echo '<div class="updated"><p>Token revoked successfully.</p></div>';
      }
    }

    $message = wp_cache_get('message', 'instamojo-plugin');

    if ($message)
    {
      echo '<div class="error"><p>'.$message.'</p></div>';
      wp_cache_delete('message', 'instamojo-plugin');
    }

    if (isset($auth_token) && !isset($auth) && $tab =='homepage')
    {
      // Display notice if Auth Token is already stored
      echo '<div class="updated"><p>You have already authenticated your account with us. If you wish to switch accounts then enter your details again.</p></div>';
    }
    else if(isset($auth) && $tab == 'homepage'){
      echo '<div class="updated"><p>Thanks for authenticating your account with us.</p></div>';      
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
          <input type="submit" name="revoke" id="revoke-button" class="button button-secondary" value="<?php _e('Revoke Token'); ?>" <?php if (!(isset($auth_token) or isset($auth))) echo 'disabled'; ?> />
        </p>
      </form>
      <?php
          break;

        case 'shortcode':
          if (!$auth_token)
          {
            echo '<p>You need to authenticate your account first to use this feature.</p>';
          }
          else
          {
            // Create new instance to interact with Instamojo API
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

          // If offer, button style or the button text are changed
          // generate shortcode and update the textarea
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
          }
          break;

        default:
          break;
      }
      ?>
    </div>
    <?php
  }

  /**
   * Revoke Token
   * @param string $auth_token Auth Token stored in options
   */
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
