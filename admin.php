<?php
/**
 * Accessibility Lite — admin settings page.
 *
 * @package a11y-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Accessibility_Lite_Admin {

	const OPTION = 'al_a11y_options';

	private static $features = array(
		'resize'            => 'Increase / Decrease text size',
		'grayscale'         => 'Grayscale',
		'high-contrast'     => 'High contrast',
		'negative-contrast' => 'Negative contrast',
		'light-bg'          => 'Light background',
		'links-underline'   => 'Underline links',
		'readable-font'     => 'Readable font',
	);

	public static function menu() {
		add_options_page(
			__( 'Accessibility Lite', 'a11y-lite' ),
			__( 'Accessibility Lite', 'a11y-lite' ),
			'manage_options',
			'a11y-lite',
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting( 'al_a11y', self::OPTION, array( __CLASS__, 'sanitize' ) );
	}

	public static function sanitize( $in ) {
		$in    = is_array( $in ) ? $in : array();
		$out   = array();
		$out['position']    = ( isset( $in['position'] ) && 'left' === $in['position'] ) ? 'left' : 'right';
		$out['vpos_desktop']    = ( isset( $in['vpos_desktop'] ) && in_array( $in['vpos_desktop'], array( 'top', 'middle', 'bottom' ), true ) ) ? $in['vpos_desktop'] : 'middle';
		$out['vpos_desktop_vh'] = isset( $in['vpos_desktop_vh'] ) ? intval( $in['vpos_desktop_vh'] ) : 50;
		$out['vpos_desktop_vh'] = min( 100, max( 0, $out['vpos_desktop_vh'] ) );
		$out['vpos_mobile']     = ( isset( $in['vpos_mobile'] ) && in_array( $in['vpos_mobile'], array( 'top', 'middle', 'bottom' ), true ) ) ? $in['vpos_mobile'] : 'bottom';
		$out['vpos_mobile_vh']  = isset( $in['vpos_mobile_vh'] ) ? intval( $in['vpos_mobile_vh'] ) : 15;
		$out['vpos_mobile_vh']  = min( 100, max( 0, $out['vpos_mobile_vh'] ) );
		$out['accent']      = ! empty( $in['accent'] ) ? sanitize_hex_color( $in['accent'] ) : '';
		$out['accent']      = $out['accent'] ? $out['accent'] : '#164b74';
		$out['accent_text'] = ! empty( $in['accent_text'] ) ? sanitize_hex_color( $in['accent_text'] ) : '';
		$out['accent_text'] = $out['accent_text'] ? $out['accent_text'] : '#ffffff';
		$out['features']    = array();
		if ( ! empty( $in['features'] ) && is_array( $in['features'] ) ) {
			foreach ( $in['features'] as $f ) {
				if ( array_key_exists( $f, self::$features ) ) {
					$out['features'][] = $f;
				}
			}
		}
		$out['skip_link']   = empty( $in['skip_link'] ) ? 0 : 1;
		$out['skip_target'] = ! empty( $in['skip_target'] ) ? sanitize_html_class( ltrim( trim( $in['skip_target'] ), '#' ) ) : '';
		$out['skip_target'] = $out['skip_target'] ? $out['skip_target'] : 'content';
		$out['focusable']   = empty( $in['focusable'] ) ? 0 : 1;
		$out['save']      = empty( $in['save'] ) ? 0 : 1;
		$out['save_exp']  = isset( $in['save_exp'] ) ? intval( $in['save_exp'] ) : 720;
		$out['save_exp']  = min( 8760, max( 1, $out['save_exp'] ) );

		$out['exclude_post_types'] = array();
		if ( ! empty( $in['exclude_post_types'] ) && is_array( $in['exclude_post_types'] ) ) {
			$valid = get_post_types( array( 'public' => true, 'show_in_nav_menus' => true ), 'names' );
			foreach ( $in['exclude_post_types'] as $pt ) {
				if ( in_array( $pt, $valid, true ) ) {
					$out['exclude_post_types'][] = $pt;
				}
			}
		}

		$out['exclude_ids'] = array();
		if ( ! empty( $in['exclude_ids'] ) ) {
			foreach ( preg_split( '/[\s,]+/', (string) $in['exclude_ids'] ) as $id ) {
				$id = absint( $id );
				if ( $id ) {
					$out['exclude_ids'][] = $id;
				}
			}
			$out['exclude_ids'] = array_values( array_unique( $out['exclude_ids'] ) );
		}

		return $out;
	}

	public static function render() {
		$o = Accessibility_Lite::instance()->options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Accessibility Lite', 'a11y-lite' ); ?></h1>
			<p><?php esc_html_e( 'Lightweight accessibility toolbar. Same features as the Pojo/Elementor toolbar, in ~5 KB of vanilla JS + CSS — no jQuery.', 'a11y-lite' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'al_a11y' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Toolbar position', 'a11y-lite' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[position]" value="right" <?php checked( $o['position'], 'right' ); ?> /> <?php esc_html_e( 'Right', 'a11y-lite' ); ?></label><br />
							<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[position]" value="left" <?php checked( $o['position'], 'left' ); ?> /> <?php esc_html_e( 'Left', 'a11y-lite' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Vertical position (desktop)', 'a11y-lite' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION ); ?>[vpos_desktop]">
								<option value="top" <?php selected( $o['vpos_desktop'], 'top' ); ?>><?php esc_html_e( 'Top', 'a11y-lite' ); ?></option>
								<option value="middle" <?php selected( $o['vpos_desktop'], 'middle' ); ?>><?php esc_html_e( 'Middle', 'a11y-lite' ); ?></option>
								<option value="bottom" <?php selected( $o['vpos_desktop'], 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'a11y-lite' ); ?></option>
							</select>
							&nbsp;
							<input type="number" min="0" max="100" step="1" name="<?php echo esc_attr( self::OPTION ); ?>[vpos_desktop_vh]" value="<?php echo esc_attr( $o['vpos_desktop_vh'] ); ?>" style="width:80px" />
							<?php esc_html_e( 'vh (top: from the top · middle: button center from the top · bottom: from the bottom)', 'a11y-lite' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Vertical position (mobile)', 'a11y-lite' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION ); ?>[vpos_mobile]">
								<option value="top" <?php selected( $o['vpos_mobile'], 'top' ); ?>><?php esc_html_e( 'Top', 'a11y-lite' ); ?></option>
								<option value="middle" <?php selected( $o['vpos_mobile'], 'middle' ); ?>><?php esc_html_e( 'Middle', 'a11y-lite' ); ?></option>
								<option value="bottom" <?php selected( $o['vpos_mobile'], 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'a11y-lite' ); ?></option>
							</select>
							&nbsp;
							<input type="number" min="0" max="100" step="1" name="<?php echo esc_attr( self::OPTION ); ?>[vpos_mobile_vh]" value="<?php echo esc_attr( $o['vpos_mobile_vh'] ); ?>" style="width:80px" />
							<?php esc_html_e( 'vh on screens up to 768px (top: from the top · middle: button center from the top · bottom: from the bottom)', 'a11y-lite' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Toggle button color', 'a11y-lite' ); ?></th>
						<td>
							<input type="color" name="<?php echo esc_attr( self::OPTION ); ?>[accent]" value="<?php echo esc_attr( $o['accent'] ); ?>" /> &nbsp;
							<input type="color" name="<?php echo esc_attr( self::OPTION ); ?>[accent_text]" value="<?php echo esc_attr( $o['accent_text'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Background and icon color of the floating toggle button.', 'a11y-lite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Features', 'a11y-lite' ); ?></th>
						<td>
							<?php foreach ( self::$features as $key => $label ) : ?>
								<label style="display:block;margin-bottom:4px">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[features][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $o['features'], true ) ); ?> />
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Skip to content link', 'a11y-lite' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[skip_link]" value="1" <?php checked( $o['skip_link'], 1 ); ?> /> <?php esc_html_e( 'Show a "Skip to content" link (accesskey: s)', 'a11y-lite' ); ?></label><br />
							<label style="margin-top:6px;display:inline-block">
								<?php esc_html_e( 'Target element ID', 'a11y-lite' ); ?>
								<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[skip_target]" value="<?php echo esc_attr( $o['skip_target'] ); ?>" style="width:160px" placeholder="content" />
							</label>
							<p class="description"><?php esc_html_e( 'The id of the element to jump to (without the #), e.g. "content" or "main". If it does not exist on the page, the script falls back to <main>/[role="main"]/#main/#primary automatically.', 'a11y-lite' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Visible focus outline', 'a11y-lite' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[focusable]" value="1" <?php checked( $o['focusable'], 1 ); ?> /> <?php esc_html_e( 'Always show a visible focus outline when tabbing', 'a11y-lite' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Save user preferences', 'a11y-lite' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[save]" value="1" <?php checked( $o['save'], 1 ); ?> /> <?php esc_html_e( 'Remember the toolbar state across pages (localStorage)', 'a11y-lite' ); ?></label><br />
							<label style="margin-top:6px;display:inline-block">
								<?php esc_html_e( 'Expire after', 'a11y-lite' ); ?>
								<input type="number" min="1" max="8760" step="1" name="<?php echo esc_attr( self::OPTION ); ?>[save_exp]" value="<?php echo esc_attr( $o['save_exp'] ); ?>" style="width:90px" />
								<?php esc_html_e( 'hours', 'a11y-lite' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Exclude on', 'a11y-lite' ); ?></th>
						<td>
							<?php foreach ( get_post_types( array( 'public' => true, 'show_in_nav_menus' => true ), 'objects' ) as $pt ) : ?>
								<label style="display:block;margin-bottom:4px">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[exclude_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $o['exclude_post_types'], true ) ); ?> />
									<?php echo esc_html( $pt->labels->name ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Hide the toolbar on every post/page of these types.', 'a11y-lite' ); ?></p>
							<label style="margin-top:6px;display:inline-block">
								<?php esc_html_e( 'Specific page/post IDs', 'a11y-lite' ); ?>
								<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[exclude_ids]" value="<?php echo esc_attr( implode( ', ', $o['exclude_ids'] ) ); ?>" style="width:260px" placeholder="12, 45, 100" />
							</label>
							<p class="description"><?php esc_html_e( 'Comma-separated. Hides the toolbar on these specific posts/pages regardless of type.', 'a11y-lite' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}