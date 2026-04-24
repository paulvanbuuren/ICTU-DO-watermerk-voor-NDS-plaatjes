<?php


// Report all PHP errors
error_reporting( 1 );

define( 'WATERMERK_TITLE', 'Watermerk generator voor Digitale Overheid' );
define( 'WATERMERK_BACK', '&lt;&lt; nog een poster' );
define( 'WATERMERK_OPTIONS', 'Welk watermerk wil je gebruiken?' );
define( 'WATERMERK_LABEL_IMAGE', 'Jouw plaatje' );
define( 'WATERMERK_LABEL_WATERMARK', 'Watermerk' );
define( 'WATERMERK_LABEL_DRAG_1', 'Sleep een plaatje hiernaartoe' );
define( 'WATERMERK_LABEL_DRAG_BROWSE', 'of selecteer' );
define( 'WATERMERK_LABEL_SUBMIT', 'Maak een nieuw plaatje' );
define( 'WATERMERK_LABEL_PROCESSING', 'Plaatje uploaden...' );

define( 'WATERMERK_READY', 'Alsjeblieft' );
define( 'WATERMERK_TXT_LENGTH', 500 );
define( 'WATERMERK_TITLE_LENGTH', 30 );
define( 'rood', '000000' );
define( 'beige', '000000' );
define( 'bruin', '000000' );
define( 'MAX_MB', 8 );
define( 'WATERMERK_FORM', '<p>Upload een plaatje en kies het bijbehorende watermerk.</p><ul><li>Maximale bestandsgrootte: ' . MAX_MB . ' MB</li><li>Alleen PNG, JPG en WEBP-bestanden worden ondersteund.</li></ul>' );

const MAX_UPLOAD_BYTES = MAX_MB * 1024 * 1024; // 10 MB – adjust as needed

define( 'PVB_DEBUG', false );

$watermerken = array(
	'watermerk-prio1-cloud',
	'watermerk-prio2-data',
	'watermerk-prio3-ai',
	'watermerk-prio4-burgers',
	'watermerk-prio5-weerbaarheid',
	'watermerk-prio6-vakmanschap'
);


function writedebug( $text ) {
	if ( PVB_DEBUG ) {
		@ini_set( 'display_errors', 1 );
		echo $text . "<br />";
	} else {
		@ini_set( 'display_errors', 0 );
	}
}


$watermarks = array(
	'watermerk-prio1-cloud'        => [ 'label'   => 'Prio 1 - Cloud',
	                                    'preview' => 'watermerk/watermerk-prio1-cloud.png'
	],
	'watermerk-prio2-data'         => [ 'label' => 'Prio 2 - Data', 'preview' => 'watermerk/watermerk-prio2-data.png' ],
	'watermerk-prio3-ai'           => [ 'label' => 'Prio 3 - AI', 'preview' => 'watermerk/watermerk-prio3-ai.png' ],
	'watermerk-prio4-burgers'      => [
		'label'   => 'Prio 4 - Burgers en buitenlui',
		'preview' => 'watermerk/watermerk-prio4-burgers.png'
	],
	'watermerk-prio5-weerbaarheid' => [
		'label'   => 'Prio 5 - Digitale weerbaarheid',
		'preview' => 'watermerk/watermerk-prio5-weerbaarheid.png'
	],
	'watermerk-prio6-vakmanschap'  => [
		'label'   => 'Prio 6 - Digitaal vakmanschap',
		'preview' => 'watermerk/watermerk-prio6-vakmanschap.png'
	]
);

