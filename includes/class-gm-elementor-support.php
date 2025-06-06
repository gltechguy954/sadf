<?php
// File: includes/class-gm-elementor-support.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GM_Elementor_Support {

    public function __construct() {
        add_shortcode( 'gm_group_landing_page', [ $this, 'render_group_landing_page' ] );
    }

    /**
     * Shortcode: [gm_group_landing_page group_id="123"]
     */
    public function render_group_landing_page( $atts ) {
        $atts = shortcode_atts( [
            'group_id' => get_queried_object_id(),
        ], $atts );

        $group_id = absint( $atts['group_id'] );
        if ( ! $group_id ) {
            return '<p>' . esc_html__( 'Invalid group ID.', 'group-monetizer' ) . '</p>';
        }

        $group = groups_get_group( $group_id );
        if ( ! $group ) {
            return '<p>' . esc_html__( 'Group not found.', 'group-monetizer' ) . '</p>';
        }

        $is_monetized = groups_get_groupmeta( $group_id, 'gm_monetized', true );
        if ( $is_monetized !== '1' ) {
            return '<p>' . esc_html__( 'This group is not monetized.', 'group-monetizer' ) . '</p>';
        }

        $product_id = groups_get_groupmeta( $group_id, 'gm_product_id', true );
        if ( ! $product_id || ! get_post( $product_id ) ) {
            return '<p>' . esc_html__( 'Product not found.', 'group-monetizer' ) . '</p>';
        }

        $product = wc_get_product( $product_id );
        ob_start();
        ?>
        <div class="gm-group-landing-page">
            <h2><?php echo esc_html( $group->name ); ?></h2>
            <p><?php echo esc_html( $group->description ); ?></p>

            <div class="gm-group-purchase">
                <h3><?php esc_html_e( 'Access this Group', 'group-monetizer' ); ?></h3>
                <?php echo $product->get_price_html(); ?>
                <a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="button">
                    <?php esc_html_e( 'Buy Access', 'group-monetizer' ); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}