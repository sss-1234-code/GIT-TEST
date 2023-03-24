<?php
if (!defined('ABSPATH')) {
    exit;
}
$const_msg = $this->cpmw_const_messages();
$options = get_option('cpmw_settings');

$network_name = $this->cpmw_supported_networks();

$payment_msg = !empty($options['payment_msg']) ? $options['payment_msg'] : __("Payment Completed Successfully", "cpmw");
$confirm_msg = !empty($options['confirm_msg']) ? $options['confirm_msg'] : __("Confirm Payment in your wallet", "cpmw");
$process_msg = !empty($options['payment_process_msg']) ? $options['payment_process_msg'] : __("Payment in process", "cpmw");
$rejected_msg = !empty($options['rejected_message']) ? $options['rejected_message'] : __("Transaction Rejected ", "cpmw");
$network = !empty($options['Chain_network']) ? $options['Chain_network'] : "";
$redirect = !empty($options['redirect_page']) ? $options['redirect_page'] : "";
$crypto_currency = ($network == '0x1' || $network == '0x3' || $network == '0x4') ? $options["eth_select_currency"] : $options["bnb_select_currency"];


$order = new WC_Order($order_id);
$total = $order->get_total();
$nonce = wp_create_nonce('cpmw_metamask_pay');
$user_wallet = $order->get_meta('cpmw_user_wallet');
$in_crypto = $order->get_meta('cpmw_in_crypto');
$currency_symbol = $order->get_meta('cpmw_currency_symbol');
$payment_status = $order->get_status();
$add_networks = $this->cpmw_add_networks();
$add_networks = isset($add_networks[$network]) ? json_encode($add_networks[$network]) : '';
$add_tokens = $this->cpmw_add_tokens();
$token_address = isset($add_tokens[$network][$currency_symbol]) ? $add_tokens[$network][$currency_symbol] : '';
$transaction_id = (!empty($order->get_meta('TransactionId'))) ? $order->get_meta('TransactionId') : "";
wp_enqueue_script('cpmw-sweet-alert2', CPMW_URL . 'assets/js/sweetalert2.js', array('jquery'), CPMW_VERSION, true);
wp_enqueue_script('cpmw-ether', CPMW_URL . 'assets/js/ethers-5.2.umd.min.js', array('jquery'), CPMW_VERSION, true);
wp_enqueue_script('cpmw_custom', CPMW_URL . 'assets/js/cpmw-custom.js', array('jquery', 'cpmw-sweet-alert2'), CPMW_VERSION, true);
wp_localize_script('cpmw_custom', "extradata",
    array(
        'url' => CPMW_URL,
        'network_name' => $network_name[$network],
        'token_address' => $token_address,
        'network_data' => $add_networks,
        'transaction_id' => $transaction_id,        
        'const_msg'=>$const_msg,
        'redirect' => $redirect,
        'order_page' => get_home_url() . '/my-account/orders/',        
        'currency_symbol' => $currency_symbol,
        'confirm_msg' => $confirm_msg,
        'network' => $network,
        'is_paid' => $order->is_paid(),
        'process_msg' => $process_msg,
        'payment_msg' => $payment_msg,
        'rejected_msg' => $rejected_msg,
        'in_crypto' => $in_crypto,
        'recever' => $user_wallet,
        'ajax' => home_url('/wp-admin/admin-ajax.php'),
        'order_status' => $payment_status,
        'id' => $order_id,
        'nonce' => $nonce,
        'payment_status' => $options['payment_status'],
    ));
wp_enqueue_style('cpmw_custom_css', CPMW_URL . 'assets/css/cpmw.css', array(), CPMW_VERSION, null, 'all');

?>
        <div class="cpmw_loader_wrap">

        <div class="cpmw_loader">
            <img src="<?php echo esc_url(CPMW_URL . '/assets/images/metamask.png') ?>" alt="metamask" >
            <h2><?php echo esc_html($confirm_msg) ?></h2>
            </div>
        </div>
       <div class="cmpw_meta_connect">
           <div class="wallet-icon" >
               <img src="<?php echo esc_url(CPMW_URL . '/assets/images/metamask.png') ?>" alt="metamask" >
            </div>

            <div class="connect-wallet" >
                <div class="cpmw_connect_btn">
                    <button class="confirm-btn" > <?php echo esc_html($const_msg['metamask_connect']); ?></button>
                </div>
            </div>
        </div>

       <div class="cmpw_meta_wrapper" >
           <div class="container" >
               <div class="cpmw-pay-wallet-icon" >
               <img src="<?php echo esc_url(CPMW_URL . '/assets/images/metamask.png') ?>" alt="MetaMask" >
            </div>
               <div class="cpmw-info" >
                   <div class="connected-wallet" ><span ><?php echo esc_html($const_msg['connnected_wallet']); ?> </span><?php echo esc_html($const_msg['metamask']); ?></div>
                   <div class="active-chain" ><span ><?php echo esc_html($const_msg['active_chain']); ?> </span><p class="cpmw_active_chain"> </p></div>
                </div>
                <div class="cpmw-info" >
                    <div class="connected-account" ><span ><?php echo esc_html($const_msg['connected_account']); ?></span>
                    <div class="account-address" ></div>
                    </div>
                    <div class="order-price" ><span ><?php echo esc_html($const_msg['order_price']); ?> </span><?php echo esc_html(get_woocommerce_currency_symbol() . $total) ?></div>
                </div>
                 <div class="clear" ></div>
                <div class="pay-btn-wrapper" ><button class="confirm-btn" > <?php echo esc_html($const_msg['pay_with'] . $in_crypto . $currency_symbol) ?></button></div>

            </div>
        </div>



        <?php