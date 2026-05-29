<?php
/**
 * FAQ rendering + FAQPage schema.
 *
 * @package AEO_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads FAQ pairs from post meta, renders an accessible accordion via the
 * [aeo_faq] shortcode, and emits FAQPage JSON-LD on single content.
 */
class AEO_Kit_FAQ {

	const META_KEY = '_aeo_faq';

	/**
	 * Register shortcode + schema hook.
	 */
	public function __construct() {
		add_shortcode( 'aeo_faq', array( $this, 'shortcode' ) );
		add_action( 'wp_head', array( $this, 'schema' ), 21 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_style' ) );
	}

	/**
	 * Register the front-end FAQ stylesheet (enqueued on demand from the shortcode).
	 */
	public function register_style() {
		wp_register_style( 'aeo-kit-public', AEO_KIT_URL . 'assets/public.css', array(), AEO_KIT_VERSION );
	}

	/**
	 * Get sanitized FAQ pairs for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array List of array( 'q' => string, 'a' => string ).
	 */
	public static function get_faqs( $post_id ) {
		$raw = get_post_meta( $post_id, self::META_KEY, true );
		if ( empty( $raw ) || ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $pair ) {
			if ( ! is_array( $pair ) ) {
				continue;
			}
			$q = isset( $pair['q'] ) ? trim( (string) $pair['q'] ) : '';
			$a = isset( $pair['a'] ) ? trim( (string) $pair['a'] ) : '';
			if ( '' !== $q && '' !== $a ) {
				$out[] = array(
					'q' => $q,
					'a' => $a,
				);
			}
		}
		return $out;
	}

	/**
	 * Render [aeo_faq] — an accessible Q/A list for the current post.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title' => '',
				'id'    => 0,
			),
			$atts,
			'aeo_faq'
		);

		$post_id = $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$faqs = self::get_faqs( $post_id );
		if ( empty( $faqs ) ) {
			return '';
		}

		wp_enqueue_style( 'aeo-kit-public' );

		// No schema.org microdata here on purpose — the canonical FAQPage is
		// emitted once as JSON-LD (see schema()). Duplicate markup risks a
		// "conflicting structured data" flag. This is plain semantic HTML.
		ob_start();
		?>
		<div class="aeo-faq">
			<?php if ( $atts['title'] ) : ?>
				<h2 class="aeo-faq__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>
			<?php foreach ( $faqs as $i => $faq ) : ?>
				<details class="aeo-faq__item"<?php echo 0 === $i ? ' open' : ''; ?>>
					<summary class="aeo-faq__q"><?php echo esc_html( $faq['q'] ); ?></summary>
					<div class="aeo-faq__a"><?php echo wp_kses_post( wpautop( $faq['a'] ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Emit FAQPage JSON-LD for the current singular view.
	 */
	public function schema() {
		if ( is_admin() || ! is_singular() || ! AEO_Kit::get_setting( 'enable_faq' ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$faqs = self::get_faqs( $post_id );
		if ( empty( $faqs ) ) {
			return;
		}

		// Only emit FAQPage when the [aeo_faq] shortcode is actually on the
		// page — structured data must match content visible to the user.
		$post = get_post( $post_id );
		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'aeo_faq' ) ) {
			return;
		}

		// Google allows a limited HTML allowlist in Answer text; keep links and
		// lists (better for both rich results and LLM extraction) but nothing else.
		$answer_allowed = array(
			'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
			'br' => array(), 'ol' => array(), 'ul' => array(), 'li' => array(), 'p' => array(), 'div' => array(),
			'a'  => array( 'href' => array() ),
			'b'  => array(), 'strong' => array(), 'i' => array(), 'em' => array(),
		);

		$entities = array();
		foreach ( $faqs as $faq ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $faq['q'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => trim( wp_kses( wpautop( $faq['a'] ), $answer_allowed ) ),
				),
			);
		}

		$doc = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'@id'        => get_permalink( $post_id ) . '#faq',
			'mainEntity' => $entities,
		);

		AEO_Kit_Schema::print_json_ld( $doc, 'aeo-faq' );
	}
}
