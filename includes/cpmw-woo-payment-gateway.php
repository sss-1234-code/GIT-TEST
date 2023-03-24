<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_cpmw_Gateway extends WC_Payment_Gateway
{
use CPMW_HELPER;
    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {

        $this->id = 'cpmw'; // payment gateway plugin ID
        $this->icon = CPMW_URL . '/assets/images/metamask.png'; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = __('MetaMask Pay', 'cpmw');
        $this->method_description = __('Cryptocurrency Payments Using MetaMask For WooCommerce', 'cpmw'); // will be displayed on the options page
        // Method with all the options fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = !empty($this->get_option('title'))?$this->get_option('title'):"MetaMask Pay";
        $this->email_enabled = $this->get_option('email_enabled');
        $this->description = $this->get_option('custom_description');
        $this->default_status = apply_filters('cpmw_process_payment_order_status', 'pending');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));       
        if (!$this->is_valid_for_use()) {
            $this->enabled = 'no';
             add_action( 'admin_notices', function(){ ?>
                <style>div#message.updated {
                    display: none;
                }</style>
                    <div class="notice notice-error is-dismissible">
                        <p><?php
                        _e(' Current WooCommerce store  currency is not supported by Cryptocurrency Payments Using MetaMask For WooCommerce','cpmw');
                        ?>
                        </p>
                    </div>

                <?php } );
        }
        $this->supports = array(
            'products',
            'subscriptions',           
            );

    }




    public function is_valid_for_use()
    {
        if (in_array(get_woocommerce_currency(), apply_filters('cpmw_supported_currencies', $this->cpmw_supported_currency()))) {
            return true;
        }

        return false;
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {       
       

     
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable MetaMask Pay',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'yes',
            ),
          
            'title' => array(
                'title' => __('Title', 'cpmw'),
                'type' => 'text',
                'description' => __('This controls the title for the payment method the customer sees during checkout.', 'cpmw'),
                'default' => 'MetaMask Pay',
                'desc_tip' => false,
            ),
            'custom_description' => array(
                'title' => 'Description',
                'type' => 'text',
                'description' => 'Add custom description for checkout payment page',
                'default' => 'Payout using MetaMask',

            ),
          
        );

    }

    public function payment_fields()
    {
      require_once CPMW_PATH . 'includes/html/cpmw-checkout-fields.php'; 
    }

    public function validate_fields()
    {
        require_once CPMW_PATH . 'includes/html/cpmw-validate-fields.php'; 
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        try {
            $order = new WC_Order($order_id);
            $settings_obj = get_option('cpmw_settings');
            $crypto_currency = !empty($_POST['cpmw_crypto_coin']) ? sanitize_text_field($_POST['cpmw_crypto_coin']) : '';
            $total = $order->get_total();
            $type = $settings_obj['currency_conversion_api'];
            $in_crypto = $this->cpmw_price_conversion($total, $crypto_currency, $type);           
            $user_wallet = $settings_obj['user_wallet'];
            $order->add_meta_data('cpmw_in_crypto', $in_crypto);
            $order->add_meta_data('cpmw_currency_symbol', $crypto_currency);
            $order->add_meta_data('cpmw_user_wallet', $user_wallet);
            $order->save_meta_data();
            $order->update_status($this->default_status);
            $woocommerce->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );

        } catch (Exception $e) {
            wc_add_notice(__('Payment error:', 'cpmw') . 'Unknown coin', 'error');
            return null;
        }
        wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again', 'cpmw'), 'error');
        return null;
    }

    public function thankyou_page($order_id)
    {
        require_once CPMW_PATH . 'includes/html/cpmw-process-order.php'; 

    }

}
