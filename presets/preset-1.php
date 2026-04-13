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
			'y' => 22,
			'w' => 27,
			'h' => 15,
			'action' => 'modal',
			'label' => 'Info: Die Wachsamkeit',
			'class' => 'is-top',
			'modal_content' => '
				<h2>Die Wachsamkeit</h2>
				<p>Der Gott des Schlafes versucht die Wachsamkeit mit einem Strauß aus Mohnblumen zu verführen. Sie lässt sich nicht beirren und bleibt aufmerksam. Der Kranich im Bild unterstreicht diese Botschaft: Er steht symbolisch für die ständige Aufmerksamkeit eines Herrschers.</p>
			',
		],
		[
			'x' => 62,
			'y' => 45,
			'w' => 27,
			'h' => 15,
			'action' => 'modal',
			'label' => 'Info: Künstler',
			'class' => 'is-center',
			'modal_content' => '
				<h2>Künstler: Alberto Carmesina Gips, Kalk und Fingerspitzengefühl</h2>
				<p>Die Stuckreliefs des Alexander-Zyklus stammen von dem Graubündner Stuckateur Alberto Carmesina. Er war einer der gefragtesten Stuckateure seiner Zeit und arbeitete als Hofstuckateur in Wien. Die Stucktechnik gelangte im Barock von Rom nach Salzburg und war an den Höfen Europas sehr beliebt.</p>
			',
		],
		[
			'x' => 62,
			'y' => 66,
			'w' => 27,
			'h' => 15,
			'action' => 'link',
			'label' => 'Link: Video',
			'class' => 'is-right',
			'url' => 'https://www.youtube.com/watch?v=Ho1O0CZli7o',
			'target' => '_blank',
			'rel' => 'noopener',
		],
	],
];