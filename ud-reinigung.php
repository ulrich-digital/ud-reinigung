<?php
/* Datei: ud-reinigung.php */

/**
 * Plugin Name:     UD Block: Reinigung
 * Description:     Tagesbasierte Reinigungs-Checklisten mit direkter Frontend-Erstellung.
 * Version:         1.0.0
 * Author:          ulrich.digital gmbh
 * Author URI:      https://ulrich.digital/
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     ud-reinigung-ud
 */

defined('ABSPATH') || exit;

// 🔹 1. Immer zuerst definieren!
define('UD_REINIGUNG_PATH', plugin_dir_path(__FILE__));

// 🔹 2. Debug: Basispfad ins Log schreiben
error_log('✅ UD Reinigung geladen, Basis-Pfad: ' . UD_REINIGUNG_PATH);

// 🔹 3. Jetzt prüfen und einbinden
if (file_exists(UD_REINIGUNG_PATH . 'includes/api-start.php')) {
    error_log('✅ UD Reinigung: api-start.php wurde gefunden.');
    require_once UD_REINIGUNG_PATH . 'includes/api-start.php';
} else {
    error_log('❌ UD Reinigung: api-start.php NICHT gefunden unter ' . UD_REINIGUNG_PATH . 'includes/api-start.php');
}

// 🔹 4. Restliche Includes
require_once UD_REINIGUNG_PATH . 'includes/cpt-register.php';
require_once UD_REINIGUNG_PATH . 'includes/enqueue.php';
require_once UD_REINIGUNG_PATH . 'includes/render.php';
require_once UD_REINIGUNG_PATH . 'includes/api-save.php';
require_once UD_REINIGUNG_PATH . 'includes/reinigung-checklisten.php';
require_once UD_REINIGUNG_PATH . 'includes/ud-suppentag-rest.php';

// 🔹 Nur im Admin-Bereich laden (nicht im Frontend)
if (is_admin()) {
    require_once UD_REINIGUNG_PATH . 'includes/admin-columns.php';
    require_once UD_REINIGUNG_PATH . 'includes/meta-box-produktionsdaten.php';
    require_once UD_REINIGUNG_PATH . 'includes/meta-box-checklisten.php'; // ✅ neue Datei
}

/**
 * Shortcode: [ud_reinigung_button date="2025-10-17"]
 * Gibt den Frontend-Button aus, um die tägliche Reinigung zu starten.
 */
add_shortcode('ud_reinigung_button', function ($atts) {
    $atts = shortcode_atts(['date' => ''], $atts);
    $date = $atts['date'] ?: current_time('Y-m-d'); // fallback: heutiges Datum

    ob_start(); ?>
    <button id="ud-start-reinigung" class="ud-reinigung-button ud-button-bar-button">
        <div class="ud-reinigung-progress-ring progress-ring">
            <svg viewBox="0 0 36 36">
                <circle class="bg" cx="18" cy="18" r="16"></circle>
                <circle class="progress" cx="18" cy="18" r="16"></circle>
            </svg>
        </div>
        <div class="ud-reinigung-button-content button-content">
            <div class="label">Reinigung</div>
            <div class="progress-text">– lädt Fortschritt –</div>
        </div>
    </button>

    <div id="ud-reinigung-modal" class="ud-reinigung-modal ud-modal" hidden>
        <div class="ud-reinigung-modal-backdrop ud-modal-backdrop"></div>
        <div class="ud-reinigung-modal-content ud-modal-content">
            <button class="ud-reinigung-modal-close ud-modal-close"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.4 19L5 17.6L10.6 12L5 6.4L6.4 5L12 10.6L17.6 5L19 6.4L13.4 12L19 17.6L17.6 19L12 13.4L6.4 19Z" fill="#B2B2B2"/></svg></button>
            <!-- Header -->
            <div class="ud-reinigung-modal-header">
                <h3 class="ud-reinigung-modal-title ud-modal-title">Reinigung</h3>
            </div>

            <!-- Body -->
            <div class="ud-reinigung-modal-body">
                <div id="ud-reinigung-loading">Lade Reinigung…</div>
                <div id="ud-reinigung-checklisten" hidden></div>
            </div>

            <!-- Footer / Actions -->
            <div class="ud-reinigung-modal-footer">
                <div class="actions">
                    <button id="cancel-reinigung" class="button-cancel">Abbrechen</button>
                    <button id="save-reinigung" class="button-save">Speichern</button>
                </div>
            </div>
        </div>
    </div>

<?php
    return ob_get_clean();
});

// Sidebar-Panel für CPT "reinigung" laden
/*
add_action('enqueue_block_editor_assets', function () {
    if ( ! function_exists('get_current_screen') ) return;
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'reinigung' ) return;

    // ✅ Default-Checklisten aus PHP ins JS
    if ( ! function_exists('ud_reinigung_get_default_checklisten') ) {
        require_once plugin_dir_path(__FILE__) . 'includes/reinigung-checklisten.php';
    }
    $defaults = ud_reinigung_get_default_checklisten();

    // Script
    wp_enqueue_script(
        'ud-reinigung-editor',
        plugins_url('build/reinigung-editor.js', __FILE__), // passe Pfad an
        [ 'wp-plugins','wp-edit-post','wp-components','wp-element','wp-data','wp-core-data','wp-i18n' ],
        filemtime( plugin_dir_path(__FILE__) . 'build/reinigung-editor.js' ),
        true
    );

    // Styles (optional)
    wp_enqueue_style(
        'ud-reinigung-editor',
        plugins_url('build/reinigung-editor.css', __FILE__),
        [],
        filemtime( plugin_dir_path(__FILE__) . 'build/reinigung-editor.css' )
    );

    // Defaults ins JS
    wp_localize_script('ud-reinigung-editor', 'udReinigungDefaults', [
        'bereiche' => $defaults,
    ]);
});
*/