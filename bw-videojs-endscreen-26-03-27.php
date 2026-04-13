<?php
/**
 * Plugin Name: BW Video.js Overlay-player
 * Description: Video.js Player für selbstgehostete MP4 inkl. klickbarer Hotspot-Bereiche auf dem Poster/Video.
 * Version: 1.2.0
 * Author: Blickwert Graz
 */

if (!defined('ABSPATH')) exit;

class BW_VideoJS_Endscreen {
	const HANDLE = 'bw-videojs-endscreen';

	public function __construct() {
		add_action('wp_enqueue_scripts', [$this, 'register_assets']);
		add_shortcode('bw_video', [$this, 'shortcode_video']);
		add_shortcode('bw_video_area', [$this, 'shortcode_area']);
	}

	public function register_assets() {
		wp_register_style(
			'videojs',
			'https://cdnjs.cloudflare.com/ajax/libs/video.js/8.10.0/video-js.min.css',
			[],
			'8.10.0'
		);

		wp_register_script(
			'videojs',
			'https://cdnjs.cloudflare.com/ajax/libs/video.js/8.10.0/video.min.js',
			[],
			'8.10.0',
			true
		);

		wp_register_style(
			'bw-videojs-css',
			plugins_url('assets/css/bw-videojs.css', __FILE__),
			['videojs'],
			'1.2.0'
		);

		wp_register_script(
			'bw-videojs-init',
			plugins_url('assets/js/bw-videojs-init.js', __FILE__),
			['videojs'],
			'1.2.0',
			true
		);
	}

	/**
	 * Child-Shortcode für aktive Flächen / Hotspots
	 *
	 * Beispiel:
	 * [bw_video_area x="33" y="5" w="34" h="16" post_id="43"][/bw_video_area]
	 * [bw_video_area x="33" y="5" w="34" h="16" modal_content="Mehr Infos"][/bw_video_area]
	 */
	public function shortcode_area($atts, $content = '') {
		$atts = shortcode_atts([
			'x'             => '0',
			'y'             => '0',
			'w'             => '20',
			'h'             => '20',
			'post_id'       => '',
			'url'           => '',
			'target'        => '',
			'rel'           => '',
			'modal_content' => '',
			'label'         => '',
			'class'         => '',
		], $atts, 'bw_video_area');

		$url = '';

		if (!empty($atts['url'])) {
			$url = esc_url_raw($atts['url']);
		} elseif (!empty($atts['post_id']) && ctype_digit((string)$atts['post_id'])) {
			$permalink = get_permalink((int)$atts['post_id']);
			$url = $permalink ? esc_url_raw($permalink) : '';
		}

		$modal_content = trim((string) $atts['modal_content']);
		$modal_encoded = $modal_content !== '' ? base64_encode($modal_content) : '';

		// Wenn weder URL noch Modal vorhanden ist, Area ignorieren
		if ($url === '' && $modal_encoded === '') {
			return '';
		}

		$label = trim((string) $atts['label']);
		if ($label === '' && !empty($content)) {
			$label = trim(wp_strip_all_tags($content));
		}

		$token = sprintf(
			'[[BWAREA x="%s" y="%s" w="%s" h="%s" url="%s" target="%s" rel="%s" modal="%s" label="%s" class="%s"]]',
			esc_attr($atts['x']),
			esc_attr($atts['y']),
			esc_attr($atts['w']),
			esc_attr($atts['h']),
			esc_attr($url),
			esc_attr(sanitize_text_field($atts['target'])),
			esc_attr(sanitize_text_field($atts['rel'])),
			esc_attr($modal_encoded),
			esc_attr($label),
			esc_attr(sanitize_html_class($atts['class']))
		);

		return $token;
	}

	private function parse_area_tokens($content) {
		$areas = [];

		if (!$content) return $areas;

		$pattern = '/\[\[BWAREA\s+x="([^"]*)"\s+y="([^"]*)"\s+w="([^"]*)"\s+h="([^"]*)"\s+url="([^"]*)"\s+target="([^"]*)"\s+rel="([^"]*)"\s+modal="([^"]*)"\s+label="([^"]*)"\s+class="([^"]*)"\]\]/';

		if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$x = is_numeric($match[1]) ? (float) $match[1] : 0;
				$y = is_numeric($match[2]) ? (float) $match[2] : 0;
				$w = is_numeric($match[3]) ? (float) $match[3] : 20;
				$h = is_numeric($match[4]) ? (float) $match[4] : 20;

				$areas[] = [
					'x'      => $x,
					'y'      => $y,
					'w'      => $w,
					'h'      => $h,
					'url'    => esc_url_raw($match[5]),
					'target' => sanitize_text_field($match[6]),
					'rel'    => sanitize_text_field($match[7]),
					'modal'  => $match[8] ? base64_decode($match[8]) : '',
					'label'  => sanitize_text_field($match[9]),
					'class'  => sanitize_html_class($match[10]),
				];
			}
		}

		return $areas;
	}

	public function shortcode_video($atts, $content = '') {
		$atts = shortcode_atts([
			'mp4'         => '',
			'poster'      => '',
			'width'       => '100%',
			'height'      => '',
			'preload'     => 'metadata',
			'controls'    => '1',
			'autoplay'    => '0',
			'muted'       => '0',
			'playsinline' => '1',
			'hotspots_on' => 'pause-ended', // pause | ended | pause-ended
		], $atts, 'bw_video');

		if (empty($atts['mp4'])) {
			return '<!-- bw_video: missing mp4 -->';
		}

		wp_enqueue_style('videojs');
		wp_enqueue_style('bw-videojs-css');
		wp_enqueue_script('videojs');
		wp_enqueue_script('bw-videojs-init');

		$id = wp_unique_id('bw-vjs-');

		$mp4         = esc_url($atts['mp4']);
		$poster      = esc_url($atts['poster']);
		$width       = esc_attr($atts['width']);
		$height      = esc_attr($atts['height']);
		$preload     = esc_attr($atts['preload']);
		$hotspots_on = esc_attr($atts['hotspots_on']);

		$controls    = $atts['controls'] === '1' ? 'controls' : '';
		$autoplay    = $atts['autoplay'] === '1' ? 'autoplay' : '';
		$muted       = $atts['muted'] === '1' ? 'muted' : '';
		$playsinline = $atts['playsinline'] === '1' ? 'playsinline' : '';

		$style = '';
		if ($width !== '') {
			$style .= "width:{$width};";
		}
		if ($height !== '') {
			$style .= "height:{$height};";
		}

		$content_rendered = do_shortcode($content);
		$areas = $this->parse_area_tokens($content_rendered);
		$areas_json = esc_attr(wp_json_encode($areas));

		ob_start();
		?>
		<div class="bw-vjs-wrap">
			<video
				id="<?php echo esc_attr($id); ?>"
				class="video-js vjs-default-skin bw-vjs"
				style="<?php echo esc_attr($style); ?>"
				preload="<?php echo $preload; ?>"
				<?php echo $controls; ?>
				<?php echo $autoplay; ?>
				<?php echo $muted; ?>
				<?php echo $playsinline; ?>
				<?php if ($poster) : ?>poster="<?php echo esc_url($poster); ?>"<?php endif; ?>
				data-hotspots="<?php echo $areas_json; ?>"
				data-hotspots-on="<?php echo $hotspots_on; ?>"
			>
				<source src="<?php echo esc_url($mp4); ?>" type="video/mp4" />
			</video>
		</div>
		<?php
		return ob_get_clean();
	}
}

new BW_VideoJS_Endscreen();