<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt\Settings;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use CUMULUS\Wordpress\RemoteAdsTxt;
use CUMULUS\Wordpress\RemoteAdsTxt\Vendors\WordPressSettingsFramework;
use Exception;

/**
 * Stores settings handlers
 */
class Container {

	/**
	 * Holds reference to WPSF instance
	 */
	private static $wpsf;

	/**
	 * Holds registered settings handlers
	 */
	private static $handlers = [];

	/**
	 * Holds default values
	 */
	public static $defaults = [];

	public static function wpsf() {
		if ( ! self::$wpsf ) {
			//require_once BASEDIR . '/vendor-prefixed/jamesckemp/wordpress-settings-framework/wp-settings-framework.php';
			self::$wpsf = new WordPressSettingsFramework( null, RemoteAdsTxt\PREFIX );
		}

		return self::$wpsf;
	}

	public static function addSettingsTab( $tab ) {
		\add_filter( 'wpsf_register_settings_' . RemoteAdsTxt\PREFIX, function ( $WPSF ) use ( $tab ) {
			$WPSF['tabs'][] = $tab;

			return $WPSF;
		} );
	}

	public static function addSettingsSection( $settings ) {
		\add_filter( 'wpsf_register_settings_' . RemoteAdsTxt\PREFIX, function ( $WPSF ) use ( $settings ) {
			if ( isset( $settings['tab_id'] ) ) {
				$WPSF['sections'][] = $settings;
			} else {
				$WPSF[] = $settings;
			}

			return $WPSF;
		} );
	}

	public static function setDefault( $section, $field, $default ) {
		if ( ! isset( self::$defaults[$section] ) ) {
			self::$defaults[$section] = [
				$field => $default,
			];
		} else {
			self::$defaults[$section][$field] = $default;
		}
	}

	public static function getDefault( $section, $field ) {
		if (
			isset( self::$defaults[$section] ) && isset( self::$defaults[$section][$field] )
		) {
			return self::$defaults[$section][$field];
		}

		return null;
	}

	public static function getSetting( $section, $field ) {
		$option_group = RemoteAdsTxt\PREFIX . '_settings';
		$options      = \get_option( $option_group );
		$default      = self::getDefault( $section, $field );

		if ( isset( $options[ $section . '_' . $field ] ) ) {
			if ( \is_bool( $default ) ) {
				return \boolval( $options[$section . '_' . $field] );
			}

			return $options[ $section . '_' . $field ];
		}

		if ( self::getDefault( $section, $field ) ) {
			return self::getDefault( $section, $field );
		}

		return false;
	}

	public static function registerHandler( $key, $handler ) {
		self::$handlers[$key] = $handler;
	}

	public static function getHandler( $key ) {
		if ( ! \array_key_exists( $key, self::$handlers ) ) {
			throw new Exception( 'Attempted to access settings handler which is not registered.' );
		}

		return self::$handlers[$key];
	}
}
