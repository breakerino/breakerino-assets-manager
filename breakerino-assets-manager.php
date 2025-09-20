<?php
/**
 * Plugin Name: Breakerino Assets Manager
 * Plugin URI:  https://breakerino.me
 * Description: A plugin to help organize and manage assets efficiently for frontend development.
 * Version:     1.0.0
 * Author:      Breakerino
 * Author URI:  https://breakerino.me
 * Text Domain: breakerino-assets-manager
 * Domain Path: /languages
 * Requires at least: 6.7
 * Requires PHP: 8.1
 *
 * @package   Breakerino
 * @author    Breakerino
 * @link      https://breakerino.me
 * @copyright 2025 Breakerino
 */

defined( 'ABSPATH' ) || exit;

define( 'BREAKERINO_ASSETS_MANAGER_PLUGIN_FILE', __FILE__ );
define( 'BREAKERINO_ASSETS_MANAGER_VERSION', '1.0.0' );
define( 'BREAKERINO_ASSETS_MANAGER_DEPENDENCIES', [] );

// Include autoloader
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

/**
 * Returns the main plugin instance
 *
 * @since  1.0.0
 * @return Breakerino\AssetsManager\Plugin
 */
function BreakerinoAssetsManager() {
	Breakerino\AssetsManager\Plugin::register_commands();
	return Breakerino\AssetsManager\Plugin::instance();
}

// Initialize plugin
add_action('breakerino_core_init', 'BreakerinoAssetsManager');