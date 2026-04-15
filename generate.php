<?php
include("common.inc.php"); 
include("blur.php"); 






// TO DO:
// - check op te veel bestanden
// debug info schrijven
// check op maximale grootte voor bestand
// glow in de tekst
// beter logo in PNG
// beter copyright symbool
// beveiliging van input (alleen A-Z, 0-9 en spaties, streep, ampersand, kromme haken)
// check rotation in EXIF voor foto-uploads


//if (empty($_POST['stoel22_body']))
//  die('Geen tekst ingevoerd<br /><a href="./index.php">opnieuw</a>');

//if (empty($_POST['stoel22_titel']))
//  die('Geen tekst ingevoerd<br /><a href="./index.php">opnieuw</a>');

$path               = dirname(__FILE__)."/";
$imagepath          = $path."img/";
$fontpath           = $path."fonts/";
$outpath            = $path."posters/";

if ( isset($_FILES["file"])) {
    $path_to_upload     = $_FILES["file"]["tmp_name"]; 
    if ($_FILES["file"]["error"] > 0) {
        writedebug("Error: " . $_FILES["file"]["error"] );
        
        $thefile            = $imagepath."mondriaan.png";
        $path_to_upload     = $_FILES["file"]["tmp_name"]; 
        $path_parts         = pathinfo($thefile);

    } 
    else {

        writedebug("Upload: " . $_FILES["file"]["name"] . "<br>");
        writedebug("Type: " . $_FILES["file"]["type"] . "<br>");
        writedebug("Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>");
        writedebug("Stored in: " . $_FILES["file"]["tmp_name"]);

        $path_parts          = pathinfo($_FILES["file"]["name"]);
        $path_to_upload     = $_FILES["file"]["tmp_name"]; 
        $thefile            = $_FILES["file"]["tmp_name"];
        
    }
}
else {
    $thefile            = $imagepath."mondriaan.png";
    $path_to_upload     = $imagepath; 
    $path_parts         = pathinfo($thefile);
}



$path_to_logo       = $imagepath."logo.png";


$copyright          = date("Y") . " Stoel22 - Massage voor kantoor en particulier";

// dimensies
$total_image_width       = 300;
$total_image_height      = 200;
$minimal_body_box_width         = 400;


$defaulttitle       = 'Zeg, je titel is leeg. Wat nu?';
//$defaulttitle       = 'WWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWW';

$fontnormal         = $fontpath."normal.ttf";
$fonttitle          = $fontpath."normal.ttf";
$text               = (empty($_POST['stoel22_body'])) ? 'body' : $_POST['stoel22_body'];
$text               = (!empty($_POST['stoel22_body']) ? $_POST['stoel22_body'] : (!empty( $_GET['stoel22_body'] ) ? $_GET['stoel22_body'] : 'Hela, je hebt geen tekst ingevoerd.'));
$titel              = (!empty($_POST['stoel22_titel']) ? $_POST['stoel22_titel'] : (!empty( $_GET['stoel22_titel'] ) ? $_GET['stoel22_titel'] : substr ( $defaulttitle, 0, STOEL22_TITLE_LENGTH )));
$titel              = strtoupper($titel);

//$titel = substr ( $defaulttitle, 0, STOEL22_TITLE_LENGTH );

$aantal_tekens  = strlen($text);
$delimiter      = "<br />";

// we mikken op een maximum van 4 zinnen en ideaal is 3
$aantal_zinnen  = 3;

// wat een fijne functie, dit wordwrap. Dank u, PHP.
$newtext        = wordwrap($text, round( ( $aantal_tekens / $aantal_zinnen  ), 3 ), $delimiter);

$resizefactor = 1;

$body_size          = 36;
$title_size         = 2 * $body_size;

$hoogteband_boven   = $title_size;


if ( strlen($text) > STOEL22_TXT_LENGTH ) {
    // kan gebeuren
    
    $factor        = ( STOEL22_TXT_LENGTH / strlen($tweettext) ); 
    $body_size     = round( $factor * $body_size, 0);
}




$titel_x            = 10;
$titel_y            = ( $title_size * 2 ) + 10;

$body_x             = $titel_x;
$body_y             = ( $body_size * 2 ) + ( $titel_y + 10 );

$upload_x           = 0;
$upload_y           = $hoogteband_boven;
    

$transparency       = 100;


$span               = 2 * $body_size;
$blurspan           = 1;
$blur               = 2;
$doblur             = false;
    

if ( PVB_DEBUG ) {
  $filename           = seoUrl("Motivational poster - " . substr ( $titel, 0, 20 ) . '-' . date("Y") . " " . date("m") . "-" . date("d") . "-" . date("H")  . "-" . date("s") . "-" . date("i") ) . ".png";
}
else {
  $filename           = seoUrl("Motivational poster - " . $titel . '-' . date("Y") . " " . date("m") . "-" . date("d") ) . ".png";
  $filename           = seoUrl("Motivational poster - " . substr ( $titel, 0, 20 ) . '-' . date("Y") . " " . date("m") . "-" . date("d") . "-" . date("H")  . "-" . date("s") . "-" . date("i") ) . ".png";
}

$destimagepath      = $outpath.$filename;


$destimageurl = "http://" . $_SERVER['HTTP_HOST'] . "/posters/".$filename;


if (!file_exists($outpath.$filename)) {


    // calculate the color for the background
    $arr_bg_titletext    = hex2rgb(rood);
    $arr_bg_bodytext     = hex2rgb(bruin);
    $arr_bg_footertext   = hex2rgb(beige);

    if ( PVB_DEBUG ) {

        writedebug('<pre>');
        writedebug(var_dump($path_parts));
        writedebug('</pre>');
        writedebug('<hr />');
        writedebug('<pre>');
        writedebug(var_dump($thefile));
        writedebug('</pre>');
        
    }    

    if($path_parts['extension']=="jpg" || $path_parts['extension']=="jpeg" )
    {
        $upload_image       =   imagecreatefromjpeg($thefile);
    }
    elseif($path_parts['extension']=="png")
    {
        $upload_image       =   imagecreatefrompng($thefile);
    }
    elseif($path_parts['extension']=="gif")
    {
        $upload_image       =   imagecreatefromgif($thefile);
    }
    else {
        die("wut: " . $path_parts['extension']);
    }

    
    $fontfactor = 1;



// bepaal breedte van geuploade plaatje

// bepaal breedte van output-plaatje

// plaats output plaatje op de juiste plek



    // ================================================================================================================================
    // bereken hoogte van titel
    $title_box                          = imagettfbbox( ( $title_size * $fontfactor ),0,$fonttitle,$titel);
    
    $title_box_dimensions               = array();
    $title_box_dimensions['height']     = ( $title_box[1] - $title_box[5] );
    $title_box_dimensions['width']      = ( $title_box[2] - $title_box[0] );

    $amtleftofBL                        = 0; // $text_box[0];
    $amtbelowBL                         = $title_box[1];
    
    $titlebox_start_y                   = 0;
    
    $titlebox_height                    = ( $title_box_dimensions['height'] ) + ( 2 * $span );    
    $titlebox_width                     = ( $title_box_dimensions['width'] ) + ( 2 * $span );    
    $body_y_offset                      = $titlebox_height;

    $titel_x                            = $span; 
    $titel_y                            = ( $title_box_dimensions['height'] - $amtbelowBL ) + ( $span );

    
    // ================================================================================================================================
    // bereken hoogte van bodytekst
    $body_box                       = imagettfbbox( ( $body_size * $fontfactor ),0,$fontnormal,$text);
    
    $body_box_dimensions            = array();
    $body_box_dimensions['height']  = ( $body_box[1] - $body_box[5] );
    $body_box_dimensions['width']   = ( $body_box[2] - $body_box[0] );

    $amtleftofBL                    = 0; // $text_box[0];
    $amtbelowBL                     = $body_box[1];

    $bodybox_start_y                = ( $body_y_offset - ( 1 * $span ) ); 
    $bodybox_height                 = ( $body_box_dimensions['height'] ) + ( 3 * $span ) + $body_y_offset;

    $bodybox_width                  = ( $body_box_dimensions['width'] ) + ( 2 * $span );

    if ( $minimal_body_box_width > $bodybox_width ) {
        writedebug('warom? bodybox_width : ' . $bodybox_width . ' &amp; minimal_body_box_width : ' . $minimal_body_box_width);
        $bodybox_width                  = $minimal_body_box_width;
    }

    $body_y                         = ( ( $body_box_dimensions['height'] - $amtbelowBL ) + ( $span ) ) + $body_y_offset;
    $body_x                         = $span; 
    // ================================================================================================================================
    // logo

    list($logowidth,$logoheight)=getimagesize($path_to_logo);
    $logoimage  = imagecreatefrompng($path_to_logo);


    $logo_box_x         = 0;


    // ================================================================================================================================

$offset_y = 200;
$offset_x = $offset_y;
    


    // bereken hoogte van plaatje
    list($uploaded_width,$uploaded_height)=getimagesize($thefile);

    if ( $uploaded_height > $bodybox_height ) {
        $bodybox_height = $uploaded_height + $body_y_offset;
        writedebug("body_y_offset: " . $body_y_offset);
    }

    $total_image_width    = $uploaded_width + ( 2 * $offset_y );



    $plaatje_x              = $offset_x;
    $plaatje_y              = $offset_y;
    
    //===
    
    $upload_image_resize_width = $uploaded_width;
    $upload_image_resize_height = $uploaded_height;

    $total_image_height  = ( ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $upload_image_resize_height ) ) + $logoheight + ( 2 * $span );

    if ( $upload_image_resize_height < ( $bodybox_height - $titlebox_height ) ) {
        writedebug("<h1>Plaatje is korter dan de tekst</h1>");
        writedebug("bodybox_height: " . ( $bodybox_height - $titlebox_height ) );
        writedebug("upload_image_resize_height: " . $upload_image_resize_height);
        writedebug("bodybox_height: " . $bodybox_height);
        writedebug("bodybox_width: " . $bodybox_width);
        

        $resizefactor =  $upload_image_resize_height / ( $bodybox_height - $titlebox_height ) ;

        writedebug("resizefactor: " . $resizefactor);

        $oldwidth = $upload_image_resize_width;

        $upload_image_resize_width = ( $upload_image_resize_width / $resizefactor);
        $upload_image_resize_height = ( $upload_image_resize_height / $resizefactor);

        $widthdiff =  ( $upload_image_resize_width - $oldwidth ) / 2;     

        $logo_box_start_y         = ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $upload_image_resize_height );
        $logo_box_start_y_end     = ( ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $uploaded_height ) ) + $upload_image_resize_height;
        
        $plaatje_x = $plaatje_x - $widthdiff;
        
    }
    else if ( $total_image_width < $titlebox_width ) {
    // het kan zijn dat de titel enorm lang is.

        writedebug("<h1>ja m'n titel is veel te lang</h1>");
        writedebug("total_image_width: " . $total_image_width);
        writedebug("titlebox_width: " . $titlebox_width);
        writedebug("bodybox_width: " . $bodybox_width);
        writedebug("bodybox_width: " . $bodybox_width);

        $total_image_width = $titlebox_width;
        
        $resizefactor =  ( $total_image_width - $bodybox_width ) / $upload_image_resize_width ;

        writedebug("resizefactor: " . $resizefactor);

        $upload_image_resize_width = ( $upload_image_resize_width * $resizefactor);
        $upload_image_resize_height = ( $upload_image_resize_height * $resizefactor);

        $total_image_height  = ( ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $upload_image_resize_height ) ) + $titlebox_height + $logoheight;
        $bodybox_height = $upload_image_resize_height + $titlebox_height;

        $logo_box_start_y         = ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $upload_image_resize_height );
        $logo_box_start_y_end     = ( ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $uploaded_height ) ) + $upload_image_resize_height;

    }
    else {
        $logo_box_start_y         = ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $upload_image_resize_height );
        $logo_box_start_y_end     = ( ( ( $bodybox_height > $upload_image_resize_height ) ? $bodybox_height : $uploaded_height ) ) + $upload_image_resize_height;
    }


    

//    $logo_x                 = ( $total_image_width - ( $resizefactor * $span ) ) - $logowidth;
//    $logo_y                 = ( $total_image_height - ( $resizefactor * $span ) ) - $logoheight;

$total_image_height = $total_image_height - $titlebox_height;

    $logo_x                 = ( $total_image_width - ( $resizefactor * ( $span / 2 ) ) ) - $logowidth;
//    $logo_y                 = ( $total_image_height - $logoheight );
    $logo_y                 = ( $total_image_height - ( $resizefactor * ( $span / 2 ) ) ) - $logoheight;

    // ================================================================================================================================

    $tmp                    = imagecreatetruecolor($upload_image_resize_width,$total_image_height);
    
    $outputimage = @imagecreatetruecolor( $total_image_width, $total_image_height )
          or die('Cannot Initialize new GD image stream');

    // zwarte tekst
    $text_color             = imagecolorallocate($tmp, 255, 255, 255);
    $title_color            = imagecolorallocate($tmp, 0, 217, 217 );
    
//    $shadow_color           = imagecolorallocate($tmp, $arr_bg_bodytext[0], $arr_bg_bodytext[1], $arr_bg_bodytext[2]);
    $shadow_color           = imagecolorallocate($tmp, 0, 0, 0);

    // Colors
    $bg_bodytext            = imagecolorallocate($outputimage, $arr_bg_bodytext[0], $arr_bg_bodytext[1], $arr_bg_bodytext[2]);
    $bg_titletext           = imagecolorallocate($outputimage, $arr_bg_titletext[0], $arr_bg_titletext[1], $arr_bg_titletext[2]);
    $bg_footertext          = imagecolorallocate($outputimage, $arr_bg_footertext[0], $arr_bg_footertext[1], $arr_bg_footertext[2]);

    imagecopyresampled($tmp,$upload_image,0,0,0,0,$upload_image_resize_width,$upload_image_resize_height, $uploaded_width, $uploaded_height);
    
    imagecopymerge($outputimage, $tmp, $plaatje_x, $plaatje_y, 0, 0, $total_image_width, $total_image_height, $transparency);


    // background body
    imagefilledrectangle( $outputimage, 0, $bodybox_start_y, $bodybox_width, $bodybox_height, $bg_bodytext);

    // background title
    imagefilledrectangle( $outputimage, 0, $titlebox_start_y, $total_image_width, $titlebox_height, $bg_titletext);

    // background logo
//    imagefilledrectangle( $outputimage, $logo_box_x, $logo_box_start_y, $total_image_width, $logo_box_start_y_end, $bg_footertext);



if ( $doblur ) {
    imagettftextblur($outputimage,$body_size,0,$body_x + $blurspan,$body_y + $blurspan,$shadow_color,$fontnormal,$text,$blur); 
    imagettftextblur($outputimage,$body_size,0,$body_x - $blurspan,$body_y + $blurspan,$shadow_color,$fontnormal,$text,$blur); 
    imagettftextblur($outputimage,$body_size,0,$body_x - $blurspan,$body_y - $blurspan,$shadow_color,$fontnormal,$text,$blur); 
    imagettftextblur($outputimage,$body_size,0,$body_x + $blurspan,$body_y - $blurspan,$shadow_color,$fontnormal,$text,$blur); 
}

    // normal text
    imagettftextblur($outputimage,$body_size,0,$body_x,$body_y,$text_color,$fontnormal,$text);
    
    // titel
    imagettftextblur($outputimage,$title_size,0,$titel_x,$titel_y,$title_color,$fonttitle,$titel);
    



    imagedestroy($upload_image);
    imagedestroy($tmp);

    imagecopy($outputimage, $logoimage, $logo_x, $logo_y, 0, 0, $logowidth, $logoheight);

    // save merged image
    imagepng($outputimage, $destimagepath);
    if ( is_resource($upload_image) ) {
        imagedestroy($upload_image);
    }
    imagedestroy($outputimage);
    

    $mailcontent = "Tekst: \n" .  $text ."\n";
    $mailcontent .= "Titel: \n" .  $titel ."\n\n";
    $mailcontent .= "URL: \n";
    
    $mailcontent .= $destimageurl;
    if ( !PVB_DEBUG ) {
        mail("vanbuuren@gmail.com", "Stoel22 : plaatje : " . $titel, $mailcontent, "From: paul@wbvb.nl");
        mail("amoorahh@gmail.com", "Stoel22 : plaatje : " . $titel, $mailcontent, "From: paul@wbvb.nl");
    }
}


//==
function seoUrl($string) {
    //Lower case everything
    $string = strtolower($string);
    //Make alphanumeric (removes all other characters)
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    //Clean up multiple dashes or whitespaces
    $string = preg_replace("/[\s-]+/", " ", $string);
    //Convert whitespaces and underscore to dash
    $string = preg_replace("/[\s_]/", "-", $string);
    return $string;
}

function create_blank($width, $height){

    global $breedtelogo, $hoogtelogo;
    
    //create image with specified sizes
    $image = imagecreatetruecolor($width, $hoogtelogo);
    //saving all full alpha channel information
    imagesavealpha($image, true);
    //setting completely transparent color
    $transparent = imagecolorallocatealpha($image, 0, 90, 0, 127);
    //filling created image with transparent color
    imagefill($image, 0, 0, $transparent);


    
    return $image;
}

function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}




?>

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

<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
<div class="container">
<div class="starter-template">
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <p> <a href="index.php"><?php echo STOEL22_BACK ?></a> </p>
                    <h1><?php echo STOEL22_TITLE ?></h1>
                    <p class="lead"> <?php echo STOEL22_READY ?> </p>
                    <a href="<?=htmlspecialchars($destimageurl)?>" target="_blank">
                        <?=htmlspecialchars($destimageurl)?>
                        <img src="<?=$destimageurl?>" alt="Verhofstadt poster" /> </a> 
                    <p>&nbsp; </p>
                    <p> <a href="index.php"><?php echo STOEL22_BACK ?></a> </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container -->

</body>
</html>
