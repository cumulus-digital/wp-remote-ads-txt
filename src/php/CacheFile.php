<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt;

use Exception;
use SplFileObject;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

class CacheFile
{

	/**
		 * Path for cache file.
		 *
		 * @var string
		 */
	private $path;

	/**
	 * Time until cache is stale
	 *
	 * @var int
	 */
	private $expire;

	/**
	 * SplFileObject for cache file.
	 *
	 * @var SplFileObject
	 */
	private $file;

	public function __construct( $filename, $expire = 21600 )
	{
		if ( ! \is_dir( CACHEDIR ) ) {
			if ( ! \wp_mkdir_p( CACHEDIR ) ) {
				throw new Exception( 'Failed to create cache dir!' );
			}
		}

		$this->setFilename( \sanitize_file_name( $filename ) );
		$this->setExpiration( $expire );
	}

	public function setFilename( $filename )
	{
		if ( ! $filename ) {
			throw new Exception( 'You must provide a filename for cache files.' );
		}
		$this->path = CACHEDIR . '/' . $filename;
	}

	public function setExpiration( $expire )
	{
		$this->expire = $expire;
	}

	public function delete()
	{
		if ( $this->exists() ) {
			$this->close();
			\unlink( $this->getPath() );
		}
	}

	public function exists()
	{
		return \file_exists( $this->getPath() );
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getModType()
	{
		if ( $this->exists() ) {
			return \mime_content_type( $this->getPath() );
		}

		return null;
	}

	public function getMTime()
	{
		if ( $this->exists() ) {
			return \filemtime( $this->getPath() );
		}

		return null;
	}

	public function touch()
	{
		if ( $this->file ) {
			$this->close();
		}
		\touch( $this->getPath() );
	}

	public function truncate()
	{
		if ( ! $this->exists() ) {
			return;
		}

		$this->openFileForWriting();
		$this->file->fflush();
		$this->file->ftruncate( 0 );
		$this->file->rewind();
		$this->close();
	}

	public function write( $content )
	{
		$this->openFileForWriting();
		$this->file->fwrite( $content );
		$this->file->fflush();
		$this->close();
	}

	public function output()
	{
		$this->openFileForReading();

		$msg = '# Retrieved from cache / MTime: ' .
			\wp_date( 'c', $this->file->getMTime() ) .
			\PHP_EOL . \PHP_EOL;

		$expires = $this->file->getMTime() + $this->expire;
		$this->outputHeaders( [
			'type'          => \mime_content_type( $this->getPath() ),
			'length'        => (int) $this->file->getSize() + (int) \mb_strlen( $msg ),
			'last_modified' => $this->file->getMTime(),
			'expires'       => $expires,
		] );

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
	public function outputHeaders( $options )
	{
		$defaults = [
			'type' => 'text/plain',
		];
		$settings = \array_merge( $defaults, $options );

		\header( 'Content-Type: ' . $settings['type'] );

		if ( isset( $settings['expires'] ) ) {
			\header( 'Cache-Control: public, max-age: ' . ( $settings['expires'] - \time() ) . ', no-transform' );
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

	private function close()
	{
		$this->unlock();
		$this->file = null;
	}

	private function lock()
	{
		if ( $this->file ) {
			if ( ! $this->file->flock( \LOCK_EX ) ) {
				throw new Exceptions\FileLockException( 'Could not obtain lock on cache file.' );
			}
		}
	}

	private function unlock()
	{
		if ( $this->file ) {
			$this->file->flock( \LOCK_UN );
		}
	}

	private function openFileForReading()
	{
		if ( ! $this->exists() ) {
			return false;
		}

		$this->close();
		$this->file = new SplFileObject( $this->getPath(), 'r' );
	}

	private function openFileForWriting()
	{
		$this->close();
		$this->file = new SplFileObject( $this->getPath(), 'c+' );
		$this->lock();
	}

	public function isFresh()
	{
		if ( \time() - $this->getMTime() < $this->expire ) {
			return true;
		}

		return false;
	}
}
