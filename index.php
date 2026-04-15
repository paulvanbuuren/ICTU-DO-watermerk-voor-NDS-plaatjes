<?php include("common.inc.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">
<title><?php echo STOEL22_TITLE ?></title>

<!-- Bootstrap core CSS -->
<link href="css/bootstrap.min.css" rel="stylesheet">

<!-- Custom styles for this template -->
<link href="css/style.css" rel="stylesheet">
</head>

<body>
<div class="container">
<div class="starter-template">
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <h1><?php echo STOEL22_TITLE ?></h1>
                    <p class="lead"> <?php echo STOEL22_FORM ?> </p>
                    <form role="form" id="posterform" name="posterform" action="generate.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="file">Filename:</label>
                            <input type="file" name="file" id="file" required="required"> 
                            <br>
                        </div>
                        <div class="form-group">
                            <label for="stoel22_titel">Titel (maximaal <?php echo STOEL22_TITLE_LENGTH ?> tekens)</label>
                            <input type="text" class="form-control" name="stoel22_titel" id="stoel22_titel" placeholder="Titel" required="required" maxlength="<?php echo STOEL22_TITLE_LENGTH ?>">
                        </div>
                        <div class="form-group">
                            <label for="stoel22_titel">Titel (maximaal <?php echo STOEL22_TITLE_LENGTH ?> tekens)</label>
                            <input type="text" class="form-control" name="stoel22_body" id="stoel22_body" placeholder="ondertitel" required="required" maxlength="<?php echo STOEL22_TITLE_LENGTH ?>">
                        </div>

                        <button type="submit" class="btn btn-primary"><?php echo STOEL22_SUBMIT ?></button>
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
