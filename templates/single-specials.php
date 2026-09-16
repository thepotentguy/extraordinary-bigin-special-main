<?php
/**
 * Single offer template.
 *
 * @package Extra_Special
 */

get_header();
?>
<main id="primary" class="site-main">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php
		$post_id      = get_the_ID();
		$price        = get_post_meta( $post_id, '_price', true );
		$packages     = get_post_meta( $post_id, '_packages', true );
		$expires      = es_get_validity_timestamp( $post_id );
		$is_expired   = es_is_offer_expired( $post_id );
		$booking_url  = es_get_booking_url( $post_id );
		$gallery_ids  = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_image_ids', true ) ) ) );
		$form_title   = get_option( 'es_form_title', __( 'Enquire about this offer', 'extra-special' ) );
		?>
		<article <?php post_class( 'specials-single' ); ?>>
			<header class="esp-hero-section">
				<?php if ( has_post_thumbnail() ) : ?><?php the_post_thumbnail( 'full', array( 'class' => 'esp-hero-image' ) ); ?><?php endif; ?>
				<div class="esp-hero-shade"></div>
				<div class="esp-hero-content">
					<?php if ( $is_expired ) : ?><span class="esp-status esp-status-ended"><?php esc_html_e( 'Offer ended', 'extra-special' ); ?></span>
					<?php elseif ( es_is_offer_ending_soon( $post_id ) ) : ?><span class="esp-status"><?php esc_html_e( 'Ending soon', 'extra-special' ); ?></span><?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<?php if ( $price ) : ?><p class="esp-price"><?php echo esc_html( $price ); ?></p><?php endif; ?>
					<?php if ( $packages ) : ?><p class="esp-package"><?php echo esc_html( $packages ); ?></p><?php endif; ?>
					<?php if ( $expires ) : ?><p class="esp-validity"><?php echo esc_html( sprintf( $is_expired ? __( 'Ended %s', 'extra-special' ) : __( 'Valid until %s', 'extra-special' ), wp_date( 'j F Y', $expires ) ) ); ?></p><?php endif; ?>
					<div class="esp-actions">
						<?php if ( $booking_url && ! $is_expired ) : ?><a class="esp-cta-btn esp-book-now" href="<?php echo esc_url( $booking_url ); ?>"><?php esc_html_e( 'Book this offer', 'extra-special' ); ?></a><?php endif; ?>
						<a class="esp-cta-btn esp-cta-secondary" href="#enquiry-form"><?php echo esc_html( $is_expired ? __( 'Ask about current offers', 'extra-special' ) : __( 'Enquire now', 'extra-special' ) ); ?></a>
					</div>
				</div>
			</header>

			<?php if ( $gallery_ids ) : ?>
				<section class="esp-gallery" aria-label="<?php esc_attr_e( 'Offer gallery', 'extra-special' ); ?>">
					<?php foreach ( $gallery_ids as $image_id ) : ?>
						<?php $full = wp_get_attachment_image_url( $image_id, 'full' ); ?>
						<?php if ( $full ) : ?><a href="<?php echo esc_url( $full ); ?>" data-lightbox="offer-gallery"><?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'loading' => 'lazy' ) ); ?></a><?php endif; ?>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

			<div class="esp-content-section">
				<div class="esp-content-two-thirds"><?php the_content(); ?></div>
				<aside class="esp-content-one-third" id="enquiry-form">
					<div class="esp-enquiry-form"><h2 class="esp-form-title"><?php echo esc_html( $form_title ); ?></h2><?php echo do_shortcode( '[bigin_form]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</aside>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
