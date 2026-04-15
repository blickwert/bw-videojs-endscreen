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
			'x' => 42,
			'y' => 6,
			'w' => 27,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Zwei Seiten einer Medaille',
			'class' => 'is-center',
			'modal_content' => '
				<h2>Zwei Seiten einer Medaille</h2>
				<div class="col">
				<p><strong>Text1:</strong><br/> 
				Die Gemälde des Konferenzzimmers vermitteln die positiven Seiten Alexanders.
				<p><strong>Text2:</strong><br/> 
				Die Stuckreliefs zeigen seine negativen Eigenschaften. Sie warnen vor Hochmut und Machtmissbrauch. 
				</p>
				</div/>
			',
		],
		[
			'x' => 26,
			'y' => 35,
			'w' => 20,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Raumfunktion',
			'class' => 'is-left',
			'modal_content' => '
				<h2>Raumfunktion</h2>
				<p>Text</p>
			',
		],
		[
			'x' => 71,
			'y' => 42,
			'w' => 27,
			'h' => 12,
			'action' => 'modal',
			'label' => 'Info: Interview Biograph',
			'class' => 'is-left',
			'modal_content' => '
				<h2>Interview Biograph</h2>
				<p>Audio</p>
			',
		],
	],

];