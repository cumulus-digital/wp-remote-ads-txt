<?php

namespace CUMULUS\RemoteAdsTxt;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use Exception;
use Throwable;

class RemoteAdsTxt {
	public const OPTION_KEY = 'remote-ads-txt';

	public const CRON_TIMER = 'hourly';

	public const CRON_EVENT = 'remote-ads-txt--update-cache';

	public const AJAX_REBUILD_EVENT = 'remote-ads-txt-rebuild';

	/**
	 * @var RemoteAdsTxt
	 */
	private static $instance;

	/**
	 * Base dir to create cache in.
	 *
	 * @var string
	 */
	private $CACHE_FILE_BASEDIR = '';

	/**
	 * File name for cache.
	 *
	 * @var string
	 */
	private $CACHE_FILE_NAME = 'remote-ads-txt-cache.txt';

	/**
	 * @var CacheFile
	 */
	private $cache;

	private function __construct() {
		// Build the cache dir from the WP uploads dir
		$this->CACHE_FILE_BASEDIR = \trailingslashit(
			\realpath( \wp_upload_dir()['basedir'] )
		);

		// Handle AJAX cache rebuild requests
		\add_action( 'wp_ajax_' . self::AJAX_REBUILD_EVENT, array( $this, 'rebuildCache' ) );

		// Register our cron event
		\add_filter( 'remote-ads-txt--on-activation', function ( $callbacks ) {
			$callbacks[] = array( $this, 'registerCronSchedule' );

			return $callbacks;
		} );

		// Add our chron event
		\add_action( self::CRON_EVENT, array( $this, 'updateCache' ) );

		// Remove cron on deactivation
		\add_filter( 'remote-ads-txt--on-deactivation', function ( $callbacks ) {
			$callbacks[] = array( $this, 'removeCronSchedule' );

			return $callbacks;
		} );

		// Delete cache on deactivation
		\add_filter( 'remote-ads-txt--on-deactivation', function ( $callbacks ) {
			$callbacks[] = array( $this->cache, 'delete' );

			return $callbacks;
		} );

		$options = $this->getOptions();

		if ( $this->isValidRemote( $options['url'] ) ) {
			$this->cache = new CacheFile( $this->CACHE_FILE_BASEDIR, $this->CACHE_FILE_NAME );
		}
	}

	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new RemoteAdsTxt();
		}

		return self::$instance;
	}

	public function addThirtyMinuteCronSchedule( $schedules ) {
		if ( ! \array_key_exists( self::CRON_TIMER, $schedules ) ) {
			$schedules[ self::CRON_TIMER ] = array(
				'interval' => 30 * 60,
				'display'  => 'Every 30 minutes',
			);
		}

		return $schedules;
	}

	public function registerCronSchedule() {
		if ( ! \wp_next_scheduled( self::CRON_EVENT ) ) {
			\wp_schedule_event( \time(), self::CRON_TIMER, self::CRON_EVENT );
			// $this->updateCache();
		}
	}

	public function removeCronSchedule() {
		\wp_clear_scheduled_hook( self::CRON_EVENT );
	}

	public function rebuildCache() {
		try {
			$this->cache->delete();
			$this->updateCache();

			\wp_send_json_success(
				\wp_date( 'F j, Y, g:i a', $this->cache->getMTime() )
			);
		} catch ( Throwable $e ) {
			\wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	public function updateCache() {
		$options = $this->getOptions();

		if ( ! $this->isValidRemote( $options['url'] ) ) {
			return;
		}

		if ( $options['timeout'] ) {
			if ( $this->cache->exists() ) {
				// Check that cache is still fresh
				if ( \time() - $this->cache->getMTime() < $options['timeout'] ) {
					// Cache is fresh

					return 'Cache is fresh.';
				}

				// Cache might be stale, check if remote is fresher.
				$head = \wp_remote_head(
					$options['url'],
					array( 'Accept-Encoding' => 'identity' )
				);

				if ( ! \is_wp_error( $head ) ) {
					$last_modified = \intval( \wp_remote_retrieve_header( $head, 'last-modified' ) );

					if ( $last_modified && $last_modified > 1 && $last_modified <= $this->cache->getMTime() ) {
						// Remote is not fresher, update cache mtime
						$this->cache->touch();

						return 'Reached timeout, but remote has not changed. Cache mtime has been updated.';
					}
				}
			}

			// Cache is stale or does not exist.
			$content = $this->getRemote( $options['url'] );
			if ( $content ) {
				$this->cache->truncate();
				$this->cache->write( $content );

				return 'Cache was updated ' . \wp_date( 'c' );
			}
		}

		// Cache updating is disabled
	}

	/**
	 * Fetch remote and return its contents.
	 *
	 * @param mixed $url
	 *
	 * @throws RemoteFetchException
	 * @throws MimeTypeException
	 *
	 * @return string
	 */
	public function getRemote( $url = null ) {
		if ( ! $url ) {
			$options = $this->getOptions();
			$url     = $options['url'];
		}

		if ( $this->isValidRemote( $url ) ) {
			$response = \wp_remote_get( $url );

			if ( ! $response || \is_wp_error( $response ) ) {
				throw new RemoteFetchException( $response->get_error_message() );
			}

			$content_type = \wp_remote_retrieve_header( $response, 'content-type' );

			if ( ! $content_type || \mb_strpos( $content_type, 'text/plain' ) === false ) {
				throw new MimeTypeException( 'Remote mime type was not text/plain' );
			}

			return \wp_remote_retrieve_body( $response );
		}

		return null;
	}

	public function interceptRequest() {
		$path = \wp_parse_url( $_SERVER['REQUEST_URI'], \PHP_URL_PATH );

		if ( $path !== '/ads.txt' ) {
			return;
		}

		$options = $this->getOptions();

		if ( ! $this->isValidRemote( $options['url'] ) ) {
			throw new Exception( 'Remote ads.txt URL is invalid.' );
		}

		if ( ! $options['timeout'] ) {
			// Timeout is set to 0, bypass cache.

			try {
				$content = $this->getRemote();

				if ( $content ) {
					$content = '# Cache is bypassed.' . \PHP_EOL . \PHP_EOL . $content;

					\http_response_code( 200 );
					$this->cache->outputHeaders( array(
						'type'          => 'text/plain',
						'length'        => \mb_strlen( $content ),
						'last_modified' => \time(),
						'expires'       => \time() + $options['timeout'],
					) );

					echo $content;

					// \wp_die( null, null, array( 'response' => 200 ) );
					exit;
				}

				throw new Exception( 'Remote was empty.' );
			} catch ( Throwable $e ) {
				$msg = '# Failed to retreive remote.' . \PHP_EOL;
				$msg = '# ' . $e->getMessage() . \PHP_EOL;

				\http_response_code( 503 );
				$this->cache->outputHeaders( array(
					'type'          => 'text/plain',
					'length'        => \mb_strlen( $msg ),
					'last_modified' => \time(),
					'expires'       => \time() + 120,
				) );
				echo $msg;

				exit;
			}

			return;
		}

		// We only call updateCache in the request phase if the cache
		// does not exist, otherwise the cache is only updated by cron.
		if ( ! $this->cache->exists() ) {
			$this->updateCache();
		}

		\http_response_code( 200 );
		$this->cache->output( $options['timeout'] );

		// \wp_die( null, null, array( 'response' => 200 ) );
		exit;
	}

	public function log( $msg ) {
		if ( \defined( '\WP_DEBUG' ) && WP_DEBUG === true ) {
			\error_log( $msg );
		}
	}

	public static function getOptions() {
		$default = array(
			'url'     => false,
			'timeout' => 6 * 3600,
		);
		$options = \get_option( self::OPTION_KEY );

		if ( isset( $options['timeout'] ) ) {
			$options['timeout'] = \intval( $options['timeout'] );
		}

		return \array_merge( $default, $options );
	}

	public static function isValidRemote( $url = null ) {
		if ( ! $url ) {
			$options = self::getOptions();
			$url     = $options['url'];
		}

		return $url && \wp_parse_url( $url ) !== false;
	}

	public function getCacheMTime() {
		if ( $this->cache ) {
			return $this->cache->getMTime();
		}

		return null;
	}

	public function getCachePath() {
		if ( $this->cache ) {
			return $this->cache->getPath();
		}

		return null;
	}
}
