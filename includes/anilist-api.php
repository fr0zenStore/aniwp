<?php

if (!defined('ABSPATH')) exit;

function aniwp_get_data($id, $type) {
    $mediaType = strtoupper($type); // "ANIME" o "MOVIE"
    $query = <<<GQL
    query {
        Media(id: $id, type: $mediaType) {
            title {
                romaji
                english
                native
            }
            description
            episodes
            duration
            status
            format
            averageScore
            genres
            studios {
                nodes {
                    name
                }
            }
            coverImage {
                large
            }
        }
    }
    GQL;

    $response = wp_remote_post('https://graphql.anilist.co', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode(['query' => $query]),
    ]);

    if (is_wp_error($response)) return false;

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['data']['Media'] ?? false;
}
