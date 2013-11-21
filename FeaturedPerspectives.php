<?php

class FeaturedPerspectives {
  private $version = '1.0';
  private $script_url = '/api/v1/enxt.js';
  private $url_prefix = 'https://';
  private $site_name = 'electnext.dev';
  private $email_contact = 'apikey@electnext.com';
  private $api_key;
  private $short_title;
  private $title;

  public function __construct() {
    $settings = get_option('wpfp_settings');
    $this->api_key = $settings['api_key'];
    $this->short_title = __('Featured Perspectives', 'wpfp');
    $this->title = __('Featured Perspectives by Versa', 'wpfp');
  }

  public function getVersion() {
    return $this->version;
  }

  public function run() {
    add_action('admin_head', array($this, 'display_missing_api_key_warning'));
    add_action('admin_init', array($this, 'init_settings'));
    add_action('admin_menu', array($this, 'add_settings_page'));
    add_filter('the_content', array($this, 'add_featured_perspective'));
  }

  public function display_missing_api_key_warning() {
    if ((basename($_SERVER['SCRIPT_NAME']) == 'plugins.php') && !$this->api_key) {
      echo '<div class="error"><p>';
      _e('Please go to the', 'wpfp');
      $link_text = $this->short_title . ' ' . __('settings page', 'wpfp');
      echo " <a href='options-general.php?page=wpfp'>$link_text</a> ";
      _e('to enter you API key.', 'wpfp');
      echo '</p></div>';
    }
  }

  public function init_settings() {
    register_setting('wpfp_settings', 'wpfp_settings');
    add_settings_section('wpfp_main', null, array($this, 'display_wpfp_main'), 'wpfp');
    add_settings_field(
      'wpfp_api_key',
      __('Featured Perspectives API Key', 'wpfp'),
      array($this, 'display_api_input'),
      'wpfp',
      'wpfp_main'
    );
  }

  public function display_wpfp_main() {
    echo null; // no need to bother displaying a header since there's just one section
  }

  public function display_api_input() {
    echo "<input id='wpfp_api_key' name='wpfp_settings[api_key]' type='text' value='{$this->api_key}' size='40'>";
  }

  public function add_settings_page() {
    add_options_page(
      $this->short_title,
      $this->short_title,
      'manage_options',
      'wpfp',
      array($this, 'display_settings_page')
    );
  }

  public function display_settings_page() {
    ?>
    <div class="wrap">
      <?php screen_icon(); ?>
      <h2><?php echo $this->title; ?></h2>

      <form action="options.php" method="post">
        <?php settings_fields('wpfp_settings'); ?>
        <?php do_settings_sections('wpfp'); ?>
        <p class="submit">
          <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes', 'wpfp'); ?>" class="button button-primary" />
        </p>

        <p><?php
          _e('To request an API key, please', 'wpfp');
          echo " <a href='mailto:{$this->email_contact}?subject=WordPress%20plugin%20API%20key%20request'>";
          _e('send an email to our WordPress partnerships director', 'wpfp');
          echo '</a>, ';
          _e("and let us know your site's domain name.", 'wpfp');
         ?></p>
      </form>
    </div>
    <?php
  }

  public function add_featured_perspective($content) {
    global $post;
    // the is_main_query() check ensures we don't add to sidebars, footers, etc
    if (is_main_query() && is_single()) {
      $fp = "
        <script data-electnext id='enxt-script' type='text/javascript'>
          //<![CDATA[
            var _enxt = _enxt || [];
            _enxt.push(['set_article', '$post->ID']);
            _enxt.push(['set_account', '{$this->api_key}']);
            _enxt.push(['setup_featured_perspective']);

            (function() {
              var enxt = document.createElement('script'); enxt.type = 'text/javascript'; enxt.async = true;
              enxt.src = '//{$this->site_name}{$this->script_url}';
              var k = document.getElementById('enxt-script');
              k.parentNode.insertBefore(enxt, k);
            })();
          //]]>
        </script>
      ";

      $content .= $fp;
    }

    return $content;
  }
}
