<?php
/**
 * Plugin Name: BW Video.js Hotspot Player
 * Description: Video.js Player mit klickbaren Hotspot-Presets aus Plugin-Dateien.
 * Version: 1.1.0
 * Author: Blickwert Graz
 */

if (!defined('ABSPATH')) exit;

class BW_VideoJS_Hotspot_Player {
	const VERSION = '1.1.0';
	const HANDLE  = 'bw-videojs-hotspot-player';
	const SLUG    = 'bw-videojs-hotspots';

	public function __construct() {
		add_action('wp_enqueue_scripts', [$this, 'register_assets']);
		add_action('admin_menu', [$this, 'register_admin_page']);

		add_shortcode('bw_video', [$this, 'shortcode_video']);
	}

    public function register_assets() {
    
    	$base_dir = plugin_dir_path(__FILE__);
    	$base_url = plugins_url('', __FILE__);
    
    	// === Video.js Paths ===
    	$vjs_js_path  = $base_dir . '/assets/lib/videojs/video.min.js';
    	$vjs_css_path = $base_dir . '/assets/lib/videojs/video-js.min.css';
    
    	// Version dynamisch (Cache Busting)
    	$vjs_js_ver  = file_exists($vjs_js_path)  ? filemtime($vjs_js_path)  : '8.10.0';
    	$vjs_css_ver = file_exists($vjs_css_path) ? filemtime($vjs_css_path) : '8.10.0';
    
    	// === Video.js CSS ===
    	wp_register_style(
    		'videojs',
    		$base_url . '/assets/lib/videojs/video-js.min.css',
    		[],
    		$vjs_css_ver
    	);
    
    	// === Video.js JS ===
    	wp_register_script(
    		'videojs',
    		$base_url . '/assets/lib/videojs/video.min.js',
    		[],
    		$vjs_js_ver,
    		true
    	);
    
    	// === Plugin CSS ===
    	$plugin_css_path = $base_dir . '/assets/css/bw-videojs.css';
    	$plugin_css_ver  = file_exists($plugin_css_path) ? filemtime($plugin_css_path) : self::VERSION;
    
    	wp_register_style(
    		'bw-videojs-css',
    		$base_url . '/assets/css/bw-videojs.css',
    		['videojs'],
    		$plugin_css_ver
    	);
    
    	// === Plugin JS ===
    	$plugin_js_path = $base_dir . '/assets/js/bw-videojs-init.js';
    	$plugin_js_ver  = file_exists($plugin_js_path) ? filemtime($plugin_js_path) : self::VERSION;
    
    	wp_register_script(
    		'bw-videojs-init',
    		$base_url . '/assets/js/bw-videojs-init.js',
    		['videojs'],
    		$plugin_js_ver,
    		true
    	);
    }
    
    
	public function register_admin_page() {
		add_menu_page(
			'BW Video Hotspots',
			'BW Video Hotspots',
			'manage_options',
			self::SLUG,
			[$this, 'render_admin_page'],
			'dashicons-format-video',
			58
		);
	}

	public function render_admin_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

		$index = $this->get_presets_index();
		?>
		<div class="wrap">
			<h1>BW Video.js Hotspot Player</h1>
			<p>Verfügbare Presets für den Shortcode <code>[bw_video id="..."]</code>.</p>

			<?php if (empty($index)) : ?>
				<div class="notice notice-warning inline">
					<p>Keine Presets gefunden. Bitte <code>presets/index.php</code> prüfen.</p>
				</div>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width: 80px;">ID</th>
							<th>Titel</th>
							<th>Datei</th>
							<th>Video</th>
							<th>Poster</th>
							<th>Hotspots</th>
							<th>Shortcode</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($index as $id => $file_name) : ?>
							<?php
							$preset = $this->get_preset($id);
							$title = (!empty($preset['title'])) ? $preset['title'] : '—';
							$mp4   = $this->get_media_reference_for_admin($preset, 'mp4');
							$poster = $this->get_media_reference_for_admin($preset, 'poster');
							$areas_count = (!empty($preset['areas']) && is_array($preset['areas'])) ? count($preset['areas']) : 0;
							?>
							<tr>
								<td><strong><?php echo esc_html($id); ?></strong></td>
								<td><?php echo esc_html($title); ?></td>
								<td><code><?php echo esc_html($file_name); ?></code></td>
								<td><code><?php echo esc_html($mp4); ?></code></td>
								<td><code><?php echo esc_html($poster); ?></code></td>
								<td><?php echo esc_html($areas_count); ?></td>
								<td><code>[bw_video id="<?php echo esc_attr($id); ?>"]</code><br><code>[bw_video id="<?php echo esc_attr($id); ?>" debug="1"]</code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2 style="margin-top: 32px;">Hinweise</h2>
				<ul style="list-style: disc; padding-left: 20px;">
					<li><strong>Normal:</strong> <code>[bw_video id="1"]</code></li>
					<li><strong>Debug:</strong> <code>[bw_video id="1" debug="1"]</code></li>
					<li>Medien können entweder über <code>mp4</code> / <code>poster</code> (plugin-intern) oder über <code>mp4_url</code> / <code>poster_url</code> (extern) definiert werden.</li>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	private function get_presets_index_path() {
		return plugin_dir_path(__FILE__) . 'presets/index.php';
	}

	private function get_presets_index() {
		$file = $this->get_presets_index_path();

		if (!file_exists($file)) {
			return [];
		}

		$data = include $file;
		return is_array($data) ? $data : [];
	}

	private function get_preset_file_by_id($id) {
		$index = $this->get_presets_index();

		if (!isset($index[$id])) {
			return false;
		}

		$filename = basename($index[$id]);
		$path = plugin_dir_path(__FILE__) . 'presets/' . $filename;

		return file_exists($path) ? $path : false;
	}

	private function get_preset($id) {
		$file = $this->get_preset_file_by_id($id);

		if (!$file) {
			return false;
		}

		$data = include $file;
		return is_array($data) ? $data : false;
	}

	private function asset_url_from_relative($relative_path) {
		$relative_path = ltrim((string) $relative_path, '/');
		return plugins_url($relative_path, __FILE__);
	}

	private function get_media_url(array $preset, $type) {
		$type = ($type === 'poster') ? 'poster' : 'mp4';

		$internal_key = $type;
		$external_key = $type . '_url';

		if (!empty($preset[$external_key])) {
			return esc_url_raw($preset[$external_key]);
		}

		if (!empty($preset[$internal_key])) {
			return $this->asset_url_from_relative($preset[$internal_key]);
		}

		return '';
	}

	private function get_media_reference_for_admin($preset, $type) {
		if (!is_array($preset)) {
			return '—';
		}

		$type = ($type === 'poster') ? 'poster' : 'mp4';

		if (!empty($preset[$type . '_url'])) {
			return $preset[$type . '_url'] . ' (extern)';
		}

		if (!empty($preset[$type])) {
			return $preset[$type] . ' (plugin)';
		}

		return '—';
	}

	private function normalize_hotspots(array $areas) {
		$out = [];

		foreach ($areas as $area) {
			if (!is_array($area)) continue;

			$x = isset($area['x']) && is_numeric($area['x']) ? (float) $area['x'] : 0;
			$y = isset($area['y']) && is_numeric($area['y']) ? (float) $area['y'] : 0;
			$w = isset($area['w']) && is_numeric($area['w']) ? (float) $area['w'] : 20;
			$h = isset($area['h']) && is_numeric($area['h']) ? (float) $area['h'] : 20;

			$action = isset($area['action']) ? sanitize_key($area['action']) : '';
			$label  = isset($area['label']) ? sanitize_text_field($area['label']) : '';
			$class  = isset($area['class']) ? trim((string) $area['class']) : '';
			$target = isset($area['target']) ? sanitize_text_field($area['target']) : '';
			$rel    = isset($area['rel']) ? sanitize_text_field($area['rel']) : '';

			$url = '';
			if ($action === 'link' && !empty($area['url'])) {
				$url = esc_url_raw($area['url']);
			}

			$modal_content = '';
			if ($action === 'modal' && !empty($area['modal_content'])) {
				$modal_content = (string) $area['modal_content'];
			}

			if ($action === 'link' && $url === '') continue;
			if ($action === 'modal' && $modal_content === '') continue;
			if ($action !== 'link' && $action !== 'modal') continue;

			$out[] = [
				'x'             => $x,
				'y'             => $y,
				'w'             => $w,
				'h'             => $h,
				'action'        => $action,
				'url'           => $url,
				'target'        => $target,
				'rel'           => $rel,
				'modal_content' => $modal_content,
				'label'         => $label,
				'class'         => $class,
			];
		}

		return $out;
	}

	private function boolish_to_attr($value) {
		return !empty($value) ? '1' : '0';
	}


    private function resolve_media_value($value, $type = 'file') {
    	$value = trim((string) $value);
    
    	if ($value === '') {
    		return '';
    	}
    
    	// Fall 1: Attachment-ID
    	if (ctype_digit($value)) {
    		$attachment_id = (int) $value;
    
    		if ($type === 'image') {
    			$url = wp_get_attachment_image_url($attachment_id, 'full');
    			return $url ? esc_url_raw($url) : '';
    		}
    
    		$url = wp_get_attachment_url($attachment_id);
    		return $url ? esc_url_raw($url) : '';
    	}
    
    	// Fall 2: URL
    	if (filter_var($value, FILTER_VALIDATE_URL)) {
    		return esc_url_raw($value);
    	}
    
    	return '';
    }

	public function shortcode_video($atts, $content = '') {
        $atts = shortcode_atts([
        	'id'     => '',
        	'mp4'    => '',
        	'poster' => '',
        	'debug'  => '',
        	'class'  => '',
        ], $atts, 'bw_video');

		$preset_id = trim((string) $atts['id']);
		if ($preset_id === '') {
			return '<!-- bw_video: missing id -->';
		}

		$preset = $this->get_preset($preset_id);
		if (!$preset) {
			return '<!-- bw_video: preset not found -->';
		}

		$mp4    = $this->get_media_url($preset, 'mp4');
		$poster = $this->get_media_url($preset, 'poster');

        $preset_mp4    = $this->get_media_url($preset, 'mp4');
        $preset_poster = $this->get_media_url($preset, 'poster');
        
        // Shortcode-Werte haben Vorrang
        $shortcode_mp4    = $this->resolve_media_value($atts['mp4'], 'file');
        $shortcode_poster = $this->resolve_media_value($atts['poster'], 'image');
        
        $mp4    = ($shortcode_mp4 !== '') ? $shortcode_mp4 : $preset_mp4;
        $poster = ($shortcode_poster !== '') ? $shortcode_poster : $preset_poster;
        
        if ($mp4 === '') {
        	return '<!-- bw_video: missing mp4 source -->';
        }
        
        $fullscreen_on_play = !empty($preset['fullscreen_on_play']);
		$width       = isset($preset['width']) ? (string) $preset['width'] : '100%';
		$height      = isset($preset['height']) ? (string) $preset['height'] : '';
		$preload     = isset($preset['preload']) ? (string) $preset['preload'] : 'metadata';
		$controls    = !isset($preset['controls']) || (bool) $preset['controls'];
		$autoplay    = !empty($preset['autoplay']);
		$muted       = !empty($preset['muted']);
		$playsinline = !isset($preset['playsinline']) || (bool) $preset['playsinline'];
		$hotspots_on = !empty($preset['hotspots_on']) ? (string) $preset['hotspots_on'] : 'pause-ended';
		$areas       = !empty($preset['areas']) && is_array($preset['areas']) ? $preset['areas'] : [];
		$debug       = $atts['debug'] !== '' ? ($atts['debug'] === '1') : !empty($preset['debug']);
		$wrap_class  = trim((string) $atts['class']);

		$areas = $this->normalize_hotspots($areas);

		wp_enqueue_style('videojs');
		wp_enqueue_style('bw-videojs-css');
		wp_enqueue_script('videojs');
		wp_enqueue_script('bw-videojs-init');

		$id_attr = wp_unique_id('bw-vjs-');

		$style = '';
		if ($width !== '') $style .= 'width:' . esc_attr($width) . ';';
		if ($height !== '') $style .= 'height:' . esc_attr($height) . ';';

		$areas_json = esc_attr(wp_json_encode($areas));

		$wrap_classes = ['bw-vjs-wrap'];
		if ($wrap_class !== '') {
			$wrap_classes[] = sanitize_html_class($wrap_class);
		}
		if ($debug) {
			$wrap_classes[] = 'bw-vjs-wrap--debug';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr(implode(' ', array_filter($wrap_classes))); ?>" data-preset-id="<?php echo esc_attr($preset_id); ?>">
			<video
				id="<?php echo esc_attr($id_attr); ?>"
				class="video-js vjs-default-skin bw-vjs"
				style="<?php echo esc_attr($style); ?>"
				preload="<?php echo esc_attr($preload); ?>"
				<?php echo $controls ? 'controls' : ''; ?>
				<?php echo $autoplay ? 'autoplay' : ''; ?>
				<?php echo $muted ? 'muted' : ''; ?>
				<?php echo $playsinline ? 'playsinline' : ''; ?>
				<?php if ($poster) : ?>poster="<?php echo esc_url($poster); ?>"<?php endif; ?>
				data-hotspots="<?php echo $areas_json; ?>"
				data-hotspots-on="<?php echo esc_attr($hotspots_on); ?>"
				data-debug="<?php echo esc_attr($this->boolish_to_attr($debug)); ?>"
    			data-fullscreen-on-play="<?php echo esc_attr($this->boolish_to_attr($fullscreen_on_play)); ?>"
			>
				<source src="<?php echo esc_url($mp4); ?>" type="video/mp4" />
			</video>
		</div>
		<?php
		return ob_get_clean();
	}
}

new BW_VideoJS_Hotspot_Player();