<?php
/**
 * Register the Exclusive Offers content type.
 *
 * @package Extra_Special
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hs_create_specials_post_type() {
	$labels = array(
		'name'          => __( 'Exclusive Offers', 'extra-special' ),
		'singular_name' => __( 'Exclusive Offer', 'extra-special' ),
		'add_new_item'  => __( 'Add New Offer', 'extra-special' ),
		'edit_item'     => __( 'Edit Offer', 'extra-special' ),
		'view_item'     => __( 'View Offer', 'extra-special' ),
		'search_items'  => __( 'Search Offers', 'extra-special' ),
	);

	register_post_type(
		'exclusive-offers',
		array(
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-megaphone',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'rewrite'      => array( 'slug' => 'exclusive-offers' ),
		)
	);
}
add_action( 'init', 'hs_create_specials_post_type' );
