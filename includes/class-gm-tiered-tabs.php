<?php
// File: includes/class-gm-tiered-tabs.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GM_Tiered_Tabs {

    public function __construct() {
        add_filter( 'bp_groups_custom_tabs', [ $this, 'maybe_hide_tabs_based_on_product' ], 20, 2 );
    }

    /**
     * Filter group tabs to restrict access based on the WooCommerce product associated with a tier.
     */
    public function maybe_hide_tabs_based_on_product( $tabs, $group_id ) {
        if ( ! function_exists( 'bp_group_get_custom_tabs' ) ) {
            return $tabs;
        }

        $user_id = get_current_user_id();

        foreach ( $tabs as $key => $tab ) {
            if ( isset( $tab['meta']['gm_required_product_id'] ) ) {
                $required_product_id = (int) $tab['meta']['gm_required_product_id'];

                if ( ! $this->user_has_active_subscription( $user_id, $required_product_id ) ) {
                    unset( $tabs[ $key ] );
                }
            }
        }

        return $tabs;
    }

    /**
     * Check if a user has an active subscription for the given product.
     */
    private function user_has_active_subscription( $user_id, $product_id ) {
        if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
            return false;
        }

        $subscriptions = wcs_get_users_subscriptions( $user_id );

        foreach ( $subscriptions as $subscription ) {
            if ( $subscription->has_status( [ 'active', 'trial' ] ) ) {
                foreach ( $subscription->get_items() as $item ) {
                    if ( (int) $item->get_product_id() === (int) $product_id ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}