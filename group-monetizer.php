<?php
/**
 * Plugin Name: UC Group Monetizer
 * Description: Adds Monetize settings to Group > Manage section (not in group nav).
 * Version: 1.1.1
 * Author: UC Dev Team
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'bp_include', function () {

    if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) return;

    class UC_Group_Monetizer extends BP_Group_Extension {
        public function __construct() {
            $args = array(
                'slug'                => 'monetize',
                'name'                => __( 'Monetize', 'uc-group-monetizer' ),
                'nav_item_position'   => 99,
                'show_tab'            => false, // hide nav tab (BuddyPress)
                'enable_nav_item'     => false, // hide nav tab (BuddyBoss)
                'enable_create_step'  => false,
                'enable_edit_item'    => true,
                'visibility'          => 'admin', // ensure appears only in admin/manage
                'admin_class'         => 'bbgm-monetize-tab',
            );

            parent::init( $args );
        }

        // Extra layer of nav tab suppression (BuddyBoss-specific quirk)
        public function enable_nav_item( $group_id = null ) {
            return false;
        }

        public function settings_screen( $group_id = null ) {
            if ( ! $group_id ) {
                $group_id = bp_get_current_group_id();
            }

            $enabled = groups_get_groupmeta( $group_id, 'ucgm_enabled', true );
            $price   = groups_get_groupmeta( $group_id, 'ucgm_price', true );
            ?>
            <h4><?php esc_html_e( 'Monetization Settings', 'uc-group-monetizer' ); ?></h4>
            <label>
                <input type="checkbox" name="ucgm_enabled" value="1" <?php checked( $enabled, '1' ); ?>>
                <?php esc_html_e( 'Enable Monetization for this Group', 'uc-group-monetizer' ); ?>
            </label>
            <br><br>
            <label for="ucgm_price"><?php esc_html_e( 'Subscription Price ($)', 'uc-group-monetizer' ); ?></label>
            <input type="text" name="ucgm_price" id="ucgm_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" />
            <?php
        }

        public function settings_screen_save( $group_id = null ) {
            if ( ! $group_id ) {
                $group_id = bp_get_current_group_id();
            }

            $enabled = isset( $_POST['ucgm_enabled'] ) ? '1' : '0';
            $price   = sanitize_text_field( $_POST['ucgm_price'] ?? '' );

            groups_update_groupmeta( $group_id, 'ucgm_enabled', $enabled );
            groups_update_groupmeta( $group_id, 'ucgm_price', $price );
        }
    }

    bp_register_group_extension( 'UC_Group_Monetizer' );

    // Add CSS for the $ icon on Monetize settings tab inside the closure
    function bbgm_enqueue_admin_styles() {
        if ( bp_is_group_admin_screen() ) {
            ?>
            <style>
            li.bbgm-monetize-tab::before {
                content: "$" !important;
                font-weight: bold;
                color: #4caf50;
                margin-right: 6px;
                font-size: 18px;
                vertical-align: middle;
            }
            </style>
            <?php
        }
    }
    add_action( 'bp_enqueue_scripts', 'bbgm_enqueue_admin_styles' );

});