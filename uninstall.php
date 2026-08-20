<?php
/**
 * Accessibility Lite — uninstall cleanup.
 *
 * @package a11y-lite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'al_a11y_options' );