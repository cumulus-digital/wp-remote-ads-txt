<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt;

/*
 * Plugin Name: Remote Ads.txt
 * Plugin URI: https://github.com/cumulus-digital/wp-remote-ads-txt/
 * GitHub Plugin URI: https://github.com/cumulus-digital/wp-remote-ads-txt/
 * Primary Branch: main
 * Description: Cache and serve a remote ads.txt
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 2.0.0
 * Author: vena
 * License: UNLICENSED
 */

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

\define( 'CUMULUS\Wordpress\RemoteAdsTxt\PLUGIN', __FILE__ );
\define( 'CUMULUS\Wordpress\RemoteAdsTxt\BASEDIR', \plugin_dir_path( __FILE__ ) );
\define( 'CUMULUS\Wordpress\RemoteAdsTxt\BASEURL', \plugin_dir_url( __FILE__ ) );
\define( 'CUMULUS\Wordpress\RemoteAdsTxt\PREFIX', 'cmls-remote-ads-txt' );
\define( 'CUMULUS\Wordpress\RemoteAdsTxt\CACHEDIR', \trailingslashit( \realpath( \wp_upload_dir()['basedir'] ) ) . PREFIX );

require_once __DIR__ . '/libs/vendor/autoload.php';
require_once __DIR__ . '/src/php/helpers.php';
require_once __DIR__ . '/src/php/index.php';
