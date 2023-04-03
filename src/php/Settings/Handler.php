<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt\Settings;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use CUMULUS\Wordpress\RemoteAdsTxt;

abstract class Handler {

	protected $tab;

	protected $section;

	protected function __construct() {
		\add_filter( RemoteAdsTxt\PREFIX . '_settings_validate', [ $this, 'validateSettings' ] );
	}

	public function getSectionId() {
		$section = $this->section;

		if ( $this->tab ) {
			$section = $this->tab . '_' . $this->section;
		}

		return $section;
	}

	public function setDefault( $field, $default ) {
		Container::setDefault(
			$this->getSectionId(),
			$field,
			$default
		);
	}

	public function getDefault( $field ) {
		return Container::getDefault(
			$this->getSectionId(),
			$field
		);
	}

	public function getSetting( $field ) {
		return Container::getSetting( $this->getSectionId(), $field );
	}

	public function validateSettings( $input ) {
		return $input;
	}

	public function generateURLField( $args ) {
		$url = Container::getHandler( 'ads-txt' )->getSetting( 'url' );

		if ( $url && ! RemoteAdsTxt\RemoteFile::isValidRemote( $url ) ) {
			$args['class'] .= ' error';
			$args['desc'] = '
				<p class="notice-error">
					URL is not valid!
				</p>
			' . $args['desc'];
		}

		$args['value'] = \esc_attr( \stripslashes( $args['value'] ) );

		echo \strtr(
			'<input type="url" name="%name%" id="%id%" value="%value%" title="%title%" placeholder="%placeholder%" pattern="%pattern%" %required% class="regular-text remote-url %class%" />',
			\array_map( 'esc_attr', [
				'%name%'        => $args['name'],
				'%id%'          => $args['id'],
				'%value%'       => $args['value'],
				'%title%'       => isset( $args['input_title'] ) ? $args['input_title'] : '',
				'%placeholder%' => isset( $args['placeholder'] ) ? $args['placeholder'] : '',
				'%pattern%'     => isset( $args['pattern'] ) ? $args['pattern'] : '',
				'%required%'    => isset( $args['required'] ) ? 'required' : '',
				'%class%'       => $args['class'],
			] )
		);

		//echo '<input type="url" name="' . \esc_attr( $args['name'] ) . '" id="' . \esc_attr( $args['id'] ) . '" value="' . \esc_attr( $args['value'] ) . '" placeholder="' . \esc_attr( $args['placeholder'] ) . '" pattern="' . \esc_attr( $args['pattern'] ) . '" class="regular-text ' . \esc_attr( $args['class'] ) . '" />';

		Container::wpsf()->generate_description( $args['desc'] );
	}

	public function outputCacheRefresher() {
		try {
			$remote = RemoteAdsTxt\REMOTES::getHandler( $this->section );

			if ( $remote ) {
				$mtime = $remote->getCacheMTime();

				?>
				<div class="cache-message ads-txt">
					<div class="cache-time">
						<?php if ( $mtime ): ?>
							Last fetched: <?php echo \wp_date( 'F j, Y, g:i a', $mtime ); ?>
						<?php else: ?>
							Cache does not yet exist.
						<?php endif; ?>
					</div>
					<div>
						<a href="#" class="button button-action cmls-remote-ads-rebuild-cache" data-handle="<?php echo $this->section; ?>">
							<span class="loader"></span>
							Rebuild Cache
						</a>
					</div>
				</div>
				<?php
			}
		} catch ( \Throwable $e ) {
			echo \esc_html( $e->getMessage() );
		}
	}
}
