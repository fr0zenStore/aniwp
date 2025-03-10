<?php
/*
Plugin Name: AniWP
Description: Genera automaticamente CPT Anime e Movie da AniList API.
Version: 1.2
Author: fr0zen
*/

if (!defined('ABSPATH')) exit;

// Registra CPT Anime e Movie
add_action('init', 'aniwp_register_cpt');
function aniwp_register_cpt() {
    $post_types = ['anime' => 'Anime', 'movie' => 'Movie'];

    foreach ($post_types as $type => $label) {
        register_post_type($type, [
            'labels' => [
                'name' => $label,
                'singular_name' => $label,
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
        ]);

        register_taxonomy("genre_$type", $type, [
            'labels' => [
                'name' => "$label Genres",
                'singular_name' => 'Genre',
            ],
            'public' => true,
            'hierarchical' => true,
        ]);
    }
}

// Aggiunge il campo ID e il pulsante ad Anime e Movie
add_action('add_meta_boxes', 'aniwp_add_meta_box');
function aniwp_add_meta_box() {
    $post_types = ['anime', 'movie'];

    foreach ($post_types as $type) {
        add_meta_box(
            "anilist_meta_$type",
            'AniList Import',
            'aniwp_meta_box_callback',
            $type
        );
    }
}

function aniwp_meta_box_callback($post) {
    echo '<label for="anilist_id">AniList ID:</label>';
    echo '<input type="number" id="anilist_id" name="anilist_id" value="" style="width: 100%; margin-bottom: 10px;">';
    echo '<button type="button" id="anilist_fetch" class="button button-primary">Importa da AniList</button>';
    wp_nonce_field('anilist_nonce', 'anilist_nonce_field');
}

// Carica JS per il pulsante
add_action('admin_enqueue_scripts', 'aniwp_enqueue_scripts');
function aniwp_enqueue_scripts($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'])) return;

    wp_enqueue_script('anilist-js', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], null, true);
    wp_localize_script('anilist-js', 'anilistAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('anilist_nonce'),
    ]);
}

// Salva i campi personalizzati
add_action('save_post', 'aniwp_save_meta');
function aniwp_save_meta($post_id) {
    if (!isset($_POST['anilist_nonce_field']) || !wp_verify_nonce($_POST['anilist_nonce_field'], 'anilist_nonce')) return;

    if (isset($_POST['anilist_id'])) {
        update_post_meta($post_id, '_anilist_id', intval($_POST['anilist_id']));
    }
}

// Endpoint AJAX per il recupero dei dati
add_action('wp_ajax_anilist_fetch', 'aniwp_fetch_data');
function aniwp_fetch_data() {
    check_ajax_referer('anilist_nonce', 'nonce');
    if (empty($_POST['id']) || empty($_POST['type'])) wp_send_json_error(['message' => 'ID o tipo non valido']);

    require_once plugin_dir_path(__FILE__) . 'includes/anilist-api.php';
    $data = aniwp_get_data(intval($_POST['id']), sanitize_text_field($_POST['type']));

    if (!$data) wp_send_json_error(['message' => 'Errore durante il recupero dati']);

    wp_send_json_success(['data' => $data]);
}
