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
	'fullscreen_on_play' => true,

	'areas' => [
		[
			'x' => 36,
			'y' => 4,
			'w' => 27,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Spiel: Hangman',
			'class' => 'is-top',
			'modal_content' => '
			    <iframe src="https://placehold.co/600x400/EEE/31343C" width="100%" height="100%" />
			',
		],
		[
			'x' => 15,
			'y' => 23,
			'w' => 27,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Raumfunktion',
			'class' => 'is-left',
			'modal_content' => '
				<h2>Das Zentrum der Macht</h2>
				<p>Text</p>
			',
		],
		[
			'x' => 62,
			'y' => 23,
			'w' => 27,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Das Ende Alexanders',
			'class' => 'is-left',
			'modal_content' => '
				<h2>Das Ende Alexanders</h2>
				<p>Legende, die den Tod überdauert</p>
			',
		],
	],

];