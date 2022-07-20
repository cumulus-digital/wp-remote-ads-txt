<?php

namespace CUMULUS\RemoteAdsTxt;

use Throwable;
use Vendors\vena\WordpressSettingsBuilder\Builder as SettingsBuilder;

\defined( 'ABSPATH' ) || exit( 'No direct access allowed.' );

\register_setting(
	'remote-ads-txt',
	RemoteAdsTxt::OPTION_KEY,
	array(
		'default' => array(
			'url'     => null,
			'timeout' => 6 * 3600,
		),
	)
);

$settings = new SettingsBuilder(
	'Remote Ads.txt',
	'Remote Ads.txt',
	'install_plugins',
	'remote-ads-txt',
	99999999
);

$section = $settings->addSection(
	'RemoteAdsTxt',
	null
);

// Handle deactivation
\add_filter( 'remote-ads-txt--on-deactivation', function ( $callbacks ) {
	\delete_option( RemoteAdsTxt::OPTION_KEY );

	return $callbacks;
} );

$section->addField( array(
	'type' => 'html',
	'args' => array(
		'content' => '<p>Serve a cached remote ads.txt file as the ads.txt for this site.</p>',
	),
) );

$section->addField( array(
	'type'     => 'html',
	'callback' => function () {
		try {
			$cache_mtime = RemoteAdsTxt::getInstance()->getCacheMTime();
			if ( $cache_mtime ) {
				$cache_date = \wp_date(
					'F j, Y, g:i a',
					$cache_mtime
				);
			} else {
				$cache_date = 'Cache does not yet exist.';
			}

			echo '
				<div class="remote-ads-txt-last_updated" style="border: 1px solid #ccc; background-color: #eee; padding: .5em .75em; margin-bottom: 1em;">
					<span class="remote-ads-txt-status" style="margin-right: 1em">
						Cache last updated:
						<time
							class="remote-ads-txt-last_updated-date"
							datetime="' .
								\wp_date(
									'c',
									$cache_mtime
								) .
							'"
						>
							' . $cache_date . '
						</time>
					</span>
					<button class="remote-ads-txt-clear">Rebuild Cache</button>
				</div>
				<script>
				(function($, window, undefined) {
					var ajax_url = "' . \admin_url( 'admin-ajax.php' ) . '";
					var $container = $(".remote-ads-txt-last_updated");
					var $date = $container.find(".remote-ads-txt-last_updated-date");
					$(".remote-ads-txt-last_updated").click(function(e) {
						e.preventDefault();
						var $btn = $(this);
						$btn.attr("disabled", true);
						$date.html("Rebuilding&hellip;");
						$.ajax({
							url: ajax_url,
							cache: false,
							method: "post",
							data: { action: "' . RemoteAdsTxt::AJAX_REBUILD_EVENT . '" },
							error: function(xhr, status, error) {
								$(".remote-ads-txt-status")
									.html("<strong>Error!</strong> " + xhr.responseJSON.data);
								$container.css({borderColor: "red"});
								$btn.attr("disabled", false);
							},
							success: function(response) {
								$date.text(response.data);
								$btn.attr("disabled", false);
							}
						});
					});
				})(jQuery, window.self);
				</script>
			';
		} catch ( Throwable $e ) {
			$msg  = \esc_html( $e->getMessage() );
			$path = \esc_html( RemoteAdsTxt::getInstance()->getCachePath() );
			echo '
				<div style="border: 1px solid red; padding: .5em .75em; margin-bottom: .75em;">
					<p>' . $msg . '</p>
					<p>Cache file path: ' . $path . '
				</div>
			';
		}
	},
) );

$section->addField( array(
	'type'        => 'url',
	'id'          => 'remote-ads-txt/url',
	'title'       => 'Remote ads.txt URL',
	'option_name' => RemoteAdsTxt::OPTION_KEY . '[url]',
	'args'        => array(
		'placeholder' => 'https://example.com/ads.txt',
		'help'        => function () {
			$url_help = '
				<p>This field must contain a valid URL to an ads.txt, served as
				plain/text, before requests will be managed by the plugin.</p>
			';

			if ( ! RemoteAdsTxt::isValidRemote() ) {
				echo $url_help;

				return;
			}

			try {
				$remote_test = RemoteAdsTxt::getInstance()->getRemote();
				if ( $remote_test ) {
					$url_help = null;
				}
			} catch ( Throwable $e ) {
				$url_help .= '<p><strong>Error received:</strong> ' . $e->getMessage();
			}

			echo $url_help;
		},
	),
) );

$section->addField( array(
	'type'        => 'select',
	'id'          => 'remote-ads-txt/timeout',
	'title'       => 'Minimum Cache Time',
	'option_name' => RemoteAdsTxt::OPTION_KEY . '[timeout]',
	'args'        => array(
		'options' => array(
			array(
				'title' => 'Disable Cache',
				'value' => '' . 0,
			),
			array(
				'title' => '1 Hour',
				'value' => '' . 1 * 3600,
			),
			array(
				'title' => '2 Hours',
				'value' => '' . 2 * 3600,
			),
			array(
				'title' => '3 Hours',
				'value' => '' . 3 * 3600,
			),
			array(
				'title' => '4 Hours',
				'value' => '' . 4 * 3600,
			),
			array(
				'title' => '5 Hours',
				'value' => '' . 5 * 3600,
			),
			array(
				'title' => '6 Hours',
				'value' => '' . 6 * 3600,
			),
		),
		'help' => '
			Cache freshness is tested regularly, but <em>not on the hour</em>.
			Updates may be delayed for a number of factors including traffic
			and the semi-random nature of Wordpress time-based events (wp-cron).
			If cache is disabled, existing cache will be bypassed but not deleted.',
	),
	'default' => '' . 6 * 3600,
) );
