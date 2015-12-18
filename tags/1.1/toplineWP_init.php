<?php
/**
 * Topline Init Method
 * Author:  DJF
 * Company: 30Lines
 * Purpose: The purpose of this method is to initialize the TopLine Service plugin custom post types,
 * taxonomies, meta boxes, menu items, and other various dependencies needed to function smoothly.
 */

function topline_init($file) {

    require_once('topline_Plugin.php');
    $topLinePlugin = new topline_Plugin();

    // Install the plugin
    // NOTE: this file gets run each time you *activate* the plugin.
    // So in WP when you "install" the plugin, all that does is dump its files in the plugin-templates directory
    // but it does not call any of its code.
    // So here, the plugin tracks whether or not it has run its install operation, and we ensure it is run only once
    // on the first activation
    if (!$topLinePlugin->isInstalled()) {
        $topLinePlugin->install();
    }
    else {
        // Perform any version-upgrade activities prior to activation (e.g. database changes)
        $topLinePlugin->upgrade();
    }

    // Add callbacks to hooks
    $topLinePlugin->addActionsAndFilters();
    $topLinePlugin->addCustomPostTypes();

    if (!$file) {
        $file = __FILE__;
    }
    // Register the Plugin Activation Hook
    register_activation_hook($file, array(&$topLinePlugin, 'activate'));

    // Register the Plugin Deactivation Hook
    register_deactivation_hook($file, array(&$topLinePlugin, 'deactivate'));
}
