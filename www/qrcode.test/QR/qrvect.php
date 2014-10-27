<?php
/**
 * PHP QR Code encoder - Image output of code using GD2
 */

define('QR_VECT', true);

class QRvect {

  //----------------------------------------------------------------------
  public static function eps($frame, $filename = false, $pixelPerPoint = 4, $outerFrame = 4,$saveandprint=FALSE, $back_color = 0xFFFFFF, $fore_color = 0x000000, $cmyk = false) {
    $vect = self::vectEPS($frame, $pixelPerPoint, $outerFrame, $back_color, $fore_color, $cmyk);

    if ($filename === false) {
      header("Content-Type: application/postscript");
      header('Content-Disposition: filename="qrcode.eps"');
      echo $vect;
    } else {
      if($saveandprint===TRUE){
        QRtools::save($vect, $filename);
        header("Content-Type: application/postscript");
        header('Content-Disposition: filename="'.$filename.'"');
        echo $vect;
      }else{
        QRtools::save($vect, $filename);
      }
    }
  }

  //----------------------------------------------------------------------
  private static function vectEPS($frame, $pixelPerPoint = 4, $outerFrame = 4, $back_color = 0xFFFFFF, $fore_color = 0x000000, $cmyk = false) {
    $h = count($frame);
    $w = strlen($frame[0]);
    
    $imgW = $w + 2*$outerFrame;
    $imgH = $h + 2*$outerFrame;
    if ($cmyk) {

      $c = round((($fore_color & 0xFF000000) >> 16) / 255, 5);      // convert color value into decimal eps format
      $m = round((($fore_color & 0x00FF0000) >> 16) / 255, 5);
      $y = round((($fore_color & 0x0000FF00) >> 8) / 255, 5);
      $k = round(($fore_color & 0x000000FF) / 255, 5);
      $fore_color_string = $c.' '.$m.' '.$y.' '.$k.' setcmykcolor'."\n";
      $c = round((($back_color & 0xFF000000) >> 16) / 255, 5);                // convert color value into decimal eps format
      $m = round((($back_color & 0x00FF0000) >> 16) / 255, 5);
      $y = round((($back_color & 0x0000FF00) >> 8) / 255, 5);
      $k = round(($back_color & 0x000000FF) / 255, 5);
      $back_color_string = $c.' '.$m.' '.$y.' '.$k.' setcmykcolor'."\n";
    }else{
      $r = round((($fore_color & 0xFF0000) >> 16) / 255, 5);      // convert a hexadecimal color code into decimal eps format (green = 0 1 0, blue = 0 0 1, ...)
      $b = round((($fore_color & 0x00FF00) >> 8) / 255, 5);
      $g = round(($fore_color & 0x0000FF) / 255, 5);
      $fore_color_string = $r.' '.$b.' '.$g.' setrgbcolor'."\n";
      $r = round((($back_color & 0xFF0000) >> 16) / 255, 5);      // convert a hexadecimal color code into decimal eps format (green = 0 1 0, blue = 0 0 1, ...)
      $b = round((($back_color & 0x00FF00) >> 8) / 255, 5);
      $g = round(($back_color & 0x0000FF) / 255, 5);
      $back_color_string = $r.' '.$b.' '.$g.' setrgbcolor'."\n";
    }
    $output = '%!PS-Adobe EPSF-3.0'."\n".'%%Creator: Zend_Matrixcode_Qrcode'."\n".'%%Title: QRcode'."\n".'%%CreationDate: '.date('Y-m-d')."\n".
              '%%DocumentData: Clean7Bit'."\n".'%%LanguageLevel: 2'."\n".'%%Pages: 1'."\n".'%%BoundingBox: 0 0 '.$imgW * $pixelPerPoint.' '.$imgH * $pixelPerPoint."\n";
    $output .= $pixelPerPoint.' '.$pixelPerPoint.' scale'."\n";          // set the scale
    $output .= $outerFrame.' '.$outerFrame.' translate'."\n";            // position the center of the coordinate system
    $output .= '/F { rectfill } def'."\n";                               // redefine the 'rectfill' operator to shorten the syntax
    $output .= $back_color_string;                                       // set the symbol color
    $output .= '-'.$outerFrame.' -'.$outerFrame.' '.($w + 2*$outerFrame).' '.($h + 2*$outerFrame).' F'."\n";
    $output .= $fore_color_string;                                       // set the symbol color
    for($i=0; $i<$h; $i++) {                                             // Convert the matrix into pixels
      for($j=0; $j<$w; $j++) {
        if( $frame[$i][$j] == '1') {
          $y = $h - 1 - $i;
          $x = $j;
          $output .= $x.' '.$y.' 1 1 F'."\n";
        }
      }
    }
    return($output . '%%EOF');
  }

  //----------------------------------------------------------------------
  public static function svg($frame, $filename = false, $pixelPerPoint = 4, $outerFrame = 4,$saveandprint=FALSE, $back_color, $fore_color) {
    $vect = self::vectSVG($frame, $pixelPerPoint, $outerFrame, $back_color, $fore_color);
    if ($filename === false) {
      header("Content-Type: image/svg+xml");
      header('Content-Disposition: filename="qrcode.svg"');
      return $vect;
    } else {
      if($saveandprint===TRUE){
        QRtools::save($vect, $filename);
        header("Content-Type: image/svg+xml");
        header('Content-Disposition: filename="'.$filename.'"');
        return $vect;
      }else{
        QRtools::save($vect, $filename);
      }
    }
  }

  //----------------------------------------------------------------------
  private static function vectSVG($frame, $pixelPerPoint = 4, $outerFrame = 4, $back_color = 0xFFFFFF, $fore_color = 0x000000) {
    $h = count($frame);
    $w = strlen($frame[0]);

    $imgW = $w + 2*$outerFrame;
    $imgH = $h + 2*$outerFrame;

    $output = '<?xml version="1.0" encoding="utf-8"?>'."\n".
              '<svg version="1.1" baseProfile="full"  width="'.$imgW * $pixelPerPoint.'" height="'.$imgH * $pixelPerPoint.'" viewBox="0 0 '.$imgW * $pixelPerPoint.' '.$imgH * $pixelPerPoint.'"
               xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events">'."\n".
              '<desc></desc>'."\n";
    $output = '<?xml version="1.0" encoding="utf-8"?>'."\n".
              '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">'."\n".
              '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" xmlns:xlink="http://www.w3.org/1999/xlink" width="'.$imgW * $pixelPerPoint.
              '" height="'.$imgH * $pixelPerPoint.'" viewBox="0 0 '.$imgW * $pixelPerPoint.' '.$imgH * $pixelPerPoint.'">'."\n".'<desc></desc>'."\n";

    if(!empty($back_color)) {
      $backgroundcolor = str_pad(dechex($back_color), 6, "0");
      $output .= '<rect width="'.$imgW * $pixelPerPoint.'" height="'.$imgH * $pixelPerPoint.'" fill="#'.$backgroundcolor.'" cx="0" cy="0" />'."\n";
    }

    $output .= '<defs>'."\n".'<rect id="p" width="'.$pixelPerPoint.'" height="'.$pixelPerPoint.'" />'."\n".'</defs>'."\n".'<g fill="#'.str_pad(dechex($fore_color), 6, "0").'">'."\n";

    for($i=0; $i<$h; $i++) {            // Convert the matrix into pixels
      for($j=0; $j<$w; $j++) {
        if( $frame[$i][$j] == '1') {
          $y = ($i + $outerFrame) * $pixelPerPoint;
          $x = ($j + $outerFrame) * $pixelPerPoint;
          $output .= '<use x="'.$x.'" y="'.$y.'" xlink:href="#p" />'."\n";
        }
      }
    }
    return($output . '</g>' . "\n" . '</svg>');
  }

};

