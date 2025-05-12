<?php
// Set the content type header to image/jpeg
header('Content-Type: image/jpeg');

// Create a 400x400 image
$image = imagecreatetruecolor(400, 400);

// Define colors
$bg_color = imagecolorallocate($image, 240, 240, 240);
$text_color = imagecolorallocate($image, 100, 100, 100);

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add text
$font_size = 5;
$text = "No Image Available";
$text_box = imagettfbbox($font_size, 0, 'arial.ttf', $text);
$text_width = abs($text_box[4] - $text_box[0]);
$text_height = abs($text_box[5] - $text_box[1]);
$x = (400 - $text_width) / 2;
$y = (400 - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Output image
imagejpeg($image);

// Free memory
imagedestroy($image);
?>
