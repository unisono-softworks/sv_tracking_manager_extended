<?php
	namespace sv_tracking_manager_extended;
	
	class plausible extends modules {
		public function __construct() {
		
		}
		
		public function init() {
			$this->set_section_title( __( 'Plausible', 'sv_tracking_manager_extended' ) )
				->set_section_desc( __( 'Extended', 'sv_tracking_manager_extended' ) )
				->set_section_template_path( $this->get_path( 'lib/backend/tpl/settings.php' ) )
				->load_settings()
				->register_scripts()
				->events()
				->get_root()->add_section( $this );

			add_action('wp', array($this, 'bypass_usercentrics'));

			add_action('init', array($this, 'local_cache'), 999);
		}
		protected function register_scripts(): plausible {
			$this->get_script('events')
				->set_path('lib/frontend/js/events.js')
				->set_type('js');

			return $this;
		}
		public function load_settings(): plausible{
			$this->get_setting( 'local_cache' )
				->set_title( __( 'Activate Local Proxy', 'sv_tracking_manager_extended' ) )
				->set_description( __( 'Communication with Plausible is tunneled by server proxy. External Files will be cached and updated every 24 hours where possible.', 'sv_tracking_manager_extended' ) )
				->load_type( 'checkbox' );

			$this->get_setting( 'bypass_usercentrics' )
				->set_title( __( 'Bypass Usercentrics', 'sv_tracking_manager_extended' ) )
				->set_description( __( 'Plausible will not wait for permission by Usercentrics', 'sv_tracking_manager_extended' ) )
				->load_type( 'checkbox' );

			// Events Groups
			$this->get_setting('custom_events')
				->set_title(__('Custom Events', 'sv_tracking_manager'))
				->load_type('group');

			$this->get_setting('custom_events')->run_type()->add_child()
				->set_ID('entry_label')
				->set_title(__('Entry Label', 'sv_tracking_manager'))
				->set_description(__('This Label will be used as Entry Title for this Settings Group.', 'sv_tracking_manager'))
				->load_type('text')
				->set_placeholder('Entry #...');

			$this->get_setting('custom_events')->run_type()->add_child()
				->set_ID('event')
				->set_title(__('Event Trigger', 'sv_tracking_manager'))
				->set_description(__('Selected trigger will be monitored for event action, see https://www.w3schools.com/jquery/jquery_events.asp', 'sv_tracking_manager'))
				->load_type('text')
				->set_placeholder('click');

			$this->get_setting('custom_events')->run_type()->add_child()
				->set_ID('element')
				->set_title(__('DOM Element', 'sv_tracking_manager'))
				->set_description(__('DOM Selector (e.g. .contact_form, #submit)', 'sv_tracking_manager'))
				->load_type('text')
				->set_placeholder('html')
				->set_default_value('html');

			$this->get_setting('custom_events')->run_type()->add_child()
				->set_ID('event_goal')
				->set_title(__('Event Goal', 'sv_tracking_manager'))
				->set_description(__('Must be an exact Match to goals defined in Plausible.', 'sv_tracking_manager'))
				->load_type('text');
				
			return $this;
		}
		public function cleanup(&$value, $key){
			$value = str_replace('"', "'", $value);
		}
		public function events(): plausible{
			$events = $this->get_setting('custom_events')->get_data();
			$events_js = array();

			if ($events && is_array($events) && count($events) > 0) {
				$this->get_script('events')->set_is_enqueued();

				foreach ( $events as $event_id => $event ) {
					array_walk($event, array($this, 'cleanup'));

					// event empty
					if (strlen($event['event']) === 0) {
						continue;
					}

					$events_js[] = array(
						'event'           => $event['event'],
						'element'         => $event['element'],
						'goal'      	  => $event['event_goal'],
					);
				}

				if(count($events_js) > 0) {
					$this->get_script( 'events' )
						->set_is_enqueued()
						->set_localized( $events_js );
				}
			}

			return $this;
		}
		public function bypass_usercentrics(): plausible{
			if(!$this->get_setting( 'bypass_usercentrics' )->get_data()){
				return $this;
			}

			if(!$this->is_instance_active('sv_tracking_manager')){
				return $this;
			}

			$this->get_instance('sv_tracking_manager')->get_module('usercentrics')->remove_consent_ID('sv_tracking_manager_plausible_scripts_default');

			add_filter('sv_tracking_manager_no_consent_required', function(array $filter){
				$filter	= array_merge($filter, array($this->get_instance('sv_tracking_manager')->get_module('plausible')->get_script('default')->get_handle()));

				return $filter;
			});

			return $this;
		}
		public function local_cache(): plausible {
			if(!$this->get_setting( 'local_cache' )->get_data()){
				return $this;
			}

			if(!$this->is_instance_active('sv_tracking_manager')){
				return $this;
			}

			$script = $this->get_instance('sv_tracking_manager')->get_module('plausible')->get_script('default');

			$script->set_path($this->cache_file($script->get_url()));

			// catch API Events
			$this->api_events();

			return $this;
		}
		private function api_events() {
			if($_SERVER['REQUEST_URI'] === '/api/event'){
				$body		= file_get_contents('php://input');
				if(json_decode($body)){
					http_response_code ( 202 );

					$headers				= array(
						'Content-Type'		=> 'application/json; charset=utf-8',
						'User-Agent'		=> isset(getallheaders()['User-Agent']) ? getallheaders()['User-Agent'] : '',
						'X-Forwarded-For'	=> isset(getallheaders()['X-Forwarded-For']) ? getallheaders()['X-Forwarded-For'] : '',
					);

					$remote_get		= static::$remote_get
						->create( $this )
						->set_request_url( 'https://plausible.io/api/event' )
						->set_args(array(
							'headers'		=> $headers,
							'data_format'	=> 'body',
							'method'		=> 'POST',
							'body'			=> $body
						));

					echo wp_remote_retrieve_body($remote_get->get_response(true));
					die();
				}
			}
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