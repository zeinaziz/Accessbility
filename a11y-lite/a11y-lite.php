<?php
/**
 * Plugin Name:       Accessibility Lite
 * Plugin URI:        https://fanaloka.co
 * Description:       Lightweight accessibility toolbar. Vanilla JS, no jQuery, ~5 KB of CSS + JS. Text resize, grayscale, contrast modes, links underline, readable font and reset — the same features as the bloated Pojo/Elementor toolbar, without the bloat.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            Fanaloka
 * License:           GPL-2.0-or-later
 * Text Domain:       a11y-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AL_VERSION', '1.1.0' );
define( 'AL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require AL_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$al_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/zeinaziz/Accessbility/',
	__FILE__,
	'a11y-lite'
);
$al_update_checker->getVcsApi()->enableReleaseAssets();

final class Accessibility_Lite {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( is_admin() ) {
			require_once AL_PLUGIN_DIR . 'admin.php';
			add_action( 'admin_menu', array( 'Accessibility_Lite_Admin', 'menu' ) );
			add_action( 'admin_init', array( 'Accessibility_Lite_Admin', 'register' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
			return;
		}
		add_action( 'wp_body_open', array( $this, 'print_toolbar' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	public function action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=a11y-lite' ) ) . '">' . esc_html__( 'Settings', 'a11y-lite' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function options() {
		$defaults = array(
			'position'           => 'right',
			'accent'             => '#164b74',
			'accent_text'        => '#ffffff',
			'vpos_desktop'       => 'middle',
			'vpos_desktop_vh'    => 50,
			'vpos_mobile'        => 'bottom',
			'vpos_mobile_vh'     => 15,
			'features'           => array( 'resize', 'grayscale', 'high-contrast', 'negative-contrast', 'light-bg', 'links-underline', 'readable-font' ),
			'skip_link'          => 1,
			'skip_target'        => 'content',
			'focusable'          => 1,
			'save'               => 1,
			'save_exp'           => 720,
			'exclude_post_types' => array(),
			'exclude_ids'        => array(),
		);
		return wp_parse_args( get_option( 'al_a11y_options', array() ), $defaults );
	}

	private function is_excluded( $o ) {
		if ( ! empty( $o['exclude_post_types'] ) ) {
			if ( is_singular( $o['exclude_post_types'] ) || is_post_type_archive( $o['exclude_post_types'] ) ) {
				return true;
			}
		}
		if ( ! empty( $o['exclude_ids'] ) ) {
			$id = get_queried_object_id();
			if ( $id && in_array( $id, $o['exclude_ids'], true ) ) {
				return true;
			}
		}
		return false;
	}

	public function body_class( $classes ) {
		$o = $this->options();
		if ( ! empty( $o['focusable'] ) ) {
			$classes[] = 'al-focusable';
		}
		return $classes;
	}

	public function assets() {
		$o = $this->options();
		if ( $this->is_excluded( $o ) ) {
			return;
		}
		wp_enqueue_style( 'al-a11y', AL_PLUGIN_URL . 'assets/css/a11y.css', array(), AL_VERSION );
		wp_enqueue_script( 'al-a11y', AL_PLUGIN_URL . 'assets/js/a11y.js', array(), AL_VERSION, true );
		wp_localize_script( 'al-a11y', 'AlA11yOpts', array(
			'save'       => empty( $o['save'] ) ? '0' : '1',
			'saveExp'    => (string) absint( $o['save_exp'] ),
			'openLabel'  => __( 'Open toolbar', 'a11y-lite' ),
			'closeLabel' => __( 'Close toolbar', 'a11y-lite' ),
		) );
	}

	public function print_toolbar() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		$o = $this->options();

		if ( $this->is_excluded( $o ) ) {
			return;
		}

		if ( ! empty( $o['skip_link'] ) ) {
			$target = ! empty( $o['skip_target'] ) ? $o['skip_target'] : 'content';
			printf(
				'<a class="al-skip-link" href="#%1$s" accesskey="s">%2$s</a>',
				esc_attr( $target ),
				esc_html__( 'Skip to content', 'a11y-lite' )
			);
		}

		$items = $this->toolbar_items( $o['features'] );
		if ( empty( $items ) ) {
			return;
		}

		$pos  = ( 'left' === $o['position'] ) ? 'al-left' : 'al-right';
		$vpos = in_array( $o['vpos_desktop'], array( 'top', 'middle', 'bottom' ), true ) ? $o['vpos_desktop'] : 'middle';
		$vmob = in_array( $o['vpos_mobile'], array( 'top', 'middle', 'bottom' ), true ) ? $o['vpos_mobile'] : 'bottom';
		$vh   = min( 100, max( 0, intval( $o['vpos_desktop_vh'] ) ) );
		$vhm  = min( 100, max( 0, intval( $o['vpos_mobile_vh'] ) ) );
		$pos .= ' al-vert-' . $vpos . ' al-vert-m-' . $vmob;

		printf(
			'<nav id="al-toolbar" class="%1$s" aria-label="%2$s" style="--al-accent:%3$s;--al-accent-text:%4$s;--al-vtop:%5$d;--al-vtop-m:%6$d">',
			esc_attr( $pos ),
			esc_attr__( 'Accessibility Toolbar', 'a11y-lite' ),
			esc_attr( $o['accent'] ),
			esc_attr( $o['accent_text'] ),
			$vh,
			$vhm
		);
		echo '<div class="al-toggle"><button type="button" aria-expanded="false" aria-label="' . esc_attr__( 'Accessibility Tools', 'a11y-lite' ) . '">';
		echo $this->icon( 'toggle' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<span class="al-sr">' . esc_html__( 'Open toolbar', 'a11y-lite' ) . '</span></button></div>';
		echo '<div class="al-overlay"><p class="al-title">' . esc_html__( 'Accessibility Tools', 'a11y-lite' ) . '</p><ul class="al-items">';
		foreach ( $items as $action => $label ) {
			printf(
				'<li class="al-item"><button type="button" data-action="%1$s">%2$s<span class="al-txt">%3$s</span></button></li>',
				esc_attr( $action ),
				$this->icon( $action ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $label )
			);
		}
		echo '</ul></div></nav>';
	}

	private function toolbar_items( $features ) {
		$items = array();
		if ( in_array( 'resize', $features, true ) ) {
			$items['resize-plus']  = __( 'Increase Text', 'a11y-lite' );
			$items['resize-minus'] = __( 'Decrease Text', 'a11y-lite' );
		}
		if ( in_array( 'grayscale', $features, true ) ) {
			$items['grayscale'] = __( 'Grayscale', 'a11y-lite' );
		}
		if ( in_array( 'high-contrast', $features, true ) ) {
			$items['high-contrast'] = __( 'High Contrast', 'a11y-lite' );
		}
		if ( in_array( 'negative-contrast', $features, true ) ) {
			$items['negative-contrast'] = __( 'Negative Contrast', 'a11y-lite' );
		}
		if ( in_array( 'light-bg', $features, true ) ) {
			$items['light-bg'] = __( 'Light Background', 'a11y-lite' );
		}
		if ( in_array( 'links-underline', $features, true ) ) {
			$items['links-underline'] = __( 'Links Underline', 'a11y-lite' );
		}
		if ( in_array( 'readable-font', $features, true ) ) {
			$items['readable-font'] = __( 'Readable Font', 'a11y-lite' );
		}
		$items['reset'] = __( 'Reset', 'a11y-lite' );
		return $items;
	}

	private function icon( $name ) {
		$icons = $this->icons();
		if ( empty( $icons[ $name ] ) ) {
			return '';
		}
		$vb = ( 'toggle' === $name ) ? '0 0 100 100' : '0 0 448 448';
		return '<svg viewBox="' . $vb . '" fill="currentColor" aria-hidden="true"><path d="' . $icons[ $name ] . '"/></svg>';
	}

	private function icons() {
		static $icons = null;
		if ( null === $icons ) {
			$icons = array(
				'toggle'            => 'M50 8.1c23.2 0 41.9 18.8 41.9 41.9 0 23.2-18.8 41.9-41.9 41.9C26.8 91.9 8.1 73.2 8.1 50S26.8 8.1 50 8.1M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zm0 11.3c-21.4 0-38.7 17.3-38.7 38.7S28.6 88.7 50 88.7 88.7 71.4 88.7 50 71.4 11.3 50 11.3zm0 8.9c4 0 7.3 3.2 7.3 7.3S54 34.7 50 34.7s-7.3-3.2-7.3-7.2 3.3-7.2 7.3-7.2zm23.7 19.7c-5.8 1.4-11.2 2.6-16.6 3.2.2 20.4 2.5 24.8 5 31.4.7 1.9-.2 4-2.1 4.7-1.9.7-4-.2-4.7-2.1-1.8-4.5-3.4-8.2-4.5-15.8h-2c-1 7.6-2.7 11.3-4.5 15.8-.7 1.9-2.8 2.8-4.7 2.1-1.9-.7-2.8-2.8-2.1-4.7 2.6-6.6 4.9-11 5-31.4-5.4-.6-10.8-1.8-16.6-3.2-1.7-.4-2.8-2.1-2.4-3.9.4-1.7 2.1-2.8 3.9-2.4 19.5 4.6 25.1 4.6 44.5 0 1.7-.4 3.5.7 3.9 2.4.7 1.8-.3 3.5-2.1 3.9z',
				'resize-plus'       => 'M256 200v16c0 4.25-3.75 8-8 8h-56v56c0 4.25-3.75 8-8 8h-16c-4.25 0-8-3.75-8-8v-56h-56c-4.25 0-8-3.75-8-8v-16c0-4.25 3.75-8 8-8h56v-56c0-4.25 3.75-8 8-8h16c4.25 0 8 3.75 8 8v56h56c4.25 0 8 3.75 8 8zM288 208c0-61.75-50.25-112-112-112s-112 50.25-112 112 50.25 112 112 112 112-50.25 112-112zM416 416c0 17.75-14.25 32-32 32-8.5 0-16.75-3.5-22.5-9.5l-85.75-85.5c-29.25 20.25-64.25 31-99.75 31-97.25 0-176-78.75-176-176s78.75-176 176-176 176 78.75 176 176c0 35.5-10.75 70.5-31 99.75l85.75 85.75c5.75 5.75 9.25 14 9.25 22.5z',
				'resize-minus'      => 'M256 200v16c0 4.25-3.75 8-8 8h-144c-4.25 0-8-3.75-8-8v-16c0-4.25 3.75-8 8-8h144c4.25 0 8 3.75 8 8zM288 208c0-61.75-50.25-112-112-112s-112 50.25-112 112 50.25 112 112 112 112-50.25 112-112zM416 416c0 17.75-14.25 32-32 32-8.5 0-16.75-3.5-22.5-9.5l-85.75-85.5c-29.25 20.25-64.25 31-99.75 31-97.25 0-176-78.75-176-176s78.75-176 176-176 176 78.75 176 176c0 35.5-10.75 70.5-31 99.75l85.75 85.75c5.75 5.75 9.25 14 9.25 22.5z',
				'grayscale'         => 'M15.75 384h-15.75v-352h15.75v352zM31.5 383.75h-8v-351.75h8v351.75zM55 383.75h-7.75v-351.75h7.75v351.75zM94.25 383.75h-7.75v-351.75h7.75v351.75zM133.5 383.75h-15.5v-351.75h15.5v351.75zM165 383.75h-7.75v-351.75h7.75v351.75zM180.75 383.75h-7.75v-351.75h7.75v351.75zM196.5 383.75h-7.75v-351.75h7.75v351.75zM235.75 383.75h-15.75v-351.75h15.75v351.75zM275 383.75h-15.75v-351.75h15.75v351.75zM306.5 383.75h-15.75v-351.75h15.75v351.75zM338 383.75h-15.75v-351.75h15.75v351.75zM361.5 383.75h-15.75v-351.75h15.75v351.75zM408.75 383.75h-23.5v-351.75h23.5v351.75zM424.5 383.75h-8v-351.75h8v351.75zM448 384h-15.75v-352h15.75v352z',
				'high-contrast'     => 'M192 360v-272c-75 0-136 61-136 136s61 136 136 136zM384 224c0 106-86 192-192 192s-192-86-192-192 86-192 192-192 192 86 192 192z',
				'negative-contrast' => 'M416 240c-23.75-36.75-56.25-68.25-95.25-88.25 10 17 15.25 36.5 15.25 56.25 0 61.75-50.25 112-112 112s-112-50.25-112-112c0-19.75 5.25-39.25 15.25-56.25-39 20-71.5 51.5-95.25 88.25 42.75 66 111.75 112 192 112s149.25-46 192-112zM236 144c0-6.5-5.5-12-12-12-41.75 0-76 34.25-76 76 0 6.5 5.5 12 12 12s12-5.5 12-12c0-28.5 23.5-52 52-52 6.5 0 12-5.5 12-12zM448 240c0 6.25-2 12-5 17.25-46 75.75-130.25 126.75-219 126.75s-173-51.25-219-126.75c-3-5.25-5-11-5-17.25s2-12 5-17.25c46-75.5 130.25-126.75 219-126.75s173 51.25 219 126.75c3 5.25 5 11 5 17.25z',
				'light-bg'          => 'M184 144c0 4.25-3.75 8-8 8s-8-3.75-8-8c0-17.25-26.75-24-40-24-4.25 0-8-3.75-8-8s3.75-8 8-8c23.25 0 56 12.25 56 40zM224 144c0-50-50.75-80-96-80s-96 30-96 80c0 16 6.5 32.75 17 45 4.75 5.5 10.25 10.75 15.25 16.5 17.75 21.25 32.75 46.25 35.25 74.5h57c2.5-28.25 17.5-53.25 35.25-74.5 5-5.75 10.5-11 15.25-16.5 10.5-12.25 17-29 17-45zM256 144c0 25.75-8.5 48-25.75 67s-40 45.75-42 72.5c7.25 4.25 11.75 12.25 11.75 20.5 0 6-2.25 11.75-6.25 16 4 4.25 6.25 10 6.25 16 0 8.25-4.25 15.75-11.25 20.25 2 3.5 3.25 7.75 3.25 11.75 0 16.25-12.75 24-27.25 24-6.5 14.5-21 24-36.75 24s-30.25-9.5-36.75-24c-14.5 0-27.25-7.75-27.25-24 0-4 1.25-8.25 3.25-11.75-7-4.5-11.25-12-11.25-20.25 0-6 2.25-11.75 6.25-16-4-4.25-6.25-10-6.25-16 0-8.25 4.5-16.25 11.75-20.5-2-26.75-24.75-53.5-42-72.5s-25.75-41.25-25.75-67c0-68 64.75-112 128-112s128 44 128 112z',
				'links-underline'   => 'M364 304c0-6.5-2.5-12.5-7-17l-52-52c-4.5-4.5-10.75-7-17-7-7.25 0-13 2.75-18 8 8.25 8.25 18 15.25 18 28 0 13.25-10.75 24-24 24-12.75 0-19.75-9.75-28-18-5.25 5-8.25 10.75-8.25 18.25 0 6.25 2.5 12.5 7 17l51.5 51.75c4.5 4.5 10.75 6.75 17 6.75s12.5-2.25 17-6.5l36.75-36.5c4.5-4.5 7-10.5 7-16.75zM188.25 127.75c0-6.25-2.5-12.5-7-17l-51.5-51.75c-4.5-4.5-10.75-7-17-7s-12.5 2.5-17 6.75l-36.75 36.5c-4.5 4.5-7 10.5-7 16.75 0 6.5 2.5 12.5 7 17l52 52c4.5 4.5 10.75 6.75 17 6.75 7.25 0 13-2.5 18-7.75-8.25-8.25-18-15.25-18-28 0-13.25 10.75-24 24-24 12.75 0 19.75 9.75 28 18 5.25-5 8.25-10.75 8.25-18.25zM412 304c0 19-7.75 37.5-21.25 50.75l-36.75 36.5c-13.5 13.5-31.75 20.75-50.75 20.75-19.25 0-37.5-7.5-51-21.25l-51.5-51.75c-13.5-13.5-20.75-31.75-20.75-50.75 0-19.75 8-38.5 22-52.25l-22-22c-13.75 14-32.25 22-52 22-19 0-37.5-7.5-51-21l-52-52c-13.75-13.75-21-31.75-21-51 0-19 7.75-37.5 21.25-50.75l36.75-36.5c13.5-13.5 31.75-20.75 50.75-20.75 19.25 0 37.5 7.5 51 21.25l51.5 51.75c13.5 13.5 20.75 31.75 20.75 50.75 0 19.75-8 38.5-22 52.25l22 22c13.75-14 32.25-22 52-22 19 0 37.5 7.5 51 21l52 52c13.75 13.75 21 31.75 21 51z',
				'readable-font'     => 'M181.25 139.75l-42.5 112.5c24.75 0.25 49.5 1 74.25 1 4.75 0 9.5-0.25 14.25-0.5-13-38-28.25-76.75-46-113zM0 416l0.5-19.75c23.5-7.25 49-2.25 59.5-29.25l59.25-154 70-181h32c1 1.75 2 3.5 2.75 5.25l51.25 120c18.75 44.25 36 89 55 133 11.25 26 20 52.75 32.5 78.25 1.75 4 5.25 11.5 8.75 14.25 8.25 6.5 31.25 8 43 12.5 0.75 4.75 1.5 9.5 1.5 14.25 0 2.25-0.25 4.25-0.25 6.5-31.75 0-63.5-4-95.25-4-32.75 0-65.5 2.75-98.25 3.75 0-6.5 0.25-13 1-19.5l32.75-7c6.75-1.5 20-3.25 20-12.5 0-9-32.25-83.25-36.25-93.5l-112.5-0.5c-6.5 14.5-31.75 80-31.75 89.5 0 19.25 36.75 20 51 22 0.25 4.75 0.25 9.5 0.25 14.5 0 2.25-0.25 4.5-0.5 6.75-29 0-58.25-5-87.25-5-3.5 0-8.5 1.5-12 2-15.75 2.75-31.25 3.5-47 3.5z',
				'reset'             => 'M384 224c0 105.75-86.25 192-192 192-57.25 0-111.25-25.25-147.75-69.25-2.5-3.25-2.25-8 0.5-10.75l34.25-34.5c1.75-1.5 4-2.25 6.25-2.25 2.25 0.25 4.5 1.25 5.75 3 24.5 31.75 61.25 49.75 101 49.75 70.5 0 128-57.5 128-128s-57.5-128-128-128c-32.75 0-63.75 12.5-87 34.25l34.25 34.5c4.75 4.5 6 11.5 3.5 17.25-2.5 6-8.25 10-14.75 10h-112c-8.75 0-16-7.25-16-16v-112c0-6.5 4-12.25 10-14.75 5.75-2.5 12.75-1.25 17.25 3.5l32.5 32.25c35.25-33.25 83-53 132.25-53 105.75 0 192 86.25 192 192z',
			);
		}
		return $icons;
	}
}

Accessibility_Lite::instance();