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
			'x' => 28,
			'y' => 12,
			'w' => 20,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Spiel: Schiebepuzzle',
			'class' => 'is-top',
			'modal_content' => '
			    <iframe src="https://placehold.co/600x400/EEE/31343C" width="100%" height="100%" />
			',
		],
		[
			'x' => 63,
			'y' => 32,
			'w' => 27,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Künstler J.M. Rottmayr',
			'class' => 'is-center',
			'modal_content' => '
				<h2>Künstler J.M. Rottmayr</h2>
				<p>Bild und Text</p>
			',
		],
		[
			'x' => 15,
			'y' => 71,
			'w' => 20,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Fürsterzbischof Harrach',
			'class' => 'is-left',
			'modal_content' => '
				<h2>Fürsterzbischof Harrach</h2>
				<p>Bild und Text</p>
			',
		],
	],

];