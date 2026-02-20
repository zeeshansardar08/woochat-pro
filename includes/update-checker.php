<?php
if (!defined('ABSPATH')) exit;

add_filter('pre_set_site_transient_update_plugins', 'wcwp_update_check');
add_filter('plugins_api', 'wcwp_update_plugin_info', 10, 3);

function wcwp_update_api_url() {
    $default = 'https://yourdomain.com/woochat-pro-update.json';
    $env = function_exists('wp_get_environment_type') ? wp_get_environment_type() : '';
    $site_url = home_url();
    $is_local = ($env === 'local') || (strpos($site_url, '.local') !== false);

    if ($is_local) {
        $default = home_url('/woochat-pro-update.json');
    }

    return apply_filters('wcwp_update_api_url', $default);
}

function wcwp_fetch_update_info() {
    $cache_key = 'wcwp_update_info';
    $cached = get_transient($cache_key);
    if (is_array($cached)) return $cached;

    $url = wcwp_update_api_url();
    if (!$url) return null;

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) return null;

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return null;

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['version'])) return null;

    set_transient($cache_key, $data, 6 * HOUR_IN_SECONDS);
    return $data;
}

function wcwp_update_check($transient) {
    if (empty($transient->checked)) return $transient;

    $info = wcwp_fetch_update_info();
    if (!$info) return $transient;

    $new_version = (string) $info['version'];
    if (!version_compare(WCWP_VERSION, $new_version, '<')) return $transient;

    if (empty($info['download_url'])) return $transient;

    $plugin = plugin_basename(WCWP_PLUGIN_FILE);
    $item = new stdClass();
    $item->slug = 'woochat-pro';
    $item->plugin = $plugin;
    $item->new_version = $new_version;
    $item->url = isset($info['homepage']) ? $info['homepage'] : '';
    $item->package = $info['download_url'];

    $transient->response[$plugin] = $item;
    return $transient;
}

function wcwp_update_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information') return $result;
    if (!isset($args->slug) || $args->slug !== 'woochat-pro') return $result;

    $info = wcwp_fetch_update_info();
    if (!$info) return $result;

    $data = new stdClass();
    $data->name = isset($info['name']) ? $info['name'] : 'WooChat Pro';
    $data->slug = 'woochat-pro';
    $data->version = (string) $info['version'];
    $data->author = isset($info['author']) ? $info['author'] : 'ZeeCreatives';
    $data->homepage = isset($info['homepage']) ? $info['homepage'] : '';
    $data->requires = isset($info['requires']) ? $info['requires'] : '';
    $data->tested = isset($info['tested']) ? $info['tested'] : '';
    $data->download_link = isset($info['download_url']) ? $info['download_url'] : '';
    $data->sections = isset($info['sections']) && is_array($info['sections']) ? $info['sections'] : [
        'description' => 'WooChat Pro updates and release details.',
    ];

    return $data;
}
