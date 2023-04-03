<?php

namespace CUMULUS\Wordpress\RemoteAdsTxt\Settings\Sections;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Container;
use CUMULUS\Wordpress\RemoteAdsTxt\Settings\Handler;

class Extras extends Handler {

	protected $section = 'extras';

	public function __construct() {
		$this->setDefault( 'cleanup', false );

		Container::addSettingsSection(
			[
				'section_id'    => $this->section,
				'section_title' => 'Additional Settings',
				'section_order' => \PHP_INT_MAX,
				'fields'        => [
					[
						'id'      => 'cleanup',
						'type'    => 'toggle',
						'title'   => 'Cleanup',
						'desc'    => 'Delete settings after deactivating',
						'default' => $this->getDefault( 'cleanup' ),
					],
					[
						'id'     => 'space-' . \random_int( 10, 9999 ),
						'type'   => 'custom',
						'title'  => '',
						'output' => '&nbsp;',
					],
					[
						'id'     => 'rebuild_cron',
						'type'   => 'custom',
						'title'  => 'Rebuild Cron Schedule',
						'output' => '
							<a href="#" class="button button-action cmls-remote-ads-rebuild-cron">
								<span class="loader"></span>
								Rebuild Cron
							</a>
							<small>Manually delete and recreate the schedule.</small>
						',
					],
				],
			]
		);
	}
}
