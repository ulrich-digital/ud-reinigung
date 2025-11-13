<?php
defined('ABSPATH') || exit;

/**
 * REST-Routen: Suppentag anhand Datum prüfen oder automatisch anlegen.
 */

add_action('rest_api_init', function () {

    // =======================================================
    // 🔹 Suppentag anhand Datum abrufen
    // =======================================================
    register_rest_route('ud-suppentag/v1', '/by-date', [
        'methods'  => 'GET',
        'callback' => function ($req) {
            $date = sanitize_text_field($req['date'] ?? '');
            if (empty($date)) {
                return new WP_Error('no_date', 'Kein Datum angegeben.', ['status' => 400]);
            }

            $query = new WP_Query([
                'post_type'      => 'ud_suppentag',
                'post_status'    => 'publish',
                'meta_key'       => 'suppentag_datum',
                'meta_value'     => $date,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ]);

            if (!empty($query->posts)) {
                return ['id' => $query->posts[0]];
            }

            return ['id' => null];
        },
        'permission_callback' => '__return_true',
    ]);

    // =======================================================
    // 🔹 Suppentag automatisch anlegen (falls keiner existiert)
    // =======================================================
    register_rest_route('ud-suppentag/v1', '/create', [
        'methods'  => 'POST',
        'callback' => function ($req) {
            $date = sanitize_text_field($req['date'] ?? '');
            if (empty($date)) {
                return new WP_Error('no_date', 'Kein Datum angegeben.', ['status' => 400]);
            }

            // Prüfen, ob bereits vorhanden
            $query = new WP_Query([
                'post_type'      => 'ud_suppentag',
                'post_status'    => 'publish',
                'meta_key'       => 'suppentag_datum',
                'meta_value'     => $date,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ]);

            if (!empty($query->posts)) {
                return ['id' => $query->posts[0], 'existing' => true];
            }

            // Neu anlegen
            $post_id = wp_insert_post([
                'post_type'   => 'ud_suppentag',
                'post_status' => 'publish',
                'post_title'  => 'Suppentag ' . esc_html($date),
                'meta_input'  => ['suppentag_datum' => $date],
            ]);

            if (is_wp_error($post_id)) {
                return new WP_Error('insert_failed', 'Suppentag konnte nicht erstellt werden.', ['status' => 500]);
            }

            return ['id' => $post_id, 'created' => true];
        },
        'permission_callback' => fn() => current_user_can('edit_posts'),
    ]);
});
