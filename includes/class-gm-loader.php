<?php
// File: includes/class-gm-loader.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GM_Loader {

    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once GM_PLUGIN_DIR . 'includes/class-gm-admin-ui.php';
        require_once GM_PLUGIN_DIR . 'includes/class-gm-product-creator.php';
        require_once GM_PLUGIN_DIR . 'includes/class-gm-access-control.php';
        require_once GM_PLUGIN_DIR . 'includes/class-gm-mmpo-bridge.php';
        require_once GM_PLUGIN_DIR . 'includes/class-gm-tiered-tabs.php';
        require_once GM_PLUGIN_DIR . 'includes/class-gm-elementor-support.php';
    }

    private function init_hooks() {
        add_action( 'bp_include', [ $this, 'init_components' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

    }

    public function init_components() {
        new GM_Admin_UI();
        new GM_Product_Creator();
        new GM_Access_Control();
        new GM_MMPO_Bridge();
        new GM_Tiered_Tabs();
        new GM_Elementor_Support();
    }
 public function enqueue_assets() {
    if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
        wp_enqueue_script(
            'gm-admin',
            plugin_dir_url( __FILE__ ) . '../assets/js/gm-admin.js',
            [ 'jquery' ],
            '1.0',
            true
        );
    }
}

}