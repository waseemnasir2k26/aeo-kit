<?php
/**
 * Per-post meta box: AEO summary + FAQ pairs.
 *
 * @package AEO_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an "AEO Kit" meta box to public post types.
 */
class AEO_Kit_Meta_Box {

	const NONCE = 'aeo_kit_meta_nonce';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register the meta box on public post types.
	 */
	public function register() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $post_types['attachment'] );

		foreach ( $post_types as $pt ) {
			add_meta_box(
				'aeo_kit_meta',
				__( 'AEO Kit — Answer Engine data', 'aeo-kit' ),
				array( $this, 'render' ),
				$pt,
				'normal',
				'default'
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render( $post ) {
		wp_nonce_field( 'aeo_kit_save_meta', self::NONCE );

		$summary = get_post_meta( $post->ID, '_aeo_summary', true );
		$faqs    = AEO_Kit_FAQ::get_faqs( $post->ID );
		?>
		<p>
			<label for="aeo_summary"><strong><?php esc_html_e( 'Citation summary', 'aeo-kit' ); ?></strong></label><br />
			<textarea id="aeo_summary" name="aeo_summary" class="large-text" rows="3" maxlength="320" placeholder="<?php esc_attr_e( 'One or two crisp sentences an AI can quote verbatim. Used in /llms.txt and the page description.', 'aeo-kit' ); ?>"><?php echo esc_textarea( $summary ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Leave blank to auto-derive from the excerpt or content.', 'aeo-kit' ); ?></span>
		</p>

		<p><strong><?php esc_html_e( 'FAQ (powers FAQPage schema + [aeo_faq] shortcode)', 'aeo-kit' ); ?></strong></p>
		<div id="aeo-faq-rows" class="aeo-faq-rows">
			<?php
			if ( empty( $faqs ) ) {
				$faqs = array( array( 'q' => '', 'a' => '' ) );
			}
			foreach ( $faqs as $i => $faq ) :
				?>
				<div class="aeo-faq-row">
					<input type="text" name="aeo_faq_q[]" class="widefat" value="<?php echo esc_attr( $faq['q'] ); ?>" placeholder="<?php esc_attr_e( 'Question', 'aeo-kit' ); ?>" />
					<textarea name="aeo_faq_a[]" class="widefat" rows="2" placeholder="<?php esc_attr_e( 'Answer', 'aeo-kit' ); ?>"><?php echo esc_textarea( $faq['a'] ); ?></textarea>
					<button type="button" class="button-link aeo-faq-remove" aria-label="<?php esc_attr_e( 'Remove this FAQ', 'aeo-kit' ); ?>">&times; <?php esc_html_e( 'Remove', 'aeo-kit' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<p>
			<button type="button" class="button" id="aeo-faq-add"><?php esc_html_e( '+ Add FAQ', 'aeo-kit' ); ?></button>
			<span class="description"><?php esc_html_e( 'Add the [aeo_faq] shortcode where you want the questions to appear on the page.', 'aeo-kit' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Save meta with nonce + capability + sanitization.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( $post_id, $post ) {
		// Autosave / revision / bulk guards.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Nonce.
		if ( ! isset( $_POST[ self::NONCE ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) );
		if ( ! wp_verify_nonce( $nonce, 'aeo_kit_save_meta' ) ) {
			return;
		}

		// Capability.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Summary.
		if ( isset( $_POST['aeo_summary'] ) ) {
			$summary = sanitize_textarea_field( wp_unslash( $_POST['aeo_summary'] ) );
			if ( '' === $summary ) {
				delete_post_meta( $post_id, '_aeo_summary' );
			} else {
				update_post_meta( $post_id, '_aeo_summary', $summary );
			}
		}

		// FAQ pairs.
		$questions = isset( $_POST['aeo_faq_q'] ) ? (array) wp_unslash( $_POST['aeo_faq_q'] ) : array();
		$answers   = isset( $_POST['aeo_faq_a'] ) ? (array) wp_unslash( $_POST['aeo_faq_a'] ) : array();

		$pairs = array();
		$count = count( $questions );
		for ( $i = 0; $i < $count; $i++ ) {
			$q = sanitize_text_field( $questions[ $i ] );
			$a = isset( $answers[ $i ] ) ? wp_kses_post( $answers[ $i ] ) : '';
			if ( '' !== trim( $q ) && '' !== trim( wp_strip_all_tags( $a ) ) ) {
				$pairs[] = array(
					'q' => $q,
					'a' => $a,
				);
			}
		}

		if ( empty( $pairs ) ) {
			delete_post_meta( $post_id, AEO_Kit_FAQ::META_KEY );
		} else {
			update_post_meta( $post_id, AEO_Kit_FAQ::META_KEY, $pairs );
		}

		// Refresh llms.txt cache.
		if ( class_exists( 'AEO_Kit_LLMS_Txt' ) ) {
			AEO_Kit_LLMS_Txt::clear_cache();
		}
	}
}
