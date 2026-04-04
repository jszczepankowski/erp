<?php
/**
 * Plugin Name: ERP OMD
 * Plugin URI: https://example.com/erp-omd
 * Description: ERP System OMD
 * Version: 3.0.0
 * Author: OMD
 * Text Domain: erp-omd
 */

if (! defined('ABSPATH')) {
    exit;
}

define('ERP_OMD_VERSION', '3.0.0');
define('ERP_OMD_DB_VERSION', '6.5.1');
define('ERP_OMD_FILE', __FILE__);
define('ERP_OMD_PATH', plugin_dir_path(__FILE__));
define('ERP_OMD_URL', plugin_dir_url(__FILE__));

require_once ERP_OMD_PATH . 'includes/class-autoloader.php';
ERP_OMD_Autoloader::register();

register_activation_hook(ERP_OMD_FILE, ['ERP_OMD_Installer', 'activate']);
register_deactivation_hook(ERP_OMD_FILE, ['ERP_OMD_Installer', 'deactivate']);

function erp_omd()
{
    static $plugin = null;

    if (null === $plugin) {
        $plugin = new ERP_OMD_Plugin();
    }

    return $plugin;
}

add_action('plugins_loaded', static function () {
    erp_omd_invalidate_plugin_opcache();
    erp_omd()->boot();
});

function erp_omd_reports_cache_bump_version()
{
    if (! function_exists('get_option') || ! function_exists('update_option')) {
        return;
    }

    $version = (int) get_option('erp_omd_reports_cache_data_version', 1);
    update_option('erp_omd_reports_cache_data_version', (string) max(1, $version + 1), false);
}

function erp_omd_invalidate_plugin_opcache()
{
    if (! function_exists('opcache_invalidate')) {
        return;
    }

    $paths = [
        ERP_OMD_PATH . 'includes/class-autoloader.php',
        ERP_OMD_PATH . 'includes/class-plugin.php',
        ERP_OMD_PATH . 'includes/class-admin.php',
        ERP_OMD_PATH . 'includes/class-frontend.php',
        ERP_OMD_PATH . 'includes/class-rest-api.php',
    ];

    $repository_files = glob(ERP_OMD_PATH . 'includes/repositories/*.php');
    if (is_array($repository_files)) {
        $paths = array_merge($paths, $repository_files);
    }

    foreach ($paths as $path) {
        if (is_string($path) && $path !== '' && file_exists($path)) {
            @opcache_invalidate($path, true);
        }
    }
}
