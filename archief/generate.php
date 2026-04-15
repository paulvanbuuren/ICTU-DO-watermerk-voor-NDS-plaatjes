<?php
/**
 * generate.php
 * Accepts an uploaded image, resizes it to fit within 1600x900,
 * overlays a fixed server-side watermark, and outputs a JPEG.
 *
 * Usage (multipart/form-data POST):
 *   - image    : the uploaded photo (JPEG, PNG, GIF, or WebP)
 *
 * Optional POST fields:
 *   - position : center | top-left | top-right | bottom-left | bottom-right (default: bottom-right)
 *   - opacity  : watermark opacity 0–100 (default: 60)
 *   - padding  : pixels from edge for corner positions (default: 20)
 *   - wm_scale : watermark width as % of output image width, 1–100 (default: 25)
 *   - quality  : JPEG output quality 1–100 (default: 85)
 */

// ─── Configuration ────────────────────────────────────────────────────────────
const MAX_WIDTH  = 1600;
const MAX_HEIGHT = 900;

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

	// Normalise orientation using EXIF data (JPEG only)
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

	// Convert palette/non-true-colour images to true colour
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
 * Resize an image to fit within $maxW x $maxH, preserving aspect ratio.
 * Returns a new GdImage; destroys the original.
 */
function resizeToFit( GdImage $src, int $maxW, int $maxH ): GdImage {
	$srcW = imagesx( $src );
	$srcH = imagesy( $src );

	if ( $srcW <= $maxW && $srcH <= $maxH ) {
		return $src; // already fits — no resize needed
	}

	$ratio = min( $maxW / $srcW, $maxH / $srcH );
	$dstW  = (int) round( $srcW * $ratio );
	$dstH  = (int) round( $srcH * $ratio );

	$dst = imagecreatetruecolor( $dstW, $dstH );
	imagealphablending( $dst, false );
	imagesavealpha( $dst, true );
	imagecopyresampled( $dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH );
	imagedestroy( $src );

	return $dst;
}

/**
 * Overlay a watermark onto $base with per-pixel alpha + global opacity support.
 *
 * @param GdImage $base The base image – modified in place.
 * @param GdImage $wm The watermark image.
 * @param string $position center | top-left | top-right | bottom-left | bottom-right
 * @param int $opacity 0-100
 * @param int $padding pixels from edge (corner positions)
 * @param int $wmScale watermark width as % of base width (1-100)
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

	// Scale watermark proportionally
	$targetW = (int) round( $baseW * ( $wmScale / 100 ) );
	$targetH = (int) round( $wmH * ( $targetW / $wmW ) );

	$targetW = 850;
	$targetH = 300;

	// Clamp to base image dimensions
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

	// Compute destination coordinates
	[ $dstX, $dstY ] = match ( $position ) {
		'center' => [ (int) ( ( $baseW - $targetW ) / 2 ), (int) ( ( $baseH - $targetH ) / 2 ) ],
		'top-left' => [ $padding, $padding ],
		'top-right' => [ $baseW - $targetW - $padding, $padding ],
		'bottom-left' => [ $padding, $baseH - $targetH - $padding ],
		default => [ $baseW - $targetW - $padding, $baseH - $targetH - $padding ], // bottom-right
	};

	imagealphablending( $base, true );

	if ( $opacity >= 100 ) {
		// Full opacity – respect per-pixel alpha directly
		imagecopy( $base, $scaledWm, $dstX, $dstY, 0, 0, $targetW, $targetH );
	} else {
		// Combine global opacity with per-pixel alpha channel
		$tmp = imagecreatetruecolor( $targetW, $targetH );
		imagealphablending( $tmp, false );
		imagesavealpha( $tmp, true );

		for ( $x = 0; $x < $targetW; $x ++ ) {
			for ( $y = 0; $y < $targetH; $y ++ ) {
				$colour       = imagecolorat( $scaledWm, $x, $y );
				$alpha        = ( $colour >> 24 ) & 0x7F;            // 0 = opaque, 127 = transparent
				$pixelOpacity = 1 - ( $alpha / 127 );                // 0-1
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

$watermerk = isset( $_POST['watermerken'] ) ? $_POST['watermerken'][0] : '';

$path      = dirname( __FILE__ ) . "/";
$imagepath = $path . "img/";
$fontpath  = $path . "fonts/";
$outpath   = $path . "posters/";
if ( $watermerk ) {
	$watermerk_file = $path . "watermerk/" . $watermerk . ".png";
	if ( ! file_exists( $watermerk_file ) || ! is_readable( $watermerk_file ) ) {
		abort( 'Watermark file not found on the server: ' . $watermerk_file, 500 );
	}
}


// ─── Parameters ───────────────────────────────────────────────────────────────

$position = in_array( $_POST['position'] ?? '', [
	'center',
	'top-left',
	'top-right',
	'bottom-left',
	'bottom-right'
], true )
	? $_POST['position'] : 'bottom-left';
//$opacity  = max( 0, min( 100, (int) ( $_POST['opacity'] ?? 60 ) ) );
$opacity = 100;
$padding = max( 0, min( 500, (int) ( $_POST['padding'] ?? 0 ) ) );
$wmScale = max( 1, min( 100, (int) ( $_POST['wm_scale'] ?? 25 ) ) );
$quality = max( 1, min( 100, (int) ( $_POST['quality'] ?? 85 ) ) );

// ─── Load images ──────────────────────────────────────────────────────────────

$uploadedimage_mimetype = mime_content_type( $_FILES['image']['tmp_name'] );
$uploadedimage          = loadImage( $_FILES['image']['tmp_name'], $uploadedimage_mimetype );

$watermerk_mimetype = mime_content_type( $watermerk_file );
$watermerk_image    = loadImage( $watermerk_file, $watermerk_mimetype );

// ─── Process ──────────────────────────────────────────────────────────────────

$uploadedimage = resizeToFit( $uploadedimage, MAX_WIDTH, MAX_HEIGHT );
applyWatermark( $uploadedimage, $watermerk_image, $position, $opacity, $padding, $wmScale );

echo '<h1>Uploaded image</h1>';
var_dump( $uploadedimage );

echo '<h1>watermer image</h1>';
var_dump( $watermerk_image );
die( 'o nee!' );
imagedestroy( $watermerk_image );


// ─── Output as JPEG ───────────────────────────────────────────────────────────

header( 'Content-Type: image/jpeg' );
header( 'Content-Disposition: inline; filename="output.jpg"' );
imagejpeg( $uploadedimage, null, $quality );
imagedestroy( $uploadedimage );
