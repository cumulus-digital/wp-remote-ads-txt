<?php

declare( strict_types=1 );

use Isolated\Symfony\Component\Finder\Finder;

$wp_classes   = \json_decode( \file_get_contents( 'vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-classes.json' ), true );
$wp_functions = \json_decode( \file_get_contents( 'vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-functions.json' ), true );
$wp_constants = \json_decode( \file_get_contents( 'vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-constants.json' ), true );

return [
	// The prefix configuration. If a non-null value is used, a random prefix
	// will be generated instead.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
	'prefix' => 'CMLS_RemoteAdsTxt_Vendors',

	// The base output directory for the prefixed files.
	// This will be overridden by the 'output-dir' command line option if present.
	'output-dir' => 'libs',

	// By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	// directory. You can however define which files should be scoped by defining a collection of Finders in the
	// following configuration key.
	//
	// This configuration entry is completely ignored when using Box.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
	'finders' => [
		Finder::create()
			->files()
			->ignoreVCS( true )
			->exclude( [
				'doc',
				'test',
				'test_old',
				'tests',
				'Tests',
				'vendor-bin',
			] )
			->in( [
				'vendor/jamesckemp/wordpress-settings-framework',
			] )
			->append( ['vendor/autoload.php'] ),
		Finder::create()
			->append( [
				'composer.json',
			] ),
	],

	// List of excluded files, i.e. files for which the content will be left untouched.
	// Paths are relative to the configuration file unless if they are already absolute
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
	'exclude-files' => [
		// 'src/an-excluded-file.php',
	],

	// When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	// original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	// support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	// heart contents.
	//
	// For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
	'patchers' => [
		static function ( string $filePath, string $prefix, string $contents ): string {
			// Change the contents here.

			// remove class aliases
			$contents = \str_replace( "\class_alias('{$prefix}", "// class_alias('{$prefix}", $contents );

			return $contents;
		},
	],

	// List of symbols to consider internal i.e. to leave untouched.
	//
	// For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
	'exclude-namespaces' => [
		'~^CUMULUS\\\\Wordpress\\\\RemoteAdsTxt$~',
	],
	'exclude-classes'   => $wp_classes,
	'exclude-functions' => $wp_functions,
	'exclude-constants' => $wp_constants,
];
