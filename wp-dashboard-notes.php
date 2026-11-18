<?php
/**
 * Plugin Name:       WP Dashboard Notes
 * Plugin URI:        https://github.com/Azanumer/wp-dashboard-notes
 * Description:       Sticky notes on the WordPress dashboard — shared team notes plus personal notes, colour-coded, with one-click delete.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Azan Umer
 * Author URI:        https://azanumer.cv
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-dashboard-notes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WDN_VERSION', '1.0.0' );
define( 'WDN_SHARED_OPTION', 'wdn_shared_notes' );
define( 'WDN_PERSONAL_META', 'wdn_personal_notes' );
define( 'WDN_MAX_NOTES', 50 );
define( 'WDN_MAX_LENGTH', 500 );

/**
 * Allowed note colours (slug => label).
 *
 * @return string[]
 */
function wdn_colours() {
	return array(
		'yellow' => __( 'Yellow', 'wp-dashboard-notes' ),
		'green'  => __( 'Green', 'wp-dashboard-notes' ),
		'blue'   => __( 'Blue', 'wp-dashboard-notes' ),
		'pink'   => __( 'Pink', 'wp-dashboard-notes' ),
	);
}

/**
 * Get the shared notes (visible to everyone who can see the dashboard).
 *
 * @return array[] Each note: array( id, text, colour, author, time ).
 */
function wdn_get_shared_notes() {
	$notes = get_option( WDN_SHARED_OPTION, array() );
	return is_array( $notes ) ? $notes : array();
}

/**
 * Get the current user's personal notes.
 *
 * @return array[]
 */
function wdn_get_personal_notes() {
	$notes = get_user_meta( get_current_user_id(), WDN_PERSONAL_META, true );
	return is_array( $notes ) ? $notes : array();
}

/**
 * Validate + normalise a note array from storage.
 *
 * @param mixed $note Raw note.
 * @return array|null Clean note, or null when invalid.
 */
function wdn_clean_note( $note ) {
	if ( ! is_array( $note ) || empty( $note['text'] ) ) {
		return null;
	}
	$colours = wdn_colours();
	$colour  = isset( $note['colour'] ) ? sanitize_key( $note['colour'] ) : 'yellow';
	if ( ! isset( $colours[ $colour ] ) ) {
		$colour = 'yellow';
	}
	return array(
		'id'     => isset( $note['id'] ) ? sanitize_key( $note['id'] ) : wp_generate_uuid4(),
		'text'   => sanitize_textarea_field( $note['text'] ),
		'colour' => $colour,
		'author' => isset( $note['author'] ) ? (int) $note['author'] : 0,
		'time'   => isset( $note['time'] ) ? (int) $note['time'] : time(),
	);
}

/**
 * Build a new note from form input.
 *
 * @param string $text   Note text.
 * @param string $colour Colour slug.
 * @return array|null Note array, or null when the text is empty.
 */
function wdn_make_note( $text, $colour ) {
	$text = trim( sanitize_textarea_field( $text ) );
	if ( '' === $text ) {
		return null;
	}
	if ( mb_strlen( $text ) > WDN_MAX_LENGTH ) {
		$text = mb_substr( $text, 0, WDN_MAX_LENGTH );
	}
	$note = wdn_clean_note(
		array(
			'text'   => $text,
			'colour' => $colour,
			'author' => get_current_user_id(),
			'time'   => time(),
		)
	);
	return $note;
}

/* -------------------------------------------------------------------------
 * Dashboard widget
 * ------------------------------------------------------------------------- */

/**
 * Register the dashboard widget.
 */
function wdn_register_widget() {
	wp_add_dashboard_widget(
		'wdn_notes',
		__( 'Notes', 'wp-dashboard-notes' ),
		'wdn_render_widget'
	);
}
add_action( 'wp_dashboard_setup', 'wdn_register_widget' );

/**
 * Render the widget: add-note form + shared notes + personal notes.
 */
function wdn_render_widget() {
	$shared   = array_map( 'wdn_clean_note', wdn_get_shared_notes() );
	$shared   = array_values( array_filter( $shared ) );
	$personal = array_map( 'wdn_clean_note', wdn_get_personal_notes() );
	$personal = array_values( array_filter( $personal ) );
	$colours  = wdn_colours();
	$can_edit_shared = current_user_can( 'edit_posts' );
	?>
	<div class="wdn-widget">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wdn-add-form">
			<?php wp_nonce_field( 'wdn_add_note' ); ?>
			<input type="hidden" name="action" value="wdn_add_note">
			<p>
				<label for="wdn-note-text"><?php esc_html_e( 'New note', 'wp-dashboard-notes' ); ?></label><br>
				<textarea id="wdn-note-text" name="wdn_text" rows="2" maxlength="<?php echo (int) WDN_MAX_LENGTH; ?>" class="widefat" required></textarea>
			</p>
			<p>
				<label for="wdn-note-colour"><?php esc_html_e( 'Colour', 'wp-dashboard-notes' ); ?></label>
				<select id="wdn-note-colour" name="wdn_colour">
					<?php foreach ( $colours as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<label>
					<input type="checkbox" name="wdn_scope" value="shared" <?php echo $can_edit_shared ? 'checked' : 'disabled'; ?>>
					<?php esc_html_e( 'Shared with the team', 'wp-dashboard-notes' ); ?>
				</label>
				<?php if ( ! $can_edit_shared ) : ?>
					<input type="hidden" name="wdn_scope" value="personal">
				<?php endif; ?>
			</p>
			<?php submit_button( __( 'Add note', 'wp-dashboard-notes' ), 'secondary', 'submit', false ); ?>
		</form>

		<?php if ( ! empty( $shared ) ) : ?>
			<h4><?php esc_html_e( 'Team notes', 'wp-dashboard-notes' ); ?></h4>
			<?php wdn_render_note_list( $shared, 'shared' ); ?>
		<?php endif; ?>

		<?php if ( ! empty( $personal ) ) : ?>
			<h4><?php esc_html_e( 'My notes', 'wp-dashboard-notes' ); ?></h4>
			<?php wdn_render_note_list( $personal, 'personal' ); ?>
		<?php endif; ?>

		<?php if ( empty( $shared ) && empty( $personal ) ) : ?>
			<p><?php esc_html_e( 'No notes yet. Add one above — shared notes are visible to the whole team, personal notes only to you.', 'wp-dashboard-notes' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render one list of notes with delete buttons.
 *
 * @param array[] $notes Note arrays.
 * @param string  $scope 'shared' or 'personal'.
 */
function wdn_render_note_list( $notes, $scope ) {
	$can_delete_shared = current_user_can( 'edit_posts' );
	?>
	<ul class="wdn-notes">
		<?php foreach ( $notes as $note ) : ?>
			<li class="wdn-note wdn-<?php echo esc_attr( $note['colour'] ); ?>">
				<span class="wdn-text"><?php echo esc_html( $note['text'] ); ?></span>
				<span class="wdn-meta">
					<?php
					if ( $note['author'] > 0 ) {
						$author = get_userdata( $note['author'] );
						echo $author ? esc_html( $author->display_name ) : esc_html__( 'Unknown', 'wp-dashboard-notes' );
					}
					echo ' &middot; ' . esc_html( date_i18n( get_option( 'date_format' ), $note['time'] ) );
					?>
				</span>
				<?php if ( 'personal' === $scope || $can_delete_shared ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wdn-delete-form">
						<?php wp_nonce_field( 'wdn_delete_note' ); ?>
						<input type="hidden" name="action" value="wdn_delete_note">
						<input type="hidden" name="wdn_id" value="<?php echo esc_attr( $note['id'] ); ?>">
						<input type="hidden" name="wdn_scope" value="<?php echo esc_attr( $scope ); ?>">
						<button type="submit" class="wdn-delete" aria-label="<?php esc_attr_e( 'Delete note', 'wp-dashboard-notes' ); ?>">&times;</button>
					</form>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/* -------------------------------------------------------------------------
 * Form handlers
 * ------------------------------------------------------------------------- */

/**
 * Handle adding a note (admin-post.php action).
 */
function wdn_handle_add_note() {
	if ( ! is_user_logged_in() ) {
		wp_die( esc_html__( 'You must be logged in.', 'wp-dashboard-notes' ) );
	}
	check_admin_referer( 'wdn_add_note' );

	$scope = isset( $_POST['wdn_scope'] ) ? sanitize_key( $_POST['wdn_scope'] ) : 'personal';
	if ( 'shared' === $scope && ! current_user_can( 'edit_posts' ) ) {
		$scope = 'personal'; // Users without edit_posts can only add personal notes.
	}

	$note = wdn_make_note(
		isset( $_POST['wdn_text'] ) ? wp_unslash( $_POST['wdn_text'] ) : '',
		isset( $_POST['wdn_colour'] ) ? sanitize_key( $_POST['wdn_colour'] ) : 'yellow'
	);

	if ( null !== $note ) {
		if ( 'shared' === $scope ) {
			$notes = wdn_get_shared_notes();
			array_unshift( $notes, $note );
			update_option( WDN_SHARED_OPTION, array_slice( $notes, 0, WDN_MAX_NOTES ) );
		} else {
			$notes = wdn_get_personal_notes();
			array_unshift( $notes, $note );
			update_user_meta( get_current_user_id(), WDN_PERSONAL_META, array_slice( $notes, 0, WDN_MAX_NOTES ) );
		}
	}

	wp_safe_redirect( admin_url() );
	exit;
}
add_action( 'admin_post_wdn_add_note', 'wdn_handle_add_note' );

/**
 * Handle deleting a note (admin-post.php action).
 */
function wdn_handle_delete_note() {
	if ( ! is_user_logged_in() ) {
		wp_die( esc_html__( 'You must be logged in.', 'wp-dashboard-notes' ) );
	}
	check_admin_referer( 'wdn_delete_note' );

	$id    = isset( $_POST['wdn_id'] ) ? sanitize_key( $_POST['wdn_id'] ) : '';
	$scope = isset( $_POST['wdn_scope'] ) ? sanitize_key( $_POST['wdn_scope'] ) : '';

	if ( '' !== $id ) {
		if ( 'shared' === $scope && current_user_can( 'edit_posts' ) ) {
			$notes = array_values(
				array_filter(
					wdn_get_shared_notes(),
					function ( $note ) use ( $id ) {
						return ! ( is_array( $note ) && isset( $note['id'] ) && $note['id'] === $id );
					}
				)
			);
			update_option( WDN_SHARED_OPTION, $notes );
		} elseif ( 'personal' === $scope ) {
			$notes = array_values(
				array_filter(
					wdn_get_personal_notes(),
					function ( $note ) use ( $id ) {
						return ! ( is_array( $note ) && isset( $note['id'] ) && $note['id'] === $id );
					}
				)
			);
			update_user_meta( get_current_user_id(), WDN_PERSONAL_META, $notes );
		}
	}

	wp_safe_redirect( admin_url() );
	exit;
}
add_action( 'admin_post_wdn_delete_note', 'wdn_handle_delete_note' );

/* -------------------------------------------------------------------------
 * Widget styling
 * ------------------------------------------------------------------------- */

/**
 * Enqueue the widget CSS on the dashboard.
 *
 * @param string $hook Current admin page hook.
 */
function wdn_enqueue_styles( $hook ) {
	if ( 'index.php' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'wdn-widget',
		plugins_url( 'assets/css/wdn-widget.css', __FILE__ ),
		array(),
		WDN_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'wdn_enqueue_styles' );
