<?php
/**
 * Offer archive template.
 *
 * @package Extra_Special
 */

get_header();
?>
<main id="primary" class="site-main specials-archive">
	<?php if ( 'show' === get_option( 'es_archive_title_rads', 'show' ) ) : ?><header class="esp-archive-header"><h1><?php post_type_archive_title(); ?></h1></header><?php endif; ?>
	<?php if ( have_posts() ) : ?>
		<div class="esp-specials-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php
				$post_id    = get_the_ID();
				$price      = get_post_meta( $post_id, '_price', true );
				$packages   = get_post_meta( $post_id, '_packages', true );
				$expires    = es_get_validity_timestamp( $post_id );
				$is_expired = es_is_offer_expired( $post_id );
				?>
				<article <?php post_class( 'esp-special-card' ); ?>>
					<a class="esp-card-image" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?><?php the_post_thumbnail( 'large', array( 'class' => 'esp-special-img', 'loading' => 'lazy' ) ); ?><?php endif; ?>
						<?php if ( $is_expired ) : ?><span class="esp-status esp-status-ended"><?php esc_html_e( 'Offer ended', 'extra-special' ); ?></span><?php elseif ( es_is_offer_ending_soon( $post_id ) ) : ?><span class="esp-status"><?php esc_html_e( 'Ending soon', 'extra-special' ); ?></span><?php endif; ?>
					</a>
					<div class="esp-card-content"><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php if ( $price ) : ?><p class="esp-price"><?php echo esc_html( $price ); ?></p><?php endif; ?>
						<?php if ( $packages ) : ?><p><?php echo esc_html( $packages ); ?></p><?php endif; ?>
						<?php if ( $expires ) : ?><p class="esp-validity"><?php echo esc_html( sprintf( $is_expired ? __( 'Ended %s', 'extra-special' ) : __( 'Valid until %s', 'extra-special' ), wp_date( 'j F Y', $expires ) ) ); ?></p><?php endif; ?>
						<a class="esp-cta-btn" href="<?php the_permalink(); ?>"><?php echo esc_html( $is_expired ? __( 'View details', 'extra-special' ) : __( 'View offer', 'extra-special' ) ); ?></a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_navigation(); ?>
	<?php else : ?><p class="esp-no-offers"><?php esc_html_e( 'No offers are currently available.', 'extra-special' ); ?></p><?php endif; ?>
</main>
<?php get_footer(); ?>
