<?php
/**
 * Automated Build Webhook Manager with HMAC SHA256 and Debounce
 *
 * @package ProjectCore
 */

if (!defined('ABSPATH')) {
    exit;
}

class Project_Core_Webhook_Manager {

    const DEBOUNCE_TRANSIENT = 'project_core_webhook_debounce';
    const DEBOUNCE_SECONDS   = 30;

    public function init() {
        // Post events
        add_action('transition_post_status', array($this, 'on_post_status_transition'), 10, 3);
        add_action('before_delete_post', array($this, 'on_post_deleted'));

        // Taxonomy events
        add_action('created_category', array($this, 'on_taxonomy_changed'));
        add_action('edited_category', array($this, 'on_taxonomy_changed'));
        add_action('delete_category', array($this, 'on_taxonomy_changed'));
        add_action('created_post_tag', array($this, 'on_taxonomy_changed'));
        add_action('edited_post_tag', array($this, 'on_taxonomy_changed'));
        add_action('delete_post_tag', array($this, 'on_taxonomy_changed'));
    }

    /**
     * Handle transition of post status
     */
    public function on_post_status_transition($new_status, $old_status, $post) {
        // Prevent autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post->ID)) {
            return;
        }

        // Only handle post types that affect frontend
        $allowed_types = array('post', 'banner');
        if (!in_array($post->post_type, $allowed_types, true)) {
            return;
        }

        // Published new post
        if ($new_status === 'publish' && $old_status !== 'publish') {
            self::trigger('content_published', array(
                'post_id'   => $post->ID,
                'post_type' => $post->post_type,
                'slug'      => $post->post_name,
            ));
        }
        // Updated published post
        elseif ($new_status === 'publish' && $old_status === 'publish') {
            self::trigger('content_updated', array(
                'post_id'   => $post->ID,
                'post_type' => $post->post_type,
                'slug'      => $post->post_name,
            ));
        }
        // Unpublished or trashed from publish
        elseif ($new_status !== 'publish' && $old_status === 'publish') {
            self::trigger('content_unpublished', array(
                'post_id'    => $post->ID,
                'post_type'  => $post->post_type,
                'new_status' => $new_status,
            ));
        }
    }

    /**
     * Handle permanent post deletion
     */
    public function on_post_deleted($post_id) {
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, array('post', 'banner'), true)) {
            return;
        }

        if ($post->post_status === 'publish') {
            self::trigger('content_deleted', array(
                'post_id'   => $post_id,
                'post_type' => $post->post_type,
            ));
        }
    }

    /**
     * Handle category or tag changes
     */
    public function on_taxonomy_changed($term_id) {
        self::trigger('taxonomy_updated', array(
            'term_id' => $term_id,
        ));
    }

    /**
     * Trigger webhook with debounce, HMAC SHA256 signature, and error logging
     *
     * @param string $event
     * @param array $payload
     */
    public static function trigger($event, $payload = array()) {
        $settings = Project_Core_Site_Settings::get_settings();

        if (empty($settings['webhook_enabled']) || empty($settings['webhook_url'])) {
            Project_Core_Logger::info('Webhook trigger skipped: Webhooks disabled or URL missing', array('event' => $event));
            return;
        }

        // Debounce check: Prevent flood of rebuilds when multiple edits happen within 30s
        $debounce_key = self::DEBOUNCE_TRANSIENT . '_' . $event;
        if (get_transient($debounce_key)) {
            Project_Core_Logger::info('Webhook debounced: Rapid edit throttled', array('event' => $event));
            return;
        }
        set_transient($debounce_key, true, self::DEBOUNCE_SECONDS);

        $body_data = array(
            'event'       => $event,
            'timestamp'   => time(),
            'site_url'    => home_url(),
            'data'        => $payload,
        );

        $json_payload = wp_json_encode($body_data);

        // Compute HMAC signature
        $secret = $settings['webhook_secret'];
        $signature = !empty($secret) ? hash_hmac('sha256', $json_payload, $secret) : '';

        $headers = array(
            'Content-Type'         => 'application/json; charset=utf-8',
            'User-Agent'           => 'Technosrc-WordPress-Webhook/1.0',
            'X-Project-Event'      => $event,
            'X-Project-Signature'  => 'sha256=' . $signature,
        );

        Project_Core_Logger::webhook('Dispatching webhook', array(
            'url'   => $settings['webhook_url'],
            'event' => $event,
        ));

        // Send non-blocking HTTP request
        $response = wp_remote_post($settings['webhook_url'], array(
            'method'      => 'POST',
            'timeout'     => 10,
            'redirection' => 3,
            'httpversion' => '1.1',
            'blocking'    => false, // Non-blocking to keep admin UI responsive
            'headers'     => $headers,
            'body'        => $json_payload,
        ));

        if (is_wp_error($response)) {
            Project_Core_Logger::error('Webhook dispatch failed', array(
                'error' => $response->get_error_message(),
                'event' => $event,
            ));
        }
    }
}
