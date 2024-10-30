<?php
/*
Plugin Name: bizconnector
Plugin URI: http://wordpress.org/plugins/bizconnector/
Description: A plugin for BizConnector customers
Version: 1.0.0
Author: BizConnector
Author URI: http://www.bizconnector.com/
*/

$dir = bizconnector_dir();
@include_once "$dir/singletons/api.php";
@include_once "$dir/singletons/query.php";
@include_once "$dir/singletons/introspector.php";
@include_once "$dir/singletons/response.php";
@include_once "$dir/models/post.php";
@include_once "$dir/models/comment.php";
@include_once "$dir/models/category.php";
@include_once "$dir/models/tag.php";
@include_once "$dir/models/author.php";
@include_once "$dir/models/attachment.php";

function bizconnector_init() {
  global $bizconnector;
  if (phpversion() < 5) {
    add_action('admin_notices', 'bizconnector_php_version_warning');
    return;
  }
  if (!class_exists('bizconnector')) {
    add_action('admin_notices', 'bizconnector_class_warning');
    return;
  }
  add_filter('rewrite_rules_array', 'bizconnector_rewrites');
  $bizconnector = new bizconnector();
}

function bizconnector_php_version_warning() {
  echo "<div id=\"bizconnector-warning\" class=\"updated fade\"><p>Sorry, bizconnector requires PHP version 5.0 or greater.</p></div>";
}

function bizconnector_class_warning() {
  echo "<div id=\"bizconnector-warning\" class=\"updated fade\"><p>Oops, bizconnector class not found. If you've defined a bizconnector_DIR constant, double check that the path is correct.</p></div>";
}

function bizconnector_activation() {
  // Add the rewrite rule on activation
  global $wp_rewrite;
  add_filter('rewrite_rules_array', 'bizconnector_rewrites');
  $wp_rewrite->flush_rules();
}

function bizconnector_deactivation() {
  // Remove the rewrite rule on deactivation
  global $wp_rewrite;
  $wp_rewrite->flush_rules();
}

function bizconnector_rewrites($wp_rules) {
  $base = get_option('bizconnector_base', 'api');
  if (empty($base)) {
    return $wp_rules;
  }
  $bizconnector_rules = array(
    "$base\$" => 'index.php?json=info',
    "$base/(.+)\$" => 'index.php?json=$matches[1]'
  );
  return array_merge($bizconnector_rules, $wp_rules);
}

function bizconnector_dir() {
  if (defined('bizconnector_DIR') && file_exists(bizconnector_DIR)) {
    return bizconnector_DIR;
  } else {
    return dirname(__FILE__);
  }
}

// Add initialization and activation hooks
add_action('init', 'bizconnector_init');
register_activation_hook("$dir/bizconnector.php", 'bizconnector_activation');
register_deactivation_hook("$dir/bizconnector.php", 'bizconnector_deactivation');

?>
