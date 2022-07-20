<?php

namespace CUMULUS\RemoteAdsTxt;

use Exception;
use SplFileObject;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

class CacheFile {
	/**
	 * Basedir for cache file.
	 *
	 * @var string
	 */
	private $basedir;

	/**
	 * Filename for cache file.
	 *
	 * @var string
	 */
	private $filename;

	/**
	 * SplFileObject for cache file.
	 *
	 * @var SplFileObject
	 */
	private $file;

	public function __construct( $basedir, $filename = 'remote-ads-txt-cache.txt' ) {
		if ( ! $basedir ) {
			throw new Exception( 'Attempted to create a cache file without a base path.' );
		}

		$this->basedir  = $basedir;
		$this->filename = $filename;

		if ( \mb_strpos( $this->basedir, '..' ) !== false ) {
			throw new Exception( 'Cache basedir must be absolute.' );
		}

		$wp_uploads_path = \realpath( \wp_upload_dir()['basedir'] );

		if ( \mb_strpos( $this->basedir, $wp_uploads_path ) !== 0 ) {
			throw new Exception( 'Attempted to create cache file outside of Wordpress uploads dir.' );
		}

		if ( ! \is_dir( $basedir ) ) {
			if ( ! \wp_mkdir_p( $basedir ) ) {
				throw new Exception( 'Cache basedir does not exists and could not be created.' );
			}
		}

		if ( \file_exists( $this->getPath() ) && ! \is_writable( $this->getPath() ) ) {
			throw new Exception( 'Cache file exists but is not writable.' );
		}
	}

	public function delete() {
		if ( $this->exists() ) {
			$this->close();
			\unlink( $this->getPath() );
		}
	}

	public function exists() {
		return \file_exists( $this->getPath() );
	}

	public function getPath() {
		return $this->basedir . '/' . $this->filename;
	}

	public function getModType() {
		if ( $this->exists() ) {
			return \mime_content_type( $this->getPath() );
		}

		return null;
	}

	public function getMTime() {
		if ( $this->exists() ) {
			return \filemtime( $this->getPath() );
		}

		return null;
	}

	public function touch() {
		if ( $this->file ) {
			$this->close();
		}
		\touch( $this->getPath() );
	}

	public function truncate() {
		if ( ! $this->exists() ) {
			return;
		}

		$this->openFileForWriting();
		$this->file->fflush();
		$this->file->ftruncate( 0 );
		$this->file->rewind();
		$this->close();
	}

	public function write( $content ) {
		$this->openFileForWriting();
		$this->file->fwrite( $content );
		$this->file->fflush();
		$this->close();
	}

	public function output( $pad_expire = 21600 ) {
		$this->openFileForReading();

		$msg = '# Retrieved from cache / MTime: ' .
			\wp_date( 'c', $this->file->getMTime() ) .
			\PHP_EOL . \PHP_EOL;

		$expires = $this->file->getMTime() + $pad_expire;
		$this->outputHeaders( array(
			'type'          => \mime_content_type( $this->getPath() ),
			'length'        => (int) $this->file->getSize() + (int) \mb_strlen( $msg ),
			'last_modified' => $this->file->getMTime(),
			'expires'       => $expires,
		) );

		echo $msg;

		$this->file->fpassthru();
		$this->close();
	}

	/**
	 * @param array $options['type']
	 * @param array $options['length']
	 * @param array $options['expires']
	 * @param array $options['last_modified']
	 */
	public function outputHeaders( $options ) {
		$defaults = array(
			'type' => 'text/plain',
		);
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

	private function close() {
		$this->unlock();
		$this->file = null;
	}

	private function lock() {
		if ( $this->file ) {
			if ( ! $this->file->flock( \LOCK_EX ) ) {
				throw new FileLockException( 'Could not obtain lock on cache file.' );
			}
		}
	}

	private function unlock() {
		if ( $this->file ) {
			$this->file->flock( \LOCK_UN );
		}
	}

	private function openFileForReading() {
		if ( ! $this->exists() ) {
			return false;
		}

		$this->close();
		$this->file = new SplFileObject( $this->getPath(), 'r' );
	}

	private function openFileForWriting() {
		$this->close();
		$this->file = new SplFileObject( $this->getPath(), 'c+' );
		$this->lock();
	}
}
