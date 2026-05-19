<?php
/**
 * مدیریت درخواست تماس / پشتیبانی برای پیش‌فاکتورها.
 *
 * @package WC_Request_Quotation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_RQ_Support
 */
class WC_RQ_Support {

	const META_NEEDS_SUPPORT  = 'rq_needs_support';
	const META_SUPPORT_SEEN   = 'rq_support_seen';
	const META_SUPPORT_CALLED = 'rq_support_called';
	const META_SUPPORT_REPORT = 'rq_support_report';

	const DEFAULT_CHECKBOX_LABEL = 'نیاز به راهنمایی و تماس کارشناس دارم';

	/**
	 * URL ریشهٔ افزونه (نه پوشهٔ includes).
	 *
	 * @return string
	 */
	private static function plugin_url() {
		if ( defined( 'WC_RQ_PLUGIN_FILE' ) ) {
			return plugin_dir_url( WC_RQ_PLUGIN_FILE );
		}
		return plugin_dir_url( dirname( __DIR__ ) . '/wc-request-quotation.php' );
	}

	/**
	 * WC_RQ_Support constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post_quotation_request', array( $this, 'save_metabox' ), 10, 2 );

		add_filter( 'manage_quotation_request_posts_columns', array( $this, 'filter_columns' ), 20 );
		add_action( 'manage_quotation_request_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'post_class', array( $this, 'row_class' ), 10, 3 );
		add_filter( 'display_post_states', array( $this, 'post_states' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'load-post.php', array( $this, 'maybe_mark_seen_on_edit' ) );

		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
		add_action( 'admin_menu', array( $this, 'menu_badge' ), 99 );
	}

	/**
	 * @return string
	 */
	public static function get_default_checkbox_label() {
		return self::DEFAULT_CHECKBOX_LABEL;
	}

	/**
	 * @return string
	 */
	public static function get_checkbox_label() {
		$settings = get_option( 'wc_rq_settings', array() );
		$label    = isset( $settings['support_checkbox_label'] ) ? trim( (string) $settings['support_checkbox_label'] ) : '';

		return $label !== '' ? $label : self::DEFAULT_CHECKBOX_LABEL;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function needs_support( $post_id ) {
		return (bool) get_post_meta( $post_id, self::META_NEEDS_SUPPORT, true );
	}

	/**
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_pending_call( $post_id ) {
		if ( ! self::needs_support( $post_id ) ) {
			return false;
		}
		return ! (bool) get_post_meta( $post_id, self::META_SUPPORT_CALLED, true );
	}

	/**
	 * @return int
	 */
	public static function count_pending_calls() {
		$q = new WP_Query(
			array(
				'post_type'      => 'quotation_request',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => self::META_NEEDS_SUPPORT,
						'value' => '1',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => self::META_SUPPORT_CALLED,
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'   => self::META_SUPPORT_CALLED,
							'value' => '0',
						),
					),
				),
			)
		);

		return (int) $q->found_posts;
	}

	/**
	 * @return void
	 */
	public function register_metabox() {
		add_meta_box(
			'rq_support_box',
			'پیگیری تماس با مشتری',
			array( $this, 'render_metabox' ),
			'quotation_request',
			'side',
			'high'
		);
	}

	/**
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_metabox( $post ) {
		$needs_support = self::needs_support( $post->ID );
		$called        = (bool) get_post_meta( $post->ID, self::META_SUPPORT_CALLED, true );
		$report        = get_post_meta( $post->ID, self::META_SUPPORT_REPORT, true );

		wp_nonce_field( 'wc_rq_support_save', 'wc_rq_support_nonce' );

		if ( ! $needs_support ) {
			echo '<p class="rq-support-muted">مشتری درخواست تماس کارشناس ثبت نکرده است.</p>';
			return;
		}

		$box_class = $called ? 'rq-support-box rq-support-box--done' : 'rq-support-box rq-support-box--pending';
		?>
		<div class="<?php echo esc_attr( $box_class ); ?>">
			<p class="rq-support-flag">
				<span class="dashicons dashicons-phone" aria-hidden="true"></span>
				<strong>درخواست تماس فعال است</strong>
			</p>

			<p>
				<label>
					<input type="checkbox" name="rq_support_called" value="1" <?php checked( $called ); ?> />
					تماس با مشتری گرفته شد
				</label>
			</p>

			<p class="rq-support-report-wrap<?php echo $called ? '' : ' rq-support-report-wrap--hidden'; ?>">
				<label for="rq_support_report"><strong>گزارش مختصر از ارتباط با مشتری</strong></label>
				<textarea name="rq_support_report" id="rq_support_report" rows="4" class="widefat"><?php echo esc_textarea( $report ); ?></textarea>
			</p>
		</div>
		<?php
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_metabox( $post_id, $post ) {
		if ( ! $post || 'quotation_request' !== $post->post_type ) {
			return;
		}
		if ( ! isset( $_POST['wc_rq_support_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc_rq_support_nonce'] ) ), 'wc_rq_support_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! self::needs_support( $post_id ) ) {
			return;
		}

		$called = ! empty( $_POST['rq_support_called'] );
		update_post_meta( $post_id, self::META_SUPPORT_CALLED, $called ? 1 : 0 );
		update_post_meta( $post_id, self::META_SUPPORT_SEEN, 1 );

		if ( $called && isset( $_POST['rq_support_report'] ) ) {
			update_post_meta(
				$post_id,
				self::META_SUPPORT_REPORT,
				sanitize_textarea_field( wp_unslash( $_POST['rq_support_report'] ) )
			);
		}
	}

	/**
	 * @param array $columns Columns.
	 * @return array
	 */
	public function filter_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'rq_phone' === $key ) {
				$new['rq_support'] = 'تماس';
			}
		}
		if ( ! isset( $new['rq_support'] ) ) {
			$new['rq_support'] = 'تماس';
		}
		return $new;
	}

	/**
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 'rq_support' !== $column ) {
			return;
		}

		if ( ! self::needs_support( $post_id ) ) {
			echo '<span class="rq-support-badge rq-support-badge--none">—</span>';
			return;
		}

		$called = (bool) get_post_meta( $post_id, self::META_SUPPORT_CALLED, true );
		$seen   = (bool) get_post_meta( $post_id, self::META_SUPPORT_SEEN, true );

		if ( $called ) {
			echo '<span class="rq-support-badge rq-support-badge--done">تماس گرفته شد</span>';
			return;
		}

		$class = $seen ? 'rq-support-badge rq-support-badge--pending' : 'rq-support-badge rq-support-badge--new';
		$text  = $seen ? 'در انتظار تماس' : 'تماس جدید';
		echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $text ) . '</span>';
	}

	/**
	 * @param string[] $classes CSS classes.
	 * @param string[] $class   More classes.
	 * @param int      $post_id Post ID.
	 * @return string[]
	 */
	public function row_class( $classes, $class, $post_id ) {
		if ( ! is_admin() || 'quotation_request' !== get_post_type( $post_id ) ) {
			return $classes;
		}

		if ( ! self::needs_support( $post_id ) ) {
			return $classes;
		}

		if ( self::is_pending_call( $post_id ) ) {
			$seen      = (bool) get_post_meta( $post_id, self::META_SUPPORT_SEEN, true );
			$classes[] = $seen ? 'rq-row-support-pending' : 'rq-row-support-new';
		} else {
			$classes[] = 'rq-row-support-done';
		}

		return $classes;
	}

	/**
	 * @param string[] $post_states States.
	 * @param WP_Post  $post        Post.
	 * @return string[]
	 */
	public function post_states( $post_states, $post ) {
		if ( 'quotation_request' !== $post->post_type ) {
			return $post_states;
		}
		if ( self::is_pending_call( $post->ID ) ) {
			$post_states['rq_needs_call'] = 'نیاز به تماس';
		}
		return $post_states;
	}

	/**
	 * @param string $hook Hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		$is_list = ( 'edit.php' === $hook && 'quotation_request' === $screen->post_type );
		$is_edit = ( 'post.php' === $hook && 'quotation_request' === $screen->post_type );

		if ( ! $is_list && ! $is_edit ) {
			return;
		}

		$assets_ver = '2.2.4';

		wp_enqueue_style(
			'wc-rq-admin-support',
			self::plugin_url() . 'assets/admin-support.css',
			array(),
			$assets_ver
		);

		if ( $is_edit ) {
			wp_enqueue_script(
				'wc-rq-admin-support',
				self::plugin_url() . 'assets/admin-support.js',
				array( 'jquery' ),
				$assets_ver,
				true
			);
		}
	}

	/**
	 * @return void
	 */
	public function maybe_mark_seen_on_edit() {
		if ( ! isset( $_GET['post'] ) ) {
			return;
		}
		$post_id = (int) $_GET['post'];
		if ( 'quotation_request' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( self::needs_support( $post_id ) ) {
			update_post_meta( $post_id, self::META_SUPPORT_SEEN, 1 );
		}
	}

	/**
	 * @return void
	 */
	public function register_dashboard_widget() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'wc_rq_support_widget',
			'پیش‌فاکتور — در انتظار تماس',
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * @return void
	 */
	public function render_dashboard_widget() {
		$posts = get_posts(
			array(
				'post_type'      => 'quotation_request',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => self::META_NEEDS_SUPPORT,
						'value' => '1',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => self::META_SUPPORT_CALLED,
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'   => self::META_SUPPORT_CALLED,
							'value' => '0',
						),
					),
				),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $posts ) ) {
			echo '<p>درخواستی در انتظار تماس نیست.</p>';
			return;
		}

		echo '<ul class="rq-dashboard-list">';
		foreach ( $posts as $post ) {
			$name  = get_post_meta( $post->ID, 'rq_name', true );
			$phone = get_post_meta( $post->ID, 'rq_phone', true );
			$edit  = get_edit_post_link( $post->ID );
			echo '<li>';
			echo '<a href="' . esc_url( $edit ) . '"><strong>' . esc_html( $name ? $name : $post->post_title ) . '</strong></a>';
			if ( $phone ) {
				echo ' — <a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>';
			}
			echo '<br><span class="rq-dashboard-date">' . esc_html( get_the_date( 'Y/m/d H:i', $post ) ) . '</span>';
			echo '</li>';
		}
		echo '</ul>';
		echo '<p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=quotation_request' ) ) . '">مشاهده همه درخواست‌ها</a></p>';
	}

	/**
	 * @return void
	 */
	public function menu_badge() {
		$count = self::count_pending_calls();
		if ( $count < 1 ) {
			return;
		}

		global $menu;
		foreach ( $menu as $key => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=quotation_request' === $item[2] ) {
				$menu[ $key ][0] .= ' <span class="awaiting-mod count-' . (int) $count . '"><span class="pending-count">' . (int) $count . '</span></span>';
				break;
			}
		}
	}
}
