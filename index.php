<?php

namespace CUMULUS\RemoteAdsTxt;

/*
 * Plugin Name: Remote Ads.txt
 * Plugin URI: https://github.com/cumulus-digital/wp-remote-ads-txt/
 * GitHub Plugin URI: https://github.com/cumulus-digital/wp-remote-ads-txt/
 * Primary Branch: main
 * Description: Cache and serve a remote ads.txt
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.1
 * Author: vena
 * License: UNLICENSED
 */

use Throwable;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

\define( 'CUMULUS\\RemoteAdsTxt\\PLUGIN', __FILE__ );
\define( 'CUMULUS\\RemoteAdsTxt\\BASEDIR', \plugin_dir_path( __FILE__ ) );
\define( 'CUMULUS\\RemoteAdsTxt\\BASEURL', \plugin_dir_url( __FILE__ ) );

require BASEDIR . 'build/composer/vendor/scoper-autoload.php';

// Register settings menu.
require BASEDIR . '/settings.php';

try {
	RemoteAdsTxt::getInstance()->interceptRequest();
} catch ( Throwable $e ) {
	if ( \is_admin() ) {
		\add_action( 'admin_init', function () use ( $e ) {
			\add_settings_error(
				'WSB/remote-ads-txt',
				'WSB/remote-ads-txt/message',
				$e->getMessage(),
				'error'
			);
		} );
	}
	/*
	\wp_die(
		'An error occurred in remote ads.txt, see log for details.',
		'An error has occurred.',
		array( 'response' => 500 )
	);
	*/
}

// Register activation hooks
\register_activation_hook( \CUMULUS\RemoteAdsTxt\PLUGIN, function () {
	$callbacks = (array) \apply_filters( 'remote-ads-txt--on-activation', array() );
	foreach ( $callbacks as $callback ) {
		\call_user_func( $callback );
	}

	// Register uninstall hooks
	\register_uninstall_hook( \CUMULUS\RemoteAdsTxt\PLUGIN, __NAMESPACE__ . '\\handleUninstall' );
} );

function handleUninstall() {
	$deactivators = (array) \apply_filters( 'remote-ads-txt--on-deactivation', array() );
	foreach ( $deactivators as $callback ) {
		\call_user_func( $callback );
	}
}

// Register deactivation hooks
\register_deactivation_hook( \CUMULUS\RemoteAdsTxt\PLUGIN, function () {
	$callbacks = (array) \apply_filters( 'remote-ads-txt--on-deactivation', array() );
	foreach ( $callbacks as $callback ) {
		\call_user_func( $callback );
	}
} );
