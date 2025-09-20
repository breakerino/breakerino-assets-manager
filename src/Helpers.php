<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Assets Manager > Helpers
 * ------------------------------------------------------------------------------
 * @created     02/11/2023
 * @updated     10/11/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */

namespace Breakerino\AssetsManager;

defined('ABSPATH') || exit;

use Breakerino\Core\Exceptions\Generic as GenericException;

class Helpers {
	/**
	 * Undocumented function
	 *
	 * @param string $filePath
	 * @return array|null
	 */
	public static function parse_file_metadata($filePath) {
		if ( ! file_exists($filePath) ) {
			return null;
		}

		$fileContent = file_get_contents($filePath);

		$metaInfo = [];

		if (!preg_match(Constants::FILE_META_REGEX, $fileContent, $match)) {
			return $metaInfo;
		}

		// Extract the first comment
		$comment = $match[0];

		// Remove the comment delimiters (/* and */)
		$comment = trim($comment, '/* ');

		// Split the comment by line
		$lines = explode("\n", $comment);

		// Loop through the lines and parse key-value pairs
		foreach ($lines as $line) {
			$line = trim($line);
			
			if ( count(explode(':', $line, 2)) !== 2 ) {
				continue;
			}

			if (!empty($line)) {
				list($key, $value) = explode(':', $line, 2);
				$key = trim($key);

				if (!in_array($key, Constants::SUPPORTED_ASSET_META)) {
					continue;
				}

				$metaInfo[$key] = trim($value);
			}
		}

		return $metaInfo;
	}

	/**
	 * Undocumented function
	 *
	 * @param string $type
	 * @return string
	 */
	public static function get_assets_dirs($type) {
		$assetDirs = apply_filters('breakerino/assets-manager/assets_dirs', []);
		
		return array_map(function($assetDir) use ($type) {
			return sprintf('%s/%s', $assetDir, $type);
		}, $assetDirs);
	}

	/**
	 * Undocumented function
	 *
	 * @param string $dir
	 * @return array|null
	 */
	public static function scan_directory($dir) {
		$items = [];

		$contents = scandir($dir);

		if (empty($contents)) {
			return null;
		}

		foreach ($contents as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}

			$itemPath = sprintf('%s/%s', $dir, $item);

			$item = [
				'name' => $item,
				'type' => is_dir($itemPath) ? 'dir' : pathinfo($itemPath)['extension'],
				'path' => $itemPath,
			];

			$item['url'] = '/' . str_replace(ABSPATH, '', $item['path']);

			if (is_file($itemPath)) {
				// TODO: Filter
				if ($item['type'] === 'css') {
					$scssItemPath = str_replace('css', 'scss', $itemPath);
					$item['meta'] = self::parse_file_metadata($scssItemPath);
				} else {
					$item['meta'] = self::parse_file_metadata($itemPath);
				}

				//
				if (!$item['meta'] || count(array_intersect(Constants::REQUIRED_ASSET_META, array_keys($item['meta']))) !== count(Constants::REQUIRED_ASSET_META)) {
					// TODO: Warninig
					continue;
				}

				//
				if ( isset($item['meta']['active']) && $item['meta']['active'] === 'false' ) {
					// TODO: Debug
					continue;
				}
			}

			if (is_dir($itemPath)) {
				$item['files'] = self::scan_directory($itemPath);
			}

			// TODO: Filter
			// Warning: Undefined array key "meta" in /home/dev/repositories/breakerino/packages/assets-manager/plugin/src/Helpers.php on line 131
			if (isset($item['meta']) && $item['meta']['type'] === 'partial') {
				continue;
			}
			
			$items[] = $item;
		}
		
		return $items;
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	public static function get_supported_conditions() {
		return \apply_filters('breakerino/assets-manager/supported_asset_conditions', Constants::SUPPORTED_ASSET_CONDITIONS);
	}
}