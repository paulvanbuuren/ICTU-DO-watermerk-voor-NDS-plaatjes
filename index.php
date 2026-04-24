<?php include( "common.inc.php" ); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo WATERMERK_TITLE ?></title>


    <?php
    $jsfile  = 'css/style.css';
    $version = filemtime( $jsfile );
    ?>

    <link href="css/style.css?v=<?php echo $version ?>" rel="stylesheet">

</head>
<body>

<?php
// ── Watermark options ────────────────────────────────────────────────────────
// Each entry: 'post_value' => ['label' => '...', 'preview' => 'path/to/preview.png']
// preview is the same image file shown as a thumbnail in the form.


$defaultWm = array_key_first( $watermarks );
?>

<div class="panel">
    <h1 class="panel__heading"><?php echo WATERMERK_TITLE ?></h1>
    <p class="panel__sub"><?php echo WATERMERK_FORM ?></p>

    <div class="error-banner" id="errorBanner"></div>

    <form id="uploadForm" method="post" action="/generate.php" enctype="multipart/form-data">

        <!-- Image upload -->
        <div class="field">
            <label class="field__label" for="fileInput"><?php echo WATERMERK_LABEL_IMAGE ?></label>
            <div class="dropzone" id="dropzone">
                <input type="file" id="fileInput" name="image" accept="image/*" required>
                <p class="dropzone__label"><?php echo WATERMERK_LABEL_DRAG_1 ?> <span><?php echo WATERMERK_LABEL_DRAG_BROWSE ?></span></p>
                <p class="dropzone__filename" id="fileName"></p>
            </div>
        </div>

        <!-- Watermark choice -->
        <div class="field">
            <label class="field__label"><?php echo WATERMERK_LABEL_WATERMARK ?></label>
            <div class="wm-grid">
                <?php foreach ( $watermarks as $key => $wm ): ?>
                    <label class="wm-option">
                        <input
                                type="radio"
                                name="watermark"
                                value="<?php echo  htmlspecialchars( $key ) ?>"
                                <?php echo  $key === $defaultWm ? 'checked' : '' ?>
                        >
                        <span class="wm-option__card">
                        <img
                                class="wm-option__preview"
                                src="<?php echo  htmlspecialchars( $wm['preview'] ) ?>"
                                alt="<?php echo  htmlspecialchars( $wm['label'] ) ?>"
                        >
                        <span class="wm-option__name"><?php echo  htmlspecialchars( $wm['label'] ) ?></span>
                    </span>
                    </label>
                <?php endforeach ?>
            </div>
        </div>

        <button type="submit" class="btn" id="submitBtn"><?php echo WATERMERK_LABEL_SUBMIT ?></button>
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
