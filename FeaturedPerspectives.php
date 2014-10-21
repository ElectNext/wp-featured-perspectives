<?php

class FeaturedPerspectives {
  private $version = '1.4';
  private $script_url = '/api/v1/enxt.js';
  private $site_name = 'versahq.com';
  private $email_contact = 'apikey@versaHQ.com';
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
    add_action('wp_ajax_flush_rewrite_rules', array($this, 'flush_rewrite_rules'));
    add_filter('the_content', array($this, 'add_featured_perspective'));
    add_action('init', array($this, 'init_versa_feed'));
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
    // Getting the custom RSS feed initialzed requires flushing the rewrite
    // rules *after* the feed is initialized. To make this painless for users,
    // it's hooked to saving the API key. Unfortunately there's no way to take
    // a custom action on save without completely throwing out the standard WP
    // handling for settings pages, so we'll do it via ajax.
    ?>
    <script type='text/javascript'>
      jQuery(document).ready(function($) {
        $( "#wpfp_settings_form" ).submit(function(ev) {
          ev.preventDefault();

          $.ajax({
            type: 'POST',
            url: 'admin-ajax.php',
            context: $(this),
            data: {
              action: 'flush_rewrite_rules'
            },
            complete: function() {
              this.off('submit');
              this.submit();
            }
          })
        });
      });
    </script>

    <div class="wrap">
      <?php screen_icon(); ?>
      <h2><?php echo $this->title; ?></h2>

      <form action="options.php" method="post" id="wpfp_settings_form">
        <?php settings_fields('wpfp_settings'); ?>
        <?php do_settings_sections('wpfp'); ?>
        <p class="submit">
          <input name="versa_settings_submit" type="submit" value="<?php esc_attr_e('Save Changes', 'wpfp'); ?>" class="button button-primary" />
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

  // called via ajax
  public function flush_rewrite_rules() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
    die(); // this is standard WP practice for ajax calls - it prevent WP from returning "0"
  }

  public function add_featured_perspective($content) {
    global $post;
    // the is_main_query() check ensures we don't add to sidebars, footers, etc
    if (is_main_query() && is_single()) {
      $guid = FeaturedPerspectives::get_post_guid();
      $fp = "
        <script data-electnext data-cfasync='false' id='enxt-script' type='text/javascript'>
          //<![CDATA[
            var _enxt = _enxt || [];
            _enxt.push(['set_article', '{$guid}']);
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
        <noscript>Please enable JavaScript to view the <a href='https://{$this->site_name}?utm_source=noscript&utm_medium=richardson_widget' target='_blank'>Featured Perspectives by Versa.</a></noscript>
      ";

      $content .= $fp;
    }

    return $content;
  }

  public function init_versa_feed() {
    add_feed('versa', array($this, 'add_versa_feed'));
  }

  public function add_versa_feed() {
    load_template(dirname(__FILE__) . '/versa_feed.php');
  }

  // make this a static function so we can call it easily from versa_feed.php
  static public function get_post_guid() {
    global $post;
    return FeaturedPerspectives::parameterize(get_bloginfo('name')) . '-' . $post->ID;
  }

  static public function parameterize($string, $sep = '-') {
    # Turn unwanted chars into the separator
    $parameterized_string = preg_replace('/[^a-zA-Z0-9\-_]+/', $sep, $string);
    # Remove leading/trailing separator.
    $parameterized_string = preg_replace("/^$sep|$sep$/", '', $parameterized_string);
    $parameterized_string = strtolower($parameterized_string);
    return $parameterized_string;
  }
}
