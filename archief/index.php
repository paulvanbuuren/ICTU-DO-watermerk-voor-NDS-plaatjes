<?php include( "common.inc.php" ); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title><?php echo WATERMERK_TITLE ?></title>
    <?php
    $counter = 0;
    $jsfile  = 'css/style.css';
    $version = filemtime( $jsfile );
    $counter = 0;
    ?>
    <link href="css/style.css?v=<?php echo $version ?>" rel="stylesheet">
</head>

<body>
<div class="container">
    <div class="starter-template">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <h1><?php echo WATERMERK_TITLE ?></h1>
                        <div class="lead"> <?php echo WATERMERK_FORM ?> </div>
                        <form role="form" id="posterform" name="posterform" action="generate.php" method="post"
                              enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="image">Filename:</label>
                                <input type="file" name="image" id="image" required="required">
                                <br>
                            </div>
                            <fieldset class="form-group">
                                <legend><?php echo WATERMERK_OPTIONS ?></legend>
                                <ul>
                                    <?php
                                    foreach ( $watermerken as $watermerk ):
                                        $counter ++;
                                        $checked = '';
                                        if ( $counter === 1 ) {
                                            $checked = 'checked ';
                                        }
                                        ?>
                                        <li>
                                            <input type="radio" name="watermerken[]"
                                                   <?php echo $checked ?>value="<?php echo $watermerk ?>"
                                                   id="<?php echo $watermerk ?>"><label
                                                    for="<?php echo $watermerk ?>"><img
                                                        src="/watermerk/<?php echo $watermerk ?>.svg"></label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </fieldset>

                            <button type="submit" class="btn btn-primary"><?php echo WATERMERK_SUBMIT ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container -->

    <!-- Bootstrap core JavaScript
        ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
