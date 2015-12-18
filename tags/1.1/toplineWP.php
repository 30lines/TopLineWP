<?php
/*
   Plugin Name: TopLine WP
   Plugin URI: http://wordpress.org/extend/plugins/toplineWP/
   Version: 1.0.0
   Author: <a href="http://30lines.com/">30lines</a>
   Description: Seemlessly connects real estate agents to their property information for any wordpress site. Import Support: Entrata.
   Text Domain: topline-wp
   License: GPLv3
  */
/*
    GNU GPLv3 License Origin: http://www.gnu.org/licenses/gpl-3.0.html
*/

$topline_minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function topline_noticePhpVersionWrong() {
    global $topline_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "Topline WP" requires a newer version of PHP to be running.',  'topline-wp').
            '<br/>' . __('Minimal version of PHP required: ', 'topline-wp') . '<strong>' . $topline_minimalRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'topline-wp') . '<strong>' . phpversion() . '</strong>' .
         '</div>';
}


function topline_PhpVersionCheck() {
    global $topline_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $topline_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'topline_noticePhpVersionWrong');
        return false;
    }
    return true;
}


/**
 * Initialize internationalization (i18n) for this plugin.
 * Dev References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 * @return void
 */
function topline_i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
    load_plugin_textdomain('topline-wp', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// Initialize i18n
add_action('plugins_loadedi','topline_i18n_init');

// Run the version check.
// If it is successful, continue with initialization for this plugin
if (topline_PhpVersionCheck()) {
    // Only load and run the init function if we know PHP version can parse it
    include_once('toplineWP_init.php');
    topline_init(__FILE__);
}
