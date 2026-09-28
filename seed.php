<?php
if (get_option('shoe_seeded')) return;
$env = function ($k, $d = '') { $v = getenv($k); return ($v === false || $v === '') ? $d : $v; };

foreach ([
  'woocommerce_currency' => 'IRT', 'woocommerce_currency_pos' => 'right_space',
  'woocommerce_price_thousand_sep' => ',', 'woocommerce_price_decimal_sep' => '.', 'woocommerce_price_num_decimals' => '0',
  'woocommerce_default_country' => 'IR', 'woocommerce_allowed_countries' => 'specific', 'woocommerce_specific_allowed_countries' => ['IR'],
  'woocommerce_calc_taxes' => 'no', 'woocommerce_enable_guest_checkout' => 'yes', 'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
  'woocommerce_manage_stock' => 'yes', 'woocommerce_task_list_hidden' => 'yes', 'woocommerce_show_marketplace_suggestions' => 'no',
  'woocommerce_coming_soon' => 'no', 'timezone_string' => 'Asia/Tehran', 'permalink_structure' => '/%postname%/',
  'blogdescription' => 'فروشگاه آنلاین کفش مردانه، زنانه و بچگانه',
] as $k => $v) update_option($k, $v);

if (class_exists('WC_Install')) WC_Install::create_pages();
foreach (['checkout' => '[woocommerce_checkout]', 'cart' => '[woocommerce_cart]'] as $k => $c) {
  $id = wc_get_page_id($k);
  if ($id > 0) wp_update_post(['ID' => $id, 'post_content' => $c]);
}
$shop = wc_get_page_id('shop');
if ($shop > 0) { update_option('show_on_front', 'page'); update_option('page_on_front', $shop); }

update_option('woocommerce_c2c_settings', [
  'enabled' => 'yes', 'title' => 'کارت به کارت',
  'description' => 'مبلغ سفارش را به کارت زیر واریز کنید و سپس کد پیگیری را ثبت نمایید. سفارش پس از تأیید واریز پردازش می‌شود.',
  'card' => $env('CARD_NUMBER'), 'holder' => $env('CARD_HOLDER'), 'bank' => $env('CARD_BANK'),
]);

$z = new WC_Shipping_Zone();
$z->set_zone_name('سراسر ایران'); $z->add_location('IR', 'country'); $z->save();
$iid = $z->add_shipping_method('flat_rate');
update_option('woocommerce_flat_rate_' . $iid . '_settings', ['title' => 'ارسال', 'tax_status' => 'none', 'cost' => $env('SHIPPING_COST', '0')]);

$cat = function ($name, $parent = 0) {
  $t = term_exists($name, 'product_cat', $parent);
  if (!$t) $t = wp_insert_term($name, 'product_cat', ['parent' => $parent]);
  return is_wp_error($t) ? 0 : (int) (is_array($t) ? $t['term_id'] : $t);
};
$stock = (int) $env('DEFAULT_STOCK', '10');
$items = json_decode(file_get_contents('/setup/products.json'), true) ?: [];
foreach ($items as $p) {
  $g = $cat($p['gender']); $t = $cat($p['type'], $g);
  $pr = new WC_Product_Variable();
  $pr->set_name($p['name']); $pr->set_status('publish'); $pr->set_sku($p['id']);
  $pr->set_category_ids(array_filter([$g, $t]));
  $pr->set_short_description($p['gender'] . ' · ' . $p['type']);
  $a = new WC_Product_Attribute();
  $a->set_id(0); $a->set_name('سایز'); $a->set_options(array_map('strval', $p['sizes'])); $a->set_visible(true); $a->set_variation(true);
  $pr->set_attributes([$a]);
  $pid = $pr->save();
  foreach ($p['sizes'] as $s) {
    $v = new WC_Product_Variation();
    $v->set_parent_id($pid); $v->set_sku($p['id'] . '-' . $s);
    $v->set_attributes([sanitize_title('سایز') => (string) $s]);
    $v->set_regular_price((string) $p['price']);
    $v->set_manage_stock(true); $v->set_stock_quantity($stock);
    $v->save();
  }
  WC_Product_Variable::sync($pid);
}
flush_rewrite_rules();
update_option('shoe_seeded', 1);
