<?php
// File: includes/class-gm-admin-ui.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GM_Admin_UI {

    public function __construct() {
        // Register the new Monetization tab in Group Admin menu
        add_filter( 'bp_groups_admin_tabs', [ $this, 'register_monetization_tab' ], 10, 2 );

        // Save form on group settings edit
        add_action( 'groups_group_settings_edited', [ $this, 'save_group_settings' ] );

        // Enqueue scripts only on Monetization tab
        add_action( 'bp_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    /**
     * Register Monetization tab in Group Admin menu
     *
     * @param array $tabs
     * @param BP_Groups_Group|null $group
     * @return array
     */
    public function register_monetization_tab( $tabs = [], $group = null ) {
        $tabs['gm_monetization'] = [
            'name'             => __( 'Monetization', 'group-monetizer' ),
            'slug'             => 'gm-monetization',
            'position'         => 50, // Adjust position as needed
            'screen_function'  => 'gm_render_group_monetization_tab',
            'enable_nav_item'  => true,
            'show_for_admins_only' => false, // Show to group admins and mods
        ];

        return $tabs;
    }

    /**
     * Render the Monetization tab content
     *
     * @param int $group_id
     */
    public function render_group_settings( $group_id ) {
        $is_monetized    = groups_get_groupmeta( $group_id, 'gm_monetized', true );
        $price           = groups_get_groupmeta( $group_id, 'gm_price', true );
        $frequency       = groups_get_groupmeta( $group_id, 'gm_frequency', true );
        $length          = groups_get_groupmeta( $group_id, 'gm_length', true );
        $nyp_enabled     = groups_get_groupmeta( $group_id, 'gm_nyp_enabled', true );
        $custom_name     = groups_get_groupmeta( $group_id, 'gm_product_name', true );
        ?>

        <div class="gm-group-monetization" style="max-width:600px; margin:20px auto;">
            <h2><?php esc_html_e( 'Group Monetization Settings', 'group-monetizer' ); ?></h2>

            <form method="post" action="">
                <?php wp_nonce_field( 'gm_save_monetization_settings', 'gm_monetization_nonce' ); ?>

                <p>
                    <label>
                        <input type="checkbox" name="gm_monetized" value="1" <?php checked( $is_monetized, '1' ); ?> />
                        <?php esc_html_e( 'Monetize this group', 'group-monetizer' ); ?>
                    </label>
                </p>

                <div id="gm_monetization_fields" style="<?php echo $is_monetized ? '' : 'display:none;'; ?> margin-top: 15px;">
                    <p>
                        <label for="gm_price"><?php esc_html_e( 'Subscription Price ($)', 'group-monetizer' ); ?></label><br />
                        <input type="text" id="gm_price" name="gm_price" value="<?php echo esc_attr( $price ); ?>" />
                    </p>

                    <p>
                        <label for="gm_frequency"><?php esc_html_e( 'Subscription Frequency', 'group-monetizer' ); ?></label><br />
                        <select id="gm_frequency" name="gm_frequency">
                            <option value="one_time" <?php selected( $frequency, 'one_time' ); ?>><?php esc_html_e( 'One-time', 'group-monetizer' ); ?></option>
                            <option value="day" <?php selected( $frequency, 'day' ); ?>><?php esc_html_e( 'Daily', 'group-monetizer' ); ?></option>
                            <option value="week" <?php selected( $frequency, 'week' ); ?>><?php esc_html_e( 'Weekly', 'group-monetizer' ); ?></option>
                            <option value="month" <?php selected( $frequency, 'month' ); ?>><?php esc_html_e( 'Monthly', 'group-monetizer' ); ?></option>
                            <option value="year" <?php selected( $frequency, 'year' ); ?>><?php esc_html_e( 'Yearly', 'group-monetizer' ); ?></option>
                        </select>
                    </p>

                    <p>
                        <label for="gm_length"><?php esc_html_e( 'Subscription Length (in units of frequency)', 'group-monetizer' ); ?></label><br />
                        <input type="number" id="gm_length" name="gm_length" min="1" value="<?php echo esc_attr( $length ); ?>" />
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="gm_nyp_enabled" value="1" <?php checked( $nyp_enabled, '1' ); ?> />
                            <?php esc_html_e( 'Enable Name Your Price', 'group-monetizer' ); ?>
                        </label>
                    </p>

                    <p>
                        <label for="gm_product_name"><?php esc_html_e( 'Custom Subscription Name (optional)', 'group-monetizer' ); ?></label><br />
                        <input type="text" id="gm_product_name" name="gm_product_name" value="<?php echo esc_attr( $custom_name ); ?>" />
                    </p>
                </div>

                <p>
                    <input type="submit" name="gm_save_settings" value="<?php esc_attr_e( 'Save Settings', 'group-monetizer' ); ?>" class="button button-primary" />
                </p>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const checkbox = document.querySelector('input[name="gm_monetized"]');
                const fieldset = document.getElementById('gm_monetization_fields');
                if (checkbox && fieldset) {
                    checkbox.addEventListener('change', function () {
                        fieldset.style.display = checkbox.checked ? 'block' : 'none';
                    });
                }
            });
        </script>

        <?php
    }

    /**
     * Save monetization settings from the form submission
     *
     * @param int $group_id
     */
    public function save_group_settings( $group_id ) {
        if ( ! isset( $_POST['gm_save_settings'] ) ) {
            return;
        }

        if ( ! isset( $_POST['gm_monetization_nonce'] ) || ! wp_verify_nonce( $_POST['gm_monetization_nonce'], 'gm_save_monetization_settings' ) ) {
            return;
        }

        $is_monetized = isset( $_POST['gm_monetized'] ) ? '1' : '0';
        $price        = isset( $_POST['gm_price'] ) ? sanitize_text_field( $_POST['gm_price'] ) : '';
        $frequency    = isset( $_POST['gm_frequency'] ) ? sanitize_text_field( $_POST['gm_frequency'] ) : '';
        $length       = isset( $_POST['gm_length'] ) ? absint( $_POST['gm_length'] ) : '';
        $nyp_enabled  = isset( $_POST['gm_nyp_enabled'] ) ? '1' : '0';
        $custom_name  = isset( $_POST['gm_product_name'] ) ? sanitize_text_field( $_POST['gm_product_name'] ) : '';

        groups_update_groupmeta( $group_id, 'gm_monetized', $is_monetized );
        groups_update_groupmeta( $group_id, 'gm_price', $price );
        groups_update_groupmeta( $group_id, 'gm_frequency', $frequency );
        groups_update_groupmeta( $group_id, 'gm_length', $length );
        groups_update_groupmeta( $group_id, 'gm_nyp_enabled', $nyp_enabled );
        groups_update_groupmeta( $group_id, 'gm_product_name', $custom_name );

        // After save, redirect to avoid resubmission on refresh
        wp_safe_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/gm-monetization/' );
        exit;
    }

    /**
     * Enqueue JS/CSS on Monetization tab only
     */
    public function enqueue_admin_scripts() {
        // Check if current component and action match our tab
        if ( bp_is_group() && bp_is_group_admin_screen() && bp_is_current_action( 'gm-monetization' ) ) {
            wp_enqueue_script(
                'gm-admin-js',
                plugin_dir_url( __FILE__ ) . '../assets/js/gm-admin.js',
                [ 'jquery' ],
                '1.0',
                true
            );

            wp_enqueue_style(
                'gm-admin-css',
                plugin_dir_url( __FILE__ ) . '../assets/css/gm-admin.css',
                [],
                '1.0'
            );
        }
    }
}