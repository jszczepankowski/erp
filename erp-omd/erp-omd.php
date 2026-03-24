<?php
/**
 * Plugin Name: ERP OMD
 * Plugin URI: https://example.com/erp-omd
 * Description: ERP_OMD V2 FRONT-5: frontend kosztorysów managera, wielu managerów projektu i reset hasła pracownika.
 * Version: 1.0.0
 * Author: OMD
 * Text Domain: erp-omd
 */

if (! defined('ABSPATH')) {
    exit;
}

define('ERP_OMD_VERSION', '1.0.0');
define('ERP_OMD_DB_VERSION', '6.4.0');
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
    erp_omd()->boot();
});
