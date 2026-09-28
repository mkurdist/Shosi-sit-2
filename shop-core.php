<?php
/* Plugin Name: Shop Core (performance + security + design) */
defined('ABSPATH') || exit;

/* ---------- performance / security ---------- */
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
  wp_enqueue_style('shop-font', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap', [], null);
}, 100);
add_action('send_headers', function () {
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: same-origin');
});

/* ---------- design: shoe illustrations for products without photo ---------- */
function shop_shoe_svg($type, $color) {
  $SH = [
    'چرم' => '<path d="M10 62C10 50 18 46 30 44L52 40C60 30 72 28 84 34C96 40 108 48 110 62Z" fill="%c"/><path d="M40 46l8 6M50 42l8 6M60 38l8 6" stroke="#fff8" stroke-width="2"/><rect x="6" y="62" width="104" height="10" rx="3" fill="#222"/><rect x="8" y="72" width="22" height="6" rx="2" fill="#222"/>',
    'اسپورت' => '<path d="M8 60C8 40 16 30 28 24L42 22C50 34 62 38 74 38C92 40 110 46 114 60Z" fill="%c"/><path d="M38 52Q70 40 104 54" stroke="#fff" stroke-width="4" fill="none"/><path d="M6 60h110q4 0 4 5v6q0 4-4 4H10q-4 0-4-4z" fill="#f4f4f4" stroke="#0003"/><path d="M14 68h96" stroke="#0003"/>',
    'کتونی' => '<path d="M10 62C10 44 18 36 30 34L50 36C62 44 76 46 86 48L86 62Z" fill="%c"/><path d="M84 62C84 48 98 44 112 54L112 62Z" fill="#fff" stroke="#0003"/><path d="M34 40l10 4M40 36l10 4M46 34l8 4" stroke="#fff" stroke-width="2"/><rect x="6" y="62" width="108" height="10" rx="4" fill="#fff" stroke="#0003"/><path d="M6 66h108" stroke="#c33" stroke-width="2"/>',
  ];
  $c = preg_match('/^#[0-9a-f]{3,8}$/i', (string) $color) ? $color : '#888888';
  $p = $SH[$type] ?? $SH['کتونی'];
  return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 84" role="img" aria-label="' . esc_attr($type) . '">' . str_replace('%c', $c, $p) . '</svg>';
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
  return '<div class="shoe-ph" style="background:' . esc_attr($j['color']) . '22">' . shop_shoe_svg($j['type'], $j['color']) . '</div>';
}, 10, 2);
add_filter('woocommerce_placeholder_img_src', function () {
  return 'data:image/svg+xml;utf8,' . rawurlencode(shop_shoe_svg('کتونی', '#cccccc'));
});
add_filter('storefront_loop_columns', function () { return 4; });

/* ---------- design: home page sections ---------- */
add_action('storefront_content_top', function () {
  if (!is_front_page() || is_paged()) return;
  $cards = '';
  foreach (['مردانه' => 'کفش آقایان', 'زنانه' => 'کفش بانوان', 'بچگانه' => 'کفش کودکان'] as $name => $sub) {
    $t = get_term_by('name', $name, 'product_cat');
    $l = $t ? get_term_link($t) : '';
    if (!$l || is_wp_error($l)) continue;
    $cards .= '<a class="shop-cat" href="' . esc_url($l) . '"><b>' . esc_html($name) . '</b><span>' . esc_html($sub) . '</span></a>';
  }
  echo '<section class="shop-hero"><h1>کفشی که با قدم‌هات جور باشه</h1><p>چرم، اسپورت و کتونی برای آقایان، بانوان و کودکان. ارسال به سراسر کشور.</p><a class="shop-btn" href="#main">مشاهده محصولات</a></section>';
  if ($cards) echo '<section class="shop-cats">' . $cards . '</section>';
  echo '<section class="shop-trust"><div>🚚 ارسال به سراسر کشور</div><div>💳 پرداخت ساده با کارت به کارت</div><div>📞 پشتیبانی سفارش</div></section>';
});

/* ---------- design: footer ---------- */
add_action('wp', function () { remove_action('storefront_footer', 'storefront_credit', 20); });
add_action('storefront_footer', function () {
  $link = function ($slug, $title) {
    $p = get_page_by_path($slug);
    return $p ? '<a href="' . esc_url(get_permalink($p)) . '">' . esc_html($title) . '</a>' : '';
  };
  echo '<div class="shop-footer"><div><b>کفش‌سرا</b><p>فروشگاه آنلاین کفش مردانه، زنانه و بچگانه</p></div>';
  echo '<div>' . $link('about', 'درباره ما') . $link('contact', 'تماس با ما') . $link('rules', 'قوانین و مقررات') . '</div></div>';
  echo '<p class="shop-copy">کلیه حقوق برای کفش‌سرا محفوظ است.</p>';
}, 20);

/* ---------- design: css ---------- */
add_action('wp_head', function () {
  echo '<style>' . <<<'CSS'
:root{--ink:#111;--ac:#ff5a1f;--acd:#e04a10;--sf:#f5f5f3;--ln:#e6e6e2}
body,button,input,select,textarea,h1,h2,h3,h4,h5,h6,.site-title{font-family:Vazirmatn,Tahoma,sans-serif!important}
body{font-size:16px;color:#222;background:#fff}
.site-header{background:var(--ink)!important;border:0!important;padding-top:.6rem!important;padding-bottom:.6rem!important}
.site-branding a,.site-header .main-navigation ul.menu>li>a,.site-header .main-navigation ul.nav-menu>li>a,.site-header-cart .cart-contents,.menu-toggle{color:#fff!important}
.site-header .main-navigation ul.menu>li>a:hover,.site-header .main-navigation ul.nav-menu>li>a:hover{color:var(--ac)!important}
.site-header .site-search input[type=search]{background:#222;color:#fff;border-color:#333}
.site-header .site-search .widget_product_search form:before{color:#aaa}
.button,.added_to_cart,input[type=submit],button.alt,button[type=submit],#respond input#submit{background:var(--ac)!important;color:#fff!important;border:0!important;border-radius:999px!important;font-weight:700}
.button:hover,.added_to_cart:hover,input[type=submit]:hover,button.alt:hover{background:var(--acd)!important}
ul.products li.product{background:var(--sf);border-radius:16px;overflow:hidden;padding-bottom:1rem;text-align:center;box-sizing:border-box;transition:transform .2s,box-shadow .2s}
ul.products li.product:hover{transform:translateY(-4px);box-shadow:0 10px 24px #0002}
ul.products li.product .woocommerce-loop-product__title{font-size:1rem;font-weight:700;padding:.6rem .8rem 0}
ul.products li.product .price{color:var(--ac);font-weight:700;display:block;margin:.3rem 0 .6rem}
ul.products li.product .price ins{background:none}
.shoe-ph{aspect-ratio:1;display:grid;place-items:center}.shoe-ph svg{width:72%}
.woocommerce-products-header,.home .woocommerce-products-header{display:none}
.shop-hero{background:linear-gradient(135deg,#111 0%,#2a2a2a 60%,var(--ac) 140%);color:#fff;border-radius:20px;padding:clamp(2rem,6vw,4.5rem);margin:1.5rem 0}
.shop-hero h1{color:#fff;font-weight:900;font-size:clamp(2rem,6vw,3.6rem);line-height:1.2;max-width:16ch;margin:0 0 .8rem}
.shop-hero p{color:#ddd;max-width:44ch;margin:0 0 1.4rem}
.shop-btn{display:inline-block;background:var(--ac);color:#fff!important;border-radius:999px;padding:.7rem 1.6rem;font-weight:700;text-decoration:none}
.shop-cats{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.2rem}
.shop-cat{background:var(--sf);border:2px solid transparent;border-radius:16px;padding:1.6rem;text-align:center;color:var(--ink)!important;text-decoration:none;display:grid;gap:.2rem;transition:border-color .2s}
.shop-cat:hover{border-color:var(--ac)}.shop-cat b{font-size:1.3rem}.shop-cat span{color:#667085}
.shop-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem;margin-bottom:2rem;text-align:center;font-weight:700}
.shop-trust div{border:1px solid var(--ln);border-radius:12px;padding:.8rem}
.site-footer{background:var(--ink)!important;color:#bbb!important;border:0}
.site-footer a{color:#fff!important}
.shop-footer{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;padding:1rem 0}
.shop-footer div:last-child a{display:block;margin-bottom:.4rem}.shop-footer b{color:#fff;font-size:1.2rem}
.shop-copy{border-top:1px solid #333;padding-top:1rem;font-size:.85rem;text-align:center}
@media(max-width:700px){.shop-cats,.shop-trust,.shop-footer{grid-template-columns:1fr}}
CSS
  . '</style>';
}, 999);

/* ---------- one-time setup: pages, menu, cleanup ---------- */
add_action('init', function () {
  if (get_option('shop_design_v1') || !get_option('shoe_seeded') || !function_exists('wc_get_page_id')) return;
  foreach ([['sample-page', 'page'], ['hello-world', 'post']] as $x) {
    $p = get_page_by_path($x[0], OBJECT, $x[1]);
    if ($p) wp_trash_post($p->ID);
  }
  $mk = function ($title, $slug, $content) {
    $e = get_page_by_path($slug);
    if ($e) return $e->ID;
    return wp_insert_post(['post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_status' => 'publish', 'post_type' => 'page']);
  };
  $about = $mk('درباره ما', 'about', '<p>کفش‌سرا فروشگاه اینترنتی کفش مردانه، زنانه و بچگانه است. این متن را از بخش «برگه‌ها» در پنل مدیریت ویرایش کنید.</p>');
  $contact = $mk('تماس با ما', 'contact', '<p>اطلاعات تماس فروشگاه را اینجا بنویسید. این متن را از بخش «برگه‌ها» در پنل مدیریت ویرایش کنید.</p>');
  $mk('قوانین و مقررات', 'rules', '<p>قوانین ارسال، پرداخت و مرجوعی فروشگاه را اینجا بنویسید. این متن را از بخش «برگه‌ها» در پنل مدیریت ویرایش کنید.</p>');

  $m = wp_get_nav_menu_object('منوی اصلی');
  $menu = $m ? $m->term_id : wp_create_nav_menu('منوی اصلی');
  if ($menu && !is_wp_error($menu)) {
    $add = function ($a) use ($menu) { wp_update_nav_menu_item($menu, 0, $a + ['menu-item-status' => 'publish']); };
    $page = function ($title, $id) use ($add) { if ($id) $add(['menu-item-title' => $title, 'menu-item-object' => 'page', 'menu-item-object-id' => $id, 'menu-item-type' => 'post_type']); };
    $page('فروشگاه', wc_get_page_id('shop'));
    foreach (['مردانه', 'زنانه', 'بچگانه'] as $n) {
      $t = get_term_by('name', $n, 'product_cat');
      if ($t) $add(['menu-item-title' => $n, 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $t->term_id, 'menu-item-type' => 'taxonomy']);
    }
    $page('درباره ما', $about);
    $page('تماس با ما', $contact);
    $page('حساب کاربری', wc_get_page_id('myaccount'));
    set_theme_mod('nav_menu_locations', ['primary' => $menu, 'handheld' => $menu]);
  }

  $sb = get_option('sidebars_widgets');
  if (is_array($sb)) {
    foreach (['footer-1', 'footer-2', 'footer-3', 'footer-4'] as $s) if (isset($sb[$s])) $sb[$s] = [];
    update_option('sidebars_widgets', $sb);
  }
  update_option('shop_design_v1', 1);
}, 30);
