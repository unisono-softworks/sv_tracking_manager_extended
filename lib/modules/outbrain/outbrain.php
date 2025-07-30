<?php
	namespace sv_tracking_manager_extended;
	
	class outbrain extends modules {
		public function init() {
			// Section Info
			$this->set_section_title( __('Outbrain', 'sv_tracking_manager_extended' ) )
				 ->set_section_desc(__( sprintf('%sOutbrain Login%s', '<a target="_blank" href="https://my.outbrain.com/">','</a>'), 'sv_tracking_manager_extended' ))
				 ->set_section_type( 'settings' )
				 ->load_settings()
				 ->get_root()->add_section( $this );

			add_action('init', array($this, 'load'));
		}
		
		public function load_settings(): outbrain {
			$this->get_setting('activate')
				 ->set_title( __( 'Activate', 'sv_tracking_manager_extended' ) )
				 ->set_description('Enable Tracking')
				 ->load_type( 'checkbox' );
			
			$this->get_setting('conversion_tracking_id')
				 ->set_title( __( 'Conversion Tracking ID', 'sv_tracking_manager_extended' ) )
				 ->set_description( __( sprintf('%sHow to retrieve Tracking ID%s', '<a target="_blank" href="https://www.outbrain.com/help/advertisers/multiple-conversions/">','</a>'), 'sv_tracking_manager_extended' ) )
				 ->load_type( 'text' );

			return $this;
		}
		protected function register_scripts(): outbrain {
			if($this->is_active()) {
				$this->get_script('conversion_tracking')
					->set_path('lib/frontend/js/conversion_tracking.js')
					->set_type('js');
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
			// Tracking ID not set
			if(!$this->get_setting('conversion_tracking_id')->get_data()){
				return false;
			}
			// Tracking ID empty
			if(strlen(trim($this->get_setting('conversion_tracking_id')->get_data())) === 0){
				return false;
			}
			
			return true;
		}
		public function load(): outbrain{
			if($this->is_active()){
				$this->register_scripts()->add_service();

				$this->get_script('conversion_tracking')
					->set_is_enqueued()
					->set_localized(array(
						'conversion_tracking_id'	=> $this->get_setting('conversion_tracking_id')->get_data()
					));
			}
			
			return $this;
		}
	}