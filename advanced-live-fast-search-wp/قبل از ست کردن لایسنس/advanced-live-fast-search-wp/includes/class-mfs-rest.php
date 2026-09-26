<?php
defined('ABSPATH') || exit;

class MFS_Rest {

	const NAMESPACE_V1 = 'mfs/v1';

	public static function init() {
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
		add_action('init', array(__CLASS__, 'register_rewrite'));
		add_filter('query_vars', array(__CLASS__, 'add_query_var'));
		add_filter('rewrite_rules_array', array(__CLASS__, 'inject_rewrite'));
		add_action('parse_request', array(__CLASS__, 'maybe_serve_file'));
	}

	public static function register_routes() {
		register_rest_route(self::NAMESPACE_V1, '/data', array(
			'methods'             => 'GET',
			'callback'            => array(__CLASS__, 'get_data'),
			'permission_callback' => '__return_true',
		));

		register_rest_route(self::NAMESPACE_V1, '/rebuild', array(
			'methods'             => 'POST',
			'callback'            => array(__CLASS__, 'rebuild'),
			'permission_callback' => array(__CLASS__, 'can_manage'),
		));

		register_rest_route(self::NAMESPACE_V1, '/clear', array(
			'methods'             => 'POST',
			'callback'            => array(__CLASS__, 'clear'),
			'permission_callback' => array(__CLASS__, 'can_manage'),
		));
	}

	public static function can_manage() {
		return current_user_can('manage_options');
	}

	public static function register_rewrite() {
		add_rewrite_rule('search-data\.json$', 'index.php?mfs_search_data=1', 'top');

		if (!get_option('mfs_rewrite_flushed')) {
			update_option('mfs_rewrite_flushed', 1, false);
			flush_rewrite_rules();
		}
	}

	public static function add_query_var($vars) {
		$vars[] = 'mfs_search_data';
		return $vars;
	}

	public static function inject_rewrite($rules) {
		return array('search-data\.json$' => 'index.php?mfs_search_data=1') + $rules;
	}

	public static function maybe_serve_file($query) {
		if (empty($query->query_vars['mfs_search_data'])) {
			return;
		}

		$payload = self::payload();

		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: public, max-age=300, stale-while-revalidate=86400');
		echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	public static function payload() {
		$data = MFS_Cache::read();

		if (is_array($data)) {
			$changed = false;
			$data    = MFS_Cache::normalize_urls($data, $changed);

			if ($changed || !MFS_Cache::is_fresh()) {
				MFS_Cache::schedule_refresh();
			}
		} elseif (current_user_can('manage_options')) {
			$data = MFS_Cache::build();
		} else {
			MFS_Cache::schedule_refresh();
			$data = array(
				'generated' => time(),
				'sections'  => new stdClass(),
				'terms'     => new stdClass(),
			);
		}

		if (!is_array($data)) {
			$data = array(
				'generated' => time(),
				'sections'  => new stdClass(),
				'terms'     => new stdClass(),
			);
			MFS_Cache::schedule_refresh();
		}

		return $data;
	}

	public static function get_data(WP_REST_Request $request) {
		unset($request);

		$response = rest_ensure_response(self::payload());
		$response->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=86400');
		return $response;
	}

	public static function rebuild(WP_REST_Request $request) {
		unset($request);
		delete_transient(MFS_CACHE_LOCK);
		$data = MFS_Cache::build();

		return rest_ensure_response(array(
			'built' => (bool) $data,
			'info'  => MFS_Cache::info(),
		));
	}

	public static function clear(WP_REST_Request $request) {
		unset($request);
		MFS_Cache::clear();
		return rest_ensure_response(array('cleared' => true));
	}
}
