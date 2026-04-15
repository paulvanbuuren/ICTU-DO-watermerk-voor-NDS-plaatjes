<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Generator</title>
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
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .panel {
            width: 100%;
            max-width: 640px;
            background: #1a1a1a;
            border-radius: 12px;
            padding: 2.5rem 2.5rem 2rem;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6);
        }

        .panel__heading {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #fff;
            margin-bottom: 0.4rem;
        }

        .panel__sub {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 2rem;
        }

        /* ── Field groups ── */
        .field {
            margin-bottom: 1.5rem;
        }

        label.field__label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 0.5rem;
        }

        input[type="text"] {
            width: 100%;
            background: #111;
            border: 1px solid #2e2e2e;
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            color: #e8e8e8;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }

        input[type="text"]:focus {
            border-color: #555;
        }

        input[type="text"]::placeholder {
            color: #444;
        }

        /* ── Drop zone ── */
        .dropzone {
            border: 2px dashed #2e2e2e;
            border-radius: 8px;
            padding: 2rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            position: relative;
        }

        .dropzone:hover, .dropzone.over {
            border-color: #555;
            background: #1f1f1f;
        }

        .dropzone.has-file {
            border-color: #3a7a3a;
            background: #131f13;
        }

        .dropzone__icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .dropzone__label {
            font-size: 0.9rem;
            color: #888;
        }

        .dropzone__label span {
            color: #aaa;
            text-decoration: underline;
        }

        .dropzone__filename {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #6a6;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #fileInput {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        /* ── Watermark picker ── */
        .wm-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .wm-option {
            position: relative;
        }

        .wm-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .wm-option__card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.6rem;
            padding: 0.9rem 0.5rem 0.75rem;
            background: #111;
            border: 2px solid #2e2e2e;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            user-select: none;
        }

        .wm-option input[type="radio"]:checked + .wm-option__card {
            border-color: #888;
            background: #1f1f1f;
        }

        .wm-option__card:hover {
            border-color: #444;
        }

        .wm-option__preview {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: contain;
            border-radius: 4px;
            background: repeating-conic-gradient(#222 0% 25%, #2a2a2a 0% 50%) 0 0 / 12px 12px;
            /* checkerboard shows transparency */
        }

        .wm-option__name {
            font-size: 0.78rem;
            color: #888;
            text-align: center;
        }

        .wm-option input[type="radio"]:checked + .wm-option__card .wm-option__name {
            color: #ccc;
        }

        /* ── Submit ── */
        .btn {
            display: block;
            width: 100%;
            padding: 0.8rem;
            background: #e8e8e8;
            color: #0f0f0f;
            font-size: 0.95rem;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
            margin-top: 2rem;
        }

        .btn:hover {
            background: #fff;
        }

        .btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        /* ── Progress overlay ── */
        .progress {
            display: none;
            text-align: center;
            padding: 1rem 0 0.5rem;
            font-size: 0.875rem;
            color: #666;
        }

        .progress.visible {
            display: block;
        }

        /* ── Error banner ── */
        .error-banner {
            display: none;
            background: #2a1010;
            border: 1px solid #5a2020;
            color: #f88;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .error-banner.visible {
            display: block;
        }
    </style>
</head>
<body>

<?php
// ── Watermark options ────────────────────────────────────────────────────────
// Each entry: 'post_value' => ['label' => '...', 'preview' => 'path/to/preview.png']
// preview is the same image file shown as a thumbnail in the form.
$watermarks = [
        'watermark1' => [ 'label' => 'Watermark 1', 'preview' => 'watermarks/watermark1.png' ],
        'watermark2' => [ 'label' => 'Watermark 2', 'preview' => 'watermarks/watermark2.png' ],
        'watermark3' => [ 'label' => 'Watermark 3', 'preview' => 'watermarks/watermark3.png' ],
];

$watermarks = array(
        'watermerk-prio1-cloud'        => [ 'label' => 'prio1', 'preview' => '/watermerk/watermerk-prio1-cloud.png' ],
        'watermerk-prio2-data'         => [ 'label' => 'prio2', 'preview' => '/watermerk/watermerk-prio2-data.png' ],
        'watermerk-prio3-ai'           => [ 'label' => 'prio3', 'preview' => '/watermerk/watermerk-prio3-ai.png' ],
        'watermerk-prio4-burgers'      => [ 'label' => 'prio4', 'preview' => '/watermerk/watermerk-prio4-burgers.png' ],
        'watermerk-prio5-weerbaarheid' => [ 'label'   => 'prio5',
                                            'preview' => '/watermerk/watermerk-prio5-weerbaarheid.png'
        ],
        'watermerk-prio6-vakmanschap'  => [ 'label'   => 'prio6',
                                            'preview' => '/watermerk/watermerk-prio6-vakmanschap.png'
        ]
);


$defaultWm = array_key_first( $watermarks );
?>

<div class="panel">
    <h1 class="panel__heading">Image Generator</h1>
    <p class="panel__sub">Upload a photo, choose a watermark, and generate a 1600 × 900 JPEG.</p>

    <div class="error-banner" id="errorBanner"></div>

    <form id="uploadForm" method="post" action="/generate.php" enctype="multipart/form-data">

        <!-- Title -->
        <div class="field">
            <label class="field__label" for="titleInput">Title</label>
            <input type="text" id="titleInput" value="Digitale Overheid" name="title" placeholder="Enter a title…" required>
        </div>

        <!-- Image upload -->
        <div class="field">
            <label class="field__label">Photo</label>
            <div class="dropzone" id="dropzone">
                <input type="file" id="fileInput" name="image" accept="image/*" required>
                <div class="dropzone__icon">🖼️</div>
                <p class="dropzone__label">Drop an image here or <span>browse</span></p>
                <p class="dropzone__filename" id="fileName"></p>
            </div>
        </div>

        <!-- Watermark choice -->
        <div class="field">
            <label class="field__label">Watermark</label>
            <div class="wm-grid">
                <?php foreach ( $watermarks as $key => $wm ): ?>
                    <label class="wm-option">
                        <input
                                type="radio"
                                name="watermark"
                                value="<?= htmlspecialchars( $key ) ?>"
                                <?= $key === $defaultWm ? 'checked' : '' ?>
                        >
                        <span class="wm-option__card">
                        <img
                                class="wm-option__preview"
                                src="<?= htmlspecialchars( $wm['preview'] ) ?>"
                                alt="<?= htmlspecialchars( $wm['label'] ) ?>"
                        >
                        <span class="wm-option__name"><?= htmlspecialchars( $wm['label'] ) ?></span>
                    </span>
                    </label>
                <?php endforeach ?>
            </div>
        </div>

        <button type="submit" class="btn" id="submitBtn">Generate image →</button>
        <p class="progress" id="progress">Processing… please wait.</p>

    </form>
</div>

<script>
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const form = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const progress = document.getElementById('progress');
    const errorBanner = document.getElementById('errorBanner');

    // Update filename display when a file is picked
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (file) {
            fileName.textContent = file.name;
            dropzone.classList.add('has-file');
        } else {
            fileName.textContent = '';
            dropzone.classList.remove('has-file');
        }
    });

    // Drag-and-drop visual feedback
    dropzone.addEventListener('dragover', e => {
        e.preventDefault();
        dropzone.classList.add('over');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('over'));
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('over');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    // Show progress on submit
    form.addEventListener('submit', e => {
        errorBanner.classList.remove('visible');

        if (!fileInput.files.length) {
            e.preventDefault();
            showError('Please select a photo before submitting.');
            return;
        }

//        submitBtn.disabled = true;
        progress.classList.add('visible');
    });

    function showError(msg) {
        errorBanner.textContent = msg;
        errorBanner.classList.add('visible');
    }
</script>

</body>
</html>
