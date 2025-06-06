<?php
// File: includes/class-gm-mmpo-bridge.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GM_MMPO_Bridge {

    public function __construct() {
        add_filter( 'mmpo_get_product_owner', [ $this, 'override_product_owner_with_group_owner' ], 10, 2 );
        add_filter( 'mmpo_api_keys_for_product', [ $this, 'override_api_keys_with_group_keys' ], 10, 2 );
    }

    /**
     * Return the group "owner" for monetized group products.
     */
    public function override_product_owner_with_group_owner( $original_author_id, $product_id ) {
        $group_id = $this->get_group_id_by_product( $product_id );
        if ( ! $group_id ) return $original_author_id;

        return $this->get_primary_group_admin_id( $group_id );
    }

    /**
     * Return the API keys stored for the group (not just the author) for MMPO routing.
     */
    public function override_api_keys_with_group_keys( $api_keys, $product_id ) {
        $group_id = $this->get_group_id_by_product( $product_id );
        if ( ! $group_id ) return $api_keys;

        $public_key  = groups_get_groupmeta( $group_id, 'gm_nmi_public_key', true );
        $private_key = groups_get_groupmeta( $group_id, 'gm_nmi_private_key', true );

        if ( $public_key && $private_key ) {
            return [
                'public_key'  => $public_key,
                'private_key' => $private_key,
            ];
        }

        return $api_keys;
    }

    /**
     * Helper: Get group ID by WooCommerce product ID.
     */
    private function get_group_id_by_product( $product_id ) {
        global $wpdb;
        $group_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT group_id FROM {$wpdb->prefix}bp_groups_groupmeta WHERE meta_key = 'gm_product_id' AND meta_value = %d",
            $product_id
        ) );

        return $group_id ? absint( $group_id ) : false;
    }

    /**
     * Get the primary admin of a BuddyBoss group.
     */
    private function get_primary_group_admin_id( $group_id ) {
        $admins = groups_get_group_admins( $group_id );
        return ! empty( $admins ) ? (int) $admins[0]->user_id : 0;
    }
}