<?php
/*

Plugin Name: GoPay for WooCommerce 
Plugin URI: http://www.aw-dev.cz
Description: GoPay platebni brana pro zjednoduseni plateb na internetu
Version: 1.5.5
Author:AW-DEV, v.o.s
Author URI: http://www.aw-dev.cz
License: .....
(GPP = Gateway GoPay)
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


add_action('plugins_loaded', 'GGP__plugins_loaded__load_gopay_gateway', 0);
add_action('plugins_loaded', 'GGP_registerTextDomain');

function GGP__plugins_loaded__load_gopay_gateway ()
{

    if (!class_exists('WC_Payment_Gateway'))
    	// Nothing happens here is WooCommerce is not loaded
    	return;

	//=======================================================================
	/**
        * GoPay gateway
        *
        * Provides a GoPay Gateway. Based on code by Mike Pepper.
        *
        * @class 		WC_Gateway_GoPay
        * @extends		WC_Payment_Gateway
        * @version		1.0.0
        * @package		WooCommerce/Classes/Payment
        * @author 		AW-Dev, v.o.s, www.aw-dev.cz
        */
   
   class GPP_order extends WC_Order {
       
         /** PRIDANO PRO GOPAY MODUL
         * @version 1.0.0
         * @author AW-Dev, v.o.s 
         */
        /** @public string */
        public $payment_session_id;
        
         /** @public string */
        public $payed_status;
        /**************************/
              
         /**
         * FUNKCE PRO GOPAY MODUL!!
         */
        
        /**
         * Add session id for internet payments method
         * 
         * @param type $session_id
         * @return boolean
         * 
         * @version 1.0.0
         * @author AW-Dev, v.o.s         
         */

        public function set_session_id($session_id) {
             update_post_meta( $this->id, 'session_id', $session_id );
             return true;
        }
        
        /**
         * Get session id for internet payments method
         * 
         * @param type $session_id
         * @return boolean
         * 
         * @version 1.0.0
         * @author AW-Dev, v.o.s         
         */

        public function get_session_id() {
             $values = get_post_custom( $this->id );             
             return ($values['session_id'][0] ) ? esc_attr( $values['session_id'][0]) : null;
        }
        
        
        /**
         * Add session id for internet payments method
         * 
         * @param type $session_id
         * @return boolean
         * 
         * @version 1.0.0
         * @author AW-Dev, v.o.s         
         */

        public function set_payed_status($status) {
             update_post_meta( $this->id, 'payed_status', $status );
             return true;
        }
        
        /**
         * Get session id for internet payments method
         * 
         * @param type $session_id
         * @return boolean
         * 
         * @version 1.0.0
         * @author AW-Dev, v.o.s         
         */

        public function get_payed_status() {
             //global $woocommerce;
             //global $post;
             $values = get_post_custom( $this->id );             
             return ($values['payed_status'][0] ) ? esc_attr( $values['payed_status'][0]) : null;
        }
        /***************************************************************************************************/
       
   }
   
 
   class WC_Gateway_GoPay extends WC_Payment_Gateway {

    var $notify_url;

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
    public function __construct() {
		$this->id		= 'gopay';
		$logo = $this->get_option( 'logo_img' );
                switch($logo) {
                    case 'no_logo': 
                            $this->icon = apply_filters('woocommerce_gopay_icon', '');
                            break;
                    case 'gp_logo1': 
                            $this->icon = plugins_url('/images/banner-1-mini.png', __FILE__);    // 32 pixels high
                            break;
                    case 'gp_logo2': 
                            $this->icon = plugins_url('/images/banner-2-mini.png', __FILE__);    // 32 pixels high
                            break;
                    case 'gp_logo3': 
                            $this->icon = plugins_url('/images/banner-3-mini.png', __FILE__);    // 32 pixels high
                            break;
                    case 'gp_comm': 
                            $this->icon = plugins_url('/images/community-gopay-logo.png', __FILE__);    // 32 pixels high
                            break;                     
                }
                        
                
		$this->has_fields 	= false;
		$this->method_title     = __( 'GoPay', 'woocommerce-gopay' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title 			= $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->gopay_identity     = $this->get_option( 'gopay_identity' );
		$this->gopay_seckey   = $this->get_option( 'gopay_seckey' );
                $this->gopay_enviroment   = $this->get_option( 'gopay_enviroment' );
                
                //NOTFIKACNI URL 
                    //bez hezkych URL ->  www.vasserver.cz/?wc-api=WC_Gateway_GoPay 
                    //s hezkymi URL -> www.vasserver.cz/wc-api/WC_Gateway_GoPay 
                
                $this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_GoPay', home_url( '/' ) ) );
                $this->gopay_fail_url   = $this->get_option( 'gopay_fail_url' );
                $this->gopay_success_url   = $this->get_option( 'gopay_success_url' );
                
                

		// Actions
                add_action( 'valid-gopay-standard-ipn-request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thankyou_' . $this->id, array(&$this, 'GGP_update_order')); // hooks into the thank you page after payment                
                
                // Customer Emails
                add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 2 );
                
                
                
                require_once(dirname(__FILE__) . "/api/gopay_config.php");
                if ($this->gopay_enviroment == "yes") { // testovaci provoz                   
                    GopayConfig::init(GopayConfig::TEST);
                } else {
                    GopayConfig::init(GopayConfig::PROD);
                }
                
                // Payment listener/API hook
                add_action( 'woocommerce_api_wc_gateway_gopay', array( $this, 'notify' ) );
                
    }
        
    /**
     * zakladni obsluha IPN notifikaci z GoPay     
     */             
    function notify() {
        @ob_clean();
        if ($this->get_option('gopay_enviroment_debug') == 'yes') {
            if (($this->get_option('gopay_enviroment_email')) != "") {
                wp_mail( $this->get_option('gopay_enviroment_email'), 'POZADAVEK NA BRANU IPN - '.$_SERVER['HTTP_HOST'], print_r($_REQUEST,true));
            } 
        }
        if ( !empty( $_REQUEST ) ) {
            header( 'HTTP/1.1 200 OK' );
            do_action( "valid-gopay-standard-ipn-request", $_REQUEST );            
        } else {            
            if ($this->get_option('gopay_enviroment_debug') == 'yes') {
                if (($this->get_option('gopay_enviroment_email')) != "") {
                    wp_mail( $this->get_option('gopay_enviroment_email'), 'CHYBNY POZADAVEK NA BRANU IPN DIE!- '.$_SERVER['HTTP_HOST'], print_r($_REQUEST,true));
                } 
            }            
            wp_die("GoPay notify/request failed.");            
        }
    }

    
     /**
      * funkce zpracuje odlozenou transakci      
      * @param type $posted
      */

    function successful_request($posted) {
            global $woocommerce;            
            $status = 0; // sberna status promena
            $order_id = $posted['p1'];            
            $order = new GPP_order( $order_id );
            $payment_status = null;
            if($order) {
                //objednavku jsem nasel                
                if ($posted['p2'] == $order->order_key ) {
                    $currency = get_option( 'woocommerce_currency' );
                    // objednavka je validni, zjistim tedy jaky je jeji novy stav                    
                    // include essential file
                    require_once(dirname(__FILE__) . "/order.php");
                    require_once(dirname(__FILE__) . "/api/gopay_helper.php");
                    require_once(dirname(__FILE__) . "/api/gopay_soap.php");
                    
                    $payment_status = GopaySoap::isPaymentDone($posted['paymentSessionId'],
                                                               $posted['targetGoId'],
                                                               $posted['orderNumber'], 
                                                               $order->order_total,
                                                               $currency, 
                                                               "Objednávka ".$order->get_order_number(), 
                                                               $this->gopay_seckey);                                                              
                    
                    
                    if ($payment_status['sessionState'] == 'PAID') {
                        $order->set_payed_status(GopayHelper::PAID);
                        $order->update_status('completed');                                                
                    } else if($payment_status['sessionState'] == 'PAYMENT_METHOD_CHOSEN') {
                        $order->set_payed_status(GopayHelper::PAYMENT_METHOD_CHOSEN);
                        $order->update_status('on-hold');
                    } else if($payment_status['sessionState'] == 'AUTHORIZED') {
                        $order->set_payed_status(GopayHelper::AUTHORIZED);
                        $order->update_status('on-hold');
                    } else if($payment_status['sessionState'] == 'CANCELED') {
                        $order->set_payed_status(GopayHelper::CANCELED);
                        $order->update_status('cancelled');
                    } else if($payment_status['sessionState'] == 'TIMEOUTED') {
                        $order->set_payed_status(GopayHelper::TIMEOUTED);
                        $order->update_status('cancelled');
                    } else if($payment_status['sessionState'] == 'REFUNDED') {
                        $order->set_payed_status(GopayHelper::REFUNDED);
                        $order->update_status('refunded');
                    } else if($payment_status['sessionState'] == 'FAILED') {
                        $order->set_payed_status(GopayHelper::FAILED);
                        $order->update_status('failed');
                    } else if($payment_status['sessionState'] == 'CREATED') {
                        $order->set_payed_status(GopayHelper::CREATED);
                        $order->update_status('on-hold');
                    } else {
                        if ($this->get_option('gopay_enviroment_debug') == 'yes') {
                            if (($this->get_option('gopay_enviroment_email')) != "") {
                                wp_mail( $this->get_option('gopay_enviroment_email'), 'ZPRACOVANI POZADAVKU - neznamy typ - '.$_SERVER['HTTP_HOST'], print_r($payment_status,true));
                            } 
                        }

                    }
                      
                
                    $status = $payment_status;
                } else {
                    // objednavka sice existuje ale nesouhlasi ochrany klic
                    $status = -4;
                }
    
            } else {
                // chyba objednavku jsem nanasel
                $status = -2;
            }
          if ($this->get_option('gopay_enviroment_debug') == 'yes') {
                            if (($this->get_option('gopay_enviroment_email')) != "") {
                                wp_mail( $this->get_option('gopay_enviroment_email'), 'ZPRACOVANI POZADAVKU - '.$_SERVER['HTTP_HOST'], print_r($status,true).print_r($posted,true).print_r($order,true));
                            } 
                        }          
    }
    

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

        $description = '<div> GoPay Logo 1 </div> <div> <img src="'.plugins_url('/images/banner-1-mini.png', __FILE__).'"></div>
                        <div> GoPay Logo 2 </div> <div> <img src="'.plugins_url('/images/banner-2-mini.png', __FILE__).'"></div>
                        <div> GoPay Logo 3 </div> <div> <img src="'.plugins_url('/images/banner-3-mini.png', __FILE__).'"></div>
                        <div style="width:300px"> GoPay Community </div> <div> <img src="'.plugins_url('/images/community-gopay-logo.png', __FILE__).'"></div>';
        
    	$this->form_fields = array(
                        'enabled' => array(
							'title' => __( 'Zapnout/Vypnout', 'woocommerce-gopay' ),
							'type' => 'checkbox',
							'label' => __( 'Povolit platební bránu', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                    
            
			'enabled' => array(
							'title' => __( 'Zapnout/Vypnout', 'woocommerce-gopay' ),
							'type' => 'checkbox',
							'label' => __( 'Povolit platební bránu', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
            
                                    
                                                
                        'logo_img' => array(
							'title' => __( 'Logo k platbě', 'woocommerce-gopay' ),
							'type' => 'select',
							'label' => __( 'Povolit platební bránu', 'woocommerce-gopay' ),
                                                        'options' => array('no_logo' => __('Bez loga', 'woocommerce-gopay'), 
                                                                           'gp_logo1' => __('GoPay Logo 1', 'woocommerce-gopay'),
                                                                           'gp_logo2' => __('GoPay Logo 2', 'woocommerce-gopay'),
                                                                           'gp_logo3' => __('GoPay Logo 3', 'woocommerce-gopay'),
                                                                           'gp_comm' => __('GoPay Community', 'woocommerce-gopay')),
                                                        'description' => __( 'Vyberte si logo které se bude zobrazovat u platební brány.'.$description, 'woocommerce-gopay' ), 
							'desc_tip' => true,
						),
            
                                    
            
            
			'title' => array(
							'title' => __( 'Název platební brány', 'woocommerce-gopay' ),
							'type' => 'text',
							'description' => __( 'Pod tímto názvem se zobrazuje platební brána vašim zákazníkům.', 'woocommerce-gopay' ),
							'default' => __( 'GoPay platební brána', 'woocommerce-gopay' ),
							'desc_tip'      => true,
						),
			'description' => array(
							'title' => __( 'Popis platební metody', 'woocommerce-gopay' ),
							'type' => 'textarea',
							'description' => __( 'Popis jak platební metodu použít. Zobrazí se zákazníkům při výběru platební brány.', 'woocommerce-gopay' ),
							'default' => __( 'Platba přes GoPay je rychlá a bezpečná.', 'woocommerce-gopay' )
						),
			'gopay_identity_label' => array(
							'title' => __( 'GoPay číslo obchodníka (GOID)', 'woocommerce-gopay' ),
							'type' => 'title',
							'description' => __( 'Číslo obchodníka vám přidelí společnost GoPay (gopay.cz). Pokud ještě žádné nemáte použijte testovací "8540279704".', 'woocommerce-gopay' ),
							'default' => ''
						),
			'gopay_identity' => array(
							'title' => __( 'Vložte číslo obchodníka', 'woocommerce-gopay' ),
							'type' => 'text'
						),
			'gopay_seckey_label' => array(
							'title' => __( 'GoPay tajný klíč', 'woocommerce-gopay' ),
							'type' => 'title',
							'description' => __( 'Tajný klíč pro vaše platby vám přidělí společnost GoPay (gopay.cz). Pro testovací účely použijte "ocxgXEL5psb7PAllKuCSblc9".', 'woocommerce-gopay' ),
							'default' => ''
						),
			'gopay_seckey' => array(
							'title' => __( 'Vložte váš tajný klíč', 'woocommerce-gopay' ),
							'type' => 'text'							
						),
                        
                        'gopay_methods_label' => array(
							'title' => __( 'Možné platební metody', 'woocommerce-gopay' ),
							'type' => 'title',
							'description' => __( 'Vyberte platební metody, které chcete na vaší bráně používat.', 'woocommerce-gopay' ),
							'default' => '',
						),
             
                        'gopay_method_eu_gp_w' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'GoPay peněženka Elektronická peněženka.', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_eu_bank' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Bankovní převod Běžný bankovní převod – GoPay sestavuje instrukce pro provedení platby.', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_cz_cs_c' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Platba prostřednictvím GoPay peněženky- platební karty Česká spořitelna, a.s. - MasterCard, VISA E-commerce 3-D Secure.', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_eu_gp_u' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Platba prostřednictvím GoPay peněženky- platební karty UniCredit Bank - Global MasterCard, VISA payments.', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_SUPERCASH' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'SuperCASH Terminál České pošty, Sazka a.s.', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_eu_pr_sms' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Premium SMS Mobilní telefon - Premium SMS', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_cz_mp' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Mobilní platba - M-platba Mobilní telefon - platební brána operátora', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_cz_kb' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Platba KB – Mojeplatba - platební tlačítko Internetové bankovnictví Komerční banky a.s.', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_cz_rb' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Platba RB – ePlatby - platební tlačítko Internetové bankovnictví Raiffeisenbank a.s. ', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_cz_mb' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Platba mBank – mPeníze - platební tlačítko Internetové bankovnictví MBank', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_cz_fb' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Platba Fio Banky – platební tlačítko Internetové bankovnictví  Fio banky', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_sk_uni' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'Platba UniCredit Bank - uniplatba - platební tlačítko Internetové bankovnictví UniCredit Bank a.s.', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
                        'gopay_method_sk_sp' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'SporoPay - platební tlačítko Internetové bankovnictví Slovenská sporiteľňa, a.s.', 'woocommerce-gopay' ),
							'default' => 'yes'
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
						),
            
                        'gopay_enviroment_debug' => array(							
							'type' => 'checkbox',
                                                        'label' => __( 'zasílat ladící emaily při zpracování plateb a komunikaci s GoPay', 'woocommerce-gopay' ),
							'default' => 'yes'
						),
            
                        'gopay_enviroment_email' => array(	
                                                        'title' => __( 'email na který se mají zasílat ladící informace', 'woocommerce-gopay' ),
							'type' => 'text',                                                        
							'default' => 'jakub@aw-dev.cz'
						),
            
            
                        'gopay_fail_url_label' => array(
							'title' => __( 'Stránka při chybě', 'woocommerce-gopay' ),
							'type' => 'title',
							'description' => __( 'zadejte celou cestu ke stránce s chybou', 'woocommerce-gopay' ),							
						),
			'gopay_fail_url' => array(
							'title' => __( 'Stránka při chybné transakci', 'woocommerce-gopay' ),
							'type' => 'text',
                                                        'default' => 'http://PROSIM.UPRAVTE.ME/pokladna/prijate-objednavky'
						),
            
                        'gopay_success_url_label' => array(
							'title' => __( 'Stránka pro úspěšnou transakci. ', 'woocommerce-gopay' ),
							'type' => 'title',
							'description' => __( 'Stránka pokud se vše podaří', 'woocommerce-gopay' ),			
                            
						),
			'gopay_success_url' => array(
							'title' => __( 'Stránka pokud se vše podaři.', 'woocommerce-gopay' ),
							'type' => 'text',
                            'default' => 'http://PROSIM.UPRAVTE.ME/pokladna/prijate-objednavky'
						),
			);
    }


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
    	?>
    	<h3><?php _e( 'GoPay Plugin (helpdesk@aw-dev.cz)', 'woocommerce-gopay' ); ?></h3>
    	<p><?php _e('Umožňuje platby přes bránu GoPay', 'woocommerce-gopay' ); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    }


    
    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
    function GGP_update_order() {
              global $woocommerce;               
              
             /*
              * Parametry obsazene v redirectu po potvrzeni / zruseni platby, predavane od GoPay e-shopu
              */
              $order_id = (isset($_GET['p1'])?$_GET['p1']:null);

              /**
               * Provede overeni zaplacenosti objednavky po zpetnem presmerovani z platebni brany                   
               */
               if ($order_id) {
                   $order = new GPP_order($order_id);

                       if ($order->payment_method == "gopay" ) {
                           $currency = get_option( 'woocommerce_currency' );

                           require_once(dirname(__FILE__) .'/api/gopay_helper.php');
                           require_once(dirname(__FILE__) .'/api/gopay_soap.php');
                           
                           $GOID = $this->gopay_identity;
                           $SECURE_KEY = $this->gopay_seckey;

                           //hodnoty vracene GoPay branou
                           $returnedPaymentSessionId = (isset($_GET['paymentSessionId'])?$_GET['paymentSessionId']:null);
                           $returnedGoId = (isset($_GET['targetGoId'])?$_GET['targetGoId']:null);
                           $returnedOrderNumber = (isset($_GET['orderNumber'])?$_GET['orderNumber']:null);
                           $returnedEncryptedSignature = (isset($_GET['encryptedSignature'])?$_GET['encryptedSignature']:null);



                          /*
                           * Kontrola validity parametru v redirectu, opatreni proti podvrzeni potvrzeni / zruseni platby
                           */
                          try {
                                  GopayHelper::checkPaymentIdentity(
                                                          (float)$returnedGoId,
                                                          (float)$returnedPaymentSessionId,
                                                          null,
                                                          $returnedOrderNumber,
                                                          $returnedEncryptedSignature,
                                                          (float)$GOID,
                                                          $order->id,
                                                          $SECURE_KEY);

                                  /*
                                   * Kontrola zaplacenosti objednavky na serveru GoPay
                                   */
                                  
                                  
                                  $style_success = 'style="padding:10px; background-color:lightgreen; border: 1px solid green"'; 
                                  $style_hold =  'style="padding:10px; background-color:#FEC1BD; border: 1px solid red"';
                                  $style_error = 'style="padding:10px; background-color:#FEC1BD; border: 1px solid red"';     
                                  
                                  $result = GopaySoap::isPaymentDone(
                                                          (float)$returnedPaymentSessionId,
                                                          (float)$GOID,
                                                          $order->id,
                                                          (int)$order->order_total,
                                                          $currency,
                                                          "Objednávka ".$order->get_order_number(),
                                                          $SECURE_KEY);

                                  if ($result["sessionState"] == GopayHelper::PAID ) {                                                                                                          
                                          /*
                                           * Presmerovani na prezentaci uspesne platby
                                           */
                                          $order->set_payed_status(GopayHelper::PAID);
                                          $order->update_status('completed');                                          
                                          echo '<div '.$style_success.'>';
                                            echo _e( 'Objednávka byla uhrazena.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          unset( $woocommerce->session->order_awaiting_payment );                                                                                    
                                          
                                  } else if ( $result["sessionState"] == GopayHelper::PAYMENT_METHOD_CHOSEN) {
                                          /* Platba ceka na zaplaceni */
                                          $order->update_status('on-hold');
                                          echo '<div '.$style_hold.'>';
                                            echo _e( 'Objednávka nebyla zatím uhrazena.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          $order->set_payed_status(GopayHelper::PAYMENT_METHOD_CHOSEN);

                                  } else if ( $result["sessionState"] == GopayHelper::CREATED) {
                                          /* Platba nebyla zaplacena */
                                          $order->update_status('on-hold');
                                          echo '<div '.$style_hold.'>';
                                            echo _e( 'Objednávka nebyla zatím uhrazena.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          
                                          $order->set_payed_status(GopayHelper::CREATED);

                                  } else if ( $result["sessionState"] == GopayHelper::CANCELED) {
                                          /* Platba byla zrusena objednavajicim */                                      
                                          $order->update_status('cancelled');
                                           echo '<div '.$style_error.'>';
                                            echo _e( 'Platba za objednávku byla zrušena.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          $order->set_payed_status(GopayHelper::CANCELED);

                                  } else if ( $result["sessionState"] == GopayHelper::TIMEOUTED) {
                                          /* Platnost platby vyprsela  */
                                          $order->update_status('cancelled');
                                          echo '<div '.$style_error.'>';
                                            echo _e( 'Platební příkaz za objednávku vypršel.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          $order->set_payed_status(GopayHelper::TIMEOUTED);

                                  } else if ( $result["sessionState"] == GopayHelper::AUTHORIZED) {
                                          /* Platba byla autorizovana, ceka se na dokonceni  */
                                          $order->update_status('on-hold');
                                          echo '<div '.$style_hold.'>';
                                            echo _e( 'Platba byla autorizována ale ještě nebyla uhrazena.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          $order->set_payed_status(GopayHelper::AUTHORIZED);

                                  } else if ( $result["sessionState"] == GopayHelper::REFUNDED) {
                                          /* Platba byla vracena - refundovana  */
                                          $order->update_status('refunded');
                                          echo '<div '.$style_success.'>';
                                            echo _e( 'Platba za objednávku byla vrácena.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          $order->set_payed_status(GopayHelper::REFUNDED);

                                  } else {
                                          /* Chyba ve stavu platby */
                                          $order->set_payed_status(GopayHelper::FAILED);
                                          $order->update_status('failed');
                                          echo '<div '.$style_error.'>';
                                            echo _e( 'Platba selhala. Pro další postup nás prosím kontaktujte.', 'woocommerce-gopay' );
                                          echo '</div>';
                                          $result["sessionState"] = GopayHelper::FAILED;
                                  }
                                  
                          } catch (Exception $e) {
                                  /*
                                   * Nevalidni informace z redirectu
                                   */
                                  if ($this->get_option('gopay_enviroment_debug') == 'yes') {
                                      if (($this->get_option('gopay_enviroment_email')) != "") {
                                          wp_mail( $this->get_option('gopay_enviroment_email'), 'ZPRACOVANI PLATBY - nevalidni informace - '.$_SERVER['HTTP_HOST'], print_r($e,true));
                                      } 
                                  }
                                  
                                  exit("chyba redirectu z gopay!");                                        
                          }
                   }              
            }
    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param GPP_order $order
     * @param bool $sent_to_admin
     * @return void
     */
    function email_instructions( $order, $sent_to_admin ) {

    	if ( $sent_to_admin ) return;

    	if ( $order->status !== 'on-hold') return;

    	if ( $order->payment_method !== 'gopay') return;

		if ( $description = $this->get_description() )
        	echo wpautop( wptexturize( $description ) );

		?><h2><?php _e( 'Naše detaily', 'woocommerce-gopay' ) ?></h2><ul class="order_details gopay_details"><?php

		?></ul><?php
    }


    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    function process_payment( $order_id ) {
                
                global $woocommerce;

		$order = new GPP_order( $order_id );
               
                $GOID = $this->gopay_identity;
                $SECURE_KEY = $this->gopay_seckey;
                $FAILED_URL = $this->gopay_fail_url;
                $currency = get_option( 'woocommerce_currency' );
                                
		// Mark as on-hold (we're awaiting the payment)
		$order->update_status('on-hold', __( 'Platba čeká na uhrazení', 'woocommerce-gopay' ));
                
                //prepare order
                require_once(dirname(__FILE__) . "/order.php");
                require_once(dirname(__FILE__) . "/api/gopay_helper.php");
                require_once(dirname(__FILE__) . "/api/gopay_soap.php");
                
                
                $gopay_order = new Order;
                
                $nextmonth = date("Y-m-d", mktime(0, 0, 0, date("m") + 1, date("d"), date("Y"))); 

                $gopay_order->orderNumber = $order->get_order_number();
                $gopay_order->totalPrice = $order->get_order_total();
                $gopay_order->currency = $currency;
                $gopay_order->productName = "Objednávka ".$order->get_order_number();

                $gopay_order->preAuthorization = false;
                $gopay_order->recurrentPayment = false;
                $gopay_order->recurrenceDateTo = $nextmonth;
                $gopay_order->recurrenceCycle = GopayHelper::RECURRENCE_CYCLE_ON_DEMAND;
                $gopay_order->recurrencePeriod = 1;

                $gopay_order->firstName = $order->billing_first_name;
                $gopay_order->lastName = $order->billing_last_name;
                $gopay_order->city = $order->billing_city;
                $gopay_order->street = $order->billing_address;
                $gopay_order->postalCode = $order->billing_postcode;
                $gopay_order->countryCode = CountryCode::CZE; // napevno nastavena ceska republika
                $gopay_order->email = $order->billing_email;
                $gopay_order->phoneNumber = $order->billing_phone;
                                                
                                
               /*
                * Vytvoreni platby na strane GoPay a nasledne presmerovani na platebni branu
                */                
                
                /* MOZNE PLATEBNI METODY
                
                eu_gp_w GoPay peněženka Elektronická peněženka.
                eu_bank Bankovní převod Běžný bankovní převod – GoPay sestavuje instrukce pro provedení platby.      
                cz_cs_c Platba prostřednictvím GoPay peněženky- platební karty Česká spořitelna, a.s. - MasterCard, VISA E-commerce 3-D Secure
                eu_gp_u Platba prostřednictvím GoPay peněženky- platební karty UniCredit Bank - Global MasterCard, VISA payments
                SUPERCASH SuperCASH Terminál České pošty, Sazka a.s.
                eu_pr_sms Premium SMS Mobilní telefon - Premium SMS
                cz_mp Mobilní platba - M-platba Mobilní telefon - platební brána operátora
                cz_kb Platba KB – Mojeplatba - platební tlačítko Internetové bankovnictví Komerční banky a.s.   
                cz_rb Platba RB – ePlatby - platební tlačítko Internetové bankovnictví Raiffeisenbank a.s.             
                cz_mb Platba mBank – mPeníze - platební tlačítko Internetové bankovnictví MBank
                cz_fb Platba Fio Banky – platební tlačítko Internetové bankovnictví  Fio banky
                sk_uni Platba UniCredit Bank - uniplatba - platební tlačítko Internetové bankovnictví UniCredit Bank a.s.
                sk_sp Platba SLSP - sporopay - platební tlačítko Internetové bankovnictví Slovenská sporiteľňa, a. s.
                
                
                */
                               
                //DOSTUPNE BRANY PRO ZAKAZNIKA (zakomentujte nebo smazte ty ktere nechcete)                         
                
                $paymentChannels = array();
                if($this->get_option('gopay_method_eu_gp_w') == 'yes') $paymentChannels[] = 'eu_gp_w';
                if($this->get_option('gopay_method_eu_bank') == 'yes') $paymentChannels[] = 'eu_bank';
                if($this->get_option('gopay_method_cz_cs_c') == 'yes') $paymentChannels[] = 'cz_cs_c';
                if($this->get_option('gopay_method_eu_gp_u') == 'yes') $paymentChannels[] = 'eu_gp_u';
                if($this->get_option('gopay_method_SUPERCASH') == 'yes') $paymentChannels[] = 'SUPERCASH';
                if($this->get_option('gopay_method_eu_pr_sms') == 'yes') $paymentChannels[] = 'eu_pr_sms';
                if($this->get_option('gopay_method_cz_mp') == 'yes') $paymentChannels[] = 'cz_mp';
                if($this->get_option('gopay_method_cz_kb') == 'yes') $paymentChannels[] = 'cz_kb';
                if($this->get_option('gopay_method_cz_rb') == 'yes') $paymentChannels[] = 'cz_rb';
                if($this->get_option('gopay_method_cz_mb') == 'yes') $paymentChannels[] = 'cz_mb';
                if($this->get_option('gopay_method_cz_fb') == 'yes') $paymentChannels[] = 'cz_fb';
                if($this->get_option('gopay_method_sk_uni') == 'yes') $paymentChannels[] = 'sk_uni';
                if($this->get_option('gopay_method_sk_sp') == 'yes') $paymentChannels[] = 'sk_sp';
                
                if (!count($paymentChannels)) {
                    // defaultni brana, pokud neni zadna vybrana!
                    $paymentChannels[] = 'eu_gp_u';
                }
                //VYCHOZI BRANA    
                $defaultPaymentChannel = "eu_gp_u";     
                    
//              $paymentChannels = array('eu_bank',
//                                         'eu_gp_u',
//                                           'cz_kb',
//                                           'cz_rb',
//                                           'cz_mb',
//                                           'cz_fb'); // povolene platebni brany                
                
                
                
                //uzivatelske parametry
                $p1 = $order->id; // woocomerce order id ex. "#65"
                $p2 = $order->order_key; // woocommerce order_key ex. "order_23af32456322"
                $p3 = null;
                $p4 = null;
                                               
                $success_url = $this->gopay_success_url."/?order=".$order->id."&key=".$order->order_key;
                $failed_url = $this->gopay_success_url."/?order=".$order->id."&key=".$order->order_key; 
                
                try {   $paymentSessionId = GopaySoap::createPayment(
                                            (float)$GOID,
                                            $gopay_order->getProductName(),
                                            (int)$gopay_order->getTotalPrice()*100,
                                            $gopay_order->getCurrency(),
                                            $gopay_order->getOrderNumber(),
                                            $success_url,
                                            $failed_url,
                                            $paymentChannels,
                                            $defaultPaymentChannel,
                                            $SECURE_KEY,
                                            $gopay_order->firstName,
                                            $gopay_order->lastName,
                                            $gopay_order->city,
                                            $gopay_order->street,
                                            $gopay_order->postalCode,
                                            $gopay_order->countryCode,
                                            $gopay_order->email,
                                            $gopay_order->phoneNumber,
                                            $p1,
                                            $p2,
                                            $p3,
                                            $p4,
                                            'cs');

                } catch (Exception $e) {
                        /*
                         *  Osetreni chyby v pripade chybneho zalozeni platby
                         */
                        if ($this->get_option('gopay_enviroment_debug') == 'yes') {
                                      if (($this->get_option('gopay_enviroment_email')) != "") {
                                          wp_mail( $this->get_option('gopay_enviroment_email'), 'CHYBA - INICIALIZACE NOVE PLATBY - '.$_SERVER['HTTP_HOST'], print_r($e,true));
                                      } 
                                  }
                        return array(
                            'result' 	=> 'chyba_pri_zakladani_platby_na_gopay',
                            'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, $FAILED_URL. "?sessionState=" . GopayHelper::FAILED))
                    	);
                        
                }

                /*
                 * Platba na strane GoPay uspesne vytvorena
                 * Ulozeni paymentSessionId k objednavce. Slouzi pro komunikaci s GoPay
                 */
                $gopay_order->setPaymentSessionId($paymentSessionId);
                
                $order->set_session_id($paymentSessionId);                                                                
                
                $encryptedSignature = GopayHelper::encrypt(
                                      GopayHelper::hash(
                                      GopayHelper::concatPaymentSession((float)$GOID,
                                                                        (float)$paymentSessionId, $SECURE_KEY)
                                                                        ), $SECURE_KEY);			
                
                /*
                 * Presmerovani na platebni branu GoPay s predvybranou platebni metodou GoPay penezenka ($defaultPaymentChannel)
                 */                           
                //go to web page                
                if ($this->gopay_enviroment == "yes") {
                    // testovaci prostredi
                    $payment_url = "https://testgw.gopay.cz/gw/pay-full-v2?sessionInfo.targetGoId=".$GOID."&sessionInfo.paymentSessionId=".$paymentSessionId."&sessionInfo.encryptedSignature=".$encryptedSignature;
                } else {
                    // ostry provoz
                    $payment_url =  "https://gate.gopay.cz/gw/pay-full-v2?sessionInfo.targetGoId=".$GOID."&sessionInfo.paymentSessionId=".$paymentSessionId."&sessionInfo.encryptedSignature=".$encryptedSignature;
                }                
                return array(
                            'result' 	=> 'success',
                            'redirect'	=> $payment_url
                    	);                                                                
    }


  }
  
        //=======================================================================
        // Hook into WooCommerce - add necessary hooks and filters
	add_filter ('woocommerce_payment_gateways', 	'GGP_add_gopay_gateway' );
        add_action( 'init', 'woocommerce_gopay_notify' );
  
	/**
	 * Add the gateway to WooCommerce
	 *
	 * @access public
	 * @param array $methods
	 * @package
	 * @return array
	 */
	function GGP_add_gopay_gateway( $methods )
	{
		$methods[] = 'WC_Gateway_GoPay';
		return $methods;
	}
  
        /**
        * Handle notify request from gopay!
        * @author AW-Dev, .v.o.s
        * @access public
        * @return void
        */
        function woocommerce_gopay_notify() {
            do_action( 'woocommerce_api_wc_gateway_gopay' );        
        }
        
        /**
         * funkce ktera nacte jazykovou domenu
         */
        function GGP_registerTextDomain(){
            load_plugin_textdomain( 'woocommerce-gopay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }
        
        
  
}