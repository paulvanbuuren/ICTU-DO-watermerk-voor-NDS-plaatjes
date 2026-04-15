<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars( $title ?? 'Image' ) ?></title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f0f;
            color: #e8e8e8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .card {
            width: 100%;
            max-width: 1600px;
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6);
        }

        .card__image {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
            object-fit: cover;
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
            font-size: 0.875rem;
            color: #555;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back:hover {
            color: #aaa;
        }
    </style>
</head>
<body>
<?php
// ─── Validate query parameters ────────────────────────────────────────────────
$imagesDir = __DIR__ . '/generated-images/';
$file      = basename( $_GET['file'] ?? '' );
$title     = trim( $_GET['title'] ?? '' );
$date      = trim( $_GET['date'] ?? '' );

// Sanitise: only allow our generated filenames (hex timestamp + random suffix)
if ( ! preg_match( '/^\d{14}_[0-9a-f]{8}\.jpg$/', $file ) || ! file_exists( $imagesDir . $file ) ) {
    http_response_code( 404 );
    echo '<p style="color:#f55">Image not found.</p>';
    exit;
}

if ( $title === '' ) {
    $title = 'Untitled';
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
    <a href="<?= $imageSrc ?>" download="<?= htmlspecialchars( $file ) ?>">
    <img
            class="card__image"
            src="<?= $imageSrc ?>"
            alt="<?= htmlspecialchars( $title ) ?>"
            width="1600"
            height="900"
    >Download <?= htmlspecialchars( $file ) ?></a>
    <div class="card__meta">
        <h1 class="card__title"><?= htmlspecialchars( $title ) ?></h1>
        <?php if ( $displayDate ): ?>
            <time class="card__date" datetime="<?= htmlspecialchars( $date ) ?>">
                <?= $displayDate ?>
            </time>
        <?php endif ?>
    </div>
</article>

<a class="back" href="/">← Back</a>
</body>
</html>