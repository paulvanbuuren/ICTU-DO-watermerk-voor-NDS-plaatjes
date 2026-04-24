<?php
include( "common.inc.php" );


/**
 * generate.php
 * Accepts an uploaded image, resizes & centre-crops it to exactly 1600x900,
 * overlays a chosen server-side watermark, saves the result to /generated-images/,
 * then redirects to display.php to show the image with a title and date.
 *
 * Required POST fields (multipart/form-data):
 *   - image     : the photo to process (JPEG, PNG, GIF, or WebP)
 *   - title     : title text shown on the display page
 *   - watermark : watermark key – one of the keys defined in WATERMARKS below
 *
 * Optional POST fields:
 *   - position : watermark position – center | top-left | top-right | bottom-left | bottom-right (default: bottom-left)
 *   - opacity  : watermark opacity 0–100 (default: 60)
 *   - padding  : pixels from edge for corner positions (default: 20)
 *   - wm_scale : watermark width as % of output image width, 1–100 (default: 25)
 *   - quality  : JPEG output quality 1–100 (default: 85)
 */

// ─── Configuration ────────────────────────────────────────────────────────────
const OUT_WIDTH  = 1600;
const OUT_HEIGHT = 900;
const IMAGES_DIR = __DIR__ . '/generated-images/';

// Watermark options – keys must match the values used in index.php's radio buttons.
// Set each path to the corresponding file on your server.

// ─── Helpers ──────────────────────────────────────────────────────────────────

function abort( string $msg, int $code = 400 ): never {
	http_response_code( $code );
	header( 'Content-Type: application/json' );
	echo json_encode( [ 'error' => $msg ] );
	exit;
}

function loadImage( string $path, string $mime ): GdImage {
	$img = match ( true ) {
		str_contains( $mime, 'jpeg' ), str_contains( $mime, 'jpg' ) => imagecreatefromjpeg( $path ),
		str_contains( $mime, 'png' ) => imagecreatefrompng( $path ),
		str_contains( $mime, 'gif' ) => imagecreatefromgif( $path ),
		str_contains( $mime, 'webp' ) => imagecreatefromwebp( $path ),
		default => false,
	};
	if ( $img === false ) {
		abort( 'Could not decode the image. Ensure it is a valid JPEG, PNG, GIF, or WebP file.' );
	}

	// Normalise EXIF orientation (JPEG only)
	if ( function_exists( 'exif_read_data' ) && ( str_contains( $mime, 'jpeg' ) || str_contains( $mime, 'jpg' ) ) ) {
		$exif        = @exif_read_data( $path );
		$orientation = $exif['Orientation'] ?? 1;
		$img         = match ( $orientation ) {
			3 => imagerotate( $img, 180, 0 ),
			6 => imagerotate( $img, - 90, 0 ),
			8 => imagerotate( $img, 90, 0 ),
			default => $img,
		};
	}

	// Convert palette / indexed images to true-colour
	if ( ! imageistruecolor( $img ) ) {
		$tc = imagecreatetruecolor( imagesx( $img ), imagesy( $img ) );
		imagealphablending( $tc, false );
		imagesavealpha( $tc, true );
		$transparent = imagecolorallocatealpha( $tc, 0, 0, 0, 127 );
		imagefilledrectangle( $tc, 0, 0, imagesx( $img ) - 1, imagesy( $img ) - 1, $transparent );
		imagecopy( $tc, $img, 0, 0, 0, 0, imagesx( $img ), imagesy( $img ) );
		imagedestroy( $img );
		$img = $tc;
	}

	return $img;
}

/**
 * Cover-resize and centre-crop $src to exactly $outW x $outH.
 * The image is scaled up or down so that it completely fills the canvas
 * (no letterboxing), then the excess is cropped from the centre.
 * Returns a new GdImage; destroys the original.
 */
function coverCrop( GdImage $src, int $outW, int $outH ): GdImage {
	$srcW = imagesx( $src );
	$srcH = imagesy( $src );

	// Scale factor: use whichever axis needs the LARGER scale so both dimensions
	// are at least as big as the output (cover behaviour, not contain).
	$scale = max( $outW / $srcW, $outH / $srcH );

	$scaledW = (int) round( $srcW * $scale );
	$scaledH = (int) round( $srcH * $scale );

	// Crop offsets (centre the scaled image over the canvas)
	$cropX = (int) round( ( $scaledW - $outW ) / 2 );
	$cropY = (int) round( ( $scaledH - $outH ) / 2 );

	$dst = imagecreatetruecolor( $outW, $outH );
	imagealphablending( $dst, false );
	imagesavealpha( $dst, true );

	// imagecopyresampled can do the scale + crop in one pass:
	// copy from ($cropX/$scale, $cropY/$scale) in the source at the correct size
	$srcCropX = (int) round( $cropX / $scale );
	$srcCropY = (int) round( $cropY / $scale );
	$srcCropW = (int) round( $outW / $scale );
	$srcCropH = (int) round( $outH / $scale );

	imagecopyresampled( $dst, $src, 0, 0, $srcCropX, $srcCropY, $outW, $outH, $srcCropW, $srcCropH );
	imagedestroy( $src );

	return $dst;
}

/**
 * Overlay a watermark onto $base with per-pixel alpha + global opacity support.
 */
function applyWatermark(
	GdImage $base,
	GdImage $wm,
	string $position,
	int $opacity,
	int $padding,
	int $wmScale
): void {
	$baseW = imagesx( $base );
	$baseH = imagesy( $base );
	$wmW   = imagesx( $wm );
	$wmH   = imagesy( $wm );

	// Scale watermark proportionally to the requested percentage of output width
	$targetW = (int) round( $baseW * ( $wmScale / 100 ) );
	$targetH = (int) round( $wmH * ( $targetW / $wmW ) );

	$targetW = 850;
	$targetH = 300;

	if ( $targetW > $baseW ) {
		$targetW = $baseW;
	}
	if ( $targetH > $baseH ) {
		$targetH = $baseH;
	}

	$scaledWm = imagecreatetruecolor( $targetW, $targetH );
	imagealphablending( $scaledWm, false );
	imagesavealpha( $scaledWm, true );
	imagecopyresampled( $scaledWm, $wm, 0, 0, 0, 0, $targetW, $targetH, $wmW, $wmH );

	[ $dstX, $dstY ] = match ( $position ) {
		'center' => [ (int) ( ( $baseW - $targetW ) / 2 ), (int) ( ( $baseH - $targetH ) / 2 ) ],
		'top-left' => [ $padding, $padding ],
		'top-right' => [ $baseW - $targetW - $padding, $padding ],
		'bottom-left' => [ $padding, $baseH - $targetH - $padding ],
		default => [ $baseW - $targetW - $padding, $baseH - $targetH - $padding ],
	};

	imagealphablending( $base, true );

	if ( $opacity >= 100 ) {
		imagecopy( $base, $scaledWm, $dstX, $dstY, 0, 0, $targetW, $targetH );
	} else {
		$tmp = imagecreatetruecolor( $targetW, $targetH );
		imagealphablending( $tmp, false );
		imagesavealpha( $tmp, true );

		for ( $x = 0; $x < $targetW; $x ++ ) {
			for ( $y = 0; $y < $targetH; $y ++ ) {
				$colour       = imagecolorat( $scaledWm, $x, $y );
				$alpha        = ( $colour >> 24 ) & 0x7F;
				$pixelOpacity = 1 - ( $alpha / 127 );
				$newAlpha     = (int) round( 127 - ( $pixelOpacity * ( $opacity / 100 ) * 127 ) );
				$newAlpha     = max( 0, min( 127, $newAlpha ) );

				$r = ( $colour >> 16 ) & 0xFF;
				$g = ( $colour >> 8 ) & 0xFF;
				$b = $colour & 0xFF;

				imagesetpixel( $tmp, $x, $y, imagecolorallocatealpha( $tmp, $r, $g, $b, $newAlpha ) );
			}
		}

		imagecopy( $base, $tmp, $dstX, $dstY, 0, 0, $targetW, $targetH );
		imagedestroy( $tmp );
	}

	imagedestroy( $scaledWm );
}

// ─── Request validation ───────────────────────────────────────────────────────

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
	abort( 'Only POST requests are accepted.', 405 );
}

if ( empty( $_FILES['image'] ) || $_FILES['image']['error'] !== UPLOAD_ERR_OK ) {
	abort( 'No valid image uploaded. Send a file in the "image" field.' );
}

$wmKey = $_POST['watermark'] ?? '';
if ( ! array_key_exists( $wmKey, $watermarks ) ) {
	abort( 'Invalid watermark choice. Must be one of: ' . implode( ', ', array_keys( WATERMARKS ) ) . '.' );
}
$wmPath = $watermarks[ $wmKey ]['preview'];

if ( ! file_exists( $wmPath ) || ! is_readable( $wmPath ) ) {
	abort( 'Watermark file not found on the server: ' . $wmKey, 500 );
}

// Create /images directory if it doesn't exist
if ( ! is_dir( IMAGES_DIR ) ) {
	if ( ! mkdir( IMAGES_DIR, 0755, true ) ) {
		abort( 'Could not create the images/ directory. Check server permissions.', 500 );
	}
}

// ─── Parameters ───────────────────────────────────────────────────────────────

$title    = trim( $_POST['title'] ?? '' );
$position = in_array( $_POST['position'] ?? '', [
	'center',
	'top-left',
	'top-right',
	'bottom-left',
	'bottom-right'
], true )
	? $_POST['position'] : 'bottom-left';
$opacity  = max( 0, min( 100, (int) ( $_POST['opacity'] ?? 100 ) ) );
$padding  = max( 0, min( 500, (int) ( $_POST['padding'] ?? 0 ) ) );
$wmScale  = max( 1, min( 100, (int) ( $_POST['wm_scale'] ?? 25 ) ) );
$quality  = max( 1, min( 100, (int) ( $_POST['quality'] ?? 85 ) ) );

// ─── Load images ──────────────────────────────────────────────────────────────

$imageMime = mime_content_type( $_FILES['image']['tmp_name'] );
$baseImg   = loadImage( $_FILES['image']['tmp_name'], $imageMime );

$wmMime = mime_content_type( $wmPath );
$wmImg  = loadImage( $wmPath, $wmMime );

// ─── Process ──────────────────────────────────────────────────────────────────

$baseImg = coverCrop( $baseImg, OUT_WIDTH, OUT_HEIGHT );
applyWatermark( $baseImg, $wmImg, $position, $opacity, $padding, $wmScale );
imagedestroy( $wmImg );

// ─── Save to /generated-images/ ─────────────────────────────────────────────────────────

$filename = date( 'YmdHis' ) . '_' . bin2hex( random_bytes( 4 ) ) . '.jpg';
$savePath = IMAGES_DIR . $filename;

if ( ! imagejpeg( $baseImg, $savePath, $quality ) ) {
	abort( 'Failed to save the image. Check write permissions on the images/ directory.', 500 );
}
imagedestroy( $baseImg );

// ─── Redirect to display page ─────────────────────────────────────────────────

$query = http_build_query( [
	'file'  => $filename,
	'title' => $title,
	'date'  => date( 'Y-m-d' ),
] );
header( 'Location: display.php?' . $query );
exit;
