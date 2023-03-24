<?php
if (!defined('ABSPATH')) {
    exit();
}
if (!class_exists('CPMW_CONFIRM_TRANSACTION')) {
    class CPMW_CONFIRM_TRANSACTION
    {
        use CPMW_HELPER;

        public function __construct()
        {

        }


       public static function cpmw_payment_verify()
        {
            global $woocommerce;
            $options_settings= get_option('cpmw_settings');
            $user_address=!empty($options_settings["user_wallet"])?$options_settings["user_wallet"]:"";

            $obj= new self();
            $order_id = sanitize_text_field($_REQUEST['order_id']);
            $nonce = !empty($_REQUEST['nonce']) ? sanitize_text_field($_REQUEST['nonce']) : "";
            $trasn_id=!empty($_REQUEST['payment_processed']) ? sanitize_text_field($_REQUEST['payment_processed']) : "";  
            $payment_status_d = !empty($_REQUEST['payment_status']) ? sanitize_text_field($_REQUEST['payment_status']) : "";
            $order_expired = !empty($_REQUEST['rejected_transaction']) ? sanitize_text_field($_REQUEST['rejected_transaction']) : "";
            $selected_network = !empty($_REQUEST['selected_network']) ? sanitize_text_field($_REQUEST['selected_network']) : "";
            $sender = !empty($_REQUEST['sender']) ? sanitize_text_field($_REQUEST['sender']) : "";
            $recever = !empty($_REQUEST['recever']) ? sanitize_text_field($_REQUEST['recever']) : "";
            $amount = !empty($_REQUEST['amount']) ? $_REQUEST['amount'] : "";
            $secret_code = !empty($_REQUEST['secret_code']) ? $_REQUEST['secret_code'] : "";

            $networks =$obj->cpmw_supported_networks();

            $order = new WC_Order($order_id);
            $total = $order->get_meta('cpmw_in_crypto');          
              $verify_secretCode = get_post_meta($order_id, 'cpmw_secret_code', true);
           

            if ($order->is_paid() || !wp_verify_nonce($nonce, 'cpmw_metamask_pay') ||$user_address!=$recever ||$amount!=$total) {
                die("*ok*");
            }
             if ($secret_code != $verify_secretCode) {
            die("*Unauthorized access*");
            }
            $transaction=[];
            $current_user = wp_get_current_user();
            $user_name = $current_user->user_firstname .' '.$current_user->user_lastname;
            $transaction['order_id']=$order_id;
            $transaction['chain_id']=$selected_network;
            $transaction['order_price'] = get_woocommerce_currency_symbol().$order->get_total();
            $transaction['user_name']=$user_name;
            $transaction['crypto_price'] = $order->get_meta('cpmw_in_crypto').' '.$order->get_meta('cpmw_currency_symbol');
            $transaction['selected_currency'] = $order->get_meta('cpmw_currency_symbol');
            $transaction['chain_name'] = $networks[$selected_network];       
            try {
                    if ($order_expired == "true" && $trasn_id=="false" || $secret_code != $verify_secretCode) {
                // $order->add_meta_data('Payment_status', "CANCEL");
                    $order->add_order_note(__('Order has been canceled due to user rejection', 'cbpw'));
                    //  $order->add_meta_data('TransectionId', $trasn_id);
                    $order->update_status('wc-cancelled', __('Order has been canceled due to user rejection', 'cbpw'));
                }
                if($trasn_id!="false"){
                    $link_hash="";            
                    if($selected_network=='0x61'){
                    $link_hash='<a href="https://testnet.bscscan.com/tx/'.$trasn_id.'" target="_blank">'.$trasn_id.'</a>';
                    }
                    elseif ($selected_network=='0x38') {
                        $link_hash='<a href="https://bscscan.com/tx/'.$trasn_id.'" target="_blank">'.$trasn_id.'</a>';
                    }
                    elseif ($selected_network=='0x1') {
                        $link_hash = '<a href="https://etherscan.io/tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';

                    }
                    elseif ($selected_network=='0x3') {
                        $link_hash = '<a href="https://ropsten.etherscan.io/tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';

                    }
                    elseif ($selected_network=='0x4') {
                        $link_hash = '<a href="https://rinkeby.etherscan.io/tx/' . $trasn_id . '" target="_blank">' . $trasn_id . '</a>';

                    }
                
                        if ($payment_status_d == "default") {
                            $order->add_meta_data('TransactionId', $trasn_id);
                            $transection = __('Payment Received via Pay with MetaMask - Transaction ID:', 'cpmw') . $link_hash;               
                            $order->add_order_note($transection);
                            $order->add_meta_data( 'Sender', $sender);
                            $order->payment_complete($trasn_id);
                            // send email to costumer
                            WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                            // send email to admin
                            WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);

                        } else {
                            $order->add_meta_data('TransactionId', $trasn_id);
                            $order->add_meta_data( 'Sender', $sender);
                            $transection = __('Payment Received via Pay with MetaMask - Transaction ID:', 'cpmw') . $link_hash;                
                            $order->add_order_note($transection);
                            $order->update_status(apply_filters('cpmw_capture_payment_order_status', $payment_status_d));
                            // send email to costumer
                            WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
                            // send email to admin
                            WC()->mailer()->emails['WC_Email_New_Order']->trigger($order_id);
                        }           
                }
                $db = new CPMW_database();       
                $transaction['status'] = $order->get_status();
                $transaction['sender'] = $sender;        
                $transaction['transaction_id'] = !empty($trasn_id)?$trasn_id:"false";
                $order->save_meta_data();
                $data = [
                    'is_paid' => ($order->get_status()=="on-hold" && !empty($trasn_id))?true:$order->is_paid(),           
                    'order_status' => $order->get_status(),
                ];
                echo json_encode($data);
                $db->cpmw_insert_data($transaction);
                die();

            } catch (Exception $e) {

            }

            echo json_encode(['status' => 'error', 'error' => 'not a valid order_id']);
            die();
        }



        
       public static function cpmw_get_transaction_hash()
        {

             global $woocommerce;           
             $obj = new self();

            $order_id = sanitize_text_field($_REQUEST['order_id']);
            $order = new WC_Order($order_id);
            $nonce = !empty($_REQUEST['nonce']) ? sanitize_text_field($_REQUEST['nonce']) : "";
            $trasn_id=!empty($_REQUEST['transaction_id']) ? sanitize_text_field($_REQUEST['transaction_id']) : ""; 

            $secretCode = $obj->cpmw_generate_secretcode($order_id);
            update_post_meta($order_id, 'cpmw_secret_code', $secretCode);
            update_post_meta($order_id, 'cpmw_transaction_id', $trasn_id);
             $data = [
                    'secret_code' =>$secretCode,                    
                ];
                echo json_encode($data);
                die();


        }



    }
}