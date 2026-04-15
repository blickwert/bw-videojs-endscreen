<?php

return [
	'title'        => 'Preset 1',
	'mp4'          => 'assets/media/placeholder.mp4',
	'poster'       => 'assets/media/placeholder.png',
	// Alternativ extern:
	// 'mp4_url'    => 'https://cdn.example.com/video-1.mp4',
	// 'poster_url' => 'https://cdn.example.com/poster-1.jpg',

	'width'        => '100%',
	'height'       => '',
	'preload'      => 'metadata',
	'controls'     => true,
	'autoplay'     => false,
	'muted'        => false,
	'playsinline'  => true,
	'hotspots_on'  => 'ended',
	'debug'        => false,
	'fullscreen_on_play' => false,

	'areas' => [
		[
			'x' => 60,
			'y' => 63,
			'w' => 20,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Spiel: Puzzle',
			'class' => 'is-top',
			'modal_content' => '
			    <iframe src="https://placehold.co/600x400/EEE/31343C" width="100%" height="100%" />
			',
		],
		[
			'x' => 71,
			'y' => 78,
			'w' => 27,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Die Legende von Gordios',
			'class' => 'is-left',
			'modal_content' => '
				<h2>Die Legende von Gordios</h2>
				<p>Audio</p>
			',
		],
		[
			'x' => 37,
			'y' => 8,
			'w' => 20,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Künstler M. Altomonte',
			'class' => 'is-right',
			'modal_content' => '
				<h2>Künstler M. Altomonte</h2>
				<p>Bild+Text</p>
			',
		],
	],

];