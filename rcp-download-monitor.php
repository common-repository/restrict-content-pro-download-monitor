<?php
/**
 * Plugin Name: RCP - Download Monitor Bridge
 * Plugin URL: https://restrictcontentpro.com/downloads/download-monitor/
 * Description: Limit file downloads to active Restrict Content Pro members.
 * Version: 1.0.5
 * Author: iThemes, LLC
 * Author URI: https://ithemes.com
 * Contributors: jthillithemes, layotte, ithemes
 * iThemes Package: restrict-content-pro-download-monitor
 */


class RCP_Download_Monitor {

	/**
	 * @var RCP_Download_Monitor The one true RCP_Download_Monitor
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * Main RCP_Download_Monitor Instance
	 *
	 * Insures that only one instance of RCP_Download_Monitor exists in memory at any one
	 * time.
	 *
	 * @var object
	 * @access public
	 * @since 1.0
	 * @return RCP_Download_Monitor
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RCP_Download_Monitor ) ) {
			self::$instance = new RCP_Download_Monitor;
			self::$instance->init();
		}
		return self::$instance;
	}


	/**
	 * Setup filters and actions
	 *
	 * @access private
	 * @since 1.0
	 */
	private function init() {
		// Check for RCP and Download Monitor
		if( ! function_exists( 'rcp_is_active' ) || ! class_exists( 'WP_DLM' ) )
			return;

		// Add content restriction meta box to download post type.
		add_filter( 'rcp_metabox_post_types', array( $this, 'add_metabox' ) );

		// Hide additional options section.
		add_filter( 'rcp_metabox_show_additional_options', array( $this, 'hide_additional_options' ) );

		// Check if user can download the file.
		add_filter( 'dlm_can_download', array( $this, 'can_download' ), 10, 3 );

	}

	/**
	 * Add meta box to 'Download' post type.
	 *
	 * @param array $post_types
	 *
	 * @access public
	 * @since 1.0.3
	 * @return array
	 */
	public function add_metabox( $post_types ) {
		if ( is_array( $post_types ) ) {
			$post_types[ 'dlm_download' ] = 'dlm_download';
		}

		return $post_types;
	}

	/**
	 * Hide additional options section for DLM downloads.
	 *
	 * @param bool $show_options
	 *
	 * @access public
	 * @since 1.0.3
	 * @return bool
	 */
	public function hide_additional_options( $show_options ) {

		if ( 'dlm_download' == get_post_type() ) {
			$show_options = false;
		}

		return $show_options;

	}


	/**
	 * Can the current user download files?
	 *
	 * @param bool                 $can      Whether or not they can download the file.
	 * @param DLM_Download         $download Download object.
	 * @param DLM_Download_Version $version  Download version.
	 *
	 * @access public
	 * @since 1.0
	 * @return bool
	 */
	public function can_download( $can, $download, $version ) {

		if( version_compare( RCP_PLUGIN_VERSION, '2.7', '<' ) ) {

			if( $download->is_members_only() && ! rcp_is_active() && 'free' != rcp_get_status() )
				$can = false;

		} else {

			if ( method_exists( $download, 'get_id' ) ) {
				$download_id = $download->get_id();
			} else {
				$download_id = $download->post->ID;
			}

			if ( ! rcp_user_can_access( get_current_user_id(), $download_id ) ) {
				$can = false;
			}

		}

		return $can;
	}


}
add_action( 'plugins_loaded', array( 'RCP_Download_Monitor', 'get_instance' ), 20 );

if ( ! function_exists( 'ithemes_restrict_content_pro_download_monitor_updater_register' ) ) {
	function ithemes_restrict_content_pro_download_monitor_updater_register( $updater ) {
		$updater->register( 'REPO', __FILE__ );
	}
	add_action( 'ithemes_updater_register', 'ithemes_restrict_content_pro_download_monitor_updater_register' );

	require( __DIR__ . '/lib/updater/load.php' );
}