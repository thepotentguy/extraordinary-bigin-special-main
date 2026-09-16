<?php
/**
 * Offer editor fields.
 *
 * @package Extra_Special
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function es_add_special_meta_boxes() {
	add_meta_box(
		'es_offer_details',
		__( 'Offer and booking details', 'extra-special' ),
		'es_render_offer_details_meta_box',
		'exclusive-offers',
		'normal',
		'high'
	);

	add_meta_box(
		'es_special_gallery',
		__( 'Offer gallery', 'extra-special' ),
		'es_render_gallery_meta_box',
		'exclusive-offers',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'es_add_special_meta_boxes' );

function es_render_offer_details_meta_box( $post ) {
	wp_nonce_field( 'es_save_offer_details', 'es_offer_details_nonce' );

	$fields = array(
		'_validity_date' => array(
			'label'       => __( 'Valid until', 'extra-special' ),
			'type'        => 'date',
			'description' => __( 'The offer is shown as ended after this date.', 'extra-special' ),
		),
		'_price'         => array(
			'label'       => __( 'Display price', 'extra-special' ),
			'type'        => 'text',
			'placeholder' => 'R 10,692 per person sharing',
		),
		'_packages'      => array(
			'label'       => __( 'Package summary', 'extra-special' ),
			'type'        => 'textarea',
			'placeholder' => __( 'What is included in this offer?', 'extra-special' ),
		),
		'_promo_code'    => array(
			'label'       => __( 'eRes promo code', 'extra-special' ),
			'type'        => 'text',
			'placeholder' => 'SUMMER25',
			'description' => __( 'Added automatically to the eRes booking page. Leave blank when the offer has no promo code.', 'extra-special' ),
		),
		'_product_code'  => array(
			'label'       => __( 'Product code', 'extra-special' ),
			'type'        => 'text',
			'placeholder' => 'HAM-SUMMER-2026',
			'description' => __( 'Use the same stable code in Bigin, website analytics and WhatsApp.', 'extra-special' ),
		),
		'_booking_url'   => array(
			'label'       => __( 'eRes booking URL', 'extra-special' ),
			'type'        => 'url',
			'placeholder' => 'https://nebulacrs.hti.app/extraordinary/property/desktop.html?locale=en_US',
			'description' => __( 'Optional offer-specific URL. The site-wide booking URL is used when this is blank.', 'extra-special' ),
		),
	);

	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
		if ( 'textarea' === $field['type'] ) {
			echo '<textarea class="large-text" rows="4" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" placeholder="' . esc_attr( $field['placeholder'] ?? '' ) . '">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input class="regular-text" type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $field['placeholder'] ?? '' ) . '">';
		}
		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

function es_render_gallery_meta_box( $post ) {
	wp_nonce_field( 'es_save_gallery', 'es_gallery_nonce' );
	$gallery = get_post_meta( $post->ID, '_image_ids', true );
	$gallery = array_filter( array_map( 'absint', explode( ',', (string) $gallery ) ) );
	?>
	<div id="es-gallery-preview">
		<?php foreach ( $gallery as $attachment_id ) : ?>
			<?php $thumbnail = wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
			<?php if ( $thumbnail ) : ?>
				<div class="es-gallery-image" data-id="<?php echo esc_attr( $attachment_id ); ?>">
					<?php echo wp_kses_post( $thumbnail ); ?>
					<button type="button" class="button-link-delete es-remove-gallery-image"><?php esc_html_e( 'Remove', 'extra-special' ); ?></button>
					<input type="hidden" name="gallery[]" value="<?php echo esc_attr( $attachment_id ); ?>">
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<p><button type="button" class="button" id="es-add-gallery-images"><?php esc_html_e( 'Add images', 'extra-special' ); ?></button></p>
	<?php
}

function es_save_special_meta_boxes( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['es_offer_details_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['es_offer_details_nonce'] ) ), 'es_save_offer_details' ) ) {
		$text_fields = array( '_validity_date', '_price', '_promo_code', '_product_code' );
		foreach ( $text_fields as $field ) {
			$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
			update_post_meta( $post_id, $field, $value );
		}

		$packages = isset( $_POST['_packages'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_packages'] ) ) : '';
		update_post_meta( $post_id, '_packages', $packages );

		$booking_url = isset( $_POST['_booking_url'] ) ? esc_url_raw( wp_unslash( $_POST['_booking_url'] ) ) : '';
		update_post_meta( $post_id, '_booking_url', $booking_url );
	}

	if ( isset( $_POST['es_gallery_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['es_gallery_nonce'] ) ), 'es_save_gallery' ) ) {
		$gallery = isset( $_POST['gallery'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['gallery'] ) ) ) : array();
		update_post_meta( $post_id, '_image_ids', implode( ',', array_values( array_unique( $gallery ) ) ) );
	}
}
add_action( 'save_post_exclusive-offers', 'es_save_special_meta_boxes' );

function es_enqueue_offer_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'exclusive-offers' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'es-admin-specials', ES_PLUGIN_URL . 'js/admin-specials.js', array( 'jquery' ), ES_VERSION, true );
	wp_enqueue_style( 'es-admin-style', ES_PLUGIN_URL . 'styles/admin-style.css', array(), ES_VERSION );
}
add_action( 'admin_enqueue_scripts', 'es_enqueue_offer_admin_assets' );
