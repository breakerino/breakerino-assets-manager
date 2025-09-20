<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Assets Manager > Plugin
 * ------------------------------------------------------------------------------
 * @created     02/11/2023
 * @updated     29/05/2024
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\AssetsManager;

defined('ABSPATH') || exit;

use Breakerino\Core\Abstracts\Plugin as PluginBase;
use Breakerino\Core\Helpers as CoreHelpers;

class Plugin extends PluginBase implements Constants {
	public const PLUGIN_ID         	= 'breakerino-assets-manager';
	public const PLUGIN_NAME		 		= 'Breakerino Assets Manager';
	public const PLUGIN_VERSION     = '1.1.0';

	public const PLUGIN_HOOKS = [
		'global' => [
			[
				'type'		=> 'action',
				'hooks'		=> ['admin_post_wa_load_assets'],
				'callback' 	=> ['$this', 'handle_load_assets'],
				'priority' 	=> 10,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['admin_post_wa_compile_assets'],
				'callback' 	=> ['$this', 'handle_compile_assets'],
				'priority' 	=> 10,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['wp_enqueue_scripts', 'admin_enqueue_scripts'],
				'callback' 	=> ['$this', 'handle_enqueue_assets'],
				'priority' 	=> 20,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['wp_loaded'],
				'callback' 	=> ['$this', 'handle_auto_regenerate_assets'],
				'priority' 	=> 10,
				'args'		=> 0
			],
			[
				'type'		=> 'action',
				'hooks'		=> ['wp_loaded'],
				'callback' 	=> ['$this', 'handle_register_assets'],
				'priority' 	=> 20,
				'args'		=> 0
			],
		],
		'public' => [],
		'admin' => [],
	];

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	protected function get_assets_list() {
		return \get_option(self::ASSETS_OPTION_NAME);
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	protected function get_supported_asset_types() {
		return array_keys(self::ASSET_TYPES);
	}

	/**
	 * Undocumented function
	 *
	 * @param string $type
	 * @return array|null
	 */
	protected function get_asset_type($type) {
		return self::ASSET_TYPES[$type] ?? null;
	}

	protected function get_asset_id($item) {
		return sprintf('%s-%s-%s', $item['meta']['group'], $item['meta']['id'], $item['meta']['type']);
	}

	protected function get_asset_enqueue_args($item, $type) {
		switch ($type) {
			case 'style':
				// TODO: Take from meta (after sanitization)
				return 'all';
			case 'script':
				return [
					// Inject into footer by default
					'in_footer' => isset($item['meta']['position']) ? ($item['meta']['position'] === 'footer' ? true : false ) : true
				];
		}

		return null;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $assets
	 * @return bool
	 */
	protected function update_assets($assets) {
		return \update_option(self::ASSETS_OPTION_NAME, $assets);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function register_commands() {
		if ( ! CoreHelpers::is_cli() ) {
			return;
		}

		require_once __DIR__ . '/CLI.php';

		\WP_CLI::add_command( 'wa assets', 'Breakerino\AssetsManager\CLI' );
	}

	/**
	 * Undocumented function
	 *
	 * @param string $type
	 * @param array $item
	 * @return bool
	 */
	public function register_asset_item($type, $item) {
		$assetType = $this->get_asset_type($type);

		if ( ! $assetType || ! $assetType['enqueue'] ) {
			return;
		}
		
		return call_user_func(
			'wp_register_' . $assetType['enqueue'],
			$this->get_asset_id($item),
			$item['url'],
			isset($item['meta']['dependencies']) ? explode(',', str_replace(' ', '', $item['meta']['dependencies'])) : null,
			CoreHelpers::is_dev_mode() ? time() : $item['meta']['version'],
			$this->get_asset_enqueue_args($item, $assetType['enqueue'])
		);
	}

	/**
	 * Undocumented function
	 *
	 * @param string $assetType
	 * @param array $item
	 * @return boolean
	 */
	public function enqueue_asset_item($type, $item) {
		$assetType = $this->get_asset_type($type);

		if ( ! $assetType || ! $assetType['enqueue'] ) {
			return;
		}

		//
		$shouldEnqueue = true;

		# Temporarily disable due to security concerns
		# Instead of this approach, it will be possible to provide list of functions with operator (&& / ||) or filterable
		if ( isset($item['meta']['conditions']) ) {
			$shouldEnqueue = false;
			$supportedConditions = Helpers::get_supported_conditions();
			
			// TODO: Define as: function:function_name[arg1, arg2], ...
			// TODO: Operator (AND/OR) - OR by default
			$conditions = preg_replace("/\s{1,}/", '', $item['meta']['conditions']);
			$conditions = explode(',', $item['meta']['conditions']);
			$conditions = array_filter($conditions, function($condition) use ($supportedConditions) {
				return in_array($condition, $supportedConditions) && function_exists($condition);
			});
			
			$shouldEnqueue = array_reduce($conditions, function($carry, $condition) {
				return $carry && ((boolean) call_user_func($condition));
			}, true);
		}

		if ( ! $shouldEnqueue ) {
			return;
		}
		
		return call_user_func(
			'wp_enqueue_' . $assetType['enqueue'],
			$this->get_asset_id($item)
		);
	}

	/**
	 * Undocumented function
	 *
	 * @param string $type
	 * @param array $items
	 * @return void
	 */
	public function register_asset_items($type, $items) {
		foreach ($items as $item) {
			if ($item['type'] === 'dir') {
				$this->register_asset_items($type, $item['files']);
				continue;
			}

			$assetType = $this->get_asset_type($item['type']);

			if ( ! $assetType ) {
				continue;
			}

			if ( ! $assetType['enqueue'] ) {
				continue;
			}

			$this->register_asset_item($type, $item);
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param string $type
	 * @param array $items
	 * @return array
	 */
	 public function enqueue_asset_items($type, $items) {
		foreach ($items as $item) {
			//
			if ($item['type'] === 'dir') {
				$this->enqueue_asset_items($type, $item['files']);
				continue;
			}

			$assetType = $this->get_asset_type($item['type']);

			if ( ! $assetType ) {
				continue;
			}

			if ( ! $assetType['enqueue'] ) {
				continue;
			}

			$item['enqueued'] = $this->enqueue_asset_item($type, $item);
		}

		return $items;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $item
	 * @return array
	 */
	public function compile_scss_file($item) {
		// TODO: Compile on demand (checksum)
		$scssContents = file_get_contents($item['path']);

		$compiler = new \ScssPhp\ScssPhp\Compiler();
		$compiler->setImportPaths(dirname($item['path']));

		try {
			$cssContent = $compiler->compileString($scssContents)->getCss();
			$item['compiled'] = true;
		} catch (\Exception $e) {
			$cssContent = $e->getMessage();
			$item['compiled'] = false;
		}

		$cssFilePath = str_replace('scss', 'css', $item['path']);
		$cssFileDir = pathinfo($cssFilePath, PATHINFO_DIRNAME);

		// Remove file meta comments
		$cssContent = preg_replace(Constants::FILE_META_REGEX, '', $cssContent);

		// Remove blank lines
		// NOTE: Not working in some cases
		// wp-content/themes/breakerino-theme/assets/css/global.css
		$cssContent = preg_replace("/^(?:[\t ]*(?:\r?\n|\r))+/", '', $cssContent);

		// Create file directory if not exists
		if ( ! is_dir($cssFileDir) ) {
			mkdir($cssFileDir, 0755, true);
		}

		$cssContent = '/* Last update: ' . date('c') . " */\n" . "\n" . $cssContent;

		if ( \Breakerino\Core\Helpers::is_dev_mode() ) {
			$cssContent = '/* THIS FILE IS GENERATED AUTOMATICALLY, DO NOT MODIFY IT DIRECTLY, ANY DIRECT CHANGES WILL BE LOST */' . "\n" . $cssContent;
		}

		// Save CSS file contents
		// TODO: Store compiled css files inside wp-content/breakerino/css/...
		file_put_contents($cssFilePath, $cssContent);

		return $item;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $items
	 * @return array
	 */
	public function compile_scss_assets($items) {
		foreach ($items as $item) {
			//
			if ($item['type'] === 'dir') {
				$this->compile_scss_assets($item['files']);
				continue;
			}

			if ( $item['type'] === 'scss' && $item['meta']['type'] !== 'partial' ) {
				$item['compiled'] = $this->compile_scss_file($item);
			}
		}

		return $items;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_load_assets() {
		$assets = [
			'css' => [],
			'scss' => [],
			'js' => []
		];

		foreach ($this->get_supported_asset_types() as $assetType) {
			$assetsDirs = Helpers::get_assets_dirs($assetType);
			
			foreach ( $assetsDirs as $assetDir ) {
				if (!is_dir($assetDir)) {
					continue;
				}
	
				$files = Helpers::scan_directory($assetDir);
				$assets[$assetType] = array_merge($assets[$assetType], $files);
			}
		}
						
		$this->update_assets($assets);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_enqueue_assets() {
		$assets = $this->get_assets_list();

		foreach ($assets as $type => $items) {
			$this->enqueue_asset_items($type, $items);
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_register_assets() {
		$assets = $this->get_assets_list();

		foreach ($assets as $type => $items) {
			$this->register_asset_items($type, $items);
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_compile_assets() {
		$assets = $this->get_assets_list();

		foreach ( $assets as $type => $files ) {
			$assetType = $this->get_asset_type($type);

			if ( ! $assetType || ! $assetType['compile'] ) {
				continue;
			}

			$compileMethodName = 'compile_' . $type . '_assets';

			if ( ! method_exists($this, $compileMethodName) ) {
				continue;
			}

			$this->{$compileMethodName}($files);
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function handle_regenerate_assets() {
		$this->handle_load_assets();
		$this->handle_compile_assets();
		$this->handle_load_assets();
	}

	public function handle_auto_regenerate_assets() {
		if ( CoreHelpers::is_cli() || ! \apply_filters('breakerino/assets-manager/auto_regenerate_enabled', false) ) {
			return;
		}

		$this->handle_regenerate_assets();
	}
}
