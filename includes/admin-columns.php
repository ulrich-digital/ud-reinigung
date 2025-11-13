<?php
/* Datei: admin-columns.php */

/**
 * 🔹 Zusätzliche Spalten für CPT "reinigung"
 */
add_filter('manage_reinigung_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['fortschritt'] = 'Fortschritt';
            $new['bemerkungen'] = 'Bemerkung';
            $new['suppentag']   = 'Suppentag'; // 🔹 neue Spalte
        }
    }
    return $new;
});


/**
 * 🔹 Inhalte für die neuen Spalten
 */
add_action('manage_reinigung_posts_custom_column', function ($column, $post_id) {

    // Fortschrittsanzeige
    if ($column === 'fortschritt') {
        $meta = get_post_meta($post_id, 'checklisten', true);
        if (!is_array($meta)) {
            $meta = json_decode($meta, true) ?: [];
        }

        $total = 0;
        $done  = 0;

        foreach ($meta as $bereich) {
            if (is_array($bereich)) {
                $total += count($bereich);
                $done  += count(array_filter($bereich));
            }
        }

        if ($total > 0) {
            $percent = round(($done / $total) * 100);
            $color   = $percent >= 100 ? '#11863a' : ($percent > 0 ? '#46b450' : '#ccc');

            echo '<div style="min-width:90px">';
            echo "<div style='background:#f2f2f2;border-radius:4px;height:8px;overflow:hidden;position:relative;margin-bottom:4px;'>";
            echo "<div style='background:{$color};width:{$percent}%;height:100%;transition:width .3s;'></div>";
            echo "</div>";
            echo "<span style='font-size:12px;color:#555'>{$done} / {$total} erledigt ({$percent} %)</span>";
            echo '</div>';
        } else {
            echo '<span style="color:#aaa;">–</span>';
        }
    }

    // Bemerkungen anzeigen
    if ($column === 'bemerkungen') {
        $bemerkung = trim(get_post_meta($post_id, 'bemerkungen', true));
        if ($bemerkung) {
            echo '<span style="font-size:13px;color:#444;">' .
                esc_html(wp_trim_words($bemerkung, 15, ' …')) .
                '</span>';
        } else {
            echo '<span style="color:#aaa;">Keine</span>';
        }
    }

    // 🔹 Verknüpfter Suppentag
    if ($column === 'suppentag') {
        $suppentag_id = get_post_meta($post_id, 'reinigung_suppentag_id', true);

        if ($suppentag_id) {
            $title = get_the_title($suppentag_id);
            $url   = get_edit_post_link($suppentag_id);
            $date  = get_post_meta($suppentag_id, 'suppentag_date', true);

            printf(
                '<a href="%s" style="color:#0073aa; text-decoration:none;" title="Suppentag bearbeiten">%s<br><span style="font-size:11px;color:#666;">%s</span></a>',
                esc_url($url),
                esc_html($title ?: 'Suppentag'),
                esc_html($date ?: '–')
            );
        } else {
            echo '<span style="color:#aaa;">–</span>';
        }
    }
}, 10, 2);


/**
 * 🔹 Fortschritt sortierbar machen (nach %)
 */
add_filter('manage_edit-reinigung_sortable_columns', function ($columns) {
    $columns['fortschritt'] = 'fortschritt';
    return $columns;
});

/**
 * 🔹 Sortierlogik (berechnet % on-the-fly)
 */
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') === 'fortschritt' && $query->get('post_type') === 'reinigung') {
        $query->set('meta_key', 'checklisten');
        // Kein echtes numerisches Sortieren, WP kann JSON nicht direkt vergleichen,
        // aber wir lassen den Hook vorbereitet, falls später Scores gecached werden.
    }
});

/**
 * 🔹 Schnellbearbeitung: Feld "Bemerkung"
 */
add_action('quick_edit_custom_box', function ($column, $post_type) {
    if ($post_type !== 'reinigung' || $column !== 'bemerkungen') {
        return;
    }
?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <span class="title"><?php _e('Bemerkung', 'ud-reinigung-ud'); ?></span>
                <textarea name="bemerkungen" rows="2" style="width:100%;"></textarea>
            </label>
        </div>
    </fieldset>
<?php
}, 10, 2);


/**
 * 🔹 Schnellbearbeitung speichern
 */
add_action('save_post_reinigung', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['bemerkungen'])) {
        update_post_meta($post_id, 'bemerkungen', sanitize_textarea_field($_POST['bemerkungen']));
    }
});
