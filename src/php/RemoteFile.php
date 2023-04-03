<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt;

use CUMULUS\Wordpress\RemoteAdsTxt\Exceptions\RemoteFetchException;
use Exception;
use Throwable;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

require_once BASEDIR . '/src/php/CacheFile.php';

class RemoteFile {

	/**
	 * Cache file handle
	 *
	 * @var string
	 */
	private $handle;

	/**
	 * Cache storage
	 *
	 * @var CacheFile
	 */
	private $cache;

	/**
	 * Cache timeout
	 *
	 * @var int
	 */
	private $cache_timeout;

	/**
	 * Remote URL
	 *
	 * @var string
	 */
	private $remote_url;

	/**
	 * Intercept path
	 *
	 * @var string
	 */
	private $intercept_path;

	/**
	 * @param string $handle         Registered name for later retrieval
	 * @param string $remote_url     URL of the remote file
	 * @param string $intercept_path Path to intercept on this server to return the remote file contents
	 * @param int    $cache_timeout  How long to hold onto cache
	 * @param string $cache_path     Path to cache file for this remote
	 */
	public function __construct( $handle, $remote_url, $intercept_path, $cache_timeout, $cache_path ) {
		$this->handle = $handle;

		if ( ! $this->isValidRemote( $remote_url ) ) {
			throw new Exception( 'URL is not valid.' );
		}

		$this->intercept_path = $intercept_path;
		$this->remote_url     = $remote_url;
		$this->cache_timeout  = $cache_timeout;

		$this->cache = new CacheFile( $cache_path, $this->cache_timeout );

		// Register AJAX rebuild handler
		\add_action( 'wp_ajax_' . PREFIX . '-' . $this->handle . '-rebuild_cache', [$this, 'handleAjaxRebuild'] );

		// Attempt to intercept right away
		$this->interceptRequest();
	}

	public static function isValidRemote( $url ) {
		return $url && \filter_var( $url, \FILTER_VALIDATE_URL ) && \wp_parse_url( $url ) !== false;
	}

	public function getHandle() {
		return $this->handle;
	}

	public function handleAjaxRebuild() {
		$nonce = \filter_input( \INPUT_POST, 'nonce' );

		if ( empty( $nonce ) || ! \wp_verify_nonce( $nonce, PREFIX ) ) {
			\wp_die( 'Invalid action!' );

			return;
		}
		$this->rebuildCache();
	}

	public function rebuildCache() {
		if ( ! $this->cache_timeout ) {
			return \wp_send_json_error( [
				'msg'   => 'Cache is bypassed and will not update.',
				'nonce' => \wp_create_nonce( PREFIX ),
			], 200 );
		}

		try {
			$this->cache->delete();
			$refresh = $this->updateCache();

			if ( $refresh ) {
				\wp_send_json_success(
					[
						'msg'   => 'Last fetched: ' . \wp_date( 'F j, Y, g:i a', $this->cache->getMTime() ),
						'nonce' => \wp_create_nonce( PREFIX ),
					]
				);
			} else {
				throw new RemoteFetchException( $refresh );
			}
		} catch ( Throwable $e ) {
			\wp_send_json_error( [
				'msg'   => $e->getMessage(),
				'nonce' => \wp_create_nonce( PREFIX ),
			], 500 );
		}
	}

	/**
	 * Validate a remote by actually attempting to retrieve it.
	 */
	public static function validateRemote( $url ) {
		if ( ! self::isValidRemote( $url ) ) {
			throw new Exception( 'URL is invalid.' );
		}

		$response = \wp_remote_get( $url );

		if ( ! $response || \is_wp_error( $response ) ) {
			throw new Exceptions\RemoteFetchException( $response->get_error_message() );
		}

		$content_type = \wp_remote_retrieve_header( $response, 'content-type' );

		if ( ! $content_type || \mb_strpos( $content_type, 'text/plain' ) === false ) {
			throw new Exceptions\MimeTypeException( 'Remote mime type was not text/plain' );
		}

		return $response;
	}

	public function updateCache() {
		if ( ! $this->cache_timeout ) {
			return;
		}

		if ( ! $this->isValidRemote( $this->remote_url ) ) {
			return;
		}

		if ( $this->cache->exists() ) {
			// Check if cache is still fresh
			if ( $this->cache->isFresh() ) {
				return 'Cache is fresh';
			}

			// Cache might be stale, check if remote is fresher
			$head = \wp_remote_head(
				$this->remote_url,
				['Accept-Encoding' => 'identity']
			);

			if ( ! \is_wp_error( $head ) ) {
				$last_modified = \intval( \wp_remote_retrieve_header( $head, 'last-modified' ) );

				if ( $last_modified && $last_modified > 1 && $last_modified <= $this->cache->getMTime() ) {
					$this->cache->touch();

					return 'Reached timeout, but remote has not changed. Cache mtime has been updated.';
				}
			}
		}

		// Cache is stale or does not exist
		$content = $this->getRemote();

		if ( $content ) {
			$this->cache->truncate();
			$this->cache->write( $content );

			return 'Cache was updated ' . \wp_date( 'c' );
		}
	}

	public function getCacheMTime() {
		if ( $this->cache ) {
			return $this->cache->getMTime();
		}

		return false;
	}

	public function getRemote() {
		if ( ! $this->remote_url ) {
			throw new Exception( 'Attempted to fetch remote without a URL!' );
		}

		$response = $this->validateRemote( $this->remote_url );

		if ( $response ) {
			return \wp_remote_retrieve_body( $response );
		}

		return null;
	}

	/**
	 * @param array $options['type']
	 * @param array $options['length']
	 * @param array $options['expires']
	 * @param array $options['last_modified']
	 */
	public function outputHeaders( $options ) {
		$defaults = [
			'type' => 'text/plain',
		];
		$settings = \array_merge( $defaults, $options );

		\header( 'Content-Type: ' . $settings['type'] );

		if ( isset( $settings['expires'] ) ) {
			\header( 'Cache-Control: public, max-age: ' . ( $settings['expires'] - \time() ) . ', no-transform'  );
			\header( 'Expires: ' . \gmdate( 'D, d M Y H:i:s T', $settings['expires'] ) );
		} else {
			\header( 'Cache-Control: public, no-transform' );
		}

		if ( isset( $settings['last_modified'] ) ) {
			\header( 'Last-Modified: ' . \date( 'D, d M Y H:i:s T', $settings['last_modified'] ) );
		}

		if ( isset( $settings['length'] ) ) {
			\header( 'Content-Length: ' . $settings['length'] );
		}

		if ( isset( $settings['etag'] ) ) {
			\header( 'Etag: ' . $settings['etag'] );
		}
	}

	public function interceptRequest() {
		try {
			if ( ! $this->intercept_path ) {
				return;
			}

			$path = \wp_parse_url( $_SERVER['REQUEST_URI'], \PHP_URL_PATH );

			if ( \mb_strtolower( $path ) !== \mb_strtolower( $this->intercept_path ) ) {
				return;
			}

			if ( ! $this->isValidRemote( $this->remote_url ) ) {
				throw new Exception( 'Remote URL is invalid!' );
			}

			if ( ! $this->cache_timeout ) {
				// Timeout is 0, bypass cache

				try {
					$content = $this->getRemote();

					if ( $content ) {
						$content = '# Cache is bypassed.' . \PHP_EOL . $content;

						\http_response_code( 200 );
						$this->cache->outputHeaders( [
							'type'          => 'text/plain',
							'length'        => \mb_strlen( $content ),
							'last_modified' => \time(),
							'expires'       => \time() + $this->cache_timeout,
						] );
						echo $content;

						exit;
					}

					$msg = '# Remote was empty!';
					\http_response_code( 503 );
					$this->cache->outputHeaders( [
						'type'          => 'text/plain',
						'length'        => \mb_strlen( $msg ),
						'last_modified' => \time(),
						'expires'       => \time() + 120,
					] );
					echo $msg;
					exit;
				} catch ( Throwable $e ) {
					\http_response_code( 503 );
					$msg = '# Failed to retrieve remote!' . \PHP_EOL;
					$this->cache->outputHeaders( [
						'type'          => 'text/plain',
						'length'        => \mb_strlen( $msg ),
						'last_modified' => \time(),
						'expires'       => \time() + 120,
					] );
					echo $msg;
					exit;
				}

				return;
			}

			// We only call updateCache in the request phase if the
			// cache does not exist, otherwise the cache is only updated
			// by the cron.
			if ( ! $this->cache->exists() ) {
				$this->updateCache();
			}

			\http_response_code( 200 );
			$this->cache->output();
			exit;
		} catch ( Throwable $e ) {
			if ( \is_admin() ) {
				\add_action( 'admin_init', function () use ( $e ) {
					\add_settings_error(
						PREFIX . '_settings',
						PREFIX . '/error',
						$e->getMessage(),
						'error'
					);
				} );
			}
		}
	}
}
