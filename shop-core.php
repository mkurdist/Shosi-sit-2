<?php
/* Plugin Name: Shop Core (WoodMart Compatibility) */
defined('ABSPATH') || exit;

/* ---------- performance / security ---------- */
add_filter('xmlrpc_enabled', '__return_false');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
add_action('send_headers', function () {
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: same-origin');
});

/* ---------- design: shoe illustrations (Fixed for WoodMart) ---------- */
function shop_shoe_svg($type, $color) {
  $SH = [
    'چرم' => '<path d="M10 62C10 50 18 46 30 44L52 40C60 30 72 28 84 34C96 40 108 48 110 62Z" fill="%c"/><path d="M40 46l8 6M50 42l8 6M60 38l8 6" stroke="#fff8" stroke-width="2"/><rect x="6" y="62" width="104" height="10" rx="3" fill="#222"/><rect x="8" y="72" width="22" height="6" rx="2" fill="#222"/>',
    'اسپورت' => '<path d="M8 60C8 40 16 30 28 24L42 22C50 34 62 38 74 38C92 40 110 46 114 60Z" fill="%c"/><path d="M38 52Q70 40 104 54" stroke="#fff" stroke-width="4" fill="none"/><path d="M6 60h110q4 0 4 5v6q0 4-4 4H10q-4 0-4-4z" fill="#f4f4f4" stroke="#0003"/><path d="M14 68h96" stroke="#0003"/>',
    'کتونی' => '<path d="M10 62C10 44 18 36 30 34L50 36C62 44 76 46 86 48L86 62Z" fill="%c"/><path d="M84 62C84 48 98 44 112 54L112 62Z" fill="#fff" stroke="#0003"/><path d="M34 40l10 4M40 36l10 4M46 34l8 4" stroke="#fff" stroke-width="2"/><rect x="6" y="62" width="108" height="10" rx="4" fill="#fff" stroke="#0003"/><path d="M6 66h108" stroke="#c33" stroke-width="2"/>',
  ];
  $c = preg_match('/^#[0-9a-f]{3,8}$/i', (string) $color) ? $color : '#888888';
  $p = $SH[$type] ?? $SH['کتونی'];
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 84" width="400" height="400" style="background-color:#f8f8f8"><g transform="translate(0, 10)">' . str_replace('%c', $c, $p) . '</g></svg>';
  return $svg;
}

function shop_json() {
  static $m = null;
  if ($m === null) {
    $m = [];
    $f = '/setup/products.json';
    if (is_readable($f)) foreach ((array) json_decode(file_get_contents($f), true) as $p) $m[$p['id']] = $p;
  }
  return $m;
}

add_filter('woocommerce_product_get_image', function ($img, $product) {
  if ($product->get_image_id()) return $img;
  $j = shop_json()[$product->get_sku()] ?? null;
  if (!$j) return $img;
  
  $svg_b64 = 'data:image/svg+xml;base64,' . base64_encode(shop_shoe_svg($j['type'], $j['color']));
  return '<img src="' . $svg_b64 . '" alt="' . esc_attr($product->get_name()) . '" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail wp-post-image" style="width:100%;height:auto;object-fit:cover;" />';
}, 99, 2);
