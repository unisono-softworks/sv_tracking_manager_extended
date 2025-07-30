<?php
	namespace sv_tracking_manager_extended;
	
	class usercentrics extends modules {
		public function init() {
			$this->set_section_title( __( 'Usercentrics', 'sv_tracking_manager_extended' ) )
				->set_section_desc( __( 'Extended', 'sv_tracking_manager_extended' ) )
				->load_settings()
				->get_root()->add_section( $this );

			add_action('init', array($this, 'settings_toggle'));
			add_action('plugins_loaded', array($this, 'local_cache'));
			add_action('plugins_loaded', array($this, 'sdp_block_only'));
		}

		public function settings_toggle(): usercentrics{
			if(!$this->is_instance_active('sv_tracking_manager')){
				return $this;
			}

			if(!$this->get_instance('sv_tracking_manager')->get_module('usercentrics')->is_active()){
				return $this;
			}

			$this->get_script('default')
				->set_path('lib/js/frontend/default.js')
				->set_type('js')
				->set_is_enqueued();

			return $this;
		}
		
		public function load_settings(): usercentrics{
			$this->get_setting( 'local_cache' )
				->set_title( __( 'Activate Local Cache', 'sv_tracking_manager_extended' ) )
				->set_description( __( 'External Files will be cached and updated every 24 hours where possible', 'sv_tracking_manager_extended' ) )
				->load_type( 'checkbox' );

			$this->get_setting( 'sdp_block_only' )
				->set_title( __( 'Beta: Smart Data Protector Block Only', 'sv_tracking_manager_extended' ) )
				->set_description( __( 'Smart Data Protector will try to load only those codeparts which are needed to block the Services added here. Add the Service Template IDs, like BJz7qNsdj-7 for Youtube or HkocEodjb7 for Google Analytics as shown in Usercentrics Dashboard.', 'sv_tracking_manager_extended' ) )
				->load_type( 'group' );

			$this->get_setting('sdp_block_only')->run_type()->add_child()
				->set_ID('entry_label')
				->set_title(__('Entry Label', 'sv_tracking_manager_extended'))
				->set_description(__('This Label will be used as Entry Title for this Settings Group.', 'sv_tracking_manager_extended'))
				->load_type('text')
				->set_placeholder('Entry #...');

			$this->get_setting('sdp_block_only')->run_type()->add_child()
				->set_ID('id')
				->set_title(__('ID', 'sv_tracking_manager_extended'))
				->set_description(__('Service Template ID from Usercentrics', 'sv_tracking_manager_extended'))
				->load_type('text');
				
			return $this;
		}

		public function sdp_block_only(): usercentrics {
			if(!$this->get_setting( 'sdp_block_only' )->get_data()){
				return $this;
			}

			if(!$this->is_instance_active('sv_tracking_manager')){
				return $this;
			}

			if(!$this->get_instance('sv_tracking_manager')->get_module('usercentrics')->is_activate_shield()){
				return $this;
			}

			$services	= array();
			foreach($this->get_setting( 'sdp_block_only' )->get_data() as $service){
				$services[]		= $service['id'];
			}

			add_filter( 'rocket_delay_js_exclusions', function ( $excluded_files = array() ) {
				$excluded_files[] = '/sdp_block_only.js';

				return $excluded_files;
			} );

			$this->get_script('sdp_block_only')
				->set_deps(array('jquery'))
				->set_path('lib/js/frontend/sdp_block_only.js')
				->set_type('js')
				->set_is_enqueued()
				->set_localized($services);

			return $this;
		}
		
		public function local_cache(): usercentrics {
			if(!$this->get_setting( 'local_cache' )->get_data()){
				return $this;
			}

			if(!$this->is_instance_active('sv_tracking_manager')){
				return $this;
			}

			add_filter('usercentrics-cmp', array($this,'cache_file'));
			add_filter('usercentrics-privacy-shield', array($this,'cache_file'));

			return $this;
		}
		public function cache_file(string $url): string{
			$hash = md5($url);

			if(strlen(get_transient( $this->get_prefix($hash))) === 0) {
				$remote_get		= static::$remote_get->create( $this )
					->set_request_url( $url );

				$file_content	= $remote_get->get_response_body();
				$file_version	= '?ver='.md5($file_content);
				$file_name		= explode('.',basename($url));

				if($file_name){
					$file_ext = '.'.end($file_name);
				}else{
					$file_ext = '';
				}

				$new_file_name	= $hash.$file_ext;

				file_put_contents($this->get_path_cached($new_file_name), $file_content);

				set_transient($this->get_prefix($hash), $this->get_url_cached($new_file_name), 24 * HOUR_IN_SECONDS);
			}

			add_filter( 'rocket_exclude_defer_js', function($excluded_files = array()) use($hash) : array{
				$excluded_files[] = get_transient( $this->get_prefix($hash));

				return $excluded_files;
			} );

			return get_transient( $this->get_prefix($hash) );
		}
		public function get_path_cached(string $file): string{
			return static::$scripts->create( $this )->get_path_cached($file);
		}
		public function get_url_cached(string $file): string{
			return static::$scripts->create( $this )->get_url_cached($file);
		}
	}