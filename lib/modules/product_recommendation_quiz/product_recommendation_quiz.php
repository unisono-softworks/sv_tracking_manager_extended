<?php
	namespace sv_tracking_manager_extended;

	class product_recommendation_quiz extends modules {
		public function init() {
			$this->set_section_title( __( 'Product Recommendation Quiz', 'sv_tracking_manager_extended' ) )
				->set_section_type( 'settings' )
				->load_settings()
				->get_root()->add_section( $this );

			add_action('wp_enqueue_scripts', array($this, 'usercentrics_support'), 100);
		}

		public function load_settings(): product_recommendation_quiz{
			$this->get_setting( 'usercentrics_support' )
				->set_title( __( 'Enable Javascript Loading Opt In via Usercentrics', 'sv_tracking_manager_extended' ) )
				->load_type( 'checkbox' );

			return $this;
		}

		public function is_active(): bool{
			if(!$this->get_setting( 'usercentrics_support' )->get_data()){
				return false;
			}
			if(!$this->is_instance_active('sv_tracking_manager')){
				return false;
			}

			if(!defined('PRQ_ADMIN_URL') || !defined('PRQ_STORE_URL')) {
				return false;
			}

			return true;
		}

		public function usercentrics_support(): product_recommendation_quiz{
			if(!$this->is_active()){
				return $this;
			}

			wp_dequeue_script('product-recommendation-quiz-for-ecommerce');

			$this->get_script('product-recommendation-quiz-for-ecommerce')
					->set_is_no_prefix()
					->set_path(PRQ_ADMIN_URL . '/embed.js?shop=' . PRQ_STORE_URL)
					->set_type('js')
					->set_is_enqueued();

			$this->add_service();

			return $this;
		}
	}