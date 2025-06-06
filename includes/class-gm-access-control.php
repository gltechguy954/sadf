<?php
// File: includes/class-gm-access-control.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GM_Access_Control {

    public function __construct() {
        add_action( 'bp_group_before_access_check', [ $this, 'enforce_group_subscription_access' ] );
    }

    /**
     * Restrict access to monetized groups unless the user has the correct subscription.
     */
    public function enforce_group_subscription_access( $group_id ) {
        if ( is_super_admin() || groups_is_user_admin( get_current_user_id(), $group_id ) ) {
            return; // Always allow site/network admins or group admins
        }

        $is_monetized = groups_get_groupmeta( $group_id, 'gm_monetized', true );
        if ( $is_monetized !== '1' ) {
            return; // Not a monetized group
        }

        $product_id = groups_get_groupmeta( $group_id, 'gm_product_id', true );
        if ( ! $product_id ) {
            return; // No associated product
        }

        if ( ! $this->user_has_active_subscription( get_current_user_id(), $product_id ) ) {
            bp_core_add_message( __( 'You must purchase a subscription to access this group.', 'group-monetizer' ), 'error' );
            bp_core_redirect( wc_get_product( $product_id )->get_permalink() );
            exit;
        }
    }

    /**
     * Check if a user has an active subscription for the product.
     */
    private function user_has_active_subscription( $user_id, $product_id ) {
        if ( ! class_exists( 'WC_Subscriptions_Manager' ) ) {
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