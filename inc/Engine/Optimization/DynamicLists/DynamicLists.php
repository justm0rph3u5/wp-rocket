<?php
declare(strict_types=1);

namespace WP_Rocket\Engine\Optimization\DynamicLists;

use WP_Rocket\Abstract_Render;
use WP_Rocket\Engine\License\API\User;

class DynamicLists extends Abstract_Render {
	/**
	 * APIClient instance
	 *
	 * @var APIClient
	 */
	private $api;

	/**
	 * DataManager instance
	 *
	 * @var DataManager
	 */
	private $data_manager;

	/**
	 * User instance
	 *
	 * @var User
	 */
	private $user;

	const ROUTE_NAMESPACE = 'wp-rocket/v1';

	/**
	 * Instantiate the class.
	 *
	 * @param APIClient   $api APIClient instance.
	 * @param DataManager $data_manager DataManager instance.
	 * @param User        $user User instance.
	 * @param string      $template_path Path to views.
	 */
	public function __construct( APIClient $api, DataManager $data_manager, User $user, $template_path ) {
		parent::__construct( $template_path );

		$this->api          = $api;
		$this->data_manager = $data_manager;
		$this->user         = $user;
	}

	/**
	 * Registers the dynamic lists update route
	 *
	 * @return void
	 */
	public function register_rest_route() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'dynamic_lists/update',
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'rest_update_response' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
		);
	}
	/**
	 * Checks user's permissions. This is a callback registered to REST route's "permission_callback" parameter.
	 *
	 * @return bool true if the user has permission; else false.
	 */
	public function check_permissions() {
		return current_user_can( 'rocket_manage_options' );
	}

	/**
	 * Returns the update response
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_update_response() {
		return rest_ensure_response( $this->update_lists_from_remote() );
	}

	/**
	 * Updates the lists from remote
	 *
	 * @return array
	 */
	public function update_lists_from_remote() {
		if ( $this->user->is_license_expired() ) {
			return [
				'success' => false,
				'data'    => '',
				'message' => __( 'You need an active license to get the latest version of the lists from our server.', 'rocket' ),
			];
		}

		$result = $this->api->get_exclusions_list( $this->data_manager->get_lists_hash() );

		if (
			( 200 !== $result['code'] && 206 !== $result['code'] )
			|| empty( $result['body'] )
		) {
			return [
				'success' => false,
				'data'    => '',
				'message' => __( 'Could not get updated lists from server.', 'rocket' ),
			];
		}

		if ( 206 === $result['code'] ) {
			return [
				'success' => true,
				'data'    => '',
				'message' => __( 'Lists are up to date.', 'rocket' ),
			];
		}

		if ( ! $this->data_manager->save_dynamic_lists( $result['body'] ) ) {
			return [
				'success' => false,
				'data'    => '',
				'message' => __( 'Could not update lists.', 'rocket' ),
			];
		}

		return [
			'success' => true,
			'data'    => $result['body'],
			'message' => __( 'Lists are successfully updated.', 'rocket' ),
		];
	}

	/**
	 * Schedule cron to update dynamic lists weekly.
	 *
	 * @return void
	 */
	public function schedule_lists_update() {
		if ( ! wp_next_scheduled( 'rocket_update_dynamic_lists' ) ) {
			wp_schedule_event( time(), 'weekly', 'rocket_update_dynamic_lists' );
		}
	}

	/**
	 * Clear dynamic lists update event.
	 */
	public function clear_schedule_lists_update() {
		wp_clear_scheduled_hook( 'rocket_update_dynamic_lists' );
	}

	/**
	 * Displays the dynamic lists update section on tools tab
	 *
	 * @return void
	 */
	public function display_update_lists_section() {
		if ( ! current_user_can( 'rocket_manage_options' ) ) {
			return;
		}

		echo $this->generate( 'settings/dynamic-lists-update' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
