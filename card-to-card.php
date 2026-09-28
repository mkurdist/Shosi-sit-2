<?php
/* Plugin Name: Card to Card Gateway */
defined('ABSPATH') || exit;

function c2c_en($s) {
  return strtr((string) $s, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
}
function c2c_box($amount = null) {
  $s = get_option('woocommerce_c2c_settings', []);
  $n = preg_replace('/\D/', '', c2c_en($s['card'] ?? ''));
  ob_start(); ?>
  <div style="border:1px dashed #999;border-radius:10px;padding:12px;margin:8px 0">
    <?php if ($amount) : ?><div>مبلغ قابل پرداخت: <b><?php echo wp_kses_post(wc_price($amount)); ?></b></div><?php endif; ?>
    <div>شماره کارت:</div>
    <div dir="ltr" style="font-size:1.3em;font-weight:700;letter-spacing:1px"><?php echo esc_html(trim(chunk_split($n, 4, ' '))); ?></div>
    <div>به نام: <b><?php echo esc_html($s['holder'] ?? ''); ?></b> — <?php echo esc_html($s['bank'] ?? ''); ?></div>
    <button type="button" style="margin-top:6px" onclick="navigator.clipboard&&navigator.clipboard.writeText('<?php echo esc_js($n); ?>');this.textContent='کپی شد'">کپی شماره کارت</button>
  </div>
  <?php return ob_get_clean();
}

add_action('before_woocommerce_init', function () {
  if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil'))
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
});

add_action('plugins_loaded', function () {
  if (!class_exists('WC_Payment_Gateway')) return;

  class WC_Gateway_C2C extends WC_Payment_Gateway {
    public function __construct() {
      $this->id = 'c2c';
      $this->method_title = 'کارت به کارت';
      $this->method_description = 'پرداخت کارت به کارت با تأیید دستی مدیر';
      $this->has_fields = true;
      $this->supports = ['products'];
      $this->init_form_fields(); $this->init_settings();
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
      add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou']);
      add_action('woocommerce_email_before_order_table', [$this, 'email_note'], 10, 3);
    }
    public function init_form_fields() {
      $this->form_fields = [
        'enabled' => ['title' => 'فعال', 'type' => 'checkbox', 'label' => 'فعال‌سازی کارت به کارت', 'default' => 'yes'],
        'title' => ['title' => 'عنوان', 'type' => 'text', 'default' => 'کارت به کارت'],
        'description' => ['title' => 'توضیحات', 'type' => 'textarea', 'default' => ''],
        'card' => ['title' => 'شماره کارت (۱۶ رقم)', 'type' => 'text'],
        'holder' => ['title' => 'نام صاحب کارت', 'type' => 'text'],
        'bank' => ['title' => 'نام بانک', 'type' => 'text'],
      ];
    }
    public function is_available() {
      $n = preg_replace('/\D/', '', c2c_en($this->get_option('card')));
      return parent::is_available() && strlen($n) === 16;
    }
    public function payment_fields() {
      if ($this->description) echo wpautop(wp_kses_post($this->description));
      echo c2c_box(WC()->cart ? WC()->cart->get_total('edit') : 0);
      echo '<p class="form-row form-row-wide"><label>کد پیگیری واریز (اختیاری، بعد از پرداخت هم می‌توانید ثبت کنید)</label><input type="text" name="c2c_ref" autocomplete="off" dir="ltr"></p>';
      echo '<p class="form-row form-row-wide"><label>۴ رقم آخر کارت پرداخت‌کننده (اختیاری)</label><input type="text" name="c2c_last4" maxlength="4" inputmode="numeric" autocomplete="off" dir="ltr"></p>';
    }
    public function process_payment($order_id) {
      $o = wc_get_order($order_id);
      $ref = substr(preg_replace('/[^0-9A-Za-z]/', '', c2c_en(wp_unslash($_POST['c2c_ref'] ?? ''))), 0, 40);
      $l4 = substr(preg_replace('/\D/', '', c2c_en(wp_unslash($_POST['c2c_last4'] ?? ''))), 0, 4);
      if ($ref !== '') $o->update_meta_data('_c2c_ref', $ref);
      if ($l4 !== '') $o->update_meta_data('_c2c_last4', $l4);
      $o->update_status('on-hold', 'در انتظار پرداخت کارت به کارت و تأیید مدیر.');
      WC()->cart->empty_cart();
      return ['result' => 'success', 'redirect' => $this->get_return_url($o)];
    }
    public function thankyou($id) {
      $o = wc_get_order($id);
      if (!$o || !$o->has_status('on-hold')) return;
      echo '<h2>پرداخت کارت به کارت</h2>' . c2c_box($o->get_total());
      $ref = $o->get_meta('_c2c_ref'); $l4 = $o->get_meta('_c2c_last4');
      if ($ref || $l4) echo '<p>اطلاعات پرداخت ثبت شد و پس از بررسی، سفارش تأیید می‌شود.</p>';
      ?>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="c2c_submit">
        <input type="hidden" name="order_id" value="<?php echo (int) $id; ?>">
        <input type="hidden" name="key" value="<?php echo esc_attr($o->get_order_key()); ?>">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('c2c_' . $id)); ?>">
        <p><label>کد پیگیری واریز<br><input type="text" name="ref" dir="ltr" value="<?php echo esc_attr($ref); ?>"></label></p>
        <p><label>۴ رقم آخر کارت پرداخت‌کننده<br><input type="text" name="last4" maxlength="4" inputmode="numeric" dir="ltr" value="<?php echo esc_attr($l4); ?>"></label></p>
        <button type="submit" class="button">ثبت اطلاعات پرداخت</button>
      </form>
      <?php
    }
    public function email_note($o, $admin, $plain) {
      if ($admin || $plain || $o->get_payment_method() !== 'c2c' || !$o->has_status('on-hold')) return;
      echo '<h2>پرداخت کارت به کارت</h2>' . c2c_box($o->get_total());
    }
  }
  add_filter('woocommerce_payment_gateways', function ($g) { $g[] = 'WC_Gateway_C2C'; return $g; });
}, 11);

function c2c_submit() {
  $id = absint($_POST['order_id'] ?? 0);
  $o = wc_get_order($id);
  if (!$o || $o->get_payment_method() !== 'c2c' || !$o->has_status('on-hold')
      || !hash_equals($o->get_order_key(), (string) wp_unslash($_POST['key'] ?? ''))
      || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'c2c_' . $id)) wp_die('درخواست نامعتبر است.', '', 400);
  $ref = substr(preg_replace('/[^0-9A-Za-z]/', '', c2c_en(wp_unslash($_POST['ref'] ?? ''))), 0, 40);
  $l4 = substr(preg_replace('/\D/', '', c2c_en(wp_unslash($_POST['last4'] ?? ''))), 0, 4);
  $o->update_meta_data('_c2c_ref', $ref); $o->update_meta_data('_c2c_last4', $l4);
  $o->add_order_note('مشتری اطلاعات پرداخت را ثبت کرد. کد پیگیری: ' . ($ref ?: '—') . ' | ۴ رقم آخر کارت: ' . ($l4 ?: '—'));
  $o->save();
  wp_safe_redirect($o->get_checkout_order_received_url()); exit;
}
add_action('admin_post_nopriv_c2c_submit', 'c2c_submit');
add_action('admin_post_c2c_submit', 'c2c_submit');

add_action('woocommerce_admin_order_data_after_billing_address', function ($o) {
  if ($o->get_payment_method() !== 'c2c') return;
  echo '<p><strong>کارت به کارت</strong><br>کد پیگیری: ' . esc_html($o->get_meta('_c2c_ref') ?: '—') . '<br>۴ رقم آخر کارت: ' . esc_html($o->get_meta('_c2c_last4') ?: '—') . '</p>';
});
add_filter('woocommerce_order_actions', function ($a) {
  global $theorder;
  if ($theorder instanceof WC_Order && $theorder->get_payment_method() === 'c2c' && $theorder->has_status('on-hold'))
    $a['c2c_confirm'] = 'تأیید پرداخت کارت به کارت';
  return $a;
});
add_action('woocommerce_order_action_c2c_confirm', function ($o) {
  if ($o->has_status('on-hold')) { $o->add_order_note('پرداخت کارت به کارت توسط مدیر تأیید شد.'); $o->payment_complete(); }
});
