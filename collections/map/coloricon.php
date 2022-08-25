<?php
/**
* @author Greg Post
*/

include_once('../../config/symbini.php');

if (isset($_GET['shape'])){
    
    $iconFilePath = $SERVER_ROOT . '/images/mapicons/';
    $iconFileName = '';
    $iconFileExtension = '.png';

    if ($_GET['shape'] == 'triangle'){
        $iconFileName = 'IconTriangle50';
    }
    else {
        $iconFileName = 'IconCircle50';
    }
    
    $RBG_array = HexToRGB($_GET['color']);

    $iconFile  = $iconFilePath . $iconFileName . $iconFileExtension;
    $outputFilename = $iconFileName . $RBG_array['r'] .'_' . $RBG_array['g'] .'_' . $RBG_array['b'] . $iconFileExtension;
    colorizeBasedOnAplhaChannnel($iconFile, $RBG_array['r'], $RBG_array['g'], $RBG_array['b'], $outputFilename);
    exit;

} 


function colorizeBasedOnAplhaChannnel( $file, $targetR, $targetG, $targetB, $targetName) {

    $im_src = imagecreatefrompng( $file );
   
    $width = imagesx($im_src);
    $height = imagesy($im_src);
    $im_dst = imagecreatefrompng( $file );

    // Turn off alpha blending and set alpha flag
    imagealphablending($im_dst, false);

    // Fill transparent first (otherwise would result in black background)
    imagefill($im_dst, 0, 0, imagecolorallocatealpha($im_dst, 0, 0, 0, 127));


      for( $x=0; $x<$width; $x++ ) {
        for( $y=0; $y<$height; $y++ ) {

            $alpha = ( imagecolorat( $im_src, $x, $y ) >> 24 & 0xFF );

            $col = imagecolorallocatealpha( $im_dst,
                $targetR - (int) ( 1.0 / 255.0  * $alpha * (double) $targetR ),
                $targetG - (int) ( 1.0 / 255.0  * $alpha * (double) $targetG ),
                $targetB - (int) ( 1.0 / 255.0  * $alpha * (double) $targetB ),
                $alpha 
                );

            if ( false === $col ) {
                die( 'sorry, out of colors...' );
            }

            imagesetpixel( $im_dst, $x, $y, $col );

        }

    }

    header("Content-type: image/png");
    header("Content-Disposition: inline; filename=\"{$targetName}\"");
    imagesavealpha($im_dst, true);
    imagepng($im_dst);
    imagedestroy($im_dst);

}

// Convert Hex to RGB Value
function HexToRGB($hex) {
    $hex = str_replace("#", "", $hex);
    $color = array();

    if(strlen($hex) == 3) {
        $color['r'] = hexdec(substr($hex, 0, 1));
        $color['g'] = hexdec(substr($hex, 1, 1));
        $color['b'] = hexdec(substr($hex, 2, 1));
    }
    else if(strlen($hex) == 6) {
        $color['r'] = hexdec(substr($hex, 0, 2));
        $color['g'] = hexdec(substr($hex, 2, 2));
        $color['b'] = hexdec(substr($hex, 4, 2));
    }

    return $color;
    
}





?>