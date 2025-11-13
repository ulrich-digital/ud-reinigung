<?php
/**
 * Speichert einzelne Checkboxen aus der Frontend-Checkliste
 */

add_action('rest_api_init', function () {
	register_rest_field('reinigung', 'checklisten', [
		'get_callback'    => function ($object) {
			return get_post_meta($object['id'], 'checklisten', true);
		},
		'update_callback' => function ($value, $object) {
			// wird nicht benötigt, da wir eine eigene Route nutzen
			return true;
		},
	]);
});

/**
 * Eigene kleine Route zum gezielten Update einzelner Aufgaben
 */
add_action('rest_api_init', function () {
	register_rest_route('ud-reinigung/v1', '/update', [
		'methods'             => 'POST',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
		'callback'            => function (WP_REST_Request $request) {
			$post_id  = intval($request->get_param('id'));
			$data     = $request->get_param('checklisten_update');

			if (empty($post_id) || !is_array($data)) {
				return new WP_Error('invalid_data', 'Ungültige Anfrage.', ['status' => 400]);
			}

			$bereich = sanitize_text_field($data['bereich']);
			$aufgabe = sanitize_text_field($data['aufgabe']);
			$checked = filter_var($data['checked'], FILTER_VALIDATE_BOOLEAN);

			$checklisten = get_post_meta($post_id, 'checklisten', true);

			if (!isset($checklisten[$bereich][$aufgabe])) {
				return new WP_Error('not_found', 'Aufgabe nicht gefunden.', ['status' => 404]);
			}

			$checklisten[$bereich][$aufgabe] = $checked;

			update_post_meta($post_id, 'checklisten', $checklisten);

			return [
				'success'    => true,
				'post_id'    => $post_id,
				'bereich'    => $bereich,
				'aufgabe'    => $aufgabe,
				'checked'    => $checked,
				'updated_at' => current_time('mysql'),
			];
		},
	]);
});
