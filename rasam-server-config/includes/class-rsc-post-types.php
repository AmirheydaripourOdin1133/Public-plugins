<?php
/**
 * CPT و تاکسونومی قطعات سرور.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Post_Types {

	const CPT_COMPONENT       = 'server_component';
	const TAX_COMPONENT_TYPE  = 'server_component_type';

	/**
	 * @var RSC_Post_Types|null
	 */
	private static $instance = null;

	/**
	 * @return RSC_Post_Types
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ), 5 );
		add_action( 'init', array( $this, 'register_post_types' ), 6 );
	}

	public function register_taxonomies() {
		$labels = array(
			'name'              => __( 'انواع قطعه', 'rasam-server-config' ),
			'singular_name'     => __( 'نوع قطعه', 'rasam-server-config' ),
			'search_items'      => __( 'جستجوی انواع', 'rasam-server-config' ),
			'all_items'         => __( 'همه انواع', 'rasam-server-config' ),
			'parent_item'       => __( 'نوع والد', 'rasam-server-config' ),
			'parent_item_colon' => __( 'نوع والد:', 'rasam-server-config' ),
			'edit_item'         => __( 'ویرایش نوع', 'rasam-server-config' ),
			'update_item'       => __( 'به‌روزرسانی نوع', 'rasam-server-config' ),
			'add_new_item'      => __( 'افزودن نوع جدید', 'rasam-server-config' ),
			'new_item_name'     => __( 'نام نوع جدید', 'rasam-server-config' ),
			'menu_name'         => __( 'نوع قطعه', 'rasam-server-config' ),
		);

		register_taxonomy(
			self::TAX_COMPONENT_TYPE,
			array( self::CPT_COMPONENT ),
			array(
				'labels'            => $labels,
				'hierarchical'      => false,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
				'show_in_rest'      => true,
				'rewrite'           => false,
			)
		);
	}

	public function register_post_types() {
		$labels = array(
			'name'               => __( 'قطعات سرور', 'rasam-server-config' ),
			'singular_name'      => __( 'قطعه سرور', 'rasam-server-config' ),
			'menu_name'          => __( 'قطعات سرور', 'rasam-server-config' ),
			'add_new'            => __( 'افزودن قطعه', 'rasam-server-config' ),
			'add_new_item'       => __( 'افزودن قطعه جدید', 'rasam-server-config' ),
			'edit_item'          => __( 'ویرایش قطعه', 'rasam-server-config' ),
			'new_item'           => __( 'قطعه جدید', 'rasam-server-config' ),
			'view_item'          => __( 'مشاهده قطعه', 'rasam-server-config' ),
			'search_items'       => __( 'جستجوی قطعات', 'rasam-server-config' ),
			'not_found'          => __( 'قطعه‌ای یافت نشد', 'rasam-server-config' ),
			'not_found_in_trash' => __( 'در زباله‌دان یافت نشد', 'rasam-server-config' ),
		);

		register_post_type(
			self::CPT_COMPONENT,
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-admin-generic',
				'menu_position'       => 56,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'show_in_rest'        => true,
				'rest_base'           => 'server-components',
			)
		);
	}
}
