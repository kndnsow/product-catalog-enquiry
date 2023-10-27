<?php
class Woocommerce_Catalog_Enquiry_Ajax {
	public $error_mail_report;
	public function __construct() {
		add_action( 'wp_ajax_send_enquiry_mail', array(&$this, 'send_product_enqury_mail') );
		add_action( 'wp_ajax_nopriv_send_enquiry_mail', array( &$this, 'send_product_enqury_mail' ) );
		add_action( 'wp_ajax_add_variation_for_enquiry_mail', array( $this, 'add_variation_for_enquiry_mail'));
		add_action( 'wp_ajax_nopriv_add_variation_for_enquiry_mail', array( $this, 'add_variation_for_enquiry_mail'));
		add_action( 'wp_mail_failed', array( $this, 'catalog_enquiry_error_mail_report'));

	}
	
	public function add_variation_for_enquiry_mail() {
        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        if ($product_id) {
			if ( get_transient('variation_list') ) delete_transient('variation_list');
			$variation_data = isset($_POST['variation_data']) ? $_POST['variation_data'] : '';
			set_transient('variation_list', $variation_data, 30 * MINUTE_IN_SECONDS);
        }	
        die;
	}

	public function catalog_enquiry_error_mail_report($wp_error) {
        if ( true === WP_DEBUG ) {
            error_log(print_r($wp_error, true));
        }
        if (is_object( $wp_error ) ) {
            if (isset($wp_error->errors['wp_mail_failed']) || isset($wp_error->error_data['wp_mail_failed'])) {
                if (isset($wp_error->error_data['wp_mail_failed']['phpmailer_exception_code'])) {
                    $this->error_mail_report = 'Mailer Error: '.$wp_error->error_data['wp_mail_failed']['phpmailer_exception_code'];
                }
                if (isset($wp_error->errors['wp_mail_failed'][0])) {
                    $this->error_mail_report .= ', '.$wp_error->errors['wp_mail_failed'][0];
                }
            }
        }
    }

	public function send_product_enqury_mail() {
		global $Woocommerce_Catalog_Enquiry;
		
		// check catalog nonce 
    	if ( !isset( $_POST['wc_catalog_enq'] ) || !wp_verify_nonce( $_POST['wc_catalog_enq'], 'wc_catalog_enquiry_mail_form' ) ) {
    		die();
    	}
    	$status = $file_name = $target_file = '';
		$attachments = $enquiry_form_fileds = [];
		$settings = $Woocommerce_Catalog_Enquiry->options_general_settings;
		$settings_form = $Woocommerce_Catalog_Enquiry->options_form_settings;
		
        if (isset($settings_form['enquiry_form_fileds']) && !empty($settings_form['enquiry_form_fileds'])) {
            foreach ($settings_form['enquiry_form_fileds'] as $key_e => $value_e) {
                $enquiry_form_fileds[$value_e[0]] = $value_e[1];
            }
        }

		if (isset($_FILES['fileupload'])) {

			foreach ($_FILES['fileupload'] as $key => $value) {
		        $_FILES['fileupload'][$key] = $value[0]; 
		    }
		    $woocommerce_customer_filesize = 2097152;
		    if (!empty($enquiry_form_fileds) && array_key_exists('filesize-limit_checkbox', $enquiry_form_fileds) && array_key_exists('filesize-limit', $enquiry_form_fileds)) {
		    	$woocommerce_customer_filesize = intval($enquiry_form_fileds['filesize-limit']) * 1024 * 1024;
		    }
		    
		    if (in_array($_FILES['fileupload']['type'], wp_get_mime_types())) {
		    	$file_name = mt_rand().'.'.explode(".",basename($_FILES['fileupload']['name']))[1];
		    	// Check file size
				if ($_FILES['fileupload']['size'] <= $woocommerce_customer_filesize) {
					$target_file = sys_get_temp_dir().'/'.$file_name;
				    if (move_uploaded_file($_FILES['fileupload']['tmp_name'], $target_file)) {
				    	$attachments[] = $target_file;
				    }
				} else {
					$status = 3;
		    		die;
				}
		    } else {
		    	$status = 2;
		    	die;
		    }
		}

		$name = sanitize_text_field($_POST['woocommerce_customer_name']);
		$email = sanitize_email($_POST['woocommerce_customer_email']);
		$product_id = (int) $_POST['woocommerce_customer_product_id'];
		$subject = sanitize_text_field($_POST['woocommerce_customer_subject']);
		$phone = sanitize_text_field($_POST['woocommerce_customer_phone']);
		$comment = sanitize_text_field($_POST['woocommerce_customer_comment']);
		$address = sanitize_text_field($_POST['woocommerce_customer_address']);
		$product_name = sanitize_text_field($_POST['woocommerce_customer_product_name']);
		$product_url = esc_url($_POST['woocommerce_customer_product_url']);
		$enquiry_product_type = sanitize_text_field($_POST['enquiry_product_type']);
        $product_variations = (get_transient('variation_list')) ? get_transient('variation_list') : array();
                
		if (isset($settings['is_other_admin_mail']) && mvx_catalog_get_settings_value($settings['is_other_admin_mail'], 'checkbox') == 'Enable') {
			$email_admin = '';
		} else {
			$email_admin = get_option( 'admin_email' );
		}
		
		if (isset($settings['other_emails'])) {
			$email_admin .= ','.$settings['other_emails'];	
		}

		$product = wc_get_product($product_id);
		
		if ($product) {
			$enquiry_data = apply_filters( 'woocommerce_catalog_enquiry_form_data', array(
				'cust_name' => $name,
				'cust_email' => $email,
				'product_id' => $product_id,
                'variations' => $product_variations,
				'subject' => $subject,
				'phone' => $phone,
				'comment' => $comment,
				'address' => $address,
				'attachments' => $attachments,
				'enquiry_product_type' => $product->get_type(),
				));

			$send_email = WC()->mailer()->emails['Woocommerce_Catalog_Enquiry_Email'];

			if ($send_email->trigger( $email_admin, $enquiry_data )) {
				
				if (get_transient('variation_list')) {
					delete_transient('variation_list');
				}
				// delete uploaded file from server temp if have
				if ($target_file)
					unlink($target_file); 
				$status = 1;

				do_action('woocommerce_catalog_enquiry_after_enquiry_email_sent', $enquiry_data, $_POST);
			}
			else {
				// delete uploaded file from server temp if have
				if ($target_file)
					unlink($target_file); 
				$status = 0;
			}
		} else {
			// delete uploaded file from server temp if have
			if ($target_file)
				unlink($target_file); 
		}
		wp_send_json(array('status' => $status, 'error_report' => $this->error_mail_report));
		die();
	}

}