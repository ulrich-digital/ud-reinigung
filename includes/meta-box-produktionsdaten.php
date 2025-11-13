<?php
/**
 * Backend-Metabox: Statistik Produktion & Verkauf
 * (aktuelle Struktur mit produktion_gesamt + Lieferantenfeldern)
 */

add_action('add_meta_boxes', function() {
	add_meta_box(
		'ud_produktionsdaten_box',
		'Statistik Produktion & Verkauf',
		'ud_render_produktionsdaten_box',
		'ud_suppentag',
		'normal',
		'high'
	);
});

function ud_render_produktionsdaten_box($post) {
	wp_nonce_field('ud_save_produktionsdaten', 'ud_produktionsdaten_nonce');

	// Metadaten laden
	$produktion_gesamt = get_post_meta($post->ID, 'produktion_gesamt', true) ?: 0;
	$lieferanten = get_post_meta($post->ID, 'suppentag_produktion', true);
	$lieferanten = is_array($lieferanten) ? $lieferanten : [];
	?>

	<style>
		.ud-admin-tabelle {
			width: 100%;
			border-collapse: collapse;
			margin-top: 0.5rem;
		}
		.ud-admin-tabelle th,
		.ud-admin-tabelle td {
			padding: 6px 8px;
			border-bottom: 1px solid #ddd;
		}
		.ud-admin-tabelle th {
			background: #f9f9f9;
			text-align: left;
		}
		.ud-admin-tabelle input[type="text"],
		.ud-admin-tabelle input[type="number"] {
			width: 100%;
			padding: 3px 5px;
		}
	</style>

	<p>
		<label for="produktion_gesamt"><strong>Gesamtproduktion (l):</strong></label><br>
		<input type="number" step="0.1" id="produktion_gesamt" name="produktion_gesamt"
			value="<?php echo esc_attr($produktion_gesamt); ?>" min="0">
	</p>

	<table class="ud-admin-tabelle">
		<thead>
			<tr>
				<th>Lieferant</th>
				<th>Lieferung (l)</th>
				<th>Retouren (l)</th>
				<th>Verkauf (l)</th>
				<th></th>
			</tr>
		</thead>
		<tbody id="ud-produktionsdaten-body">
			<?php if (!empty($lieferanten)) : ?>
				<?php foreach ($lieferanten as $index => $row) : ?>
					<tr>
						<td><input type="text" name="suppentag_produktion[<?php echo $index; ?>][name]" value="<?php echo esc_attr($row['name'] ?? ''); ?>"></td>
						<td><input type="number" step="0.1" name="suppentag_produktion[<?php echo $index; ?>][lieferung]" value="<?php echo esc_attr($row['lieferung'] ?? 0); ?>"></td>
						<td><input type="number" step="0.1" name="suppentag_produktion[<?php echo $index; ?>][retouren]" value="<?php echo esc_attr($row['retouren'] ?? 0); ?>"></td>
						<td><input type="number" step="0.1" name="suppentag_produktion[<?php echo $index; ?>][verkauf]" value="<?php echo esc_attr($row['verkauf'] ?? 0); ?>"></td>
						<td><button type="button" class="button remove">–</button></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="5">Noch keine Lieferanten erfasst.</td></tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td><strong>Total</strong></td>
				<td></td>
				<td class="total-retouren"><strong>0 l</strong></td>
				<td class="total-verkauf"><strong>0 l</strong></td>
				<td></td>
			</tr>
		</tfoot>
	</table>

	<p>
		<button type="button" class="button" id="ud-produktionsdaten-add">+ Lieferant hinzufügen</button>
	</p>

	<script>
		const tableBody = document.getElementById('ud-produktionsdaten-body');

		// ➕ Zeile hinzufügen
		document.getElementById('ud-produktionsdaten-add')?.addEventListener('click', () => {
			const index = tableBody.querySelectorAll('tr').length;
			const row = document.createElement('tr');
			row.innerHTML = `
				<td><input type="text" name="suppentag_produktion[${index}][name]" value=""></td>
				<td><input type="number" step="0.1" name="suppentag_produktion[${index}][lieferung]" value="0"></td>
				<td><input type="number" step="0.1" name="suppentag_produktion[${index}][retouren]" value="0"></td>
				<td><input type="number" step="0.1" name="suppentag_produktion[${index}][verkauf]" value="0"></td>
				<td><button type="button" class="button remove">–</button></td>
			`;
			tableBody.appendChild(row);
			updateTotals();
		});

		// Entfernen
		tableBody.addEventListener('click', (e) => {
			if (e.target.classList.contains('remove')) {
				e.target.closest('tr').remove();
				updateTotals();
			}
		});

		// Totals aktualisieren
		document.addEventListener('input', (e) => {
			if (['lieferung','retouren','verkauf'].some(k => e.target.name?.includes(k))) {
				updateTotals();
			}
		});

		function updateTotals() {
			let totalRetouren = 0;
			let totalVerkauf = 0;
			document.querySelectorAll('input[name*="[retouren]"]').forEach(el => totalRetouren += parseFloat(el.value) || 0);
			document.querySelectorAll('input[name*="[verkauf]"]').forEach(el => totalVerkauf += parseFloat(el.value) || 0);
			document.querySelector('.total-retouren strong').textContent = totalRetouren.toFixed(1) + ' l';
			document.querySelector('.total-verkauf strong').textContent = totalVerkauf.toFixed(1) + ' l';
		}

		updateTotals();
	</script>
	<?php
}

/**
 * ✅ Speichern der Produktionsdaten
 */
add_action('save_post_ud_suppentag', function($post_id) {
	if (
		!isset($_POST['ud_produktionsdaten_nonce']) ||
		!wp_verify_nonce($_POST['ud_produktionsdaten_nonce'], 'ud_save_produktionsdaten')
	) return;

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	// Gesamtproduktion
	if (isset($_POST['produktion_gesamt'])) {
		update_post_meta($post_id, 'produktion_gesamt', floatval($_POST['produktion_gesamt']));
	}

	// Lieferanten-Daten
	if (isset($_POST['suppentag_produktion']) && is_array($_POST['suppentag_produktion'])) {
		$data = array_values(array_filter($_POST['suppentag_produktion'], fn($row) => !empty($row['name'])));
		update_post_meta($post_id, 'suppentag_produktion', $data);
	}
});
