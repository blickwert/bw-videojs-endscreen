<?php
/**
 * Plugin Name: BW Video.js Overlay-player
 * Description: Video.js Player für selbstgehostete MP4 inkl. Endscreen-CTA Overlay + Modal Support.
 * Version: 1.1.0
 * Author: Blickwert Graz
 */

if (!defined('ABSPATH')) exit;

class BW_VideoJS_Endscreen {
  const HANDLE = 'bw-videojs-endscreen';

  public function __construct() {
    add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    add_shortcode('bw_video', [$this, 'shortcode_video']);
    add_shortcode('bw_video_btn', [$this, 'shortcode_btn']);
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
      '0.1.0'
    );

    wp_register_script(
      'bw-videojs-init',
      plugins_url('assets/js/bw-videojs-init.js', __FILE__),
      ['videojs'],
      '0.1.0',
      true
    );
  }

  public function shortcode_btn($atts, $content = '') {
    $atts = shortcode_atts([
      'post_id'       => '',
      'url'           => '',
      'target'        => '',
      'rel'           => '',
      'modal_content' => '',
    ], $atts, 'bw_video_btn');

    $label = trim(wp_strip_all_tags($content));
    if ($label === '') return '';

    $url = '';

    if (!empty($atts['url'])) {
      $url = esc_url_raw($atts['url']);
    } elseif (!empty($atts['post_id']) && ctype_digit((string)$atts['post_id'])) {
      $url = get_permalink((int)$atts['post_id']);
      $url = $url ? esc_url_raw($url) : '';
    }

    $modal_content = trim($atts['modal_content']);
    $modal_encoded = $modal_content ? base64_encode($modal_content) : '';

    if ($url === '' && $modal_encoded === '') return '';

    return sprintf(
      '[[BWBTN url="%s" label="%s" target="%s" rel="%s" modal="%s"]]',
      esc_attr($url),
      esc_attr($label),
      esc_attr($atts['target']),
      esc_attr($atts['rel']),
      esc_attr($modal_encoded)
    );
  }

  private function parse_btn_tokens($content) {
    $buttons = [];

    if (!$content) return $buttons;

    if (preg_match_all('/\[\[BWBTN\s+url="([^"]*)"\s+label="([^"]*)"\s+target="([^"]*)"\s+rel="([^"]*)"\s+modal="([^"]*)"\]\]/', $content, $m, PREG_SET_ORDER)) {
      foreach ($m as $match) {
        $buttons[] = [
          'url'    => esc_url_raw($match[1]),
          'label'  => wp_strip_all_tags($match[2]),
          'target' => sanitize_text_field($match[3]),
          'rel'    => sanitize_text_field($match[4]),
          'modal'  => $match[5] ? base64_decode($match[5]) : '',
        ];
      }
    }

    return $buttons;
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

      'cta_heading' => 'Hat dir das Video gefallen?',
      'cta_text'    => 'Dann schau dir das hier an:',
      'cta_align'   => 'bottom-right',
    ], $atts, 'bw_video');

    if (empty($atts['mp4'])) {
      return '<!-- bw_video: missing mp4 -->';
    }

    wp_enqueue_style('videojs');
    wp_enqueue_style('bw-videojs-css');
    wp_enqueue_script('videojs');
    wp_enqueue_script('bw-videojs-init');

    $id = wp_unique_id('bw-vjs-');

    $mp4    = esc_url($atts['mp4']);
    $poster = esc_url($atts['poster']);
    $w      = esc_attr($atts['width']);
    $h      = esc_attr($atts['height']);

    $controls = $atts['controls'] === '1' ? 'controls' : '';
    $autoplay = $atts['autoplay'] === '1' ? 'autoplay' : '';
    $muted    = $atts['muted'] === '1' ? 'muted' : '';
    $playsinline = $atts['playsinline'] === '1' ? 'playsinline' : '';

    $cta_heading = esc_attr($atts['cta_heading']);
    $cta_text    = esc_attr($atts['cta_text']);
    $cta_align   = esc_attr($atts['cta_align']);

    $style = '';
    if (!empty($w)) $style .= "width:{$w};";
    if (!empty($h)) $style .= "height:{$h};";

    $content_rendered = do_shortcode($content);
    $buttons = $this->parse_btn_tokens($content_rendered);
    $buttons = array_slice($buttons, 0, 2);

    $buttons_json = esc_attr(wp_json_encode($buttons));

    ob_start(); ?>
      <div class="bw-vjs-wrap">
        <video
          id="<?php echo esc_attr($id); ?>"
          class="video-js vjs-default-skin bw-vjs"
          style="<?php echo esc_attr($style); ?>"
          preload="<?php echo esc_attr($atts['preload']); ?>"
          <?php echo $controls; ?>
          <?php echo $autoplay; ?>
          <?php echo $muted; ?>
          <?php echo $playsinline; ?>
          <?php if ($poster) : ?>poster="<?php echo $poster; ?>"<?php endif; ?>

          data-cta-heading="<?php echo $cta_heading; ?>"
          data-cta-text="<?php echo $cta_text; ?>"
          data-cta-align="<?php echo $cta_align; ?>"
          data-cta-buttons="<?php echo $buttons_json; ?>"
        >
          <source src="<?php echo $mp4; ?>" type="video/mp4" />
        </video>
      </div>
    <?php
    return ob_get_clean();
  }
}

new BW_VideoJS_Endscreen();