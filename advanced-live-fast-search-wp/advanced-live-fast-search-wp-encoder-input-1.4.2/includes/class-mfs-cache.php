<?php
defined('ABSPATH') || exit;

class MFS_Cache {

	public static function init() {
		add_action(MFS_CACHE_HOOK, array(__CLASS__, 'build'));
	}

	public static function dir() {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit($uploads['basedir']) . 'advanced-live-fast-search';
		if (!is_dir($dir)) {
			wp_mkdir_p($dir);
		}
		return $dir;
	}

	public static function file_path() {
		$name = get_option(MFS_CACHE_NAME, 'data.json');
		return trailingslashit(self::dir()) . $name;
	}

	public static function ensure_dir() {
		$dir = self::dir();

		$htaccess = trailingslashit($dir) . '.htaccess';
		if (!file_exists($htaccess)) {
			$rules = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>";
			@file_put_contents($htaccess, $rules, LOCK_EX);
		}

		$index = trailingslashit($dir) . 'index.php';
		if (!file_exists($index)) {
			@file_put_contents($index, "<?php // Silence.\n");
		}
	}

	public static function exists() {
		$file = self::file_path();
		return file_exists($file) && is_readable($file);
	}

	public static function is_fresh() {
		$file = self::file_path();
		if (!file_exists($file)) {
			return false;
		}
		$ttl = (int) MFS_Plugin::get('cache_ttl');
		return (filemtime($file) + $ttl) > time();
	}

	public static function read() {
		$file = self::file_path();
		if (!is_readable($file)) {
			return null;
		}
		$data = json_decode(file_get_contents($file), true);
		return is_array($data) ? $data : null;
	}

	public static function write($data) {
		self::ensure_dir();

		$file = self::file_path();
		if (!is_writable(dirname($file))) {
			return false;
		}

		$json = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (false === $json) {
			return false;
		}

		$tmp = $file . '.' . wp_generate_password(8, false, false) . '.tmp';
		if (false === file_put_contents($tmp, $json, LOCK_EX)) {
			return false;
		}

		if (!@rename($tmp, $file)) {
			@unlink($tmp);
			return false;
		}

		return true;
	}

	public static function build() {
		if (get_transient(MFS_CACHE_LOCK)) {
			return null;
		}
		set_transient(MFS_CACHE_LOCK, 1, 5 * MINUTE_IN_SECONDS);

		$settings   = MFS_Plugin::settings();
		$sections   = array();
		$counts     = array('items' => array(), 'terms' => array());

		foreach ((array) $settings['post_types'] as $post_type) {
			if (!post_type_exists($post_type)) {
				continue;
			}

			$items     = array();
			$with_meta = ('product' === $post_type && function_exists('wc_get_product'));

			$query_args = array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				'has_password'        => false,
				'posts_per_page'      => -1,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			);

			// Respect WooCommerce catalog visibility: products excluded from
			// search must not leak back into the public JSON search index.
			if ('product' === $post_type && taxonomy_exists('product_visibility')) {
				$hidden_from_search = get_term_by('slug', 'exclude-from-search', 'product_visibility');
				if ($hidden_from_search && !is_wp_error($hidden_from_search)) {
					$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_visibility',
							'field'    => 'term_id',
							'terms'    => array((int) $hidden_from_search->term_id),
							'operator' => 'NOT IN',
						),
					);
				}
			}

			$query = new WP_Query($query_args);

			while ($query->have_posts()) {
				$query->the_post();
				$id   = get_the_ID();
				$item = array(
					'id'    => $id,
					'title' => esc_html(get_the_title()),
					'link'  => esc_url(get_permalink()),
					'img'   => esc_url(get_the_post_thumbnail_url($id, 'thumbnail')),
				);

				if ($with_meta) {
					$product = wc_get_product($id);
					if ($product) {
						$regular         = (float) $product->get_regular_price();
						$sale            = (float) $product->get_sale_price();
						$discount        = ($sale > 0 && $regular > 0) ? (int) ceil((($regular - $sale) / $regular) * 100) : 0;
						$item['price_html'] = $product->get_price_html();
						$item['sale']       = $discount;
						$item['stock']      = $product->is_in_stock();
					}
				}

				$items[$id] = $item;
			}
			wp_reset_postdata();

			$sections[$post_type]        = $items;
			$counts['items'][$post_type] = count($items);
		}

		$terms = array();
		foreach ((array) $settings['taxonomies'] as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$list        = array();
			$term_result = get_terms(array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			));

			if (!is_wp_error($term_result)) {
				foreach ($term_result as $term) {
					$link = get_term_link($term);
					if (is_wp_error($link)) {
						continue;
					}
					$list[$term->term_id] = array(
						'id'    => $term->term_id,
						'title' => esc_html($term->name),
						'link'  => esc_url($link),
					);
				}
			}

			$terms[$taxonomy]        = $list;
			$counts['terms'][$taxonomy] = count($list);
		}

		$data = array(
			'generated' => time(),
			'sections'  => $sections,
			'terms'     => $terms,
		);

		$written = self::write($data);
		delete_transient(MFS_CACHE_LOCK);

		if ($written) {
			update_option(MFS_CACHE_INFO, array(
				'built'  => time(),
				'size'   => filesize(self::file_path()),
				'counts' => $counts,
			), false);
		}

		return $written ? $data : null;
	}

	public static function info() {
		$info = (array) get_option(MFS_CACHE_INFO, array());
		if (self::exists()) {
			$info['file_time'] = filemtime(self::file_path());
			$info['file_size'] = filesize(self::file_path());
		}
		$info['ttl']        = (int) MFS_Plugin::get('cache_ttl');
		$info['rest_url']   = rest_url('mfs/v1/data');
		return $info;
	}

	public static function clear() {
		$file = self::file_path();
		if (file_exists($file)) {
			@unlink($file);
		}
		delete_option(MFS_CACHE_INFO);
		delete_transient(MFS_CACHE_LOCK);
	}

	public static function schedule_refresh() {
		if (!wp_next_scheduled(MFS_CACHE_HOOK)) {
			wp_schedule_single_event(time() + 10, MFS_CACHE_HOOK);
		}
	}

	public static function maybe_schedule_on_post_save($post_id, $post) {
		if (wp_is_post_revision($post_id) || !$post) {
			return;
		}
		if (!in_array($post->post_type, (array) MFS_Plugin::get('post_types'), true)) {
			return;
		}
		self::schedule_refresh();
	}

	public static function maybe_schedule_on_post_delete($post_id) {
		$post = get_post($post_id);
		if (!$post) {
			return;
		}
		if (!in_array($post->post_type, (array) MFS_Plugin::get('post_types'), true)) {
			return;
		}
		self::schedule_refresh();
	}

	public static function normalize_urls($data, &$changed = false) {
		$current_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

		foreach (array('sections', 'terms') as $group) {
			if (empty($data[$group]) || !is_array($data[$group])) {
				continue;
			}

			foreach ($data[$group] as &$items) {
				if (!is_array($items)) {
					continue;
				}
				foreach ($items as &$item) {
					foreach (array('link', 'img') as $key) {
						if (empty($item[$key]) || !is_string($item[$key])) {
							continue;
						}
						$host = wp_parse_url($item[$key], PHP_URL_HOST);
						$path = wp_parse_url($item[$key], PHP_URL_PATH);
						if ($host && $path && $host !== $current_host) {
							$item[$key] = home_url($path);
							$changed    = true;
						}
					}
				}
				unset($item);
			}
			unset($items);
		}

		return $data;
	}
}

MFS_Cache::init();
