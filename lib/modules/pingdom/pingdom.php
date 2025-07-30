<?php
	namespace sv_tracking_manager_extended;
	
	class pingdom extends modules {
		public function init() {
			// Section Info
			$this->set_section_title( __('Pingdom', 'sv_tracking_manager_extended' ) )
				 ->set_section_desc(__( sprintf('%sPingdom Login%s', '<a target="_blank" href="https://my.pingdom.com/">','</a>'), 'sv_tracking_manager_extended' ))
				 ->set_section_type( 'settings' )
				 ->load_settings()
				 ->get_root()->add_section( $this );

			add_action('init', array($this, 'load'));
		}
		
		public function load_settings(): pingdom {
			$this->get_setting('activate')
				 ->set_title( __( 'Activate', 'sv_tracking_manager_extended' ) )
				 ->set_description('Enable Tracking')
				 ->load_type( 'checkbox' );

			$this->get_setting('url')
				 ->set_title( __( 'Script URL', 'sv_tracking_manager_extended' ) )
				 ->set_placeholder( 'https://rum-static.pingdom.net/xx-12345a9e629a5e6789a621c.js' )
				 ->load_type( 'url' );

			return $this;
		}
		protected function register_scripts(): pingdom {
			if($this->is_active()) {
				$this->get_script('default')
					->set_path($this->get_setting('url')->get_data())
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
			// Script URL not set
			if(!$this->get_setting('url')->get_data()){
				return false;
			}
			// Script URL empty
			if(strlen(trim($this->get_setting('url')->get_data())) === 0){
				return false;
			}
			
			return true;
		}
		public function load(): pingdom{
			if($this->is_active()){
				$this->register_scripts()->add_service();

				$this->get_script('default')->set_is_enqueued();
			}
			
			return $this;
		}
	}