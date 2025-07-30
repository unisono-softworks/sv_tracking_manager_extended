<?php
	namespace sv_tracking_manager_extended;
	
	class finance_ads extends modules {
		public function init() {
			// Section Info
			$this->set_section_title( __('Finance Ads', 'sv_tracking_manager_extended' ) )
				 ->set_section_desc(__( sprintf('%sFinance Ads Login%s', '<a target="_blank" href="https://dashboard.financeads.net/">','</a>'), 'sv_tracking_manager_extended' ))
				 ->set_section_type( 'settings' )
				 ->set_section_template_path()
				 ->load_settings()
				 ->get_root()->add_section( $this );

			// sv_tracking_manager_extended_finance_ads
			add_shortcode( $this->get_prefix(), array( $this, 'shortcode' ) );

			add_action('init', array($this, 'load'));
		}
		
		public function load_settings(): finance_ads {
			$this->get_setting('activate')
				 ->set_title( __( 'Activate', 'sv_tracking_manager_extended' ) )
				 ->set_description('Enable Tracking')
				 ->load_type( 'checkbox' );

			return $this;
		}
		protected function register_scripts(): finance_ads {
			if($this->is_active()) {
				$this->get_script('default')
					->set_path('lib/frontend/js/default.js')
					->set_type('js');

				$this->get_script('conversion_tracking')
					->set_path('https://fat.financeads.net/fpc.js')
					->set_type('js')
					->set_deps(array($this->get_script( 'default' )->get_handle()));;
			}
			
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
			
			return true;
		}
		public function load(): finance_ads{
			if($this->is_active()){
				$this->register_scripts()->add_service();

				$this->get_script('default')->set_is_enqueued();
				$this->get_script('conversion_tracking')->set_is_enqueued();
			}
			
			return $this;
		}
		public function shortcode($atts, $content = null){
			$atts = shortcode_atts(
				array(
					'order_id'          => apply_filters($this->get_prefix('order_id'), md5(time())), // sv_tracking_manager_extended_finance_ads_order_id
					'program_id'		=> '',
					'category'			=> 'sale'
				),
				$atts,
				$this->get_prefix()
			);

			if(strlen($atts['program_id']) > 0) {
				$this->get_script('default')->set_localized($atts);
			}

			// sv_tracking_manager_extended_finance_ads_thankyou_loaded
			do_action($this->get_prefix('thankyou_loaded'), $this);

			return '';
		}
	}