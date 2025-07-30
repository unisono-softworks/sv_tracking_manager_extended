<?php
	namespace sv_tracking_manager_extended;
	
	class google_customer_reviews extends modules {
		public function init() {
			$this->set_section_title( __( 'Google Customer Reviews', 'sv_tracking_manager_extended' ) )
				->set_section_desc( __( sprintf('%sGoogle Merchant Center Login%s', '<a target="_blank" href="https://www.google.com/retail/solutions/merchant-center/">','</a>'), 'sv_tracking_manager_extended' ) )
				->load_settings()
				->get_root()->add_section( $this );

			add_action('wp', array($this, 'register_scripts'));
		}

		public function register_scripts(): google_customer_reviews{
			if(!$this->is_instance_active('sv_tracking_manager')){
				return $this;
			}

			if (!$this->is_active()) {
				return $this;
			}

			if(!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
				return $this;
			}

			global $wp;
			$order									= new \WC_Order($wp->query_vars['order-received']);

			$products								= array();
			foreach ($order->get_items() as $item) {
				$product							= $item->get_product();

				$gtin								= get_post_meta( $product->get_id(), '_cr_gtin', true);
				if(strlen($gtin) > 0){
					$products[]						= $gtin;
					continue;
				}

				if(strlen($product->get_sku()) > 0){
					$products[]						= $product->get_sku();
					continue;
				}

				$products[]							= $product->get_id();
			}

			$date_offset							= $this->get_setting('estimated_delivery_date_offset')->get_data();
			$date									= date_create($order->get_date_created());
			$date									= date_add($date, date_interval_create_from_date_string($date_offset.' day'));
			$date									= date_format($date, 'Y-m-d');

			$wpml_lang								= apply_filters( 'wpml_current_language', null );
			$lang									= (strlen($wpml_lang) > 0) ? $wpml_lang : get_locale();

			$this->get_script('default')
				->set_path('https://apis.google.com/js/platform.js?onload=renderOptIn')
				->set_type('js')
				->set_deps(array($this->get_script('options')->get_handle()))
				->set_is_enqueued();

			$this->get_script('options')
				->set_path('lib/frontend/js/options.js')
				->set_type('js')
				->set_localized(array(
					'merchant_id'					=> $this->get_setting('merchant_id')->get_data(),
					'order_id'						=> $order->get_id(),
					'email'							=> $order->get_billing_email(),
					'delivery_country'				=> $order->get_shipping_country(),
					'estimated_delivery_date'		=> $date,
					'products'						=> $products,
					'opt_in_style'					=> $this->get_setting('opt_in_style')->get_data(),
					'lang'							=> $lang
				))
				->set_is_enqueued();


			$this->add_service();

			return $this;
		}
		public function is_active(): bool{
			// activate not set
			if(!$this->get_setting('activate')->get_data()){
				return false;
			}
			// activate not true
			if($this->get_setting('activate')->get_data() !== '1'){
				return false;
			}
			// Merchant ID not set
			if(!$this->get_setting('merchant_id')->get_data()){
				return false;
			}
			// Merchant ID empty
			if(strlen(trim($this->get_setting('merchant_id')->get_data())) === 0){
				return false;
			}

			return true;
		}
		public function load_settings(): google_customer_reviews{
			$this->get_setting('activate')
				->set_title( __( 'Activate Meetings Script', 'sv_tracking_manager_extended' ) )
				->load_type( 'checkbox' );

			$this->get_setting('merchant_id')
				->set_title( __( 'Merchant ID', 'sv_tracking_manager' ) )
				->load_type( 'number' );

			$this->get_setting('opt_in_style')
				->set_title( __( 'Opt in Style', 'sv_tracking_manager_extended' ) )
				->load_type( 'select' )
				->set_default_value('CENTER_DIALOG')
				->set_options(array(
					'CENTER_DIALOG'				=> __('Center Dialog', 'sv_tracking_manager_extended'),
					'BOTTOM_RIGHT_DIALOG'		=> __('Bottom Right Dialog', 'sv_tracking_manager_extended'),
					'BOTTOM_LEFT_DIALOG'		=> __('Bottom Left Dialog', 'sv_tracking_manager_extended'),
					'TOP_RIGHT_DIALOG'			=> __('Top Right Dialog', 'sv_tracking_manager_extended'),
					'TOP_LEFT_DIALOG'			=> __('Top Left Dialog', 'sv_tracking_manager_extended'),
					'BOTTOM_TRAY'				=> __('Bottom Tray', 'sv_tracking_manager_extended')
				));

			$this->get_setting('estimated_delivery_date_offset')
				->set_title( __( 'Estimated Delivery Date Offset', 'sv_tracking_manager' ) )
				->set_description( __( 'Set estimated Offset in Days from order date', 'sv_tracking_manager' ) )
				->set_default_value(5)
				->load_type( 'number' );
				
			return $this;
		}
	}