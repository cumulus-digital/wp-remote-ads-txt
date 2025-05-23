<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt\Settings\Sections;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Container;
use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Handler;

class AppAdsTxt extends Handler
{

	protected $section = 'app-ads-txt';

	public function __construct()
	{
		$this->setDefault( 'enabled', false );
		$this->setDefault( 'url', '' );

		Container::addSettingsSection(
			[
				'section_id'    => $this->section,
				'section_title' => '',
				'section_order' => 3,
				'fields'        => [
					[
						'id'          => 'url',
						'type'        => '',
						'title'       => 'app-ads.txt URL',
						'placeholder' => 'https://example.com/app-ads.txt',
						'pattern'     => '^[hH][tT][tT][pP][sS]?://.*$',
						'input_title' => 'Must be a valid URL in the form "http(s)://"',
						'desc'        => '
							<p class="shortdesc">
								This field must contain a valid URL to an <strong>app-ads.txt</strong>, served as
								plain/text, before requests will be managed by the plugin.
							</p>
							<p>
								<small>Example: https://example.com/app-ads.txt</small>
							</p>
						',
						'type'   => 'custom',
						'output' => [$this, 'generateURLField'],
					],
					[
						'id'     => 'reset',
						'type'   => 'custom',
						'title'  => '',
						'output' => [$this, 'outputCacheRefresher'],
					],
					[
						'id'    => 'additions',
						'type'  => 'textarea',
						'title' => 'Additional app-ads.txt entries',
						'desc'  => '
							<p class="shortdesc">
								Additional entries to be appended to the app-ads.txt file.
							</p>
						',
					],
				],
			]
		);
	}
}
