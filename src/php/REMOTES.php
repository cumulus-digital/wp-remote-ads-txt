<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

class REMOTES {

	private static $handlers = [];

	public static function setHandler( $handle, $handler ) {
		self::$handlers[$handle] = $handler;
	}

	public static function getHandler( $handle ) {
		if ( \array_key_exists( $handle, self::$handlers ) ) {
			return self::$handlers[$handle];
		}

		return false;
	}

	public static function getAll() {
		return self::$handlers;
	}
}
