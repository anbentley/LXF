<?php
		$acceptedChars = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
		$stringlength = 5;
		$contrast = 60;
		$num_polygons = 3; // Number of triangles to draw.  0 = none
		$num_ellipses = 6;  // Number of ellipses to draw.  0 = none
		$num_lines = 0;  // Number of lines to draw.  0 = none
		$num_dots = 0;  // Number of dots to draw.  0 = none
		$min_thickness = 2;  // Minimum thickness in pixels of lines
		$max_thickness = 8;  // Maximum thickness in pixles of lines
		$min_radius = 5;  // Minimum radius in pixels of ellipses
		$max_radius = 15;  // Maximum radius in pixels of ellipses
		$object_alpha = 75;
		
		$min_thickness = max(1,$min_thickness);
		$max_thickness = min(20,$max_thickness);
		$min_radius *= 2;
		$max_radius *= 2;
		$contrast = 255 * ($contrast / 100.0);
		$o_contrast = 1.3 * $contrast;
		$width = 15 * imagefontwidth (5);
		$height = 2.5 * imagefontheight (5);
		$image = imagecreatetruecolor ($width, $height);
		imagealphablending($image, true);
		$black = imagecolorallocatealpha($image,0,0,0,0);
		$max = strlen($acceptedChars)-1;
		$captcha = NULL;
		for($i=0; $i < $stringlength; $i++) {
			$cnum[$i] = $acceptedChars{mt_rand(0, $max)};
			$captcha .= $cnum[$i];
		}
		$rotated = imagecreatetruecolor (70, 70);
		$x = 0;
		for ($i = 0; $i < $stringlength; $i++) {
			$buffer = imagecreatetruecolor (20, 20);
			$buffer2 = imagecreatetruecolor (40, 40);
			$red = mt_rand(0,255);
			$green = mt_rand(0,255);
			$blue = 255 - sqrt($red * $red + $green * $green);
			$color = imagecolorallocate ($buffer, $red, $green, $blue);
			imagestring($buffer, 5, 0, 0, $cnum[$i], $color);
			imagecopyresized ($buffer2, $buffer, 0, 0, 0, 0, 25 + mt_rand(0,12), 25 + mt_rand(0,12), 20, 20);
			$rotated = imagerotate($buffer2, mt_rand(-25, 25),imagecolorallocatealpha($buffer2,0,0,0,0)); 
			imagecolortransparent ($rotated, imagecolorallocatealpha($rotated,0,0,0,0));
			$y = mt_rand(1, 3);
			$x += mt_rand(2, 6); 
			imagecopymerge ($image, $rotated, $x, $y, 0, 0, 40, 40, 100);
			$x += 22;
			imagedestroy ($buffer); 
			imagedestroy ($buffer2); 
		}
		if ($num_polygons > 0) for ($i = 0; $i < $num_polygons; $i++) {
			$vertices = array (
				mt_rand(-0.25*$width,$width*1.25),mt_rand(-0.25*$width,$width*1.25),
				mt_rand(-0.25*$width,$width*1.25),mt_rand(-0.25*$width,$width*1.25),
				mt_rand(-0.25*$width,$width*1.25),mt_rand(-0.25*$width,$width*1.25)
			);
			$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), $object_alpha);
			imagefilledpolygon($image, $vertices, 3, $color);  
		}
		if ($num_ellipses > 0) for ($i = 0; $i < $num_ellipses; $i++) {
			$x1 = mt_rand(0,$width);
			$y1 = mt_rand(0,$height);
			$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), $object_alpha);
			imagefilledellipse($image, $x1, $y1, mt_rand($min_radius,$max_radius), mt_rand($min_radius,$max_radius), $color);  
		}
		if ($num_lines > 0) for ($i = 0; $i < $num_lines; $i++) {
			$x1 = mt_rand(-$width*0.25,$width*1.25);
			$y1 = mt_rand(-$height*0.25,$height*1.25);
			$x2 = mt_rand(-$width*0.25,$width*1.25);
			$y2 = mt_rand(-$height*0.25,$height*1.25);
			$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), $object_alpha);
			imagesetthickness ($image, mt_rand($min_thickness,$max_thickness));
			imageline($image, $x1, $y1, $x2, $y2 , $color);  
		}
		if ($num_dots > 0) for ($i = 0; $i < $num_dots; $i++) {
			$x1 = mt_rand(0,$width);
			$y1 = mt_rand(0,$height);
			$color = imagecolorallocatealpha ($image, mt_rand(0,$o_contrast), mt_rand(0,$o_contrast), mt_rand(0,$o_contrast),$object_alpha);
			imagesetpixel($image, $x1, $y1, $color);
		}
		$_SESSION['captcha'] = $captcha;
		header('Content-type: image/png');
		imagepng($image);
		imagedestroy($image);
?>