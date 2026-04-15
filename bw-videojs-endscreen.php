<?php
/**
 * Plugin Name: BW Video.js Hotspot Player
 * Description: Video.js Player mit klickbaren Hotspots. Verwaltung via Custom Post Type.
 * Version: 2.0.0
 * Author: Blickwert Graz
 */

if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook( __FILE__, [ 'BW_VideoJS_Hotspot_Player', 'on_activate' ] );

class BW_VideoJS_Hotspot_Player {

	const VERSION = '2.0.0';
	const CPT     = 'bw_video';

	public function __construct() {
		add_action( 'init',                         [ $this, 'register_cpt' ] );
		add_action( 'wp_enqueue_scripts',           [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts',        [ $this, 'admin_assets' ] );
		add_action( 'add_meta_boxes',               [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . self::CPT,       [ $this, 'save_meta' ] );
		add_shortcode( 'bw_video',                  [ $this, 'shortcode_video' ] );
		add_filter( 'manage_' . self::CPT . '_posts_columns', [ $this, 'add_shortcode_column' ] );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', [ $this, 'render_shortcode_column' ], 10, 2 );
	}

	public static function on_activate() {
		$instance = new self();
		$instance->register_cpt();
		flush_rewrite_rules();
		$instance->seed_from_presets();
	}

	private function seed_from_presets() {
		if ( get_option( 'bw_video_cpt_seeded_v1' ) ) return;

		$index_file = plugin_dir_path( __FILE__ ) . 'presets/index.php';
		if ( ! file_exists( $index_file ) ) {
			update_option( 'bw_video_cpt_seeded_v1', true );
			return;
		}

		$index = include $index_file;
		if ( ! is_array( $index ) ) {
			update_option( 'bw_video_cpt_seeded_v1', true );
			return;
		}

		foreach ( $index as $id => $filename ) {
			$file = plugin_dir_path( __FILE__ ) . 'presets/' . basename( $filename );
			if ( ! file_exists( $file ) ) continue;

			$preset = include $file;
			if ( ! is_array( $preset ) ) continue;

			$post_id = wp_insert_post( [
				'post_type'   => self::CPT,
				'post_title'  => $preset['title'] ?? ( 'Video ' . $id ),
				'post_status' => 'publish',
			] );

			if ( is_wp_error( $post_id ) || ! $post_id ) continue;

			update_post_meta( $post_id, '_bw_mp4',    $preset['mp4_url']    ?? $preset['mp4']    ?? '' );
			update_post_meta( $post_id, '_bw_poster', $preset['poster_url'] ?? $preset['poster'] ?? '' );
			update_post_meta( $post_id, '_bw_hotspots_on', $preset['hotspots_on'] ?? 'ended' );
			update_post_meta( $post_id, '_bw_autoplay',           ! empty( $preset['autoplay'] )                                  ? '1' : '0' );
			update_post_meta( $post_id, '_bw_muted',              ! empty( $preset['muted'] )                                     ? '1' : '0' );
			update_post_meta( $post_id, '_bw_controls',           ( ! isset( $preset['controls'] )    || $preset['controls'] )    ? '1' : '0' );
			update_post_meta( $post_id, '_bw_playsinline',        ( ! isset( $preset['playsinline'] ) || $preset['playsinline'] ) ? '1' : '0' );
			update_post_meta( $post_id, '_bw_fullscreen_on_play', ! empty( $preset['fullscreen_on_play'] )                        ? '1' : '0' );

			$hotspots = [];
			foreach ( $preset['areas'] ?? [] as $area ) {
				$action = $area['action'] ?? '';
				if ( $action === 'modal' ) {
					$hotspots[] = [
						'action'  => 'modal',
						'label'   => $area['label']         ?? '',
						'x'       => (float) ( $area['x']  ?? 0 ),
						'y'       => (float) ( $area['y']  ?? 0 ),
						'content' => $area['modal_content'] ?? '',
						'url'     => '',
					];
				} elseif ( $action === 'link' ) {
					$hotspots[] = [
						'action'  => 'link',
						'label'   => $area['label']        ?? '',
						'x'       => (float) ( $area['x'] ?? 0 ),
						'y'       => (float) ( $area['y'] ?? 0 ),
						'content' => '',
						'url'     => $area['url'] ?? '',
					];
				}
			}
			update_post_meta( $post_id, '_bw_hotspots', wp_json_encode( $hotspots ) );
		}

		update_option( 'bw_video_cpt_seeded_v1', true );
	}

	public function register_cpt() {
		register_post_type( self::CPT, [
			'labels' => [
				'name'          => 'BW Videos',
				'singular_name' => 'BW Video',
				'add_new_item'  => 'Neues Video anlegen',
				'edit_item'     => 'Video bearbeiten',
				'new_item'      => 'Neues Video',
				'not_found'     => 'Keine Videos gefunden',
				'all_items'     => 'Alle Videos',
			],
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'menu_icon'     => 'dashicons-format-video',
			'menu_position' => 58,
			'supports'      => [ 'title' ],
		] );
	}

	public function add_meta_boxes() {
		add_meta_box( 'bw_video_settings', 'Video-Einstellungen', [ $this, 'render_settings_box' ], self::CPT, 'normal', 'high' );
		add_meta_box( 'bw_video_hotspots', 'Hotspots',            [ $this, 'render_hotspots_box' ], self::CPT, 'normal', 'default' );
	}

	public function add_shortcode_column( $columns ) {
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( $key === 'title' ) {
				$new_columns['bw_shortcode'] = 'Shortcode';
			}
		}
		if ( ! isset( $new_columns['bw_shortcode'] ) ) {
			$new_columns['bw_shortcode'] = 'Shortcode';
		}
		return $new_columns;
	}

	public function render_shortcode_column( $column, $post_id ) {
		if ( $column !== 'bw_shortcode' ) return;
		$shortcode = sprintf( '[bw_video id="%d"]', (int) $post_id );
		echo '<code>' . esc_html( $shortcode ) . '</code>';
	}

	public function render_settings_box( $post ) {
		wp_nonce_field( 'bw_video_meta_save', 'bw_video_nonce' );

		$mp4         = get_post_meta( $post->ID, '_bw_mp4',               true );
		$poster      = get_post_meta( $post->ID, '_bw_poster',            true );
		$hotspots_on = get_post_meta( $post->ID, '_bw_hotspots_on',       true ) ?: 'ended';
		$autoplay    = get_post_meta( $post->ID, '_bw_autoplay',          true );
		$muted       = get_post_meta( $post->ID, '_bw_muted',             true );
		$controls    = get_post_meta( $post->ID, '_bw_controls',          true );
		$playsinline = get_post_meta( $post->ID, '_bw_playsinline',       true );
		$fullscreen  = get_post_meta( $post->ID, '_bw_fullscreen_on_play', true );

		if ( get_post_status( $post->ID ) === 'auto-draft' ) {
			$controls    = '1';
			$playsinline = '1';
		}
		?>
		<table class="form-table" style="width:100%;">
			<tr>
				<th style="width:220px;"><label for="bw_mp4">MP4 <small>(URL oder Attachment-ID)</small></label></th>
				<td>
					<div class="bw-media-field">
						<input type="text" id="bw_mp4" name="bw_mp4" value="<?php echo esc_attr( $mp4 ); ?>" class="large-text" />
						<button type="button" class="button bw-media-select" data-target="#bw_mp4" data-media-type="video">Medienauswahl</button>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="bw_poster">Poster <small>(URL oder Attachment-ID)</small></label></th>
				<td>
					<div class="bw-media-field">
						<input type="text" id="bw_poster" name="bw_poster" value="<?php echo esc_attr( $poster ); ?>" class="large-text" />
						<button type="button" class="button bw-media-select" data-target="#bw_poster" data-media-type="image">Medienauswahl</button>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="bw_hotspots_on">Hotspots anzeigen bei</label></th>
				<td>
					<select id="bw_hotspots_on" name="bw_hotspots_on">
						<?php foreach ( [ 'ended' => 'Video-Ende', 'pause' => 'Pause', 'pause-ended' => 'Pause &amp; Ende' ] as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $hotspots_on, $val ); ?>><?php echo $lbl; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th>Optionen</th>
				<td style="display:flex;flex-wrap:wrap;gap:12px 24px;padding-top:6px;">
					<label><input type="checkbox" name="bw_controls"           value="1" <?php checked( $controls,    '1' ); ?>> Controls</label>
					<label><input type="checkbox" name="bw_autoplay"           value="1" <?php checked( $autoplay,    '1' ); ?>> Autoplay</label>
					<label><input type="checkbox" name="bw_muted"              value="1" <?php checked( $muted,       '1' ); ?>> Stumm</label>
					<label><input type="checkbox" name="bw_playsinline"        value="1" <?php checked( $playsinline, '1' ); ?>> Playsinline</label>
					<label><input type="checkbox" name="bw_fullscreen_on_play" value="1" <?php checked( $fullscreen,  '1' ); ?>> Fullscreen beim Start</label>
				</td>
			</tr>
		</table>
		<?php
	}

	public function render_hotspots_box( $post ) {
		$raw      = get_post_meta( $post->ID, '_bw_hotspots', true );
		$hotspots = $raw ? json_decode( $raw, true ) : [];
		if ( ! is_array( $hotspots ) ) $hotspots = [];
		?>
		<div id="bw-hotspots-list">
			<?php foreach ( $hotspots as $i => $hs ) : ?>
				<?php $this->render_hotspot_row( $i, $hs ); ?>
			<?php endforeach; ?>
		</div>
		<p style="margin-top:10px;">
			<button type="button" id="bw-add-hotspot" class="button button-secondary">+ Hotspot hinzufügen</button>
		</p>
		<p style="color:#666;font-style:italic;font-size:12px;margin-top:4px;">
			X = horizontale Position (links→rechts), Y = vertikale Position (oben→unten), jeweils in Prozent.
		</p>
		<script type="text/html" id="bw-hotspot-template">
			<?php $this->render_hotspot_row( '__IDX__', [] ); ?>
		</script>
		<?php
	}

	private function render_hotspot_row( $idx, array $hs ) {
		$action   = $hs['action']  ?? 'modal';
		$label    = $this->decode_unicode_escapes( (string) ( $hs['label'] ?? '' ) );
		$x        = isset( $hs['x'] ) ? $hs['x'] : '';
		$y        = isset( $hs['y'] ) ? $hs['y'] : '';
		$content  = $this->decode_unicode_escapes( (string) ( $hs['content'] ?? '' ) );
		$url      = $hs['url']     ?? '';
		$is_modal = ( $action !== 'link' && $action !== 'iframe' );
		?>
		<div class="bw-hotspot-row">
			<div class="bw-hotspot-row__header">
				<span class="dashicons dashicons-move bw-drag-handle" title="Ziehen zum Sortieren"></span>
				<input type="text" name="bw_hotspots[<?php echo $idx; ?>][label]" value="<?php echo esc_attr( $label ); ?>" placeholder="Label / Überschrift" class="bw-hs-label" />
				<select name="bw_hotspots[<?php echo $idx; ?>][action]" class="bw-action-select">
					<option value="modal"  <?php selected( $action, 'modal' );  ?>>Modal</option>
					<option value="link"   <?php selected( $action, 'link' );   ?>>Link</option>
					<option value="iframe" <?php selected( $action, 'iframe' ); ?>>iFrame</option>
				</select>
				<label class="bw-pos-label">X&nbsp;%
					<input type="number" name="bw_hotspots[<?php echo $idx; ?>][x]" value="<?php echo esc_attr( $x ); ?>" placeholder="0" min="0" max="100" step="0.1" class="small-text" />
				</label>
				<label class="bw-pos-label">Y&nbsp;%
					<input type="number" name="bw_hotspots[<?php echo $idx; ?>][y]" value="<?php echo esc_attr( $y ); ?>" placeholder="0" min="0" max="100" step="0.1" class="small-text" />
				</label>
				<button type="button" class="button bw-remove-hotspot">&#x2715; Entfernen</button>
			</div>
			<div class="bw-field-content"<?php echo $is_modal ? '' : ' style="display:none"'; ?>>
				<textarea name="bw_hotspots[<?php echo $idx; ?>][content]" rows="4" placeholder="Inhalt (HTML erlaubt, z.B. <p>Text</p>)"><?php echo esc_textarea( $content ); ?></textarea>
			</div>
			<div class="bw-field-url"<?php echo $is_modal ? ' style="display:none"' : ''; ?>>
				<input type="url" name="bw_hotspots[<?php echo $idx; ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="https://" />
			</div>
		</div>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['bw_video_nonce'] ) ) return;
		if ( ! wp_verify_nonce( $_POST['bw_video_nonce'], 'bw_video_meta_save' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		foreach ( [ 'bw_mp4', 'bw_poster' ] as $key ) {
			update_post_meta( $post_id, '_' . $key, sanitize_text_field( $_POST[ $key ] ?? '' ) );
		}

		$allowed_modes = [ 'ended', 'pause', 'pause-ended' ];
		$mode = $_POST['bw_hotspots_on'] ?? 'ended';
		update_post_meta( $post_id, '_bw_hotspots_on', in_array( $mode, $allowed_modes, true ) ? $mode : 'ended' );

		foreach ( [ 'bw_autoplay', 'bw_muted', 'bw_controls', 'bw_playsinline', 'bw_fullscreen_on_play' ] as $key ) {
			update_post_meta( $post_id, '_' . $key, ! empty( $_POST[ $key ] ) ? '1' : '0' );
		}

		$hotspots = [];
		$raw = $_POST['bw_hotspots'] ?? [];
		if ( is_array( $raw ) ) {
			foreach ( $raw as $hs ) {
				if ( ! is_array( $hs ) ) continue;
				$action  = sanitize_key( $hs['action'] ?? '' );
				if ( ! in_array( $action, [ 'modal', 'link', 'iframe' ], true ) ) continue;
				$label_raw   = $this->decode_unicode_escapes( wp_unslash( (string) ( $hs['label'] ?? '' ) ) );
				$content_raw = $this->decode_unicode_escapes( wp_unslash( (string) ( $hs['content'] ?? '' ) ) );
				$url_raw     = wp_unslash( (string) ( $hs['url'] ?? '' ) );
				$label   = sanitize_text_field( $label_raw );
				$x       = min( 100.0, max( 0.0, (float) ( $hs['x'] ?? 0 ) ) );
				$y       = min( 100.0, max( 0.0, (float) ( $hs['y'] ?? 0 ) ) );
				$content = ( $action === 'modal' ) ? wp_kses_post( $content_raw ) : '';
				$url     = in_array( $action, [ 'link', 'iframe' ], true ) ? esc_url_raw( $url_raw ) : '';
				if ( $action === 'modal'  && $content === '' ) continue;
				if ( $action === 'link'   && $url     === '' ) continue;
				if ( $action === 'iframe' && $url     === '' ) continue;
				$hotspots[] = compact( 'action', 'label', 'x', 'y', 'content', 'url' );
			}
		}
		update_post_meta( $post_id, '_bw_hotspots', wp_json_encode( $hotspots ) );
	}

	private function decode_unicode_escapes( $value ) {
		$value = (string) $value;
		if ( strpos( $value, '\\u' ) === false && strpos( $value, 'u00' ) === false && strpos( $value, 'u01' ) === false ) return $value;
		return preg_replace_callback(
			'/(?:\\\\u|(?<![0-9A-Fa-f])u)([0-9a-fA-F]{4})/',
			static function ( $matches ) {
				$codepoint = hexdec( $matches[1] );
				if ( function_exists( 'mb_chr' ) ) return mb_chr( $codepoint, 'UTF-8' );
				return html_entity_decode( '&#x' . $matches[1] . ';', ENT_QUOTES, 'UTF-8' );
			},
			$value
		);
	}

	public function register_assets() {
		$base_dir = plugin_dir_path( __FILE__ );
		$base_url = plugins_url( '', __FILE__ );
		$vjs_js_ver  = file_exists( $base_dir . '/assets/lib/videojs/video.min.js' )     ? filemtime( $base_dir . '/assets/lib/videojs/video.min.js' )     : '8.10.0';
		$vjs_css_ver = file_exists( $base_dir . '/assets/lib/videojs/video-js.min.css' ) ? filemtime( $base_dir . '/assets/lib/videojs/video-js.min.css' ) : '8.10.0';
		wp_register_style(  'videojs',          $base_url . '/assets/lib/videojs/video-js.min.css', [], $vjs_css_ver );
		wp_register_script( 'videojs',          $base_url . '/assets/lib/videojs/video.min.js',     [], $vjs_js_ver, true );
		$css_ver = file_exists( $base_dir . '/assets/css/bw-videojs.css' )    ? filemtime( $base_dir . '/assets/css/bw-videojs.css' )    : self::VERSION;
		$js_ver  = file_exists( $base_dir . '/assets/js/bw-videojs-init.js' ) ? filemtime( $base_dir . '/assets/js/bw-videojs-init.js' ) : self::VERSION;
		wp_register_style(  'bw-videojs-css',  $base_url . '/assets/css/bw-videojs.css',     [ 'videojs' ], $css_ver );
		wp_register_script( 'bw-videojs-init', $base_url . '/assets/js/bw-videojs-init.js', [ 'videojs' ], $js_ver, true );
	}

	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== self::CPT ) return;
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
		$base_dir = plugin_dir_path( __FILE__ );
		$base_url = plugins_url( '', __FILE__ );
		$ver = file_exists( $base_dir . '/assets/js/bw-admin-hotspots.js' ) ? filemtime( $base_dir . '/assets/js/bw-admin-hotspots.js' ) : self::VERSION;
		wp_enqueue_media();
		wp_enqueue_script( 'bw-admin-hotspots', $base_url . '/assets/js/bw-admin-hotspots.js', [ 'jquery' ], $ver, true );
		wp_add_inline_style( 'wp-admin', $this->admin_inline_css() );
	}

	private function admin_inline_css() {
		return '
			#bw-hotspots-list .bw-hotspot-row { border:1px solid #ccd0d4; border-radius:3px; padding:12px; margin-bottom:8px; background:#fff; }
			.bw-hotspot-row__header { display:flex; align-items:center; flex-wrap:wrap; gap:6px 8px; margin-bottom:8px; }
			.bw-hotspot-row__header .bw-hs-label { flex:1; min-width:160px; }
			.bw-hotspot-row__header .small-text { width:65px !important; }
			.bw-pos-label { display:inline-flex; align-items:center; gap:4px; white-space:nowrap; }
			.bw-drag-handle { cursor:move; color:#aaa; flex-shrink:0; }
			.bw-remove-hotspot { margin-left:auto !important; flex-shrink:0; }
			.bw-field-content textarea, .bw-field-url input[type="url"] { width:100%; box-sizing:border-box; }
			.bw-media-field { display:flex; align-items:center; gap:8px; }
			.bw-media-field .large-text { flex:1; min-width:260px; }
		';
	}

	public function shortcode_video( $atts ) {
		$atts = shortcode_atts( [ 'id' => '', 'mp4' => '', 'poster' => '', 'debug' => '', 'class' => '' ], $atts, 'bw_video' );
		$post_id = (int) $atts['id'];
		if ( ! $post_id ) return '<!-- bw_video: missing id -->';
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== self::CPT || $post->post_status !== 'publish' ) return '<!-- bw_video: post not found -->';

		$mp4    = $this->resolve_media( $atts['mp4'],    'file'  ) ?: $this->resolve_media( get_post_meta( $post_id, '_bw_mp4',    true ), 'file'  );
		$poster = $this->resolve_media( $atts['poster'], 'image' ) ?: $this->resolve_media( get_post_meta( $post_id, '_bw_poster', true ), 'image' );
		if ( $mp4 === '' ) return '<!-- bw_video: missing mp4 source -->';

		$hotspots_on        = get_post_meta( $post_id, '_bw_hotspots_on',        true ) ?: 'ended';
		$controls           = get_post_meta( $post_id, '_bw_controls',           true ) !== '0';
		$autoplay           = get_post_meta( $post_id, '_bw_autoplay',           true ) === '1';
		$muted              = get_post_meta( $post_id, '_bw_muted',              true ) === '1';
		$playsinline        = get_post_meta( $post_id, '_bw_playsinline',        true ) !== '0';
		$fullscreen_on_play = get_post_meta( $post_id, '_bw_fullscreen_on_play', true ) === '1';
		$debug              = $atts['debug'] === '1';

		$raw_hotspots = get_post_meta( $post_id, '_bw_hotspots', true );
		$hotspots     = $raw_hotspots ? json_decode( $raw_hotspots, true ) : [];
		$hotspots     = is_array( $hotspots ) ? $this->normalize_hotspots( $hotspots ) : [];

		wp_enqueue_style( 'videojs' ); wp_enqueue_style( 'bw-videojs-css' );
		wp_enqueue_script( 'videojs' ); wp_enqueue_script( 'bw-videojs-init' );

		$id_attr      = wp_unique_id( 'bw-vjs-' );
		$areas_json   = esc_attr( wp_json_encode( $hotspots ) );
		$wrap_classes = [ 'bw-vjs-wrap' ];
		$extra_class  = trim( (string) $atts['class'] );
		if ( $extra_class !== '' ) $wrap_classes[] = sanitize_html_class( $extra_class );
		if ( $debug )              $wrap_classes[] = 'bw-vjs-wrap--debug';

		ob_start(); ?>
		<div class="<?php echo esc_attr( implode( ' ', array_filter( $wrap_classes ) ) ); ?>" data-video-id="<?php echo esc_attr( $post_id ); ?>">
			<video id="<?php echo esc_attr( $id_attr ); ?>" class="video-js vjs-default-skin bw-vjs" style="width:100%;" preload="metadata"
				<?php echo $controls ? 'controls' : ''; ?> <?php echo $autoplay ? 'autoplay' : ''; ?> <?php echo $muted ? 'muted' : ''; ?> <?php echo $playsinline ? 'playsinline' : ''; ?>
				<?php if ( $poster ) : ?>poster="<?php echo esc_url( $poster ); ?>"<?php endif; ?>
				data-hotspots="<?php echo $areas_json; ?>"
				data-hotspots-on="<?php echo esc_attr( $hotspots_on ); ?>"
				data-debug="<?php echo $debug ? '1' : '0'; ?>"
				data-fullscreen-on-play="<?php echo $fullscreen_on_play ? '1' : '0'; ?>">
				<source src="<?php echo esc_url( $mp4 ); ?>" type="video/mp4" />
			</video>
		</div>
		<?php return ob_get_clean();
	}

	private function normalize_hotspots( array $areas ) {
		$out = [];
		foreach ( $areas as $area ) {
			if ( ! is_array( $area ) ) continue;
			$action = $area['action'] ?? '';
			$label  = $area['label']  ?? '';
			$x      = (float) ( $area['x'] ?? 0 );
			$y      = (float) ( $area['y'] ?? 0 );
			if ( $action === 'modal' ) {
				$content = trim( (string) ( $area['content'] ?? '' ) );
				if ( $content === '' ) continue;
				$out[] = [ 'action' => 'modal', 'modal_content' => $content, 'label' => $label, 'x' => $x, 'y' => $y, 'w' => 25, 'h' => 12 ];
			} elseif ( $action === 'link' ) {
				$url = trim( (string) ( $area['url'] ?? '' ) );
				if ( $url === '' ) continue;
				$out[] = [ 'action' => 'link', 'url' => $url, 'label' => $label, 'x' => $x, 'y' => $y, 'w' => 25, 'h' => 12 ];
			} elseif ( $action === 'iframe' ) {
				$url = trim( (string) ( $area['url'] ?? '' ) );
				if ( $url === '' ) continue;
				$out[] = [ 'action' => 'modal', 'modal_content' => '<iframe src="' . esc_url( $url ) . '" width="100%" height="500" frameborder="0" allowfullscreen></iframe>', 'label' => $label, 'x' => $x, 'y' => $y, 'w' => 25, 'h' => 12 ];
			}
		}
		return $out;
	}

	private function resolve_media( $value, $type = 'file' ) {
		$value = trim( (string) $value );
		if ( $value === '' ) return '';
		if ( ctype_digit( $value ) ) {
			$id = (int) $value;
			if ( $type === 'image' ) { $url = wp_get_attachment_image_url( $id, 'full' ); return $url ? esc_url_raw( $url ) : ''; }
			$url = wp_get_attachment_url( $id );
			return $url ? esc_url_raw( $url ) : '';
		}
		if ( filter_var( $value, FILTER_VALIDATE_URL ) ) return esc_url_raw( $value );
		return '';
	}
}

new BW_VideoJS_Hotspot_Player();
