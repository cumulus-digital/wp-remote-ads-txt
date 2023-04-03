<?php
/**
 * Actions to run on plugin activation + cron
 */

namespace CUMULUS\Wordpress\RemoteAdsTxt;

use Throwable;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

function registerCron() {
	// register our global cache refreshing cron
	if ( ! \wp_next_scheduled( PREFIX . '-cron' ) ) {
		\wp_schedule_event( \time(), 'hourly', PREFIX . '-cron' );
	}
}
\register_activation_hook( PLUGIN, function () {
	$callbacks = (array) \apply_filters( PREFIX . '--on-activation', [] );

	foreach ( $callbacks as $callback ) {
		if ( \is_callable( $callback ) ) {
			\call_user_func( $callback );
		}
	}

	// Remove v1 schedule
	\wp_clear_scheduled_hook( 'remote-ads-txt--update-cache' );

	// Create new schedule
	registerCron();
} );

// Refresh our remotes on cron
function updateAllRemotes() {
	foreach ( REMOTES::getAll() as $remote ) {
		try {
			$remote->updateCache();
		} catch ( Throwable $e ) {
			\error_log( '[wp-remote-ads-txt] Error updating remotes during cron event.' . \PHP_EOL . \print_r( $e ) );
		}
	}
}
\add_action( PREFIX . '-cron', __NAMESPACE__ . '\\updateAllRemotes' );
