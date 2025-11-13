<?php
/* Datei: api-start.php */

/**
 * REST-API für Reinigung
 */

defined('ABSPATH') || exit;

// 🔹 Sicherstellen, dass die Checklisten-Referenz vorhanden ist
if (!function_exists('ud_reinigung_get_empty_checklisten')) {
	require_once plugin_dir_path(__DIR__) . 'includes/reinigung-checklisten.php';
}

add_action('rest_api_init', function () {
	register_rest_route('ud-reinigung/v1', '/clean', [
		'methods'             => ['GET', 'POST'],
		'permission_callback' => '__return_true',
		'callback'            => 'ud_reinigung_rest_handler',
	]);
});

function ud_reinigung_rest_handler(WP_REST_Request $request) {
	$method = $request->get_method();
	$date   = sanitize_text_field($request->get_param('date'));

	if (empty($date)) {
		return new WP_Error('missing_date', 'Kein Datum übergeben.', ['status' => 400]);
	}

	// 🔹 Hole Reinigungseintrag (nach Datum)
	$post = get_page_by_path($date, OBJECT, 'reinigung');

	// ===========================================================
	// 🔹 GET: Hole oder erstelle Reinigungseintrag
	// ===========================================================
	if ($method === 'GET') {

		// Falls keine Reinigung existiert → neu anlegen
		if (!$post) {
			$checklisten = ud_reinigung_get_empty_checklisten();

			$post_id = wp_insert_post([
				'post_title'  => 'Reinigung ' . $date,
				'post_name'   => $date,
				'post_type'   => 'reinigung',
				'post_status' => 'publish',
				'meta_input'  => [
					'checklisten' => $checklisten,
					'bemerkungen' => '',
					'updated_at'  => current_time('mysql'),
				],
			]);

			$post = get_post($post_id);
		}

		// 🔗 Suppentag verknüpfen (falls vorhanden)
		$suppentag = ud_get_or_create_suppentag_by_date($date);
		if ($suppentag && !is_wp_error($suppentag)) {
			update_post_meta($post->ID, 'reinigung_suppentag_id', $suppentag->ID);
		}

		// 🔹 Daten laden
		$checklisten = get_post_meta($post->ID, 'checklisten', true);
		if (!is_array($checklisten)) {
			$checklisten = json_decode($checklisten, true) ?: [];
		}
		$bemerkungen = get_post_meta($post->ID, 'bemerkungen', true) ?: '';

		// ✅ Synchronisierung mit aktuellem Standard
		if (function_exists('ud_reinigung_sync_checklisten')) {
			$checklisten = ud_reinigung_sync_checklisten($checklisten);
			update_post_meta($post->ID, 'checklisten', $checklisten);
			update_post_meta($post->ID, 'updated_at', current_time('mysql'));
		}

		return [
			'id'          => $post->ID,
			'date'        => $date,
			'suppentag'   => $suppentag ? $suppentag->ID : null,
			'checklisten' => $checklisten,
			'bemerkungen' => $bemerkungen,
			'updated_at'  => get_post_meta($post->ID, 'updated_at', true),
		];
	}

	// ===========================================================
	// 🔹 POST: Speichern / Aktualisieren
	// ===========================================================
	if ($method === 'POST') {
		if (!$post) {
			return new WP_Error('not_found', 'Kein Reinigungseintrag gefunden.', ['status' => 404]);
		}

		$params      = $request->get_json_params();
		$checklisten = isset($params['checklisten']) ? (array) $params['checklisten'] : [];
		$bemerkungen = isset($params['bemerkungen']) ? sanitize_textarea_field($params['bemerkungen']) : '';
		$date        = sanitize_text_field($params['date'] ?? $date);
		$post_id     = $post->ID;

		// 🔹 Werte speichern
		update_post_meta($post_id, 'checklisten', $checklisten);
		update_post_meta($post_id, 'bemerkungen', $bemerkungen);
		update_post_meta($post_id, 'updated_at', current_time('mysql'));

		// 🔗 Suppentag-Verknüpfung aktualisieren
		$suppentag = ud_get_or_create_suppentag_by_date($date);
		if ($suppentag && !is_wp_error($suppentag)) {
			update_post_meta($post_id, 'reinigung_suppentag_id', $suppentag->ID);
		}

		return [
			'success'   => true,
			'post_id'   => $post_id,
			'suppentag' => $suppentag ? $suppentag->ID : null,
			'message'   => 'Reinigung erfolgreich gespeichert.',
			'saved_at'  => current_time('mysql'),
			'data'      => [
				'checklisten' => $checklisten,
				'bemerkungen' => $bemerkungen,
				'updated_at'  => current_time('mysql'),
			],
		];
	}

	return new WP_Error('invalid_method', 'Ungültige Anfrage.', ['status' => 405]);
}

/**
 * Hole oder erstelle Suppentag anhand des Meta-Felds 'suppentag_date'
 *
 * @param string $date Format: YYYY-MM-DD
 * @return WP_Post|null
 */
function ud_get_or_create_suppentag_by_date($date) {
	if (empty($date)) {
		return null;
	}

	// 1️⃣ Zuerst prüfen, ob Suppentag bereits existiert
	$args = [
		'post_type'      => 'ud_suppentag',
		'post_status'    => ['publish', 'draft', 'pending'],
		'meta_query'     => [
			[
				'key'     => 'suppentag_date',
				'value'   => $date,
				'compare' => '=',
			],
		],
		'posts_per_page' => 1,
	];

	$query = new WP_Query($args);

	if (!empty($query->posts)) {
		// 🔹 Bereits vorhanden → zurückgeben
		return $query->posts[0];
	}

	// 2️⃣ Wenn keiner existiert → neuen erstellen
	$new_id = wp_insert_post([
		'post_title'  => 'Suppentag ' . $date,
		'post_type'   => 'ud_suppentag',
		'post_status' => 'publish',
		'meta_input'  => [
			'suppentag_date' => $date,
		],
	]);

	if (is_wp_error($new_id) || !$new_id) {
		return null;
	}

	return get_post($new_id);
}

