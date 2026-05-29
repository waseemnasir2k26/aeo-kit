<?php
/**
 * Settings page + registration.
 *
 * @package AEO_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin settings screen under Settings → AEO Kit.
 */
class AEO_Kit_Settings {

	const OPTION = 'aeo_kit_settings';
	const GROUP  = 'aeo_kit_group';

	/**
	 * Hook into admin.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Register the options page.
	 */
	public function add_page() {
		add_options_page(
			__( 'AEO Kit', 'aeo-kit' ),
			__( 'AEO Kit', 'aeo-kit' ),
			'manage_options',
			'aeo-kit',
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue admin assets only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function assets( $hook ) {
		if ( 'settings_page_aeo-kit' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'aeo-kit-admin', AEO_KIT_URL . 'assets/admin.css', array(), AEO_KIT_VERSION );
		wp_enqueue_script( 'aeo-kit-admin', AEO_KIT_URL . 'assets/admin.js', array( 'jquery' ), AEO_KIT_VERSION, true );
		// The media picker is only used on our settings page.
		if ( 'settings_page_aeo-kit' === $hook && function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Register setting + sanitizer.
	 */
	public function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => AEO_Kit::DEFAULTS,
			)
		);
	}

	/**
	 * Sanitize all incoming settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$out = array();

		$out['org_name'] = isset( $input['org_name'] ) ? sanitize_text_field( $input['org_name'] ) : '';
		$out['org_logo'] = isset( $input['org_logo'] ) ? esc_url_raw( $input['org_logo'] ) : '';

		$allowed_types   = array( 'Organization', 'LocalBusiness', 'Person' );
		$out['org_type'] = ( isset( $input['org_type'] ) && in_array( $input['org_type'], $allowed_types, true ) )
			? $input['org_type'] : 'Organization';

		// Social profiles: one URL per line.
		$profiles = isset( $input['social_profiles'] ) ? (string) $input['social_profiles'] : '';
		$lines    = preg_split( '/\r\n|\r|\n/', $profiles );
		$clean    = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$url = esc_url_raw( $line );
			if ( $url ) {
				$clean[] = $url;
			}
		}
		$out['social_profiles'] = implode( "\n", $clean );

		foreach ( array( 'enable_org', 'enable_website', 'enable_article', 'enable_breadcrumb', 'enable_faq', 'enable_llms' ) as $flag ) {
			$out[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		$valid_pts              = get_post_types( array( 'public' => true ) );
		$pts                    = isset( $input['llms_post_types'] ) ? (array) $input['llms_post_types'] : array();
		$pts                    = array_diff( array_map( 'sanitize_key', $pts ), array( 'attachment' ) );
		$out['llms_post_types'] = array_values( array_intersect( $pts, array_keys( $valid_pts ) ) );
		if ( empty( $out['llms_post_types'] ) ) {
			$out['llms_post_types'] = array( 'page', 'post' );
		}

		// llms.txt rewrite may have toggled; flush on next load (handled on init).
		update_option( 'aeo_kit_flush_needed', 1, false );

		// Post-type selection / enable flag may have changed — drop the cache.
		if ( class_exists( 'AEO_Kit_LLMS_Txt' ) ) {
			AEO_Kit_LLMS_Txt::clear_cache();
		}

		return $out;
	}

	/**
	 * Render the settings page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Rewrite flush is handled on `init` via AEO_Kit_LLMS_Txt::maybe_flush().

		$s              = get_option( self::OPTION, AEO_Kit::DEFAULTS );
		$s              = wp_parse_args( $s, AEO_Kit::DEFAULTS );
		$llms_url       = home_url( '/llms.txt' );
		$selected_pts   = (array) $s['llms_post_types'];
		$public_pts     = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div class="wrap aeo-kit-wrap">
			<h1><?php esc_html_e( 'AEO Kit — Answer Engine Optimization', 'aeo-kit' ); ?></h1>
			<p class="aeo-kit-sub">
				<?php esc_html_e( 'Make your content quotable by AI answer engines. Everything below runs on your own server — no external API, no cloud cost.', 'aeo-kit' ); ?>
			</p>

			<?php if ( ! empty( $s['enable_llms'] ) ) : ?>
				<div class="aeo-kit-notice">
					<strong><?php esc_html_e( 'Your llms.txt is live at:', 'aeo-kit' ); ?></strong>
					<a href="<?php echo esc_url( $llms_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $llms_url ); ?></a>
					<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
						<br /><em><?php esc_html_e( 'Heads up: your site uses "Plain" permalinks. A fallback route serves llms.txt, but enabling pretty permalinks (Settings → Permalinks) is recommended.', 'aeo-kit' ); ?></em>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<h2 class="title"><?php esc_html_e( 'Identity (Organization schema)', 'aeo-kit' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="aeo_org_name"><?php esc_html_e( 'Name', 'aeo-kit' ); ?></label></th>
						<td>
							<input type="text" id="aeo_org_name" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[org_name]" value="<?php echo esc_attr( $s['org_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Defaults to your site title if left blank.', 'aeo-kit' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aeo_org_type"><?php esc_html_e( 'Type', 'aeo-kit' ); ?></label></th>
						<td>
							<select id="aeo_org_type" name="<?php echo esc_attr( self::OPTION ); ?>[org_type]">
								<?php foreach ( array( 'Organization', 'LocalBusiness', 'Person' ) as $type ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $s['org_type'], $type ); ?>><?php echo esc_html( $type ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aeo_org_logo"><?php esc_html_e( 'Logo URL', 'aeo-kit' ); ?></label></th>
						<td>
							<input type="url" id="aeo_org_logo" class="regular-text aeo-media-field" name="<?php echo esc_attr( self::OPTION ); ?>[org_logo]" value="<?php echo esc_url( $s['org_logo'] ); ?>" />
							<button type="button" class="button aeo-media-pick"><?php esc_html_e( 'Choose', 'aeo-kit' ); ?></button>
							<p class="description"><?php esc_html_e( 'Defaults to your site icon if left blank.', 'aeo-kit' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aeo_social"><?php esc_html_e( 'Social / sameAs URLs', 'aeo-kit' ); ?></label></th>
						<td>
							<textarea id="aeo_social" class="large-text code" rows="4" name="<?php echo esc_attr( self::OPTION ); ?>[social_profiles]" placeholder="https://linkedin.com/company/&#10;https://x.com/handle"><?php echo esc_textarea( $s['social_profiles'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One URL per line. Feeds the schema "sameAs" array — helps engines tie your entity together.', 'aeo-kit' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Schema output (JSON-LD)', 'aeo-kit' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$toggles = array(
						'enable_org'        => __( 'Organization / Person schema on the home page', 'aeo-kit' ),
						'enable_website'    => __( 'WebSite schema (+ sitelinks search box)', 'aeo-kit' ),
						'enable_article'    => __( 'Article / BlogPosting schema on posts', 'aeo-kit' ),
						'enable_breadcrumb' => __( 'BreadcrumbList schema on single content', 'aeo-kit' ),
						'enable_faq'        => __( 'FAQPage schema from FAQ blocks', 'aeo-kit' ),
					);
					foreach ( $toggles as $key => $label ) :
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td>
								<label class="aeo-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $s[ $key ] ) ); ?> />
									<?php esc_html_e( 'Enabled', 'aeo-kit' ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2 class="title"><?php esc_html_e( 'llms.txt', 'aeo-kit' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Generate /llms.txt', 'aeo-kit' ); ?></th>
						<td>
							<label class="aeo-switch">
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enable_llms]" value="1" <?php checked( ! empty( $s['enable_llms'] ) ); ?> />
								<?php esc_html_e( 'Enabled', 'aeo-kit' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'A plain-text map of your best content for LLMs, following the llmstxt.org convention.', 'aeo-kit' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Include post types', 'aeo-kit' ); ?></th>
						<td>
							<?php foreach ( $public_pts as $pt ) :
								if ( 'attachment' === $pt->name ) {
									continue;
								}
								?>
								<label style="display:inline-block;margin-right:14px;">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[llms_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $selected_pts, true ) ); ?> />
									<?php echo esc_html( $pt->labels->name ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<div class="aeo-kit-footer">
				<?php
				printf(
					/* translators: %s: SkynetLabs link */
					esc_html__( 'AEO Kit is free and open source. Built by %s.', 'aeo-kit' ),
					'<a href="https://www.skynetjoe.com" target="_blank" rel="noopener">SkynetLabs</a>'
				);
				?>
			</div>
		</div>
		<?php
	}
}
