<?php
/* Datei: meta-box-checklisten.php */

/**
 * Meta-Box für tägliche Reinigungs-Checklisten (Suppentag-kompatibles Layout + Bemerkungsfeld)
 */

add_action('add_meta_boxes', function () {
	add_meta_box(
		'ud_reinigung_checklisten_box',
		'Reinigungs-Checkliste',
		'ud_render_reinigung_checklisten_box',
		'reinigung',
		'normal',
		'default'
	);
});

function ud_render_reinigung_checklisten_box($post)
{
	wp_nonce_field('ud_reinigung_checklisten_save', 'ud_reinigung_checklisten_nonce');

	$checklisten  = get_post_meta($post->ID, 'checklisten', true);
	if (!is_array($checklisten)) {
		$checklisten = json_decode($checklisten, true) ?: [];
	}

	$bemerkung    = get_post_meta($post->ID, 'bemerkungen', true);
	$suppentag_id = get_post_meta($post->ID, 'reinigung_suppentag_id', true);

	if (!function_exists('ud_reinigung_get_default_checklisten')) {
		require_once plugin_dir_path(__FILE__) . '/reinigung-checklisten.php';
	}
	$defaults = ud_reinigung_get_default_checklisten();

	echo '<style>
		.ud-meta-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 8px;
			font-size: 13px;
		}
		.ud-meta-table th {
			text-align: left;
			background: #f8f9fa;
			border-bottom: 1px solid #ddd;
			padding: 8px 10px;
			font-weight: 600;
			width: 26%;
			vertical-align: top;
		}
		.ud-meta-table td {
			border-bottom: 1px solid #eee;
			padding: 8px 10px 10px;
		}
		.ud-checklist-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 8px 16px;
			margin-top: 4px;
		}
		.ud-checklist-grid label {
			display: flex;
			align-items: center;
			gap: 8px;
			line-height: 1.4;
			padding: 3px 0;
			white-space: normal;
		}
		.ud-checklist-grid input[type="checkbox"] {
			transform: scale(1.15);
			accent-color: #007cba;
		}
		.ud-meta-table tr:last-child td,
		.ud-meta-table tr:last-child th {
			border-bottom: none;
		}
		.ud-bemerkung-box,
		.ud-suppentag-box {
			margin-top: 20px;
			border-top: 1px solid #ddd;
			padding-top: 12px;
		}
		.ud-bemerkung-box label,
		.ud-suppentag-box label {
			display: block;
			font-weight: 600;
			margin-bottom: 6px;
		}
		.ud-bemerkung-box textarea {
			width: 100%;
			min-height: 80px;
			font-size: 13px;
			padding: 8px;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			resize: vertical;
		}
		.ud-suppentag-box select {
			width: 100%;
			padding: 6px;
			font-size: 13px;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			background: #fff;
		}
	</style>';

	// 🔹 Checkliste
	echo '<table class="ud-meta-table">';
	foreach ($defaults as $bereich => $aufgaben) {
		echo '<tr>';
		echo '<th scope="row">' . esc_html($bereich) . '</th>';
		echo '<td><div class="ud-checklist-grid">';
		foreach ($aufgaben as $aufgabe) {
			$checked = !empty($checklisten[$bereich][$aufgabe]) ? 'checked' : '';
			echo '<label>';
			echo '<input type="checkbox" name="checklisten[' . esc_attr($bereich) . '][' . esc_attr($aufgabe) . ']" value="1" ' . $checked . '>';
			echo esc_html($aufgabe);
			echo '</label>';
		}
		echo '</div></td>';
		echo '</tr>';
	}
	echo '</table>';

	// 🔹 Bemerkungsfeld
	echo '<div class="ud-bemerkung-box">';
	echo '<label for="bemerkungen">Bemerkungen</label>';
	echo '<textarea name="bemerkungen" id="bemerkungen">' . esc_textarea($bemerkung) . '</textarea>';
	echo '</div>';

	// 🔹 Suppentag-Auswahl (unterhalb der Bemerkungen)
	echo '<div class="ud-suppentag-box">';
	echo '<label for="reinigung_suppentag_id">Zugehöriger Suppentag</label>';

	$suppentage = get_posts([
		'post_type'      => 'ud_suppentag',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	]);

	if (empty($suppentage)) {
		echo '<p><em>Keine Suppentage gefunden.</em></p>';
	} else {
		echo '<select name="reinigung_suppentag_id" id="reinigung_suppentag_id">';
		echo '<option value="">– Kein Suppentag zugeordnet –</option>';
		foreach ($suppentage as $suppentag) {
			printf(
				'<option value="%d" %s>%s</option>',
				$suppentag->ID,
				selected($suppentag_id, $suppentag->ID, false),
				esc_html($suppentag->post_title)
			);
		}
		echo '</select>';
	}
	echo '</div>';
}


/**
 * 🔹 Speicherung aller Felder
 */
add_action('save_post_reinigung', function ($post_id) {
	if (
		!isset($_POST['ud_reinigung_checklisten_nonce']) ||
		!wp_verify_nonce($_POST['ud_reinigung_checklisten_nonce'], 'ud_reinigung_checklisten_save')
	) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	// Checklisten speichern
	if (isset($_POST['checklisten'])) {
		$data = array_map(function ($bereich) {
			return array_map('intval', $bereich);
		}, $_POST['checklisten']);
		update_post_meta($post_id, 'checklisten', $data);
	} else {
		delete_post_meta($post_id, 'checklisten');
	}

	// Bemerkungen speichern
	if (isset($_POST['bemerkungen'])) {
		update_post_meta($post_id, 'bemerkungen', sanitize_textarea_field($_POST['bemerkungen']));
	} else {
		delete_post_meta($post_id, 'bemerkungen');
	}

	// Suppentag speichern
	if (isset($_POST['reinigung_suppentag_id']) && $_POST['reinigung_suppentag_id'] !== '') {
		update_post_meta($post_id, 'reinigung_suppentag_id', intval($_POST['reinigung_suppentag_id']));
	} else {
		delete_post_meta($post_id, 'reinigung_suppentag_id');
	}
});


/**
 * 🔹 Entfernt die Standard-Meta-Box „Individuelle Felder“ beim CPT „reinigung“
 */
add_action('do_meta_boxes', function () {
	remove_meta_box('postcustom', 'reinigung', 'normal');
});
