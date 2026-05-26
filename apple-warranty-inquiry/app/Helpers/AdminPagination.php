<?php
/**
 * WordPress admin list-table style pagination.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdminPagination
 */
class AdminPagination {

	/**
	 * Render tablenav-pages markup identical to WP_List_Table::pagination().
	 *
	 * @param string               $which       'top' or 'bottom'.
	 * @param int                  $total_items Total row count.
	 * @param int                  $total_pages Total pages.
	 * @param int                  $current     Current page (1-based).
	 * @param array<string, mixed> $query_args  Extra query args (e.g. page, s).
	 */
	public static function render( string $which, int $total_items, int $total_pages, int $current, array $query_args = array() ): void {
		$total_items = max( 0, $total_items );
		$total_pages = max( 0, $total_pages );
		$current     = max( 1, min( $current, max( 1, $total_pages ) ) );

		$output = '<span class="displaying-num">' . sprintf(
			/* translators: %s: Number of items. */
			_n( '%s مورد', '%s مورد', $total_items, 'apple-warranty-inquiry' ),
			number_format_i18n( $total_items )
		) . '</span>';

		if ( $total_pages < 2 ) {
			$page_class = ' one-page';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<div class='tablenav-pages{$page_class}'>{$output}</div>";
			return;
		}

		$removable_query_args = wp_removable_query_args();
		$current_url          = remove_query_arg( $removable_query_args, self::current_url( $query_args ) );

		$page_links = array();

		$disable_first = ( 1 === $current );
		$disable_prev  = ( 1 === $current );
		$disable_next  = ( $total_pages === $current );
		$disable_last  = ( $total_pages === $current );

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>&laquo;</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				esc_html__( 'First page' )
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>&lsaquo;</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				esc_html__( 'Previous page' )
			);
		}

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		if ( 'bottom' === $which ) {
			$html_current_page  = (string) $current;
			$total_pages_before = sprintf(
				'<span class="screen-reader-text">%s</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">',
				esc_html__( 'Current Page' )
			);
		} else {
			$html_current_page = sprintf(
				'%s<input class="current-page" id="current-page-selector" type="text" name="paged" value="%s" size="%d" aria-describedby="table-paging" /><span class="tablenav-paging-text">',
				sprintf(
					'<label for="current-page-selector" class="screen-reader-text">%s</label>',
					esc_html__( 'Current Page' )
				),
				$current,
				strlen( (string) $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

		// Use core "paging" string so fa_IR admin matches Posts/Pages list.
		$page_links[] = $total_pages_before . sprintf(
			/* translators: 1: Current page, 2: Total pages. */
			_x( '%1$s of %2$s', 'paging' ),
			$html_current_page,
			$html_total_pages
		) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>&rsaquo;</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				esc_html__( 'Next page' )
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>&raquo;</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				esc_html__( 'Last page' )
			);
		}

		$output .= "\n<span class='pagination-links'>" . implode( "\n", $page_links ) . '</span>';

		$page_class = $total_pages < 2 ? ' one-page' : '';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<div class='tablenav-pages{$page_class}'>{$output}</div>";
	}

	/**
	 * Build current admin URL with plugin query args.
	 *
	 * @param array<string, mixed> $query_args Query arguments.
	 */
	private static function current_url( array $query_args ): string {
		$args = array_merge(
			array( 'page' => 'apple-warranty' ),
			$query_args
		);

		unset( $args['paged'] );

		return add_query_arg( array_filter( $args ), admin_url( 'admin.php' ) );
	}
}
