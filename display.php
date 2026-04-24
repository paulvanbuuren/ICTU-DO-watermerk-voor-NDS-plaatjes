<?php include( "common.inc.php" ); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo  htmlspecialchars( $title ?? 'Image' ) ?></title>
    <style>
        body {
            flex-direction: column;
        }
        .card {
            width: 100%;
            max-width: 1600px;
        }

        .card__image {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            border: 4px solid silver;
        }

        .card__meta {
            padding: 1.5rem 2rem;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            border-top: 1px solid #2e2e2e;
        }

        .card__title {
            font-size: clamp(1.2rem, 2.5vw, 1.75rem);
            font-weight: 600;
            letter-spacing: -0.02em;
            color: #ffffff;
            line-height: 1.3;
        }

        .card__date {
            font-size: 0.9rem;
            color: #888;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .back {
            margin-top: 1.5rem;
            transition: color 0.2s;
        }

        .back:hover {
            color: #aaa;
        }
    </style>
    <?php
    $jsfile  = 'css/style.css';
    $version = filemtime( $jsfile );
    ?>

    <link href="css/style.css?v=<?php echo $version ?>" rel="stylesheet">

</head>
<body>
<?php
// ─── Validate query parameters ────────────────────────────────────────────────
$imagesDir = __DIR__ . '/generated-images/';
$file      = basename( $_GET['file'] ?? '' );
$date      = trim( $_GET['date'] ?? '' );

// Sanitise: only allow our generated filenames (hex timestamp + random suffix)
if ( ! preg_match( '/^\d{14}_[0-9a-f]{8}\.jpg$/', $file ) || ! file_exists( $imagesDir . $file ) ) {
    http_response_code( 404 );
    echo '<p style="color:#f55">Image not found.</p>';
    exit;
}


// Format date nicely if it's a valid Y-m-d string
$displayDate = '';
if ( $date !== '' ) {
    $d           = DateTimeImmutable::createFromFormat( 'Y-m-d', $date );
    $displayDate = $d ? $d->format( 'j F Y' ) : htmlspecialchars( $date );
}

$imageSrc = 'generated-images/' . htmlspecialchars( $file );
?>
<article class="card">
    <a href="<?php echo  $imageSrc ?>" download="<?php echo  htmlspecialchars( $file ) ?>"><span class="btn">Download <?php echo  htmlspecialchars( $file ) ?></span><br>
        <img
                class="card__image"
                src="<?php echo  $imageSrc ?>"
                alt="<?php echo  htmlspecialchars( WATERMERK_TITLE ) ?>"
                width="1600"
                height="900"
        ></a>
</article>

<p><a class="btn" href="/">← Back</a></p>
</body>
</html>