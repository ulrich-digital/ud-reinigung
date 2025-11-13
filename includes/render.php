<?php
defined('ABSPATH') || exit;

// Stelle sicher, dass die zentrale Checklisten-Referenz verfügbar ist
if (!function_exists('ud_reinigung_get_default_checklisten')) {
	require_once plugin_dir_path(__FILE__) . 'reinigung-checklisten.php';
}

/**
 * Rendert die Checklisten im Frontend für CPT „reinigung“.
 */
add_filter('the_content', function ($content) {
	// Nur auf Single-Ansichten des CPT "reinigung"
	if (get_post_type() !== 'reinigung') {
		return $content;
	}

	$post_id = get_the_ID();

	// 🔹 Meta "checklisten" robust normalisieren (Array ODER historischer JSON-String)
	$raw = get_post_meta($post_id, 'checklisten', true);
	if (is_array($raw)) {
		$checklisten = $raw;
	} elseif (is_string($raw) && $raw !== '') {
		$decoded = json_decode($raw, true);
		$checklisten = is_array($decoded) ? $decoded : [];
	} else {
		$checklisten = [];
	}

	// 🔹 Bemerkungen (kann leer sein)
	$bemerkungen = get_post_meta($post_id, 'bemerkungen', true);

	// Wenn keine gespeicherten Checklisten → Standardwerte verwenden
	if (empty($checklisten) || !is_array($checklisten)) {
		// Falls es eine leere Variante gibt, sonst Default:
		if (function_exists('ud_reinigung_get_empty_checklisten')) {
			$checklisten = ud_reinigung_get_empty_checklisten();
		} else {
			$checklisten = ud_reinigung_get_default_checklisten();
		}
	}

	ob_start();

	echo '<div id="ud-reinigung-checklisten" data-post-id="' . esc_attr($post_id) . '">';

	// Erwartete Struktur: [ 'Bereich' => [ 'Aufgabe' => bool, ... ], ... ]
	foreach ($checklisten as $bereich => $aufgaben) {
		// Defensive: falls jemand flache Arrays speichert, in assoc-Form bringen
		if (is_array($aufgaben) && array_values($aufgaben) === $aufgaben) {
			// numerisch indiziert → in "aufgabe => false" konvertieren
			$_assoc = [];
			foreach ($aufgaben as $task) {
				$_assoc[(string) $task] = false;
			}
			$aufgaben = $_assoc;
		}

		echo '<section class="ud-reinigung-bereich">';
		echo '<h3>' . esc_html($bereich) . '</h3>';
		echo '<ul>';

		if (is_array($aufgaben)) {
			foreach ($aufgaben as $aufgabe => $status) {
				$checked = !empty($status) ? 'checked' : '';
				echo '<li>';
				echo '<label>';
				echo '<input type="checkbox" data-bereich="' . esc_attr($bereich) . '" data-aufgabe="' . esc_attr($aufgabe) . '" ' . $checked . '> ';
				echo esc_html($aufgabe);
				echo '</label>';
				echo '</li>';
			}
		}

		echo '</ul>';
		echo '</section>';
	}

	// 🔹 Bemerkungen-Feld am Ende
	echo '<section class="ud-reinigung-bemerkungen">';
	echo '<h3>' . esc_html__('Bemerkungen', 'ud-reservation-ud') . '</h3>';
	echo '<textarea id="ud-reinigung-bemerkungen" rows="5" placeholder="' . esc_attr__('Bemerkungen eintragen...', 'ud-reservation-ud') . '">';
	echo esc_textarea((string) $bemerkungen);
	echo '</textarea>';
	echo '</section>';

	echo '</div>';

	return $content . ob_get_clean();
});

/**
 * Gibt eine reine HTML-Variante der Standard-Checklisten zurück (z. B. für Modals oder Shortcodes).
 */
function ud_reinigung_render_checklisten_html() {
	$bereiche = ud_reinigung_get_default_checklisten();

	$html = '<div id="ud-reinigung-checklisten">';

	// Erwartete Struktur: [ 'Bereich' => [ 'Aufgabe1', 'Aufgabe2', ... ] ] ODER assoc
	foreach ($bereiche as $bereich => $aufgaben) {
		$html .= '<div class="ud-checklist-section">';
		$html .= '<h3>' . esc_html($bereich) . '</h3>';

		// Falls assoc (aufgabe => bool), nur Keys verwenden
		if (is_array($aufgaben)) {
			// assoc → keys; numerisch → Werte
			$tasks = array_values($aufgaben) === $aufgaben ? $aufgaben : array_keys($aufgaben);

			foreach ($tasks as $aufgabe) {
				$html .= '<label class="ud-checklist-item">';
				$html .= '<input type="checkbox" data-bereich="' . esc_attr($bereich) . '" data-aufgabe="' . esc_attr($aufgabe) . '">';
				$html .= '<span>' . esc_html($aufgabe) . '</span>';
				$html .= '</label>';
			}
		}

		$html .= '</div>';
	}

	// 🔹 Bemerkungen-Feld am Ende
	$html .= '<div class="ud-checklist-section">';
	$html .= '<h3>' . esc_html__('Bemerkungen', 'ud-reservation-ud') . '</h3>';
	$html .= '<textarea id="ud-reinigung-bemerkungen" rows="5" placeholder="' . esc_attr__('Bemerkungen eintragen...', 'ud-reservation-ud') . '"></textarea>';
	$html .= '</div>';

	$html .= '</div>';

	return $html;
}
