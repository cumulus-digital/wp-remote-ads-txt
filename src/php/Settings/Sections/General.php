<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt\Settings\Sections;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Container;
use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Handler;

class General extends Handler {

	protected $section = 'general';

	public function __construct() {
		$this->setDefault( 'timeout', 6 * 3600 );

		Container::addSettingsSection(
			[
				'section_id'          => $this->section,
				'section_title'       => '',
				'section_order'       => 1,
				'section_description' => '
					<p>
						Cache and serve remote ads.txt and app-ads.txt files.
					</p>
				',
				'fields' => [
					[
						'id'    => 'timeout',
						'type'  => 'select',
						'title' => 'Minimum Cache Time',
						'desc'  => '
						<p class="shortdesc">
							Cache freshness is tested once an hour, <em>but not <strong>on</strong> the hour</em>.
							Updates may be delayed for a number of reasons including lack of traffic
							and the semi-random nature of Wordpress time-based events (wp-cron).
						</p>
						',
						'choices' => [
							'' . 0         => 'Bypass Cache',
							'' . 1  * 3600 => '1 Hour',
							'' . 2  * 3600 => '2 Hours',
							'' . 3  * 3600 => '3 Hours',
							'' . 4  * 3600 => '4 Hours',
							'' . 5  * 3600 => '5 Hours',
							'' . 6  * 3600 => '6 Hours',
							'' . 24 * 3600 => '24 Hours',
						],
						'default' => $this->getDefault( 'timeout' ),
					],
				],
			]
		);
	}
}
