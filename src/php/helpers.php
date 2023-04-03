<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

/**
 * Namespace a given function or class name, given as a string.
 *
 * @param string $str
 * @param string $ns
 *
 * @return string
 */
function ns( $str, $ns = __NAMESPACE__ ) {
	return $ns . '\\' . $str;
}
