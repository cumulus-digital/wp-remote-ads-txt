<?php
/*
* PHP-CS-Fixer project configuration
*/
$config = new PhpCsFixer\Config();
require_once __DIR__ . '/vendor/cumulus-digital/wp-php-cs-fixer/loader.php';

// Load WP core classes/functions/constants for qualifying
$wp_classes   = \json_decode( \file_get_contents( __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-classes.json' ), true );
$wp_functions = \json_decode( \file_get_contents( __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-functions.json' ), true );
$wp_constants = \json_decode( \file_get_contents( __DIR__ . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/exclude-wordpress-constants.json' ), true );
$NFI_includes = \array_filter( \array_merge(
	['@compiler_optimized'],
	['@internal'],
	$wp_classes,
	$wp_functions,
	$wp_constants
) );

return $config
	->registerCustomFixers( [
		new WeDevs\Fixer\SpaceInsideParenthesisFixer(),
		new WeDevs\Fixer\BlankLineAfterClassOpeningFixer(),
	] )
	->setRiskyAllowed( true )
	->setIndent( "\t" )
	->setRules( \array_merge(
		WeDevs\Fixer\Fixer::rules(),
		[
			'@PSR2' => true,
			// Each element of an array must be indented exactly once.
			'array_indentation' => true,
			// Replace non multibyte-safe functions with corresponding mb function.
			'mb_str_functions' => true,
			// Add leading `\` before constant invocation of internal constant to speed up resolving. Constant name match is case-sensitive, except for `null`, `false` and `true`.
			'native_constant_invocation' => true,
			// Add leading `\` before function invocation to speed up resolving.
			'native_function_invocation' => ['strict' => false, 'include' => $NFI_includes],
			// Master language constructs shall be used instead of aliases.
			'no_alias_language_construct_call' => true,
			// PHP single-line arrays should not have trailing comma.
			'no_trailing_comma_in_singleline_array' => true,
			// Remove trailing whitespace at the end of blank lines.
			'no_whitespace_in_blank_line' => true,
			// Logical NOT operators (`!`) should have leading and trailing whitespaces.
			'not_operator_with_space' => true,
			// Standardize spaces around ternary operator.
			'ternary_operator_spaces' => true,
			// Multi-line arrays, arguments list and parameters list must have a trailing comma.
			'trailing_comma_in_multiline' => true,
			// Replace control structure alternative syntax to use braces.
			'no_alternative_syntax' => false,
		],
	) );
