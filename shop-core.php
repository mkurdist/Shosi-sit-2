<?php
/* Plugin Name: Shop Core (performance + security) */
defined('ABSPATH') || exit;
add_filter('xmlrpc_enabled', '__return_false');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
add_action('wp_head', function () { echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'; }, 1);
add_action('wp_enqueue_scripts', function () {
  wp_deregister_script('wp-embed');
  wp_enqueue_style('shop-font', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap', [], null);
  wp_add_inline_style('shop-font', 'body,button,input,select,textarea,h1,h2,h3,h4,h5,h6,.site-title{font-family:Vazirmatn,Tahoma,sans-serif!important}');
}, 100);
add_action('send_headers', function () {
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: same-origin');
});
