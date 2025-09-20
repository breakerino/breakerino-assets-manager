<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Assets Manager > Constants
 * ------------------------------------------------------------------------------
 * @created     02/11/2023
 * @updated     10/11/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */
namespace Breakerino\AssetsManager;

interface Constants {
	public const FILE_META_REGEX = '/\/\*([^*]|[\r\n]|(\*+([^*\/]|[\r\n])))*\*+\//';

	public const ASSET_TYPES =  [
		'scss' => [
			'enqueue' => false,
			'compile' => true
		],
		'css' => [
			'enqueue' => 'style',
			'compile' => false
		],
		'js' => [
			'enqueue' => 'script',
			'compile' => false
		]
	];

	public const SUPPORTED_ASSET_META = [
		'id',
		'name',
		'group',
		'conditions',
		'type',
		'version',
		'created',
		'updated',
		'active',
		'position',
		'dependencies',
		// 'priority'
		// 'strategy
	];

	public const REQUIRED_ASSET_META = [
		'id',
		'name',
	];

	public const ASSETS_OPTION_NAME = 'wa_assets';

	public const SUPPORTED_ASSET_CONDITIONS = [];
}
