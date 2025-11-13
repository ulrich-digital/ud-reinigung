<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function () {
	if (is_admin()) {
		return;
	}

	// 🔹 Nur laden, wenn der Shortcode [ud_reinigung_button] im Inhalt vorkommt
	global $post;
	$content = $post ? $post->post_content : '';
	$should_enqueue = has_shortcode($content, 'ud_reinigung_button');

	if (!$should_enqueue) {
		return;
	}

	$base_dir = plugin_dir_path(dirname(__FILE__)); // plugin root/
	$base_url = plugin_dir_url(dirname(__FILE__));  // plugin root URL/

	$js_rel  = 'build/frontend.js';
	$css_rel = 'build/frontend.css';

	$js_file = $base_dir . $js_rel;
	$css_file = $base_dir . $css_rel;

	$js_url  = $base_url . $js_rel;
	$css_url = $base_url . $css_rel;

	$js_ver  = file_exists($js_file) ? filemtime($js_file) : null;
	$css_ver = file_exists($css_file) ? filemtime($css_file) : null;

	// 🔹 JavaScript laden
	if (file_exists($js_file)) {
		wp_enqueue_script(
			'ud-reinigung-frontend',
			$js_url,
			['wp-element', 'wp-api-fetch'],
			$js_ver,
			true
		);

		wp_localize_script('ud-reinigung-frontend', 'udReinigungSettings', [
			'nonce'    => wp_create_nonce('wp_rest'),
			'restRoot' => esc_url_raw(rest_url()),
			'postId'   => get_queried_object_id(),
		]);
	}

	// 🔹 CSS laden
	if (file_exists($css_file)) {
		wp_enqueue_style(
			'ud-reinigung-frontend',
			$css_url,
			[],
			$css_ver
		);
	}
});
