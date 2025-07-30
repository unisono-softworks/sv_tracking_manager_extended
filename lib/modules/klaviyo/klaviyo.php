<?php
	namespace sv_tracking_manager_extended;
	
	class klaviyo extends modules {
		public function init() {
			// Section Info
			$this->set_section_title( __('Klaviyo', 'sv_tracking_manager_extended' ) )
				 ->set_section_desc(__( sprintf('%sklaviyo Login%s', '<a target="_blank" href="https://www.klaviyo.com/login">','</a>'), 'sv_tracking_manager_extended' ))
				 ->set_section_type( 'settings' )
				 ->load_settings()
				 ->get_root()->add_section( $this );

			if($this->is_active()){
				add_filter('script_loader_tag', array($this, 'script_loader_tag'), 100000, 2);
			}
		}
		public function load_settings(): klaviyo {
			$this->get_setting('activate')
				 ->set_title( __( 'Activate Usercentrics Compatibility Mode', 'sv_tracking_manager_extended' ) )
				 ->set_description('Scripts from Klaviyo Plugin need confirmation in Usercentrics CMP')
				 ->load_type( 'checkbox' );

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
		public function script_loader_tag($tag, $handle){
			if($handle === 'klaviyojs'){
				$tag = str_replace(
					array(
						"type='text/javascript'",
						'type="text/javascript"'
					),
					'',
					$tag);

				if(strpos($tag, 'type="text/plain"') === false) {
					$tag = str_replace(
						'<script ',
						'<script type="text/plain" data-usercentrics="Klaviyo" ',
						$tag);
				}
			}

			return $tag;
		}
	}