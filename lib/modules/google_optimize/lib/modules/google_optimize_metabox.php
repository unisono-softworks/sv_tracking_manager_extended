<?php
	namespace sv_tracking_manager_extended;
	
	class google_optimize_metabox extends google_optimize{
		public function __construct(){
		
		}
		public function init(){
			$this->set_section_title('Optimize')
			->load_settings();
			
			add_action('wp', array($this, 'wp_init'));
		}
		public function load_settings(): google_optimize {
			$this->get_setting('enable_on_page')
				 ->set_title(__('Enable', 'sv_tracking_manager_extended'))
				 ->set_description(__('Enable Optimize on this page.', 'sv_tracking_manager_extended'))
				 ->load_type('checkbox');
			
			static::$metabox
				->create($this)
				->set_title('Optimize')
			    ->set_post_types(array('post', 'page', 'product'));
			
			return $this;
		}
		public function wp_init(){
			if(is_front_page()){
				$post			= get_post(get_option('page_on_front'));
			}else{
				global $post;
			}

			if($post && get_post_meta(
					$post->ID,
					'_'.$this->get_setting('enable_on_page')->get_prefix(
						$this->get_setting('enable_on_page')->get_ID()
					), true)){
				$this->get_module('google_optimize')->load();
			}
		}
	}