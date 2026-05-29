<?php
/**
 * JSON-LD schema output.
 *
 * @package AEO_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and prints JSON-LD graphs in wp_head.
 */
class AEO_Kit_Schema {

	/**
	 * Hook output late in the head.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'output' ), 20 );
	}

	/**
	 * Decide what schema to print for the current request.
	 */
	public function output() {
		if ( is_admin() || is_feed() || is_404() ) {
			return;
		}

		$graph = array();

		// Identity + WebSite only on the true front page (avoids duplicate
		// #identity / #website nodes on a separate static blog index).
		if ( is_front_page() ) {
			if ( AEO_Kit::get_setting( 'enable_org' ) ) {
				$graph[] = $this->organization();
			}
			if ( AEO_Kit::get_setting( 'enable_website' ) ) {
				$graph[] = $this->website();
			}
		}

		if ( is_singular() ) {
			// Emit a self-contained Organization node so Article/FAQ
			// publisher @id refs resolve within a single page's graph.
			if ( AEO_Kit::get_setting( 'enable_org' ) && ! is_front_page() ) {
				$graph[] = $this->organization();
			}
			if ( AEO_Kit::get_setting( 'enable_article' ) && is_singular( 'post' ) ) {
				$article = $this->article();
				if ( $article ) {
					$graph[] = $article;
				}
			}
			if ( AEO_Kit::get_setting( 'enable_breadcrumb' ) ) {
				$crumbs = $this->breadcrumb();
				if ( $crumbs ) {
					$graph[] = $crumbs;
				}
			}
		}

		/**
		 * Filter the schema graph before output.
		 *
		 * @param array $graph Array of schema nodes.
		 */
		$graph = apply_filters( 'aeo_kit_schema_graph', $graph );

		if ( empty( $graph ) ) {
			return;
		}

		$doc = array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $graph ),
		);

		$this->print_json_ld( $doc, 'aeo-graph' );
	}

	/**
	 * Print a JSON-LD block.
	 *
	 * @param array  $data Schema data.
	 * @param string $id   DOM id for debugging.
	 */
	public static function print_json_ld( $data, $id = '' ) {
		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			return;
		}
		// wp_json_encode escapes forward slashes by default, so </script> cannot break out.
		printf(
			"\n<script type=\"application/ld+json\"%s>%s</script>\n",
			$id ? ' id="' . esc_attr( $id ) . '"' : '',
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is safe; slashes escaped by wp_json_encode.
		);
	}

	/**
	 * Organization / Person node.
	 *
	 * @return array
	 */
	private function organization() {
		$name = AEO_Kit::get_setting( 'org_name' );
		if ( '' === $name ) {
			$name = get_bloginfo( 'name' );
		}

		$type = AEO_Kit::get_setting( 'org_type' );
		$type = $type ? $type : 'Organization';

		$node = array(
			'@type' => $type,
			'@id'   => home_url( '/#identity' ),
			'name'  => $name,
			'url'   => home_url( '/' ),
		);

		$logo = AEO_Kit::get_setting( 'org_logo' );
		if ( '' === $logo && function_exists( 'get_site_icon_url' ) ) {
			$logo = get_site_icon_url( 512 );
		}
		if ( $logo ) {
			$logo          = esc_url_raw( $logo );
			$node['logo']  = $logo;
			$node['image'] = $logo;
		}

		$sameas = $this->same_as();
		if ( ! empty( $sameas ) ) {
			$node['sameAs'] = $sameas;
		}

		return $node;
	}

	/**
	 * WebSite node with search action.
	 *
	 * @return array
	 */
	private function website() {
		return array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description' ),
			'publisher'       => array( '@id' => home_url( '/#identity' ) ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	/**
	 * Article / BlogPosting node for the current post.
	 *
	 * @return array|null
	 */
	private function article() {
		$post = get_post();
		if ( ! $post ) {
			return null;
		}

		$summary = get_post_meta( $post->ID, '_aeo_summary', true );
		if ( '' === $summary ) {
			$summary = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '…' );
		}

		$node = array(
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink( $post ) . '#article',
			'headline'         => get_the_title( $post ),
			'description'      => $summary,
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'url'              => get_permalink( $post ),
			'mainEntityOfPage' => get_permalink( $post ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $post->post_author ),
			),
			'publisher'        => array( '@id' => home_url( '/#identity' ) ),
		);

		// Google requires `image` for Article rich results — always populate it
		// via a fallback chain: featured image → first inline image → logo/icon.
		$img = '';
		if ( has_post_thumbnail( $post ) ) {
			$img = wp_get_attachment_image_url( get_post_thumbnail_id( $post ), 'full' );
		}
		if ( ! $img && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $m ) ) {
			$img = $m[1];
		}
		if ( ! $img ) {
			$img = $this->fallback_image();
		}
		if ( $img ) {
			$node['image'] = esc_url_raw( $img );
		}

		return $node;
	}

	/**
	 * Site-wide fallback image: configured logo, else site icon.
	 *
	 * @return string
	 */
	private function fallback_image() {
		$logo = AEO_Kit::get_setting( 'org_logo' );
		if ( '' === $logo && function_exists( 'get_site_icon_url' ) ) {
			$logo = get_site_icon_url( 512 );
		}
		return $logo ? $logo : '';
	}

	/**
	 * BreadcrumbList for the current singular view.
	 *
	 * @return array|null
	 */
	private function breadcrumb() {
		$post = get_post();
		if ( ! $post ) {
			return null;
		}

		$items = array();
		$pos   = 1;

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => __( 'Home', 'aeo-kit' ),
			'item'     => home_url( '/' ),
		);

		// Ancestor pages.
		$ancestors = array_reverse( get_post_ancestors( $post ) );
		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => get_the_title( $ancestor_id ),
				'item'     => get_permalink( $ancestor_id ),
			);
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos++,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		);

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => get_permalink( $post ) . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	/**
	 * Parse social profile URLs into a sameAs array.
	 *
	 * @return array
	 */
	private function same_as() {
		$raw = AEO_Kit::get_setting( 'social_profiles' );
		if ( '' === $raw ) {
			return array();
		}
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$out   = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$out[] = esc_url_raw( $line );
			}
		}
		return array_values( array_filter( $out ) );
	}
}
