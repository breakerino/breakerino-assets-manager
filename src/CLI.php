<?php

/**
 * ------------------------------------------------------------------------------
 * Breakerino Assets Manager > CLI
 * ------------------------------------------------------------------------------
 * @created     02/11/2023
 * @updated     10/11/2022
 * @version	    1.0.0
 * @author      Matúš Mendel | Breakerino
 * ------------------------------------------------------------------------------
 */
namespace Breakerino\AssetsManager;

defined('ABSPATH') || exit;

use WP_CLI_Command;

class CLI extends WP_CLI_Command {
	/**
	 * Undocumented function
	 *
	 * @command wp wa assets load
	 * @return void
	 */
	public function load() {
		Plugin::instance()->handle_load_assets();
	}

	/**
	 * Undocumented function
	 *
	 * @command wp wa assets compile
	 * @return void
	 */
	public function compile() {
		Plugin::instance()->handle_compile_assets();
	}

	/**
	 * Undocumented function
	 *
	 * @command wp wa assets regenerate
	 * @return void
	 */
	public function regenerate() {
		\WP_CLI::log('[Breakerino Assets Manager] Regenerating assets...');
		Plugin::instance()->handle_regenerate_assets();
		\WP_CLI::success('Regeneration finished.');
	}
}