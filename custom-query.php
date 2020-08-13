<?php
/**
 * Plugin Name:     Custom Query
 * Plugin URI:      https://github.com/bboogaard/custom-query/
 * Description:     Rendering and paging for custom wp queries
 * Author:          Bram Boogaard
 * Author URI:      https://www.wp-wikkel.nl/
 * Text Domain:     custom-query
 * Domain Path:     /languages
 * Version:         1.1.0
 *
 * @package         Custom Query
 */

// Your code starts here.
define('CUSTOM_QUERY_PATH', __FILE__);
define('CUSTOM_QUERY_TEMPLATE_PATH', path_join(plugin_dir_path(__FILE__), 'templates'));

if (file_exists('vendor/autoload.php')) {
    require('vendor/autoload.php');
}
require('ext/plugin-update-checker/plugin-update-checker.php');
require('includes/settings.php');
require('includes/template.loader.php');
require('includes/uuid.php');
require('includes/persistent-query.php');
require('includes/custom-query.php');
require('includes/updates.php');

function custom_query_run() {

    CustomQuery\Settings::register();
    CustomQuery\Updates::register();

}

custom_query_run();
