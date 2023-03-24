<?php
if (!defined('ABSPATH')) {
    exit;
}
$const_msg = $this->cpmw_const_messages();
$options = get_option('cpmw_settings');
wp_enqueue_style('ca-loader-css', CPMW_URL . 'assets/css/cpmw.css');
$get_network = $options["Chain_network"];
$crypto_currency = ($get_network == '0x1' || $get_network == '0x3' || $get_network == '0x4') ? $options["eth_select_currency"] : $options["bnb_select_currency"];
$type = $options['currency_conversion_api'];
$total_price = $this->get_order_total();
$metamask = "";
$inc = 1;
do_action('woocommerce_cpmw_form_start', $this->id);
?>
            <div class="form-row form-row-wide">
                <p><?php
$cpmw_settings = admin_url() . 'admin.php?page=cpmw-metamask-settings';
$user_wallet = $options['user_wallet'];
$bnb_currency = $options['bnb_select_currency'];
$eth_currency = $options['eth_select_currency'];
$compare_key = $options['crypto_compare_key'];
$openex_key = $options['openexchangerates_key'];
$select_currecny = $options['currency_conversion_api'];
$link_html = (current_user_can('manage_options')) ? '<a href="' . esc_url($cpmw_settings) . '" target="_blank">' . __("Click here", "cpmw") . '</a>' . __('to open settings', 'cpmw') : "";

if (empty($user_wallet)) {
    echo '<strong>' . esc_html($const_msg['metamask_address']) . wp_kses_post($link_html) . '</strong>';
    return false;

}
if (!empty($user_wallet) && strlen($user_wallet) != "42") {
    echo '<strong>' . esc_html($const_msg['valid_wallet_address']) . wp_kses_post($link_html) . '</strong>';
    return false;

}
if ($select_currecny == "cryptocompare" && empty($compare_key)) {
    echo '<strong>' . esc_html($const_msg['required_fiat_key']) . wp_kses_post($link_html) . '</strong>';
    return false;

}
if ($select_currecny == "openexchangerates" && empty($openex_key)) {
    echo '<strong>' . esc_html($const_msg['required_fiat_key']) . wp_kses_post($link_html) . '</strong>';
    return false;

}
if (empty($bnb_currency) || empty($eth_currency)) {
    echo '<strong>' . esc_html($const_msg['required_currency']) . wp_kses_post($link_html) . '</strong>';
    return false;

}

echo esc_html($this->description);?></p>
                <?php
if (is_array($crypto_currency)) {

    foreach ($crypto_currency as $key => $value) {
        $image_url=$this->cpmw_get_coin_logo($value);
        
        $in_busd = $this->cpmw_price_conversion($total_price, $value, $type);
        if (!empty($in_busd) && $in_busd != "error") {
            ?>
                    <div class="cpmw-pymentfield">
                    <input id="cpmw_payment_method" type="radio" class="input-radio" name="cpmw_crypto_coin" value="<?php echo !empty($in_busd) ? esc_attr($value) : ""; ?>"/>
                        <img src="<?php echo esc_url($image_url); ?>"/>
                        <span><?php echo esc_html($value) ?></span>
                    <p class="cpmw_crypto_price"><?php echo esc_html($in_busd . $value) ?></p>
                    </div>
                    <?php

        } else {
            if ($inc == 1 && $in_busd == "error") {
                echo '<strong>' . esc_html($const_msg['valid_fiat_key']) . wp_kses_post($link_html) . '</strong>';
            }
            $inc++;
            ?>
                    <input id="invalid_app_id" type="hidden"  name="invalid_app_id" value="<?php echo esc_attr($in_busd); ?>"/>
                    <?php
}

    }
} else {
    $in_busd = $this->cpmw_price_conversion($total_price, $crypto_currency, $type);
    if (!empty($in_busd) && $in_busd != "error") {
        $image_url=$this->cpmw_get_coin_logo($crypto_currency);
        ?>
                        <div class="cpmw-pymentfield">
                        <input id="cpmw_payment_method" type="radio" class="input-radio" name="cpmw_crypto_coin" value="<?php echo !empty($in_busd) ? esc_attr($crypto_currency) : ""; ?>"/>
                            <img src="<?php echo esc_url($image_url); ?>"/>
                            <span><?php echo esc_html($crypto_currency) ?></span>
                        <p class="cpmw_crypto_price"><?php echo esc_html($in_busd . $crypto_currency) ?></p>
                        </div>
                        <?php

    } else {
        if ($in_busd == "error") {
            echo '<strong>' . esc_html($const_msg['valid_fiat_key']) . wp_kses_post($link_html) . '</strong>';
        }
        ?>
                    <input id="invalid_app_id" type="hidden"  name="invalid_app_id" value="<?php echo esc_attr($in_busd); ?>"/>
                    <?php
}
}
?>
               </div>
                <?php
do_action('woocommerce_cpmw_form_end', $this->id);