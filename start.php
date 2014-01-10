<?php
/*
Plugin Name: Featured Perspectives
Plugin URI: http://www.versahq.com/
Description: A plugin for displaying Featured Perspectives in posts.
Author: Versa
Version: 1.2
Author URI: http://www.versahq.com
License: GPLv2 or later
*/

require_once 'FeaturedPerspectives.php';

add_action('wpmu_new_blog', 'wpfp_activate_for_new_network_site');
register_activation_hook(__FILE__, 'wpfp_activate');
load_plugin_textdomain('wpfp', false, basename(dirname(__FILE__)) . '/languages/');

$wpfp = new FeaturedPerspectives();
$wpfp->run();

function wpfp_activate_for_new_network_site($blog_id) {
  global $wpdb;

  if (is_plugin_active_for_network(__FILE__)) {
    $old_blog = $wpdb->blogid;
    switch_to_blog($blog_id);
    wpfp_activate();
    switch_to_blog($old_blog);
  }
}

function wpfp_activate() {
  $status = wpfp_activation_checks();

  if (is_string($status)) {
    wpfp_cancel_activation($status);
  }

  return null;
}

function wpfp_activation_checks() {
  if (version_compare(get_bloginfo('version'), '3.3', '<')) {
    return __('Political Profiler plugin not activated. You must have at least WordPress 3.3 to use it.', 'wpfp');
  }

  return true;
}

function wpfp_cancel_activation($message) {
  deactivate_plugins(__FILE__);
  wp_die($message);
}
