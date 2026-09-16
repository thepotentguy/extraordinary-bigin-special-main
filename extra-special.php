<?php
/**
 * Plugin Name: Extraordinary Specials
 * Plugin URI:  https://bigambitions.co.za/
 * Description: Tracked special-offer journeys from WordPress to Bigin and the eRes booking engine.
 * Version:     2.0.5
 * Author:      Steph & Ash
 * Author URI:  https://bigambitions.co.za/
 * Text Domain: extra-special
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ES_VERSION', '2.0.5' );
define( 'ES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ES_PLUGIN_DIR . 'includes/post-types.php';
require_once ES_PLUGIN_DIR . 'includes/meta-boxes.php';

/**
 * Return offer metadata, falling back to legacy offer copy where practical.
 */
function es_get_promo_code( $post_id ) {
	$code = trim( (string) get_post_meta( $post_id, '_promo_code', true ) );
	if ( $code ) {
		return strtoupper( sanitize_text_field( $code ) );
	}

	$content = (string) get_post_field( 'post_content', $post_id );
	if ( preg_match( '/promo\s+code\s*[:\-]?\s*([A-Z0-9_-]{3,30})/i', wp_strip_all_tags( $content ), $matches ) ) {
		return strtoupper( sanitize_text_field( $matches[1] ) );
	}

	return '';
}

function es_get_product_code( $post_id ) {
	$code = trim( (string) get_post_meta( $post_id, '_product_code', true ) );
	return $code ? strtoupper( sanitize_text_field( $code ) ) : 'OFFER-' . absint( $post_id );
}

function es_extract_booking_url( $content ) {
	if ( preg_match( '~https://nebulacrs\.hti\.app/extraordinary/[^\s"\'<>]+/(?:desktop|mobile)\.html(?:\?[^\s"\'<>#]*)?~i', (string) $content, $matches ) ) {
		return esc_url_raw( html_entity_decode( $matches[0] ) );
	}
	return '';
}

function es_get_booking_base_url( $post_id ) {
	$url = get_post_meta( $post_id, '_booking_url', true );
	if ( ! $url ) {
		$url = get_option( 'es_booking_url', '' );
	}
	if ( ! $url ) {
		$url = es_extract_booking_url( get_post_field( 'post_content', $post_id ) );
	}
	return esc_url_raw( $url );
}

function es_add_promo_to_booking_url( $url, $promo_code ) {
	if ( ! $url || ! $promo_code ) {
		return $url;
	}

	$base = strtok( $url, '#' );
	return $base . '#SearchResult:PromoCode=' . rawurlencode( $promo_code );
}

function es_get_booking_url( $post_id ) {
	return es_add_promo_to_booking_url( es_get_booking_base_url( $post_id ), es_get_promo_code( $post_id ) );
}

function es_get_validity_timestamp( $post_id ) {
	$date = get_post_meta( $post_id, '_validity_date', true );
	if ( ! $date ) {
		return false;
	}
	$timezone = wp_timezone();
	$parsed   = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $timezone );
	return $parsed ? $parsed->setTime( 23, 59, 59 )->getTimestamp() : false;
}

function es_is_offer_expired( $post_id ) {
	$expires = es_get_validity_timestamp( $post_id );
	return $expires && $expires < current_time( 'timestamp' );
}

function es_is_offer_ending_soon( $post_id ) {
	$expires = es_get_validity_timestamp( $post_id );
	if ( ! $expires || es_is_offer_expired( $post_id ) ) {
		return false;
	}
	$days = max( 0, absint( get_option( 'es_ending_soon_days', 7 ) ) );
	return $expires <= current_time( 'timestamp' ) + ( DAY_IN_SECONDS * $days );
}

/**
 * Upgrade legacy eRes links inside an offer to the tracked promo-code journey.
 */
function es_filter_offer_booking_links( $content ) {
	if ( ! is_singular( 'exclusive-offers' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id = get_the_ID();
	$promo   = es_get_promo_code( $post_id );
	if ( ! $promo || es_is_offer_expired( $post_id ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/(href=["\'])(https:\/\/nebulacrs\.hti\.app\/extraordinary\/[^"\']+\/desktop\.html(?:\?[^"\'#]*)?)(?:#[^"\']*)?(["\'])/i',
		function ( $matches ) use ( $promo ) {
			return $matches[1] . esc_url( es_add_promo_to_booking_url( html_entity_decode( $matches[2] ), $promo ) ) . $matches[3];
		},
		$content
	);
}
add_filter( 'the_content', 'es_filter_offer_booking_links', 20 );

function es_should_enqueue_assets() {
	return is_singular( 'exclusive-offers' ) || is_post_type_archive( 'exclusive-offers' );
}

function es_enqueue_public_assets() {
	wp_register_style( 'es-styles', ES_PLUGIN_URL . 'styles.css', array(), ES_VERSION );
	wp_register_style( 'es-lightbox', ES_PLUGIN_URL . 'vendors/lightbox2/css/lightbox.min.css', array(), '2.11.4' );
	wp_register_script( 'es-lightbox', ES_PLUGIN_URL . 'vendors/lightbox2/js/lightbox.min.js', array( 'jquery' ), '2.11.4', true );
	wp_register_script( 'es-booking-journey', ES_PLUGIN_URL . 'js/booking-journey.js', array(), ES_VERSION, true );

	if ( ! es_should_enqueue_assets() ) {
		return;
	}

	wp_enqueue_style( 'es-styles' );
	wp_enqueue_style( 'es-lightbox' );
	wp_enqueue_script( 'es-lightbox' );
	wp_enqueue_script( 'es-booking-journey' );

	$post_id = is_singular( 'exclusive-offers' ) ? get_queried_object_id() : 0;
	$fields  = array(
		'property'    => get_option( 'es_bigin_field_property', 'POTENTIALCF3' ),
		'referrer'    => get_option( 'es_bigin_field_referrer', 'POTENTIALCF1' ),
		'offer'         => get_option( 'es_bigin_field_offer', 'POTENTIALCF6' ),
		'productCode'   => get_option( 'es_bigin_field_product_code', 'POTENTIALCF7' ),
		'promoCode'     => get_option( 'es_bigin_field_promo_code', 'POTENTIALCF8' ),
		'landingUrl'    => get_option( 'es_bigin_field_landing_url', 'POTENTIALCF16' ),
		'utmSource'     => get_option( 'es_bigin_field_utm_source', 'POTENTIALCF11' ),
		'utmMedium'     => get_option( 'es_bigin_field_utm_medium', 'POTENTIALCF9' ),
		'utmCampaign'   => get_option( 'es_bigin_field_utm_campaign', 'POTENTIALCF12' ),
		'channel'       => get_option( 'es_bigin_field_channel', 'POTENTIALCF10' ),
		'submissionId'  => get_option( 'es_bigin_field_submission_id', 'POTENTIALCF13' ),
		'metaClickId'   => get_option( 'es_bigin_field_meta_click_id', 'POTENTIALCF14' ),
		'googleClickId' => get_option( 'es_bigin_field_google_click_id', 'POTENTIALCF15' ),
		'checkoutDate'  => get_option( 'es_bigin_field_checkout_date', 'POTENTIALCF54' ),
		'numberRooms'   => get_option( 'es_bigin_field_number_rooms', 'POTENTIALCF55' ),
	);

	wp_localize_script(
		'es-booking-journey',
		'esBookingJourney',
		array(
			'propertyName' => sanitize_text_field( get_option( 'es_property_name', '' ) ),
			'offerName'    => $post_id ? get_the_title( $post_id ) : '',
			'productCode'  => $post_id ? es_get_product_code( $post_id ) : '',
			'promoCode'    => $post_id ? es_get_promo_code( $post_id ) : '',
			'bookingUrl'   => $post_id ? es_get_booking_url( $post_id ) : '',
			'expired'      => $post_id ? es_is_offer_expired( $post_id ) : false,
			'fields'       => array_map( 'sanitize_text_field', $fields ),
		)
	);

	$accent = sanitize_hex_color( get_option( 'es_accent_color', '#f04e23' ) ) ?: '#f04e23';
	wp_add_inline_style( 'es-styles', ':root{--es-accent:' . $accent . ';}' );
}
add_action( 'wp_enqueue_scripts', 'es_enqueue_public_assets' );

function es_get_template( $template ) {
	if ( is_post_type_archive( 'exclusive-offers' ) ) {
		$theme_template = locate_template( array( 'archive-specials.php' ) );
		return $theme_template ?: ES_PLUGIN_DIR . 'templates/archive-specials.php';
	}
	if ( is_singular( 'exclusive-offers' ) ) {
		$theme_template = locate_template( array( 'single-specials.php' ) );
		return $theme_template ?: ES_PLUGIN_DIR . 'templates/single-specials.php';
	}
	return $template;
}
add_filter( 'template_include', 'es_get_template' );

function es_bigin_form_shortcode() {
	$form_code = get_option( 'es_form_code', '' );
	if ( ! $form_code ) {
		return '';
	}
	wp_enqueue_script( 'es-booking-journey' );
	return '<div class="es-bigin-form-wrapper">' . $form_code . '</div>';
}
add_shortcode( 'bigin_form', 'es_bigin_form_shortcode' );

function es_create_settings_page() {
	add_options_page( __( 'Extraordinary Specials', 'extra-special' ), __( 'Extraordinary Specials', 'extra-special' ), 'manage_options', 'extraordinary-specials', 'es_render_settings_page' );
}
add_action( 'admin_menu', 'es_create_settings_page' );

function es_sanitize_embed_code( $value ) {
	return current_user_can( 'unfiltered_html' ) ? $value : wp_kses_post( $value );
}

function es_register_settings() {
	$settings = array(
		'es_ending_soon_days'        => 'absint',
		'es_archive_title_rads'      => 'sanitize_key',
		'es_accent_color'            => 'sanitize_hex_color',
		'es_form_title'              => 'sanitize_text_field',
		'es_children_rads'           => 'sanitize_key',
		'es_dynamic_placeholders'    => 'sanitize_key',
		'es_form_code'               => 'es_sanitize_embed_code',
		'es_property_name'           => 'sanitize_text_field',
		'es_booking_url'             => 'esc_url_raw',
		'es_bigin_field_property'    => 'sanitize_text_field',
		'es_bigin_field_referrer'    => 'sanitize_text_field',
		'es_bigin_field_offer'       => 'sanitize_text_field',
		'es_bigin_field_product_code'=> 'sanitize_text_field',
		'es_bigin_field_promo_code'  => 'sanitize_text_field',
		'es_bigin_field_landing_url' => 'sanitize_text_field',
		'es_bigin_field_utm_source'  => 'sanitize_text_field',
		'es_bigin_field_utm_medium'  => 'sanitize_text_field',
		'es_bigin_field_utm_campaign'=> 'sanitize_text_field',
		'es_bigin_field_channel'     => 'sanitize_text_field',
		'es_bigin_field_submission_id'=> 'sanitize_text_field',
		'es_bigin_field_meta_click_id'=> 'sanitize_text_field',
		'es_bigin_field_google_click_id'=> 'sanitize_text_field',
		'es_bigin_field_checkout_date'=> 'sanitize_text_field',
		'es_bigin_field_number_rooms'=> 'sanitize_text_field',
	);
	foreach ( $settings as $name => $callback ) {
		register_setting( 'extraordinary-specials', $name, array( 'sanitize_callback' => $callback ) );
	}
}
add_action( 'admin_init', 'es_register_settings' );

function es_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$field_options = array(
		'es_bigin_field_property'     => array( 'Property field API name', 'POTENTIALCF3' ),
		'es_bigin_field_referrer'     => array( 'Referring website field API name', 'POTENTIALCF1' ),
		'es_bigin_field_offer'        => array( 'Offer name field API name', 'POTENTIALCF6' ),
		'es_bigin_field_product_code' => array( 'Offer code field API name', 'POTENTIALCF7' ),
		'es_bigin_field_promo_code'   => array( 'Promo code field API name', 'POTENTIALCF8' ),
		'es_bigin_field_landing_url'  => array( 'Landing URL field API name', 'POTENTIALCF16' ),
		'es_bigin_field_utm_source'   => array( 'UTM source field API name', 'POTENTIALCF11' ),
		'es_bigin_field_utm_medium'   => array( 'UTM medium field API name', 'POTENTIALCF9' ),
		'es_bigin_field_utm_campaign' => array( 'UTM campaign field API name', 'POTENTIALCF12' ),
		'es_bigin_field_channel'      => array( 'Lead channel field API name', 'POTENTIALCF10' ),
		'es_bigin_field_submission_id'=> array( 'Submission ID field API name', 'POTENTIALCF13' ),
		'es_bigin_field_meta_click_id'=> array( 'Meta click ID field API name', 'POTENTIALCF14' ),
		'es_bigin_field_google_click_id'=> array( 'Google click ID field API name', 'POTENTIALCF15' ),
		'es_bigin_field_checkout_date'=> array( 'Checkout date field API name', 'POTENTIALCF54' ),
		'es_bigin_field_number_rooms'=> array( 'Number of rooms field API name', 'POTENTIALCF55' ),
	);
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Extraordinary Specials', 'extra-special' ); ?></h1>
	<form action="options.php" method="post"><?php settings_fields( 'extraordinary-specials' ); ?>
	<h2><?php esc_html_e( 'Offer experience', 'extra-special' ); ?></h2><table class="form-table" role="presentation">
	<tr><th><label for="es_property_name">Property name</label></th><td><input class="regular-text" id="es_property_name" name="es_property_name" value="<?php echo esc_attr( get_option( 'es_property_name', '' ) ); ?>"></td></tr>
	<tr><th><label for="es_booking_url">Site-wide eRes booking URL</label></th><td><input class="large-text" type="url" id="es_booking_url" name="es_booking_url" value="<?php echo esc_attr( get_option( 'es_booking_url', '' ) ); ?>"></td></tr>
	<tr><th><label for="es_ending_soon_days">Ending-soon window</label></th><td><input type="number" min="0" id="es_ending_soon_days" name="es_ending_soon_days" value="<?php echo esc_attr( get_option( 'es_ending_soon_days', 7 ) ); ?>"> days</td></tr>
	<tr><th><label for="es_accent_color">Accent colour</label></th><td><input id="es_accent_color" name="es_accent_color" value="<?php echo esc_attr( get_option( 'es_accent_color', '#f04e23' ) ); ?>"></td></tr>
	<tr><th>Archive title</th><td><select name="es_archive_title_rads"><option value="show" <?php selected( get_option( 'es_archive_title_rads', 'show' ), 'show' ); ?>>Show</option><option value="hide" <?php selected( get_option( 'es_archive_title_rads', 'show' ), 'hide' ); ?>>Hide</option></select></td></tr>
	</table>
	<h2><?php esc_html_e( 'Bigin enquiry form', 'extra-special' ); ?></h2><table class="form-table" role="presentation">
	<tr><th><label for="es_form_title">Form title</label></th><td><input class="regular-text" id="es_form_title" name="es_form_title" value="<?php echo esc_attr( get_option( 'es_form_title', 'Enquire about this offer' ) ); ?>"></td></tr>
	<tr><th><label for="es_form_code">Bigin embed code</label></th><td><textarea class="large-text code" rows="10" id="es_form_code" name="es_form_code"><?php echo esc_textarea( get_option( 'es_form_code', '' ) ); ?></textarea></td></tr>
	<?php foreach ( $field_options as $name => $details ) : ?>
	<tr><th><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $details[0] ); ?></label></th><td><input class="regular-text code" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( get_option( $name, $details[1] ) ); ?>"></td></tr>
	<?php endforeach; ?>
	</table><?php submit_button(); ?></form></div>
	<?php
}

function es_deactivate() {
	$timestamp = wp_next_scheduled( 'es_daily_event' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'es_daily_event' );
	}
}
register_deactivation_hook( __FILE__, 'es_deactivate' );
