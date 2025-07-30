<?php
	namespace sv_tracking_manager_extended;
	
	class typeform extends modules {
		public function init() {
			// Section Info
			$this->set_section_title( __('Typeform', 'sv_tracking_manager_extended' ) )
				 ->set_section_desc(__( sprintf('%sTypeform Login%s', '<a target="_blank" href="https://admin.typeform.com/login">','</a>'), 'sv_tracking_manager_extended' ))
				 ->set_section_type( 'settings' )
				 ->load_settings()
				 ->get_root()->add_section( $this );

			// [sv_tracking_manager_extended_typeform id=""]
			add_shortcode($this->get_prefix(), array($this, 'load'));
		}
		
		public function load_settings(): typeform {
			$this->get_setting('activate')
				 ->set_title( __( 'Activate', 'sv_tracking_manager_extended' ) )
				 ->set_description('Insert forms via shortcode: [sv_tracking_manager_extended_typeform id="" width="" height=""]')
				 ->load_type( 'checkbox' );

			return $this;
		}
		protected function register_scripts(): typeform {
			if($this->is_active()) {
				$this->get_script('default')
					->set_path('https://embed.typeform.com/next/embed.js')
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
			
			return true;
		}
		public function load($settings = array()): string{
			if(!$this->is_active()){
				return '';
			}
			if(!$this->is_instance_active('sv_tracking_manager')){
				return '';
			}

			$settings					= shortcode_atts(
				array(
					'id'				=> '',
					'width'				=> '100%',
					'height'			=> '700px'
				),
				$settings,
				$this->get_module_name()
			);

			if(strlen($settings['id']) === 0){
				return '';
			}

			$this->register_scripts()->add_service();
			$this->get_script('default')->set_is_enqueued();

			return '<div style="width:'.$settings['width'].';height:'.$settings['height'].';max-width:100%;" data-tf-widget="'.$settings['id'].'" data-tf-inline-on-mobile data-tf-medium="snippet" data-tf-disable-auto-focus></div>';
		}
	}