<?php
/**
 * /llms.txt generation.
 *
 * @package AEO_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders an on-site /llms.txt file.
 *
 * Follows the llmstxt.org convention: an H1 site name, an optional
 * blockquote summary, then grouped link lists with short descriptions.
 */
class AEO_Kit_LLMS_Txt {

	const QUERY_VAR  = 'aeo_llms';
	const CACHE_KEY  = 'aeo_kit_llms_txt';
	const CACHE_TTL  = 6 * HOUR_IN_SECONDS;

	/**
	 * Wire rewrite + render hooks.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush' ), 20 );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'maybe_plain_route' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );

		// Bust cache whenever content changes.
		add_action( 'save_post', array( __CLASS__, 'clear_cache' ) );
		add_action( 'deleted_post', array( __CLASS__, 'clear_cache' ) );
	}

	/**
	 * Self-healing flush — fires once after activation or a settings change,
	 * including the git-drop and multisite-subsite cases the activation hook misses.
	 */
	public static function maybe_flush() {
		if ( get_option( 'aeo_kit_flush_needed' ) ) {
			flush_rewrite_rules( false ); // Soft flush — no .htaccess rewrite.
			delete_option( 'aeo_kit_flush_needed' );
		}
	}

	/**
	 * Fallback route for sites using "Plain" permalinks (no rewrite engine).
	 *
	 * @param WP $wp Current WP environment.
	 */
	public function maybe_plain_route( $wp ) {
		if ( get_option( 'permalink_structure' ) ) {
			return; // Pretty permalinks → the rewrite rule handles it.
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- path only, compared literally below.
		$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
		if ( 'llms.txt' === $path ) {
			$wp->query_vars[ self::QUERY_VAR ] = 1;
		}
	}

	/**
	 * Register the rewrite rule for /llms.txt.
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Whitelist our query var.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Flush the cached body.
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Render the file if this is the /llms.txt request.
	 */
	public function maybe_render() {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		if ( ! AEO_Kit::get_setting( 'enable_llms' ) ) {
			status_header( 404 );
			nocache_headers();
			exit;
		}

		$body = get_transient( self::CACHE_KEY );
		if ( false === $body ) {
			$body = $this->build();
			set_transient( self::CACHE_KEY, $body, self::CACHE_TTL );
		}

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: all' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped/sanitized parts below; plain text output.
		exit;
	}

	/**
	 * Build the llms.txt body.
	 *
	 * @return string
	 */
	private function build() {
		$site_name = get_bloginfo( 'name' );
		$tagline   = get_bloginfo( 'description' );

		$lines   = array();
		$lines[] = '# ' . $this->clean( $site_name );
		$lines[] = '';
		if ( $tagline ) {
			$lines[] = '> ' . $this->clean( $tagline );
			$lines[] = '';
		}
		$lines[] = sprintf( 'Home: %s', home_url( '/' ) );
		$lines[] = sprintf( 'Generated: %s by AEO Kit', gmdate( 'Y-m-d' ) );
		$lines[] = '';

		$post_types = (array) AEO_Kit::get_setting( 'llms_post_types' );
		if ( empty( $post_types ) ) {
			$post_types = array( 'page', 'post' );
		}

		foreach ( $post_types as $pt ) {
			$obj = get_post_type_object( $pt );
			if ( ! $obj ) {
				continue;
			}

			/**
			 * Filter the per-post-type entry cap for llms.txt.
			 *
			 * @param int    $limit Max entries per type.
			 * @param string $pt    Post type slug.
			 */
			$limit = (int) apply_filters( 'aeo_kit_llms_per_type_limit', 200, $pt );
			$limit = max( 1, $limit );

			$query = new WP_Query(
				array(
					'post_type'              => $pt,
					'post_status'            => 'publish',
					'posts_per_page'         => $limit,
					'orderby'                => 'modified',
					'order'                  => 'DESC',
					'ignore_sticky_posts'    => true,
					'update_post_term_cache' => false,
				)
			);

			if ( ! $query->have_posts() ) {
				continue;
			}

			$lines[] = '## ' . $this->clean( $obj->labels->name );
			$lines[] = '';

			foreach ( $query->posts as $p ) {
				$title = $this->clean( get_the_title( $p ) );
				$url   = get_permalink( $p );
				$desc  = $this->summary( $p );
				$entry = sprintf( '- [%s](%s)', $title, $url );
				if ( '' !== $desc ) {
					$entry .= ': ' . $desc;
				}
				$lines[] = $entry;
			}

			// Surface truncation instead of silently dropping content.
			if ( (int) $query->found_posts > $limit ) {
				$archive = get_post_type_archive_link( $pt );
				$lines[] = sprintf( '- _…more %1$s at %2$s_', $this->clean( $obj->labels->name ), $archive ? $archive : home_url( '/' ) );
			}

			$lines[] = '';
			wp_reset_postdata();
		}

		/**
		 * Filter the final llms.txt body.
		 *
		 * @param string $body  Generated text.
		 * @param array  $lines Line array before join.
		 */
		return apply_filters( 'aeo_kit_llms_txt', implode( "\n", $lines ) . "\n", $lines );
	}

	/**
	 * Per-post summary: custom AEO summary → excerpt → trimmed content.
	 *
	 * @param WP_Post $p Post object.
	 * @return string
	 */
	private function summary( $p ) {
		$summary = get_post_meta( $p->ID, '_aeo_summary', true );
		if ( '' === $summary ) {
			$summary = has_excerpt( $p ) ? get_the_excerpt( $p ) : wp_trim_words( wp_strip_all_tags( $p->post_content ), 30, '…' );
		}
		return $this->clean( $summary );
	}

	/**
	 * Collapse whitespace and strip markup for single-line text.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function clean( $text ) {
		$text = wp_strip_all_tags( (string) $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		return trim( $text );
	}
}
