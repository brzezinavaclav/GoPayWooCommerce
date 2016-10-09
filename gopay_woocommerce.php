<?php
/*
Plugin Name: WooCommerce GoPay
Plugin URI:
Description: GoPay - platebni brana pro zjednoduseni plateb na internetu
Version: 1.0
Author: Václav Brzezina
Author URI: http://brzezina.cz
License:
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('plugins_loaded', 'load_gopay_gateway', 0);


function load_gopay_gateway (){
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_GoPay_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id		= 'gopay';
            $this->method_title     = __( 'GoPay', 'woocommerce_gopay' );
            $this->method_description     = __( 'GoPay - platebni brana pro zjednoduseni plateb na internetu', 'woocommerce_gopay' );

            $this->title 			= $this->get_option( 'title' );
            $this->description      = $this->get_option( 'description' );
            $this->gopay_identity     = $this->get_option( 'gopay_identity' );
            $this->gopay_seckey   = $this->get_option( 'gopay_seckey' );

            $this->init_form_fields();
            $this->init_settings();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Zapnout/Vypnout', 'woocommerce-gopay' ),
                    'type' => 'checkbox',
                    'label' => __( 'Povolit platební bránu', 'woocommerce-gopay' ),
                    'default' => 'yes'
                ),
                'gopay_identity_label' => array(
                    'title' => __( 'GoPay číslo obchodníka (GOID)', 'woocommerce_gopay' ),
                    'type' => 'title',
                    'description' => __( 'Číslo obchodníka vám přidelí společnost GoPay (gopay.cz). Pokud ještě žádné nemáte použijte testovací "8540279704".', 'woocommerce_gopay' ),
                    'default' => ''
                ),
                'gopay_identity' => array(
                    'title' => __( 'Vložte číslo obchodníka', 'woocommerce_gopay' ),
                    'type' => 'text'
                ),
                'gopay_seckey_label' => array(
                    'title' => __( 'GoPay tajný klíč', 'woocommerce_gopay' ),
                    'type' => 'title',
                    'description' => __( 'Tajný klíč pro vaše platby vám přidělí společnost GoPay (gopay.cz). Pro testovací účely použijte "ocxgXEL5psb7PAllKuCSblc9".', 'woocommerce_gopay' ),
                    'default' => ''
                ),
                'gopay_seckey' => array(
                    'title' => __( 'Vložte váš tajný klíč', 'woocommerce_gopay' ),
                    'type' => 'text'
                ),


                'gopay_enviroment_label' => array(
                    'title' => __( 'TESTOVACÍ / OSTRÝ režim', 'woocommerce-gopay' ),
                    'type' => 'title',
                    'description' => __( 'Zapnutím nebo vypnutím určujete zda modul poběží v testovacím režimu. Pro ostrý režím je potřebné nové GOID a SECKEY. Dodá GoPay po úspěšném otestování.', 'woocommerce-gopay' ),
                    'default' => ''
                ),
                'gopay_enviroment' => array(
                    'title' => __( 'zaškrtnuté znamená testovací provoz', 'woocommerce-gopay' ),
                    'type' => 'checkbox',
                    'label' => __( 'testovací provoz', 'woocommerce-gopay' ),
                    'default' => 'yes'
                )
            );
        }

    }

    class WC_GoPay_Paypal extends WC_GoPay_Gateway{
        public function __construct(){

            $this->id		= 'gopay_paypal';
            $this->icon     = plugins_url('/images/banner-1-mini.png', __FILE__);

            $this->method_title     = __( 'PayPal', 'woocommerce_gopay' );

            $this->title 			= 'PayPal';
            $this->description      = 'Platba přes PayPal pomocí platební brány GoPa';

            $this->init_form_fields();
            $this->init_settings();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        }
        
    }
    class WC_GoPay_Card extends WC_GoPay_Gateway{
        public function __construct(){

            $this->id		= 'gopay_card';
            $this->icon     = plugins_url('/images/banner-1-mini.png', __FILE__);

            $this->method_title     = __( 'Platba kartou', 'woocommerce_gopay' );

            $this->title 			= 'Platba kartou';
            $this->description      = 'Platba kartou pomocí platební brány GoPay';


            $this->init_settings();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }
    }
    class WC_GoPay_BankAccount extends WC_GoPay_Gateway{
        public function __construct(){

            $this->id		= 'gopay_bank_account';
            $this->icon     = plugins_url('/images/banner-1-mini.png', __FILE__);

            $this->method_title     = __( 'Platba bankovním převodem', 'woocommerce_gopay' );
            $this->title 			= 'Platba bankovním převodem';
            $this->description      = 'Platba bankovním převodem pomocí platební brány GoPay';


            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }
    }

}



function add_gopay_woocommerce( $methods ) {
    if(is_admin()) {
        $methods[] = 'WC_GoPay_Gateway';
    }
    if(!is_admin()) {
        $methods[] = 'WC_GoPay_Paypal';
        $methods[] = 'WC_GoPay_Card';
        $methods[] = 'WC_GoPay_BankAccount';
    }
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_gopay_woocommerce' );
