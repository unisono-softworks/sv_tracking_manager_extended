<?php
	namespace sv_tracking_manager_extended;
	
	class hubspot extends modules {
		public function init() {
			$this->set_section_title( __( 'Hubspot', 'sv_tracking_manager_extended' ) )
				->set_section_desc( __( 'Extended', 'sv_tracking_manager_extended' ) )
				->load_settings()
				->get_root()->add_section( $this );

			add_action('wp', array($this, 'register_scripts'));
			add_action('wp_print_scripts', array($this, 'disable_plugin_loader'));
			// [sv_tracking_manager_extended_hubspot_meetings]
			add_shortcode($this->get_prefix('meetings'), array($this, 'shortcode_meetings'));
		}

		public function register_scripts(): hubspot{
			if(!$this->is_instance_active('sv_tracking_manager')){
				return $this;
			}

			if ($this->is_active()) {
				$this->get_script('meetings')
					->set_path('lib/frontend/js/meetings.js')
					->set_type('js')
					->set_deps(array($this->get_instance('sv_tracking_manager')->get_module('hubspot')->get_script('default')->get_handle()));

				$this->add_service();
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
			// Default URL not set
			if(!$this->get_setting('meetings_default_url')->get_data()){
				return false;
			}
			// Default URL empty
			if(strlen(trim($this->get_setting('meetings_default_url')->get_data())) === 0){
				return false;
			}

			return true;
		}
		public function load_settings(): hubspot{
			$this->get_setting( 'disable_plugin_loader' )
				->set_title( __( 'Disable Plugin Loader', 'sv_tracking_manager_extended' ) )
				->set_description( __( 'Disable Loading Hubspot Pixel via official Hubspot Plugin. This prevents doubled Script loading and respects Usercentrics CMP. You should disable Hubspot Cookie Consent Feature if loading is controlled by Usercentrics CMP.', 'sv_tracking_manager_extended' ) )
				->load_type( 'checkbox' );

			$this->get_setting('activate')
				->set_title( __( 'Activate Meetings Script', 'sv_tracking_manager_extended' ) )
				->load_type( 'checkbox' );

			$this->get_setting('meetings_default_url')
				->set_title( __( 'Meetings Default URL', 'sv_tracking_manager' ) )
				->set_placeholder('https://meetings-eu1.hubspot.com/xxx?embed=true')
				->set_description( 'You can override this as shortcode parameter, e.g. [sv_hubspot_meetings url="https://meetings-eu1.hubspot.com/zzz?embed=true"]', 'sv_tracking_manager' )
				->load_type( 'text' );
				
			return $this;
		}
		public function disable_plugin_loader(): hubspot{
			if(!$this->get_setting( 'disable_plugin_loader' )->get_data()){
				return $this;
			}

			wp_dequeue_script('leadin-script-loader-js');

			return $this;
		}
		public function shortcode_meetings( $settings = array() ){
			if(!$this->is_instance_active('sv_tracking_manager')){
				return '';
			}

			$settings					= shortcode_atts(
				array(
					'url'				=> $this->get_setting('meetings_default_url')->get_data(),
				),
				$settings,
				$this->get_module_name()
			);

			if(!$this->is_valid_url($settings['url'])){
				return '';
			}

			$this->get_script('meetings')->set_is_enqueued();

			return '<div class="meetings-iframe-container" data-src="'.$settings['url'].'"></div>';
		}
	}