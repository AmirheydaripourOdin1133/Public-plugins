<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Icon library using SVG files from assets/icons/ plus optional custom SVG upload.
 */
final class DSSG_Icons {
	/**
	 * Map of icon slugs to SVG filenames.
	 *
	 * @return array
	 */
	public static function icon_files() {
		return array(
			'info'                  => 'Information Circle.svg',
			'settings'              => 'Settings.svg',
			'chevron-down'          => 'Chevron Down.svg',
			'chevron-up'            => 'Chevron Up.svg',
			'copy'                  => 'Copy.svg',
			'delete'                => 'Trash.svg',
			'image'                 => 'Image.svg',
			'folder'                => 'Folder.svg',
			'check'                 => 'Check.svg',
			'warning'               => 'Exclamation Circle.svg',
			'help'                  => 'Question Mark Circle.svg',
			'bookmark'              => 'Bookmark.svg',
			'book'                  => 'Book.svg',
			'clock'                 => 'Clock.svg',
			'arrow-down'            => 'Arrow Down.svg',
			'arrow-up'              => 'Arrow Up.svg',
			'add'                   => 'Plus Square.svg',
			'minus-square'          => 'Minus Square.svg',
			'user'                  => 'User.svg',
			'users'                 => 'Users.svg',
			'tag'                   => 'Tag.svg',
			'document'              => 'File.svg',
			'database'              => 'Database.svg',
			'home'                  => 'Home.svg',
			'lock'                  => 'Lock.svg',
			'unlock'                => 'Unlocked.svg',
			'verified'              => 'Shield Check.svg',
			'tag-check'             => 'Tag Check.svg',
			'calendar-check'        => 'Calendar Check.svg',
			'message-info'          => 'Message Information.svg',
			'message-question-mark' => 'Message Question Mark.svg',
		);
	}

	/**
	 * Load an SVG file and prepare it for inline use (currentColor stroke).
	 *
	 * @param string $slug
	 * @return string SVG markup or fallback info icon.
	 */
	public static function icon( $slug ) {
		$files = self::icon_files();

		if ( isset( $files[ $slug ] ) ) {
			$path = DSSG_DIR . 'assets/icons/' . $files[ $slug ];
			if ( is_readable( $path ) ) {
				$svg = file_get_contents( $path );
				if ( $svg ) {
					// Normalize stroke to currentColor so it inherits the palette color.
					$svg = preg_replace( '/stroke="[^"]*"/', 'stroke="currentColor"', $svg );
					$svg = preg_replace( '/fill="[^"]*"/', 'fill="none"', $svg );
					return $svg;
				}
			}
		}

		// Fallback to info icon.
		return self::load_svg_file( 'Information Circle.svg' );
	}

	/**
	 * Load a raw SVG file by filename.
	 *
	 * @param string $filename
	 * @return string
	 */
	private static function load_svg_file( $filename ) {
		$path = DSSG_DIR . 'assets/icons/' . $filename;
		if ( is_readable( $path ) ) {
			$svg = file_get_contents( $path );
			if ( $svg ) {
				$svg = preg_replace( '/stroke="[^"]*"/', 'stroke="currentColor"', $svg );
				$svg = preg_replace( '/fill="[^"]*"/', 'fill="none"', $svg );
				return $svg;
			}
		}

		return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 21.5C17.2467 21.5 21.5 17.2467 21.5 12C21.5 6.7533 17.2467 2.5 12 2.5C6.7533 2.5 2.5 6.7533 2.5 12C2.5 17.2467 6.7533 21.5 12 21.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.0053 17.2778V10.9445" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.9562 7.44263H11.9651" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/**
	 * Custom uploaded icon URL for an item (stored in post meta keyed by item id).
	 *
	 * @param string $item_id
	 * @return string|null
	 */
	public static function custom_icon_url( $item_id ) {
		$options = DSSG_Settings::options();
		foreach ( $options['tabs'] as $tab ) {
			foreach ( $tab['items'] as $item ) {
				if ( $item['id'] === $item_id && ! empty( $item['custom_icon'] ) ) {
					return esc_url_raw( $item['custom_icon'] );
				}
			}
		}
		return null;
	}

	/**
	 * List available icon slugs for the admin dropdown.
	 *
	 * @return array
	 */
	public static function slugs() {
		return array_keys( self::icon_files() );
	}

	/**
	 * Return icon slugs with rendered SVG markup for admin dropdowns.
	 *
	 * @return array
	 */
	public static function library() {
		$out = array();
		foreach ( self::icon_files() as $slug => $filename ) {
			$out[ $slug ] = self::load_svg_file( $filename );
		}
		return $out;
	}
}