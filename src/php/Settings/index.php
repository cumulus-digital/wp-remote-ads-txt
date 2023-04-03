<?php

/**
 * Initialize Settings admin
 */

namespace CUMULUS\Wordpress\RemoteAdsTxt\Settings;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use CUMULUS\Wordpress\RemoteAdsTxt;
use Throwable;

\add_action( 'admin_menu', function () {
	Container::wpsf()->add_settings_page( [
		'parent_slug' => 'options-general.php',
		'page_title'  => 'Remote Ads.txt',
		'menu_title'  => 'Remote Ads.txt',
		'capability'  => 'install_plugins',
	] );

	\add_action( 'wpsf_before_settings_fields_' . RemoteAdsTxt\PREFIX, function () {
		echo '</form>';
		echo '<form action="options.php" method="post" enctype="multipart/form-data" id="cmls-wp-remote-ads-txt">';
	} );

	\add_action( 'admin_enqueue_scripts', function ( $hook ) {
		if ( $hook !== 'settings_page_' . RemoteAdsTxt\PREFIX . '-settings' ) {
			return;
		}

		$assets = include RemoteAdsTxt\BASEDIR . 'build/backend.asset.php';
		\wp_register_script(
			RemoteAdsTxt\PREFIX . '-backend-script',
			RemoteAdsTxt\BASEURL . '/build/backend.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);
		\wp_enqueue_script( RemoteAdsTxt\PREFIX . '-backend-script' );

		$rebuild_actions = [
			'rebuild_cron'    => RemoteAdsTxt\PREFIX . '-rebuild_cron',
			'get_mtime'       => RemoteAdsTxt\PREFIX . '-get_mtime',
			'validate_remote' => RemoteAdsTxt\PREFIX . '-validate_remote',
		];

		foreach ( RemoteAdsTxt\REMOTES::getAll() as $handle => $handler ) {
			$rebuild_actions[$handle] = RemoteAdsTxt\PREFIX . '-' . $handler->getHandle() . '-rebuild_cache';
		}

		\wp_localize_script(
			RemoteAdsTxt\PREFIX . '-backend-script',
			\str_replace( '-', '_', RemoteAdsTxt\PREFIX ),
			[
				'ajaxurl'         => \admin_url( 'admin-ajax.php' ),
				'nonce'           => \wp_create_nonce( RemoteAdsTxt\PREFIX ),
				'rebuild_actions' => $rebuild_actions,
			]
		);

		\wp_register_style(
			RemoteAdsTxt\PREFIX . '-backend-style',
			RemoteAdsTxt\BASEURL . '/build/backend.css',
			[],
			null,
			'all'
		);
		\wp_enqueue_style( RemoteAdsTxt\PREFIX . '-backend-style' );
	} );
} );

\add_action( 'admin_init', function () {
	// Fetch the latest MTime for each remote's cache
	\add_action( 'wp_ajax_' . RemoteAdsTxt\PREFIX . '-get_mtime', function () {
		$nonce = \filter_input( \INPUT_POST, 'nonce' );

		if ( empty( $nonce ) || ! \wp_verify_nonce( $nonce, RemoteAdsTxt\PREFIX ) ) {
			\wp_die( 'Invalid action!' );

			return;
		}

		try {
			$last_fetched = [];

			foreach ( RemoteAdsTxt\REMOTES::getAll() as $remote ) {
				$mtime = $remote->getCacheMTime();

				if ( $mtime ) {
					$last_fetched[$remote->getHandle()] = 'Last fetched: ' . \wp_date( 'F j, Y, g:i a', $mtime );
				}
			}
			\wp_send_json_success(
				[
					'msg'   => $last_fetched,
					'nonce' => \wp_create_nonce( RemoteAdsTxt\PREFIX ),
				]
			);
		} catch ( Throwable $e ) {
			\wp_send_json_error( [
				'msg'   => $e->getMessage(),
				'nonce' => \wp_create_nonce( RemoteAdsTxt\PREFIX ),
			], 500 );
		}
	} );

	// Ajax handler for re-registering cron
	\add_action( 'wp_ajax_' . RemoteAdsTxt\PREFIX . '-rebuild_cron', function () {
		$nonce = \filter_input( \INPUT_POST, 'nonce' );

		if ( empty( $nonce ) || ! \wp_verify_nonce( $nonce, RemoteAdsTxt\PREFIX ) ) {
			\wp_die( 'Invalid action!' );

			return;
		}

		// Remove our cron
		try {
			// Remove v1 cron
			\wp_clear_scheduled_hook( 'remote-ads-txt--update-cache' );
			// Remove v2 cron
			\wp_clear_scheduled_hook( RemoteAdsTxt\PREFIX . '-cron' );
			RemoteAdsTxt\registerCron();
		} catch ( Throwable $e ) {
			\wp_send_json_error( [
				'msg'   => $e->getMessage(),
				'nonce' => \wp_create_nonce( RemoteAdsTxt\PREFIX ),
			], 500 );
		}

		\wp_send_json_success(
			[
				'msg'   => 'Cron schedule has been rebuilt.',
				'nonce' => \wp_create_nonce( RemoteAdsTxt\PREFIX ),
			]
		);
	} );

	// Ajax handler for re-registering cron
	\add_action( 'wp_ajax_' . RemoteAdsTxt\PREFIX . '-validate_remote', function () {
		$nonce = \filter_input( \INPUT_POST, 'nonce' );

		if ( empty( $nonce ) || ! \wp_verify_nonce( $nonce, RemoteAdsTxt\PREFIX ) ) {
			\wp_die( 'Invalid action!' );

			return;
		}

		try {
			RemoteAdsTxt\RemoteFile::validateRemote( $_REQUEST['url'] );
		} catch ( Throwable $e ) {
			\wp_send_json_error( [
				'msg'   => $e->getMessage(),
				'nonce' => \wp_create_nonce( RemoteAdsTxt\PREFIX ),
			], 500 );
		}

		\wp_send_json_success(
			[
				'msg'   => 'Remote is valid',
				'nonce' => \wp_create_nonce( RemoteAdsTxt\PREFIX ),
			]
		);
	} );
} );

Container::registerHandler( 'general', new Sections\General() );
Container::registerHandler( 'extras', new Sections\Extras() );
Container::registerHandler( 'ads-txt', new Sections\AdsTxt() );
Container::registerHandler( 'app-ads-txt', new Sections\AppAdsTxt() );
