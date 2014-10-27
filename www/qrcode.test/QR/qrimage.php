<?php
/**
 * PHP QR Code encoder - Image output of code using GD2
 */

define('QR_IMAGE', true);

class QRimage {
  //----------------------------------------------------------------------
  public static function png($frame, $filename = false, $pixelPerPoint = 4, $outerFrame = 4,$saveandprint=false, $back_color, $fore_color){
    $image = self::image($frame, $pixelPerPoint, $outerFrame, $back_color, $fore_color);

    if ($filename === false) {
      header("Content-type: image/png");
      imagepng($image);
    } else {
      if($saveandprint=== true){
        imagepng($image, $filename);
        header("Content-type: image/png");
        imagepng($image);
      }else{
        imagepng($image, $filename);
      }
    }
    imagedestroy($image);
  }

  //----------------------------------------------------------------------
  public static function jpg($frame, $filename = false, $pixelPerPoint = 8, $outerFrame = 4, $q = 85){
    $image = self::image($frame, $pixelPerPoint, $outerFrame);

    if ($filename === false) {
      header("Content-type: image/jpeg");
      imagejpeg($image, null, $q);
    } else {
      imagejpeg($image, $filename, $q);
    }

    imagedestroy($image);
  }

  //----------------------------------------------------------------------
  private static function image($frame, $pixelPerPoint = 4, $outerFrame = 4, $back_color = 0xFFFFFF, $fore_color = 0x000000){
    $h = count($frame);
    $w = strlen($frame[0]);
    $imgW = $w + 2 * $outerFrame;
    $imgH = $h + 2 * $outerFrame;

    $base_image = ImageCreate($imgW, $imgH);

    $r1 = round((($fore_color & 0xFF0000) >> 16), 5);    // Convert a hexadecimal color code into decimal eps format (green = 0 1 0, blue = 0 0 1, ...)
    $b1 = round((($fore_color & 0x00FF00) >> 8), 5);
    $g1 = round(($fore_color & 0x0000FF), 5);

    $r2 = round((($back_color & 0xFF0000) >> 16), 5);    // Convert a hexadecimal color code into decimal eps format (green = 0 1 0, blue = 0 0 1, ...)
    $b2 = round((($back_color & 0x00FF00) >> 8), 5);
    $g2 = round(($back_color & 0x0000FF), 5);

    $col[0] = ImageColorAllocate($base_image,$r2,$b2,$g2);
    $col[1] = ImageColorAllocate($base_image,$r1,$b1,$g1);

    imagefill($base_image, 0, 0, $col[0]);

    for($y=0; $y<$h; $y++) {
      for($x=0; $x<$w; $x++) {
        if ($frame[$y][$x] == '1') ImageSetPixel($base_image,$x+$outerFrame,$y+$outerFrame,$col[1]);
      }
    }

    $target_image = imagecreate($imgW * $pixelPerPoint, $imgH * $pixelPerPoint);
    ImageCopyResized($target_image, $base_image, 0, 0, 0, 0, $imgW * $pixelPerPoint, $imgH * $pixelPerPoint, $imgW, $imgH);
    imagedestroy($base_image);
    return $target_image;
  }

}
