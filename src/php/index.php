<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt;

use Throwable;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

require_once BASEDIR . 'src/php/activation.php';
require_once BASEDIR . 'src/php/deactivation.php';
require_once BASEDIR . 'src/php/Settings/index.php';

try {
	$general_settings = Settings\Container::getHandler( 'general' );

	$ads_txt_settings = Settings\Container::getHandler( 'ads-txt' );

	if ( RemoteFile::isValidRemote( $ads_txt_settings->getSetting( 'url' ) ) ) {
		$adsTxtHandler = new RemoteFile(
			'ads-txt',
			$ads_txt_settings->getSetting( 'url' ),
			'/ads.txt',
			\intval( $general_settings->getSetting( 'timeout' ) ),
			'ads.txt',
			$ads_txt_settings->getSetting( 'additions' )
		);
		REMOTES::setHandler( 'ads-txt', $adsTxtHandler );
	}

	$app_ads_txt_settings = Settings\Container::getHandler( 'app-ads-txt' );

	if ( RemoteFile::isValidRemote( $app_ads_txt_settings->getSetting( 'url' ) ) ) {
		REMOTES::setHandler( 'app-ads-txt', new RemoteFile(
			'app-ads-txt',
			$app_ads_txt_settings->getSetting( 'url' ),
			'/app-ads.txt',
			\intval( $general_settings->getSetting( 'timeout' ) ),
			'app-ads.txt',
			$app_ads_txt_settings->getSetting( 'additions' )
		) );
	}
} catch ( Throwable $e ) {
	\error_log( '[wp-remote-ads-txt] Error initializing plugin!' . \PHP_EOL . \print_r( $e ) );
	\do_action( 'admin_init', function () use ( $e ) {
		\add_settings_error( PREFIX, PREFIX . '/errors', $e->getMessage(), 'error' );
	} );
}
