<?php
/*
 * Plugin Name: Comgate WooCommerce Custom Payment Gateway
 * Plugin URI: 
 * Description: Take credit card payments on your store.
 * Author: Veaceslav Mindru
 * Author URI: 
 * Version: 0.0.1
 */

/* https://rudrastyh.com/woocommerce/payment-gateway-plugin.html */

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'comgate_gateway_class' );
function comgate_gateway_class( $gateways ) {
	$gateways[] = 'Comgate_Gateway'; // your class name is here
	return $gateways;
}


add_action( 'plugins_loaded', 'comgate_init_gateway_class' );
function comgate_init_gateway_class() {

    class Comgate_Gateway extends WC_Payment_Gateway {

          /**
           *      * Class constructor, more about it in Step 3
           *           */
          public function __construct() {
						$this->id = 'comgate'; // payment gateway plugin ID
						$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
						$this->has_fields = true; // in case you need a custom credit card form
						$this->method_title = 'Comgate Pay';
						$this->method_description = 'Comgate Payment Plugin'; // will be displayed on the options page
					
						// gateways can support subscriptions, refunds, saved payment methods,
						// but in this tutorial we begin with simple payments
						$this->supports = array(
							'products'
						);
					
						// Method with all the options fields
						$this->init_form_fields();
						// Load the settings.
						$this->init_settings();
						$this->title = $this->get_option( 'title' );
						$this->description = $this->get_option( 'description' );
						$this->enabled = $this->get_option( 'enabled' );
						$this->testmode = 'yes' === $this->get_option( 'testmode' );
						$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
						$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
					
						// This action hook saves the settings
						add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
					
						// We need custom JavaScript to obtain a token
						add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
						
						// You can also register a webhook here
						// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
          }

              /**
                *      * Plugin options, we deal with it in Step 3 too
                *            */
					public function init_form_fields(){
					
						$this->form_fields = array(
							'enabled' => array(
								'title'       => 'Enable/Disable',
								'label'       => 'Enable Comgate Gateway',
								'type'        => 'checkbox',
								'description' => '',
								'default'     => 'no'
							),
							'title' => array(
								'title'       => 'Title',
								'type'        => 'text',
								'description' => 'This controls the title which the user sees during checkout.',
								'default'     => 'Credit Card',
								'desc_tip'    => true,
							),
							'description' => array(
								'title'       => 'Description',
								'type'        => 'textarea',
								'description' => 'This controls the description which the user sees during checkout.',
								'default'     => 'Platba Comgate',
							),
							'testmode' => array(
								'title'       => 'Test mode',
								'label'       => 'Enable Test Mode',
								'type'        => 'checkbox',
								'description' => 'Place the payment gateway in test mode using test API keys.',
								'default'     => 'yes',
								'desc_tip'    => true,
							),
							'test_publishable_key' => array(
								'title'       => 'Test Publishable Key',
								'type'        => 'text'
							),
							'test_private_key' => array(
								'title'       => 'Test Private Key',
								'type'        => 'password',
							),
							'publishable_key' => array(
								'title'       => 'Live Publishable Key',
								'type'        => 'text'
							),
							'private_key' => array(
								'title'       => 'Live Private Key',
								'type'        => 'password'
							)
						);
					}


#
#              /**
#                *      * You will need it if you want your custom credit card form, Step 4 is about it
#                *          */
#              public function payment_fields() {
#
#                    ...
#                               
#                          }
#
#              /*
#                *      * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
#                *          */
              public function payment_scripts() {
                // empty payment_scripts method
              }
#
#              /*
#                *      * Fields validation, more in Step 5
#                *          */
              public function validate_fields() {
                #if( empty( $_POST[ 'billing_first_name' ]) ) {
                #    wc_add_notice(  'First name is required!', 'error' );
                #      return false;
                #    }
                #return true; 

              }
#
#              /*
#                *      * We're processing the payments here, everything about it is in Step 5
#                *          */
              public function create_comgate_payment( $order ) {
               $transaction_id = base64_encode(random_bytes(10)) ;
               $order->set_transaction_id($transaction_id) ;
              }

              public function process_payment( $order_id ) {

								global $woocommerce;
							 
								// we need it to get any order detailes
								$order = wc_get_order($order_id );

//               wc_add_notice(gettype($order), "error");
//               wc_add_notice(print_r($order), "error");
               $transaction_id = $order->get_transaction_id();
               if ( strlen($transaction_id) > 0 ) {
                 $trans_status = "order exists" ;
               }
               else
               {
                 $this->create_comgate_payment($order);
                 $transaction_id = $order->get_transaction_id();
                 $trans_status = "creating order" ;
               }
               wc_add_notice($order->status, "error");
               wc_add_notice("transaction_id: $transaction_id", "error");
               wc_add_notice("transacttion status: $trans_status", "error");
//               wc_add_notice(get_object_vars($order), "error");
//               wc_add_notice('test notice', "error");
//   							wc_add_notice('TESTING Please try again.', 'error' );
                return;
							 
							 
								/*
							 	 * Array with parameters for API interaction
								 */
								$args = array(
								);
							 
								/*
								 * Your API interaction could be built with wp_remote_post()
							 	 */
								 $response = wp_remote_post( '{payment processor endpoint}', $args );
							 
							 
								 if( !is_wp_error( $response ) ) {
							 
									 $body = json_decode( $response['body'], true );
							 
									 // it could be different depending on your payment processor
									 if ( $body['response']['responseCode'] == 'APPROVED' ) {
							 
										// we received the payment
										$order->payment_complete();
										$order->reduce_order_stock();
							 
										// some notes to customer (replace true with false to make it private)
										$order->add_order_note( 'Hey, your order is paid! Thank you!', true );
							 
										// Empty cart
										$woocommerce->cart->empty_cart();
							 
										// Redirect to the thank you page
										return array(
											'result' => 'success',
											'redirect' => $this->get_return_url( $order )
										);
							 
									 } else {
										wc_add_notice(  'Please try again.', 'error' );
										return;
									}
							 
								} else {
									wc_add_notice(  'Connection error.', 'error' );
									return;
								}
    }
#                          }
#
#              /*
#                *      * In case you need a webhook, like PayPal IPN etc
#                *          */
#              public function webhook() {
#
#                    ...
#                                
#                          }
          #
  } #konec class
}