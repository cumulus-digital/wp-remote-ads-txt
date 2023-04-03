<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt\Settings\Sections;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Handler;
use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Container;

class AdsTxt extends Handler {

	protected $section = 'ads-txt';

	public function __construct() {
		parent::__construct();

		$this->setDefault( 'url', '' );

		Container::addSettingsSection(
			[
				'section_id'    => $this->section,
				'section_title' => '',
				'section_order' => 2,
				'fields'        => [
					[
						'id'          => 'url',
						'type'        => 'custom',
						'title'       => 'ads.txt URL',
						'placeholder' => 'https://example.com/ads.txt',
						'pattern'     => '^[hH][tT][tT][pP][sS]?://.*$',
						'input_title' => 'Must be a valid URL in the form "http(s)://"',
						'desc'        => '
							<p class="shortdesc">
								This field must contain a valid URL to an <strong>ads.txt</strong>, served as
								plain/text, before requests will be managed by the plugin.
							</p>
							<p>
								<small>Example: https://example.com/ads.txt</small>
							</p>
						',
						'output' => [$this, 'generateURLField'],
					],
					[
						'id'     => 'reset',
						'type'   => 'custom',
						'title'  => '',
						'output' => [$this, 'outputCacheRefresher'],
					],
				],
			]
		);
	}
}
