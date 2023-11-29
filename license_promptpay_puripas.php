<?php
if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

	$license_key = $_REQUEST['license_key'];
	
	/*
	
	if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
	
		//check ip from share internet
		
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	
	}elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	
		//to check ip is pass from proxy
		
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	
	}else {
	
		$ip = $_SERVER['REMOTE_ADDR'];
	
	}
	*/
	
	$ip = gethostbyname(get_bloginfo('url'));
	
	echo $ip;
	
	$hosts = gethostbynamel(get_bloginfo('url'));
	
	print_r($hosts);
		
	if (isset($_REQUEST['activate_license'])) {	
		//define( 'DASHBOARD_API_URL', "https://puripas.com/wp-json/license_numberden/v2?license_key=".$license_key."&domain_name=".get_bloginfo('url')."&ip=".$ip."");
		$apiUrl = "https://puripas.com/wp-json/license_numberden/v2?license_key=".$license_key."&domain_name=".get_bloginfo('url')."&ip=".$ip."&check_activate=activate";
		$response = wp_remote_get($apiUrl);
		$responseBody = wp_remote_retrieve_body( $response );
		$result = json_decode( $responseBody );
		
		echo "------".$result."-------------";
		if ( $result == "yes" ) {
			// Work with the $result data
		?>
			<div style="color:#FF0000;"> <?php echo _e('License Key installed successfully.', 'tracking_post_puripas'); ?></div>
        <?php
			update_option('promptpay_puripas_license_key', $license_key);
		} else {
			// Work with the error
		?>
			<div style="color:#FF0000;"><?php echo _e('License Key is not in the system.', 'tracking_post_puripas'); ?></div>
        <?php
		}
			
	}
	if (isset($_REQUEST['deactivate_license'])) {
	
		$apiUrl = "https://puripas.com/wp-json/license_numberden/v2?license_key=".$license_key."&domain_name=".get_bloginfo('url')."&ip=".$ip."&check_activate=deactivate";
		$response = wp_remote_get($apiUrl);
		$responseBody = wp_remote_retrieve_body( $response );
		$result = json_decode( $responseBody );
		
		echo "------".$result."-------------";
		if ( $result == "yes" ) {
			// Work with the $result data
		?>
			<div style="color:#FF0000;"> <?php echo _e('License Key canceled successfully', 'tracking_post_puripas'); ?></div>
        <?php
			update_option('promptpay_puripas_license_key', "");
		} else {
			// Work with the error
		?>
			<div style="color:#FF0000;"> <?php echo _e('License Key is not in the system', 'tracking_post_puripas'); ?> </div>
        <?php
		}	
	
	}
}

?>
<form id="form1" name="form1"  action="" method="post"     enctype="multipart/form-data">
    <h4 class="text-center">License Key</h4>
	<?php
    if( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
    
        //check ip from share internet
        
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    
    }elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
    
        //to check ip is pass from proxy
        
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    
    }else {
    
        $ip = $_SERVER['REMOTE_ADDR'];
    
    }																
    
    ?>    
    <div class="mb-3">
        <label for="exampleInputEmail1" class="form-label">License Key</label>
        <input type="text" style="width:270px;" name="license_key" id="license_key" value="<?php echo get_option('promptpay_puripas_license_key'); ?>" class="form-control">  
        <input type="hidden" id="domain_name" name="domain_name" value="<?php echo get_bloginfo('url'); ?>" />
        <input type="hidden" id="ip" name="ip" value="<?php echo $ip; ?>" />
        <br /><br />
        <!--<button type="button">บันทึกข้อมูล</button>-->
        <div>
        <input type="submit" name="activate_license" value="Activate" class="btn btn-primary" />
        <input type="submit" name="deactivate_license" value="Deactivate" class="btn btn-danger" />     
        </div>  
    </div>

</form>  
              		
