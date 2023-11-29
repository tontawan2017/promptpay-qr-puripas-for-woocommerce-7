<?php
/*
Plugin Name: Promptpay QR Puripas
Description: Promptpay QR Puripas
Version: 2.5.1
Author: Amnat Rompruek	
Author URI: https://puripas.com
Text Domain: promptpay_qr_puripas
Domain Path: /languages/
*/

/*

คาถาเรียกทรัพย์ หลวงพ่อรวย 
(ท่องนะโม 3 จบ)

สัมพุทธชิตา จะสัจจานิ เก รัตสะ พระพุทธชิตา
สัพพะโส คุณะวิภา สัมปัจโต นะรุตตะโม
มหาลาภัง สัพพะสิทธิ ภะวันตุเม

สวดเพื่อขอโชคลาภและทำมาหากินให้ร่ำรวย 


*/

load_plugin_textdomain( 'promptpay_qr_puripas', false,  plugin_basename( dirname( __FILE__ ) )  . '/languages' );

add_action('admin_menu','actions_promptpay_for_woocommerce_admin_page'); 
function actions_promptpay_for_woocommerce_admin_page(){

	add_menu_page(
		'License Promptpay', 
		__( 'License Promptpay', 'promptpay_qr_puripas' ),
		'manage_options',
		'promptpay-woocommerce-puripas',
		'actions_admin_menu_promptpay_puripas',
		'dashicons-money', 
		38
		);   
}

function actions_admin_menu_promptpay_puripas() {
	/*
	$license_key = $_REQUEST['promptpay_puripas_license_key'];
	update_option('promptpay_puripas_license_key', $license_key); 
	*/
	include(plugin_dir_path( __FILE__ )."license_promptpay_puripas.php");
}

function promptpay_unique_filename( $dir, $name, $ext ) {
	return 'slip-'.current_time( 'Y-m-d-H-i-s' ).$ext;
}
				
add_action('wp_ajax_process_payment_slip_promptpay','process_payment_slip_promptpay');
add_action('wp_ajax_nopriv_process_payment_slip_promptpay','process_payment_slip_promptpay');	

function process_payment_slip_promptpay(){
	if($_FILES['photo']){			
		/* Start  Custom Folder Upload File */
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// Random slip filename. 
		$overrides = array(
			'test_form' => false,
			'unique_filename_callback' => 'promptpay_unique_filename'
		);
		
		$photo_image = wp_handle_upload( $_FILES['photo'], $overrides );

		// Append slip image to post content. 

		if( $photo_image && !isset( $photo_image['error'] ) ){
			$photo_image_url = $photo_image['url'];
		}else{
			$photo_image_url = "";
		}
		
		/* End  Custom Folder Upload File */			
		
		//update_post_meta( $order_id, 'bank_slip', $photo_image_url );	
		
		echo $photo_image_url;
		exit();
	}					

}
// Save Slip
/*
add_action('woocommerce_checkout_create_order', 'save_slip_to_order_promptpay', 10, 2 );
function save_slip_to_order_promptpay( $order, $data ) {

	$order_data = $order->get_data(); // The Order data	
	$order_payment_method = $order_data['payment_method'];
	echo $order_payment_method;			
		
	if( isset( $_POST['text_slip_promptpay'] ) ) {
		$order->update_meta_data( 'slip_promptpay', sanitize_text_field( $_POST['text_slip_promptpay'] ) );
	}
}
add_action( 'woocommerce_checkout_create_order', 'action_woocommerce_checkout_create_order', 10, 2 );
function action_woocommerce_checkout_create_order( $order, $data ) {    
    // Some value
    $my_custom_address = 'My custom address';

    // Update meta data
    $order->update_meta_data( '_billing_address_1', $my_custom_address );
    $order->update_meta_data( '_shipping_address_1', $my_custom_address );
}

*/
add_action( 'woocommerce_checkout_update_order_meta', 'action_woocommerce_checkout_update_order_meta', 10, 2 );
function action_woocommerce_checkout_update_order_meta( $order_id, $data ) {    
    // Get an instance of the WC_Order object
    $order = wc_get_order( $order_id );
    
    // Is a WC_Order
    if ( is_a( $order, 'WC_Order' ) ) {     
        // Some value
        // Update meta data

		if( isset( $_REQUEST['text_slip_promptpay'] ) ) {
			$order->update_meta_data( 'slip_promptpay', sanitize_text_field( $_REQUEST['text_slip_promptpay'] ) );
		}		
        // Save
        $order->save();
    }
}   

add_action( 'woocommerce_email_after_order_table', 'slip_promptpay_email_after_order_table', 10, 4 );
function slip_promptpay_email_after_order_table( $order, $sent_to_admin, $plain_text, $email ) { 

	global $wpdb;

	//echo '<pre>'; print_r($email->id); echo '</pre>';  //customer_on_hold_order
	 
	//$order = wc_get_order( $order_id );
	$order_data = $order->get_data(); // The Order data	
	$order_payment_method = $order_data['payment_method'];
	/*
	echo $order_payment_method;	
	echo $order->id;
	*/
	
	$check_slip_promptpay = $wpdb->get_var("SELECT COUNT(post_id) FROM ".$wpdb->postmeta."  WHERE  post_id  = ".$order->id."  AND  meta_key='slip_promptpay'" );						
	//echo $check_slip_promptpay;

	$order_id =  $order_data['id'];
	
	$order_payment_method_title = $order_data['payment_method_title'];
	
	$order_shipping_total = $order_data['shipping_total'];	
	$image = get_post_meta( $order_id, 'slip_promptpay', true );
	
	//echo '<img src="'.$image.'"/><br />';

	if ( $email->id  ==  'new_order' ) {
	
		$image = get_post_meta( $order_data['id'], 'slip_promptpay', true );

		$check_slip_promptpay = $wpdb->get_var("SELECT COUNT(post_id) FROM ".$wpdb->postmeta."  WHERE  post_id  = ".$order_id."  AND  meta_key='slip_promptpay'" );
		
		if($check_slip_promptpay > 0){
			$sql_slip_promptpay = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta."  WHERE  post_id  = ".$order_id."  AND  meta_key='slip_promptpay'" );
			foreach ( $sql_slip_promptpay as $loop_slip_promptpay ) {
				$slip_promptpay  =  $loop_slip_promptpay->meta_value; 
			}
			//$order->update_status('processing'); 
			
			//if( is_plugin_active( 'direct-deposit-for-woocommerce/direct-deposit-for-woocommerce.php' ) ) { // Plugin is active
			if($order_payment_method == "promptpay_qr_puripas"){
				echo '<div style="text-align:center;"><a href="' . $slip_promptpay . '" target="_blank">สลิปการโอนเงิน</a></div><br /><img src="'.$slip_promptpay.'"/><br />';
			}
			
		}
	}else{
		$image = get_post_meta( $order_data['id'], 'slip_promptpay', true );

		$check_slip_promptpay = $wpdb->get_var("SELECT COUNT(post_id) FROM ".$wpdb->postmeta."  WHERE  post_id  = ".$order_id."  AND  meta_key='slip_promptpay'" );
		
		if($check_slip_promptpay > 0){
			$sql_slip_promptpay = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta."  WHERE  post_id  = ".$order_id."  AND  meta_key='slip_promptpay'" );
			foreach ( $sql_slip_promptpay as $loop_slip_promptpay ) {
				$slip_promptpay  =  $loop_slip_promptpay->meta_value; 
			}
			//$order->update_status('processing'); 
			
			//if( is_plugin_active( 'direct-deposit-for-woocommerce/direct-deposit-for-woocommerce.php' ) ) { // Plugin is active
			if($order_payment_method == "promptpay_qr_puripas"){
				echo '<div style="text-align:center;"><a href="' . $slip_promptpay . '" target="_blank">สลิปการโอนเงิน</a></div><br /><img src="'.$slip_promptpay.'"/><br />';
			}
			
		}
	}
	
	//echo  '<img src="https://filtexwater.com/wp-content/uploads/2021/07/slip-2021-07-09-08-43-06.PNG"/>';
	/*
	echo '<pre>';
	print_r($order_data);
	echo '</pre>';
	*/
}


/*  Start Check Woocommerce */
//if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	add_filter( 'manage_edit-shop_order_columns', 'promptpay_add_order_column_to_admin_table' );
	 
	function promptpay_add_order_column_to_admin_table( $columns ) {
		$columns['payment_method'] = 'Payment Gateway';
		return $columns;
	}
	 
	add_action( 'manage_shop_order_posts_custom_column', 'promptpay_add_order_column_to_admin_table_content' );
	 
	function promptpay_add_order_column_to_admin_table_content( $column ) {
	   
		global $post;
	 
		if ( 'payment_method' === $column ) {
	 
			$order = wc_get_order( $post->ID );
	
				$payment_method_title = get_post_meta( $post->ID, '_payment_method_title', true );
			  	$my_var_one = get_post_meta( $post->ID, 'slip_promptpay', true );
				/*$my_var_two = get_post_meta( $post->ID, 'bank_slip', true );*/
				if($my_var_one != "") {
					echo $payment_method_title."<br />".'<br /><img src="'.$my_var_one.'" width="100%;"/>';
				}
		   
		}
	}		

	// Adding Custom metabox in admin orders edit pages (on the right column)
	add_action( 'add_meta_boxes', 'add_slip_promptpay_metabox' );
	function add_slip_promptpay_metabox(){
		add_meta_box(
			'attendees',
			__('Prompt Pay'),
			'reiseteilnehmer_inhalt_promptpay',
			'shop_order',
			'side', // or 'normal'
			'default' // or 'high'
		);
	}
	// Adding the content for the custom metabox
	function reiseteilnehmer_inhalt_promptpay() {
		$order = wc_get_order(get_the_id());
		$my_var_two = get_post_meta( $order->get_id(), 'slip_promptpay', true );
		if($my_var_two  != "") {
			echo 'Prompt Pay '.'<br />';
			echo '<br /><img src="'.$my_var_two.'" width="100%;"/>';
		}		
	}


//}
/* End Check woocommerce  */		

add_action('plugins_loaded', 'init_promptpay_qr_puripas_class');

function init_promptpay_qr_puripas_class() {

    //Check class WC_Payment_Gateway is exists
    if (class_exists("WC_Payment_Gateway")) {

        class WC_Gateway_Promptpay_Puripas extends WC_Payment_Gateway {

            /** @var string Access key for ... */
            var $access_key;

            /**
             * Constructor for the gateway.
             *
             * @access public
             * @return void
             */
            public function __construct() {
                global $woocommerce;

                $this->id = 'promptpay_qr_puripas';
                $this->method_title = __('Promptpay QR Puripas', 'promptpay_qr_puripas');
                $this->icon = apply_filters('woocommerce_th_installment_checkout_icon', plugin_dir_url(__FILE__) . 'images/prompt-pay-logo-146x50.jpg');
					 $this->promptpay_type_name = array(
									'01' => 'Mobile Phone No.',
									'02' => 'ID No./Tax No.',
									'03' => 'E-Wallet No.'
									);

                // Load the form fields.
                $this->init_form_fields();

                // Load the settings.
                $this->init_settings();

                // Define user set variables
                // Check compatibility
                if (version_compare($woocommerce->version ,'1.6','<=')) {
                    $this->title = $this->settings['title'];
                    $this->description = $this->settings['description'];
                    $this->thank_msg = $this->settings['thank_msg'];
                    $this->promptpay_id = $this->settings['promptpay_id']; //Promptpay ID
                    $this->promptpay_type = $this->settings['promptpay_type']; //Promptpay Type
                    $this->promptpay_name = $this->settings['promptpay_name']; //Promptpay Name
                    $this->include_price = $this->settings['include_price']; //Include Price
					$this->line_token_promptpay = $this->settings['line_token_promptpay']; //line_token_promptpay

                    // Save options
                    add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
                } else {
                    $this->title = $this->get_option('title');
                    $this->description = $this->get_option('description');
                    $this->thank_msg = $this->get_option('thank_msg');
                    $this->promptpay_type = $this->get_option('promptpay_type'); //Promptpay Type 
                    $this->promptpay_id = $this->get_option('promptpay_id'); //Promptpay ID
                    $this->promptpay_name = $this->get_option('promptpay_name'); //Promptpay Name 
                    $this->include_price = $this->get_option('include_price'); //Include Price
					$this->line_token_promptpay = $this->get_option('line_token_promptpay'); //line_token_promptpay

                    // Save options
                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                }
				
									

                // Actions
                add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
                //add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
				add_action( 'woocommerce_thankyou', array( $this, 'new_order_promptpay_line_notification' ) );
				
				
			

				// Customer Emails
				add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

            }

			
			public function new_order_promptpay_line_notification( $order_id ){
				//echo $order_id;
				
				$order = wc_get_order( $order_id );

				$order_data = $order->get_data(); 
				
				$product_data = "";
				$i=0;
				foreach ($order->get_items() as $item_key => $item ){
					++$i;
					$item_data    = $item->get_data();
					$product_id   = $item_data['product_id'];
					$product_name = $item_data['name'];
					$quantity     = $item_data['quantity'];
					$line_subtotal     = $item_data['subtotal'];
					$line_total        = $item_data['total'];
					$product_data .= $i.". ".$product_name." จำนวน ".$quantity." เป็นเงิน ".$line_subtotal." บาท"." "."\n";
				}
				
				$order_billing_first_name = $order_data['billing']['first_name'];
				$order_billing_last_name = $order_data['billing']['last_name'];
				$order_billing_phone = $order_data['billing']['phone'];
				
				$order_billing_company = $order_data['billing']['company'];
				$order_billing_address_1 = $order_data['billing']['address_1'];
				$order_billing_address_2 = $order_data['billing']['address_2'];
				$order_billing_city = $order_data['billing']['city'];
				$order_billing_state = $order_data['billing']['state'];
				
				$get_billing_state   = get_post_meta( $order_id, '_billing_state', true );
				
				
				$order_billing_postcode = $order_data['billing']['postcode'];
			
				
				$get_payment_method_title   = get_post_meta( $order_id, '_payment_method_title', true );
				$order_total = $order_data['total'];
				$order_billing_email = $order_data['billing']['email'];				
		
				$get_post = get_post($order_id);
				$post_status = $get_post->post_status;
				
				if($post_status == "wc-failed"){
					$post_status = "การสั่งซื้อล้มเหลว";
				}else if($post_status == "wc-pending"){
					$post_status = "รอการชำระเงิน";
				}else if($post_status == "wc-processing"){
					$post_status = "ชำระเงินด้วยบัตรเครดิตสำเร็จ";
				}	
		
				$order = new WC_Order( $order_id );
				$items = $order->get_items();
		
				$user = $order->get_user();				

				$bank_slip = get_post_meta( $order_id, 'slip_promptpay', true );
				  
				$line_api = 'https://notify-api.line.me/api/notify';
    			$access_token = $this->line_token_promptpay;  	
				
$message = "รายการสั่งซื้อใหม่ ของเว็บไซต์ ".get_bloginfo('url')."\n"."รหัสสั่งซื้อเลขที่: " .$order_id. "\n"."".$order_billing_first_name ."  ". $order_billing_last_name ."\n"."".$order_billing_phone."\n"."".$order_billing_company."\n"."".$order_billing_address_1."\n"."ตำบล/แขวง ".$order_billing_address_2."\n"."อำเภอ/เขต ".$order_billing_city."\n"."จังหวัด ".$get_billing_state."\n".$order_billing_postcode."\n"."------------"."\n".$product_data."\n"."รวม ".$order_total." บาท";  				

				$image_thumbnail_url = $bank_slip;  // max size 240x240px JPEG
				$image_fullsize_url = $bank_slip; //max size 1024x1024px JPEG
				$imageFile = 'copy/240.jpg';
				$sticker_package_id = '2';  // Package ID sticker
				$sticker_id = '34';    // ID sticker
				
				$data = array (
					'message' => $message,
					'imageThumbnail' => $image_thumbnail_url,
  					'imageFullsize' => $image_fullsize_url,
					'imageFile' => $imageFile,
					'stickerPackageId' => $sticker_package_id,
					'stickerId' => $sticker_id
				);
				
				$order_data = $order->get_data(); // The Order data	
				$order_payment_method = $order_data['payment_method'];
				//echo $order_payment_method;				
				//if( is_plugin_active( 'direct-deposit-for-woocommerce/direct-deposit-for-woocommerce.php' ) ) { // Plugin is active
				if($order_payment_method == "promptpay_qr_puripas"){
					$this->send_notify_message_promptpay($line_api, $access_token, $data);
				}				
				
				

			}
			public  function send_notify_message_promptpay($line_api, $token, $data){
			
				  $chOne = curl_init();
				  curl_setopt( $chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
				  curl_setopt( $chOne, CURLOPT_SSL_VERIFYHOST, 0);
				  curl_setopt( $chOne, CURLOPT_SSL_VERIFYPEER, 0);
				  curl_setopt( $chOne, CURLOPT_POST, 1);
				  curl_setopt( $chOne, CURLOPT_POSTFIELDS, $data);
				  curl_setopt( $chOne, CURLOPT_FOLLOWLOCATION, 1);
				  $headers = array( 'Method: POST', 'Content-type: multipart/form-data', 'Authorization: Bearer '.$token, );
				  curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
				  curl_setopt( $chOne, CURLOPT_RETURNTRANSFER, 1);
				  $result = curl_exec( $chOne );
				  //Check error
				  if(curl_error($chOne)) { echo 'error:' . curl_error($chOne); }
				  else { $result_ = json_decode($result, true);
				  echo "status : ".$result_['status']; echo "message : ". $result_['message']; 
				  }
				  //Close connection
				  curl_close( $chOne );
				  
			}			
			
            /**
             * Initialise Gateway Settings Form Fields
             *
             * @access public
             * @return void
             */
            public function init_form_fields() {
			
				global $wpdb;
				
				$date = date("Y-m-d");
				$time = date("H:i:s");
				$full_date = $date . " " .  $time;
				
				
				if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				
					//check ip from share internet
					
					$ip = $_SERVER['HTTP_CLIENT_IP'];
				
				}elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				
					//to check ip is pass from proxy
					
					$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				
				}else {
				
					$ip = $_SERVER['REMOTE_ADDR'];
				
				}					
				
				
				$license_key = get_option('promptpay_puripas_license_key');
				$apiUrl = "https://puripas.com/wp-json/ordercar/v2?license_key=".$license_key."&domain_name=".get_bloginfo('url')."&ip=".$ip;
				$response = wp_remote_get($apiUrl);
				$responseBody = wp_remote_retrieve_body( $response );
				$result = json_decode( $responseBody );
				
				//echo "------".$result."-------------";
				if ( $result == "yes" ) {			
					$this->form_fields = array(
						'enabled' => array(
							'title' => __('Enable/Disable', 'promptpay_qr_puripas'),
							'type' => 'checkbox',
							'label' => __('Enable Promptpay Checkout', 'promptpay_qr_puripas'),
							'default' => 'no'
						),
						'title' => array(
							'title' => __('Title in checkout form', 'promptpay_qr_puripas'),
							'type' => 'text',
							'css' => 'width:300px',
							'description' => __('This controls the title which the user sees during checkout.', 'promptpay_qr_puripas'),
							'default' => __('Promptpay', 'promptpay_qr_puripas'),
							//'desc_tip' => true,
						),
						'description' => array(
							'title' => __('Description in checkout form', 'promptpay_qr_puripas'),
							'type' => 'textarea',
							'default' => __('Pay with Promptpay', 'promptpay_qr_puripas'),
							'description' => __('This controls the description which the user sees during checkout.', 'promptpay_qr_puripas'),
								),
						'thank_msg' => array(
							'title' => __('Thank you message in receipt page/email', 'promptpay_qr_puripas'),
							'type' => 'textarea',
							'default' => __('Thank you for your order, please open your mobile banking application to scan the QR Code or save this QR Code to use in your mobile banking application.', 'promptpay_qr_puripas'),
							'description' => __('This controls the message which the user sees receipt page/email.', 'promptpay_qr_puripas'),
								),
						'promptpay_type' => array(
							'title' => __('Promptpay Type', 'promptpay_qr_puripas'),
							'type' => 'select',
							'css' => 'width:300px;padding:0',
							'description' => __('Type of Promptpay that is used to receive payment', 'promptpay_qr_puripas'),
									'options' => array(
										'01' => 'Mobile Phone No.',
										'02' => 'ID No./Tax No.',
										'03' => 'E-Wallet No.'
									),
							'default' => '01',
							//'desc_tip' => true,
						),
						'promptpay_id' => array(
							'title' => __('Promptpay ID', 'promptpay_qr_puripas'),
							'type' => 'text',
							'css' => 'width:300px',
							'description' => __('Promptpay ID', 'promptpay_qr_puripas'),
							//'default' => __('Installment', 'promptpay_qr_puripas'),
							//'desc_tip' => true,
						),
						'promptpay_name' => array(
							'title' => __('Promptpay Name', 'promptpay_qr_puripas'),
							'type' => 'text',
							'css' => 'width:300px',
							'description' => __('Promptpay Name', 'promptpay_qr_puripas'),
						),
						'include_price' => array(
							'title' => __('Include Price in QR Code', 'promptpay_qr_puripas'),
							'type' => 'checkbox',
							'label' => __('Enable including price in QR Code', 'promptpay_qr_puripas'),
							'default' => 'no'
						),
						'line_token_promptpay' => array(
							'title' => __('Line Token Promptpay', 'promptpay_qr_puripas'),
							'type' => 'text',
							'css' => 'width:300px',
							'description' => __('Line Token Promptpay', 'promptpay_qr_puripas'),
						),					
					);
					
					
				}else{

				?>
						<h3 style="color:#FF0000"><center>License Key  ไม่มีในระบบ หรือ License Key หมดอายุ</center></h3>
				<?php
				
				}
				
				
				
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
                <h3><?php _e('Promptpay Info.', 'promptpay_qr_puripas'); ?></h3>

                <table class="form-table">
                <?php $this->generate_settings_html($this->form_fields); ?>
					 </table>
				<?php
				
			}

			function validate_fields(){
				global $woocommerce;

				$valid = true;

				return $valid;
			}
			

				 /**
				  * Process the payment and return the result
				  *
				  * @access public
				  * @param int $order_id
				  * @return array
				  */
				 public function process_payment($order_id) {
					  global $woocommerce;

					if(version_compare($woocommerce->version, '2.6','>=') && version_compare($woocommerce->version , '3.0','<'))
						  $order = wc_get_order($order_id);
					  else $order = new WC_Order($order_id);

						// Mark as on-hold 
						$order->update_status('on-hold', __( 'Awaiting promptpay payment', 'promptpay_qr_puripas' ));

						// Reduce stock levels
						$order->reduce_order_stock();

						// Remove cart
						$woocommerce->cart->empty_cart();

					  return array(
							'result' => 'success',
							'redirect' => $this->get_return_url($order)
					  );
				 }

				 /**
				  * Output for the order received page.
				  *
				  * @access public
				  * @return void
				  */

				 public function payment_fields(){

					global $woocommerce;  

						$promptpay_id = $this->promptpay_id;
						$promptpay_type = $this->promptpay_type;
						$include_price = $this->include_price;
						$image_url = plugin_dir_url(__FILE__) . "lib/promptpay-qr-l.php?type=$promptpay_type&promptpay_id=$promptpay_id";
						if($include_price=='yes'){
						  $price = $woocommerce->cart->total;
						  $image_url .= "&price=".$price."&p=1";
						}
						$thank_msg = $this->thank_msg;
						echo '<p>' . __($thank_msg, 'promptpay_qr_puripas') . '</p>';
						echo "<img src='".$image_url."'>";
						echo "<br><center>Promptpay ID: ".$promptpay_id."</center>";
						?>
						<div>
							<p class="qrcode_style">
								<hr />
								แนบสลิป:<input type="file" name="photo-promptpay" id="photo-promptpay"/>
								<br /> 
								<img src="" class="image_slip-promptpay" style="display:none;margin-top:10px;" width="60%;"/>  
							</p>  
							<span class="bank_slip">
							<input type="text" id="text_slip_promptpay" name="text_slip_promptpay" readonly="readonly" style="display:none;"/> <!-- <br>  -->
							</span>                            
							<script>
							jQuery(document).ready(function(){
							
								jQuery("#photo-promptpay").on('change',function(){
									//alert(this.files[0].name);
									// Serialize the entire form:
									var form_data = new FormData();	
									
									form_data.append('action', 'process_payment_slip_promptpay');		
									
									var photo = jQuery('#photo-promptpay').prop('files')[0];	
	
									form_data.append('photo', photo);	
									
									jQuery.ajax({
										url: '<?php echo admin_url('admin-ajax.php'); ?>',
										type: 'post',
										contentType: false,
										processData: false,
										data: form_data,
										success: function(response){
											jQuery('#text_slip_promptpay').val(response);
											jQuery('.image_slip-promptpay').attr('src', response);
											jQuery('.image_slip-promptpay').show();
											/*alert("อัพโหลดสลิปเสร็จเรียบร้อย");*/	
										}
									});	
									return false;																				
								});	
							});												
							</script>                      
						</div>
				<?php

				 }
				 public function receipt_page($order_id) {
					  global $woocommerce;

					if(version_compare($woocommerce->version, '2.6','>=') && version_compare($woocommerce->version , '3.0','<'))
						$order = wc_get_order($order_id);
					else $order = new WC_Order($order_id);


					  //check promptpay_id
					  $promptpay_id = $this->promptpay_id;
					  $promptpay_type = $this->promptpay_type;
					  $include_price = $this->include_price;
					  $image_url = plugin_dir_url(__FILE__) . "lib/promptpay-qr-l.php?type=$promptpay_type&promptpay_id=$promptpay_id";
					  if($include_price=='yes'){
						  $price = $order->get_total();
						  $image_url .= "&price=".$price."&p=1";
					  }
					  $thank_msg = $this->thank_msg;
					  echo '<p>' . __($thank_msg, 'promptpay_qr_puripas') . '</p>';
					  echo "<img src='".$image_url."'>";
					  echo "<br>Promptpay ID: ".$promptpay_id."";
					  
				 }

				 /**
				  * Output for the thank you page.
				  *
				  * @access public
				  * @return void
				  */
				  
				 /*
				 public function thankyou_page($order_id) {
					  global $woocommerce;

					  //echo wpautop( wptexturize( wp_kses_post( 'test edit received page' ) ) );
					if(version_compare($woocommerce->version, '2.6','>=') && version_compare($woocommerce->version , '3.0','<'))
						$order = wc_get_order($order_id);
					else $order = new WC_Order($order_id);
					  $promptpay_id = $this->promptpay_id;
					  $promptpay_type = $this->promptpay_type;
					  $promptpay_name = $this->promptpay_name;
					  $include_price = $this->include_price;
					  $image_url = plugin_dir_url(__FILE__) . "lib/promptpay-qr-l.php?type=$promptpay_type&promptpay_id=$promptpay_id";
					  if($include_price=='yes'){
						  $price = $order->get_total();
						  $image_url .= "&price=".$price."&p=1";
					  }
					  $thank_msg = $this->thank_msg;

					  echo '<p>' . __($thank_msg, 'promptpay_qr_puripas') . '</p>';

					  echo '<center>';
					  echo "<img src='".plugin_dir_url(__FILE__) . "images/prompt-pay-logo-800x275.jpg' width='300'><br>";
					  echo "<img src='".$image_url."'>";
					  echo "<br><b>Promptpay ID:</b> ".$promptpay_id."<br><b>Promptpay Name:</b> ".$promptpay_name."<br><b>Promptpay Type:</b> ".$this->promptpay_type_name[$promptpay_type];
					  echo '</center>';
					  
				 }
				 */

					/**
					* Add content to the WC emails.
					*
					* @param WC_Order $order
					* @param bool $sent_to_admin
					* @param bool $plain_text
					*/
					public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
					
						if( isset( $_POST['text_slip_promptpay'] ) ) {
							$order->update_meta_data( 'slip_promptpay', sanitize_text_field( $_POST['text_slip_promptpay'] ) );
						}					
					
						$order_data = $order->get_data(); // The Order data	
						$order_payment_method = $order_data['payment_method'];
						//echo $order_payment_method;	
						$image = get_post_meta( $order->id, 'slip_promptpay', true );
						
						//echo '<img src="'.$image.'"/><br />';						
											
						global $woocommerce;
						if(version_compare($woocommerce->version, '2.6','>=') && version_compare($woocommerce->version , '3.0','<'))
							$payment_method = get_post_meta( $order->id, '_payment_method', true );
						else
							$payment_method = $order->get_payment_method();
						if ( ! $sent_to_admin && $this->id === $payment_method && $order->has_status( 'on-hold' ) ) {
							//if(version_compare($woocommerce->version, '2.6','>=') && version_compare($woocommerce->version , '3.0','<'))
								//$order = wc_get_order($order_id);
							//else $order = new WC_Order($order_id);
							$promptpay_id = $this->promptpay_id;
							$promptpay_type = $this->promptpay_type;
							  $promptpay_name = $this->promptpay_name;
							$include_price = $this->include_price;
							$image_url = plugin_dir_url(__FILE__) . "lib/promptpay-qr-l.php?type=$promptpay_type&promptpay_id=$promptpay_id";
							if($include_price=='yes'){
							  $price = $order->get_total();
							  $image_url .= "&price=".$price."&p=1";
							}

							$thank_msg = $this->thank_msg;
							echo '<p>' . __($thank_msg, 'promptpay_qr_puripas') . '</p>';

							echo '<center>';
							echo "<img src='".plugin_dir_url(__FILE__) . "images/prompt-pay-logo-800x275.jpg' width='300'><br>";
							echo "<img src='".$image_url."'>";
							echo "<br><b>Promptpay ID:</b> ".$promptpay_id."<br><b>Promptpay Name:</b> ".$promptpay_name."<br><b>Promptpay Type:</b> ".$this->promptpay_type_name[$promptpay_type];
							echo '</center>';
						}
						/*  Start  Custom Email  */

						/* End Custom Email  */
					}
					
					

			}

	 }
	 
	 
	global $wpdb;
	
	$date = date("Y-m-d");
	$time = date("H:i:s");
	$full_date = $date . " " .  $time;
	
	
	if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
	
		//check ip from share internet
		
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	
	}elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	
		//to check ip is pass from proxy
		
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	
	}else {
	
		$ip = $_SERVER['REMOTE_ADDR'];
	
	}					
	
	
	$license_key = get_option('promptpay_puripas_license_key');
	$apiUrl = "https://puripas.com/wp-json/ordercar/v2?license_key=".$license_key."&domain_name=".get_bloginfo('url')."&ip=".$ip;
	$response = wp_remote_get($apiUrl);
	$responseBody = wp_remote_retrieve_body( $response );
	$result = json_decode( $responseBody );
	
	//echo "------".$result."-------------";
	if ( $result == "yes" ) {  
		 /**
		  * Add promoptpay payment gateway into woocommerce payment gateway
		  */
		 add_filter('woocommerce_payment_gateways', 'add_promptpay_easy_gateway');
	
		 function add_promptpay_easy_gateway($methods) {
			  $methods[] = 'WC_Gateway_Promptpay_Puripas';
			  return $methods;
		 } 

	}else{
	?>
		<h3 style="color:#FF0000"><center>License Key Promptpay QR Code  ไม่มีในระบบ <br />หรือ License Key Promptpay QR Code หมดอายุ</center></h3>
	<?php
	
	}	 
	 
}