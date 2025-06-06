<?php
// File: includes/class-gm-product-creator.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GM_Product_Creator {

    public function __construct() {
        add_action( 'groups_created_group', [ $this, 'handle_product_creation_on_group_create' ], 20, 1 );
        add_action( 'groups_updated_group', [ $this, 'handle_product_update_on_group_edit' ], 20, 1 );
    }

    public function handle_product_creation_on_group_create( $group_id ) {
        $is_monetized = groups_get_groupmeta( $group_id, 'gm_monetized', true );
        if ( $is_monetized ) {
            $this->create_or_update_product( $group_id );
        }
    }

    public function handle_product_update_on_group_edit( $group_id ) {
        $is_monetized = groups_get_groupmeta( $group_id, 'gm_monetized', true );
        if ( $is_monetized ) {
            $this->create_or_update_product( $group_id );
        }
    }

    private function create_or_update_product( $group_id ) {
        $group = groups_get_group( $group_id );
        if ( ! $group ) return;

        $product_name   = groups_get_groupmeta( $group_id, 'gm_product_name', true );
        $price          = groups_get_groupmeta( $group_id, 'gm_price', true );
        $frequency      = groups_get_groupmeta( $group_id, 'gm_frequency', true );
        $length         = groups_get_groupmeta( $group_id, 'gm_length', true );
        $nyp_enabled    = groups_get_groupmeta( $group_id, 'gm_nyp_enabled', true );

        $author_id      = $this->get_group_owner_id( $group_id );
        $title          = $product_name ? $product_name : sprintf( 'Access to %s', $group->name );
        $existing_id    = groups_get_groupmeta( $group_id, 'gm_product_id', true );

        $post_args = [
            'post_title'   => $title,
            'post_status'  => 'publish',
            'post_type'    => 'product',
            'post_author'  => $author_id,
        ];

        if ( $existing_id && get_post( $existing_id ) ) {
            $post_args['ID'] = $existing_id;
            wp_update_post( $post_args );
            $product_id = $existing_id;
        } else {
            $product_id = wp_insert_post( $post_args );
            groups_update_groupmeta( $group_id, 'gm_product_id', $product_id );
        }

        // WooCommerce product setup
        if ( $product_id ) {
            wp_set_object_terms( $product_id, 'subscription', 'product_type' );

            update_post_meta( $product_id, '_virtual', 'yes' );
            update_post_meta( $product_id, '_price', $price );
            update_post_meta( $product_id, '_regular_price', $price );
            update_post_meta( $product_id, '_subscription_price', $price );
            update_post_meta( $product_id, '_subscription_period', $frequency === 'one_time' ? '' : $frequency );
            update_post_meta( $product_id, '_subscription_length', $frequency === 'one_time' ? 0 : $length );
            update_post_meta( $product_id, '_subscription_sign_up_fee', '0' );
            update_post_meta( $product_id, '_subscription_trial_length', '0' );
            update_post_meta( $product_id, '_subscription_trial_period', '' );

            if ( $nyp_enabled === '1' ) {
                update_post_meta( $product_id, '_nyp', 'yes' );
                update_post_meta( $product_id, '_min_price', '0.01' ); // or use $price as minimum
            } else {
                update_post_meta( $product_id, '_nyp', 'no' );
            }

            do_action( 'gm_product_created_or_updated', $product_id, $group_id );
        }
    }

    private function get_group_owner_id( $group_id ) {
        $admins = groups_get_group_admins( $group_id );
        return ! empty( $admins ) ? $admins[0]->user_id : get_current_user_id();
    }
}