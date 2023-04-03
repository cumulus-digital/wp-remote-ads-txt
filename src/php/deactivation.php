<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt;

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

use WP_Filesystem_Direct;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

function handleDeactivation() {
	// Delete our options
	$fileSystemDirect = new WP_Filesystem_Direct( false );

	if ( Settings\Container::getSetting( 'extras', 'cleanup' ) ) {
		\delete_option( PREFIX . '_settings' );
	}

	// Remove our caches
	if ( $fileSystemDirect->is_dir( CACHEDIR ) && $fileSystemDirect->is_writable( CACHEDIR ) ) {
		$fileSystemDirect->rmdir( CACHEDIR, true );
	}

	// Remove our cron
	\wp_clear_scheduled_hook( PREFIX . '-cron' );
}
\register_uninstall_hook( PLUGIN, __NAMESPACE__ . '\\handleDeactivation' );
\register_deactivation_hook( PLUGIN, __NAMESPACE__ . '\\handleDeactivation' );
