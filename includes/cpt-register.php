<?php
add_action('init', function () {
    register_post_type('reinigung', [
        'labels' => [
            'name'          => __('Reinigungen', 'ud'),
            'singular_name' => __('Reinigung', 'ud'),
            'add_new_item'  => __('Reinigung hinzufügen', 'ud'),
            'edit_item'     => __('Reinigung bearbeiten', 'ud'),
        ],
        'public'             => true,
        'show_in_rest'       => true,                 // Gutenberg + REST
        'menu_icon'          => 'dashicons-admin-appearance',
        'menu_position'      => 26,
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'reinigung', 'with_front' => false],
        'hierarchical'       => false,
        'supports'           => ['title','custom-fields'], // editor wieder rein!
        'map_meta_cap'       => true,
        'publicly_queryable' => true,
    ]);

    // 🔹 Checklisten (Bereich -> Aufgabe -> bool)
    register_post_meta('reinigung', 'checklisten', [
        'single'        => true,
        'type'          => 'object',
        'show_in_rest'  => [
            'schema' => [
                'type'                 => 'object',
                'additionalProperties' => [
                    'type'                 => 'object',
                    'additionalProperties' => ['type' => 'boolean'],
                ],
            ],
        ],
        'auth_callback' => fn() => current_user_can('edit_posts'),
    ]);

    // 🔹 Freitext-Bemerkungen
    register_post_meta('reinigung', 'bemerkungen', [
        'single'        => true,
        'type'          => 'string',
        'show_in_rest'  => true,
        'auth_callback' => fn() => current_user_can('edit_posts'),
    ]);
});

// stellt sicher, dass die Metabox "Individuelle Felder" für CPT 'reinigung' existiert
add_action('add_meta_boxes', function () {
    add_meta_box(
        'postcustom',
        __('Individuelle Felder'),
        'post_custom_meta_box',
        'reinigung',
        'normal',
        'default'
    );
}, 11);

add_filter('use_block_editor_for_post_type', function($use, $post_type){
    return ($post_type === 'reinigung') ? false : $use;
}, 10, 2);




