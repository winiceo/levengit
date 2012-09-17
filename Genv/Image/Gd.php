<?php
/**
 *
 * GD Example
 * <code>
 * $image = new classImageGd();
 * $image->init( array(
 * 					'image_path'	=> "/path/to/images/",
 * 					'image_file'	=> "image_filename.jpg",
 * 			)		);
 *
 * if( $image->error )
 * {
 * 	print $image->error;exit;
 * }
 *
 * Set max width and height
 * $image->resizeImage( 600, 480 );
 * // Add a watermark
 * $image->addWatermark( "/path/to/watermark/trans.png" );
 * //$image->addCopyrightText( "Hello World!", array( 'color' => '#ffffff', 'font' => 3 ) );
 * $image->displayImage();
 * </code>
 */

class Genv_Image_Gd extends Genv_Image_Abstract
{
	protected $_Genv_Image_Gd = array(
		'image_path' => '',
		'image_file' => '',
	);

	/**
	 * Image resource
	 *
	 * @access	private
	 * @var		resource	Image resource
	 */
	private $image = null;

	/**
	 * Image quality settings
	 *
	 * @access	public
	 * @var		array 		Image quality settings
	 */
	protected $_quality	= array('png' => 8, 'jpg' => 75);

	protected function _postConfig()
	{

		//---------------------------------------------------------
	 	// Store paths
	 	//---------------------------------------------------------

		$this->_image_path = $this->_cleanPaths($this->_config['image_path']);
		$this->_image_file = $this->_config['image_file'];
		$this->_image_full = $this->_image_path . '/' . $this->_image_file;
		//---------------------------------------------------------
		// Quality values
		//---------------------------------------------------------

		if(isset($this->_config['jpg_quality'])
		   && $this->_config['jpg_quality'])
		{
			$this->_quality['jpg']	= $this->_config['jpg_quality'];
		}

		if(isset($this->_config['png_quality'])
		   && $this->_config['png_quality'])
		{
			$this->_quality['png']	= $this->_config['png_quality'];
		}
	}

	protected function _postConstruct()
	{
		//---------------------------------------------------------
	 	// Verify input
	 	//---------------------------------------------------------

		if(!isset($this->_config['image_path'])
		   || !$this->_config['image_path'])
		{
			$this->error = $this->locale('NO_IMAGE_PATH');
			return false;
		}

		if(!isset($this->_config['image_file'])
		   || !$this->_config['image_file'])
		{
			$this->error = $this->locale('NO_IMAGE_FILE');
			return false;
		}

	 	//---------------------------------------------------------
	 	// Get extension
	 	//---------------------------------------------------------

		$this->image_extension = strtolower(pathinfo($this->_image_file,
													 PATHINFO_EXTENSION));

	 	//---------------------------------------------------------
	 	// Verify this is a valid image type
	 	//---------------------------------------------------------

		if(!in_array($this->image_extension, $this->_image_types))
		{
			$this->error = $this->locale('IMAGE_NOT_SUPPORTED');
			return false;
		}



	 	//---------------------------------------------------------
	 	// Get and remember dimensions
	 	//---------------------------------------------------------

		$dimensions = getimagesize($this->_image_full);

		$this->_orig_dimensions	= array('width' => $dimensions[0],
										'height' => $dimensions[1]);
		$this->cur_dimensions	= $this->_orig_dimensions;

	 	//---------------------------------------------------------
	 	// Create image resource
	 	//---------------------------------------------------------

		switch( $this->image_extension )
		{
			case 'gif':
				$this->image = @imagecreatefromgif($this->_image_full);
			break;

			case 'jpeg':
			case 'jpg':
			case 'jpe':
				$this->image = @imagecreatefromjpeg($this->_image_full);
			break;

			case 'png':
				$this->image = @imagecreatefrompng($this->_image_full);
			break;
		}

		if(!$this->image)
		{
			if($this->image =
			   @imagecreatefromstring(file_get_contents($this->_image_full))
			  )
			{
				return true;
			}
			return false;
		}
		else
		{
			return true;
		}
	}

    /**
	 * Resize image proportionately
	 *
	 * @access	public
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	array		Dimensons of the original image and the resized dimensions
	 */
	public function resize($width, $height)
	{
	 	//---------------------------------------------------------
	 	// Get proportionate dimensions and store
	 	//---------------------------------------------------------

	 	$new_dims = $this->_getResizeDimensions($width, $height);

		if(!is_array($new_dims)
		   || !count($new_dims)
		   || !$new_dims['img_width'])
		{
			if(!$this->force_resize)
			{
				return array();
			}
			else
			{
				$new_dims['width']	= $width;
				$new_dims['height']	= $height;
			}
		}

	 	//---------------------------------------------------------
	 	// Create new image resource
	 	//---------------------------------------------------------

		$new_img = imagecreatetruecolor($new_dims['img_width'],
										$new_dims['img_height']);

	 	if(!$new_img)
	 	{
	 		$this->error = $this->locale('IMAGE_CREATION_FAILED');
		 	return array();
	 	}

	 	//---------------------------------------------------------
	 	// Apply alpha blending
	 	//---------------------------------------------------------

		switch($this->image_extension)
		{
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				imagealphablending($new_img, TRUE);
			break;
			case 'png':
				imagealphablending($new_img, FALSE);
				imagesavealpha($new_img, TRUE);
			break;
		}

	 	//---------------------------------------------------------
	 	// Copy image resampled
	 	//---------------------------------------------------------

	 	@imagecopyresampled($new_img, $this->image, 0, 0, 0 ,0,
							$new_dims['img_width'], $new_dims['img_height'],
							$this->cur_dimensions['width'],
							$this->cur_dimensions['height']);

	 	$this->cur_dimensions = array('width' => $new_dims['img_width'],
									  'height' => $new_dims['img_height']);

	 	//---------------------------------------------------------
	 	// Don't forget the alpha blending
	 	//---------------------------------------------------------

		switch($this->image_extension)
		{
			case 'png':
				imagealphablending($new_img, FALSE);
				imagesavealpha($new_img, TRUE);
			break;
		}

	 	//---------------------------------------------------------
	 	// Destroy original resource and store new resource
	 	//---------------------------------------------------------

	 	@imagedestroy($this->image);

	 	$this->image = $new_img;

	 	return array('originalWidth'  => $this->_orig_dimensions['width'],
					 'originalHeight' => $this->_orig_dimensions['height'],
					 'newWidth'       => $new_dims['img_width'],
					 'newHeight'      => $new_dims['img_height'] );
	}

    /**
	 * Write image to file
	 *
	 * @access	public
	 * @param	string 		File location (including file name)
	 * @return	boolean		File write successful
	 */
	public function write($path)
	{
	 	//---------------------------------------------------------
	 	// Remove image if it exists
	 	//---------------------------------------------------------

		if(file_exists($path))
		{
			@unlink($path);
		}

	 	//---------------------------------------------------------
	 	// Write file and verify
	 	//---------------------------------------------------------

		switch($this->image_extension)
		{
			case 'gif':
				@imagegif( $this->image, $path );
			break;

			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@imagejpeg($this->image, $path, $this->_quality['jpg']);
			break;

			case 'png':
				@imagepng($this->image, $path, $this->_quality['png']);
			break;
		}

		if(!file_exists($path))
		{
	 		$this->error = $this->locale('UNABLE_TO_WRITE_IMAGE');
		 	return false;
	 	}

	 	//---------------------------------------------------------
	 	// Chmod 777
	 	//---------------------------------------------------------

	 	@chmod($path, 0777);

	 	//---------------------------------------------------------
	 	// Destroy image resource
	 	//---------------------------------------------------------
	 	@imagedestroy($this->image);
	 	return true;
	}

    /**
	 * Print image to screen
	 *
	 * @access	public
	 * @return	void		Image printed and script exits
	 */
	public function fetch()
	{
	 	//---------------------------------------------------------
	 	// Send appropriate header and output image
	 	//---------------------------------------------------------

		switch($this->image_extension)
		{
			case 'gif':
				@header('Content-type: image/gif');
				@imagegif( $this->image);
			break;

			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@header('Content-Type: image/jpeg');
				@imagejpeg( $this->image, null, $this->_quality['jpg']);
			break;

			case 'png':
				@header('Content-Type: image/png' );
				@imagepng($this->image, null, $this->_quality['png']);
			break;
		}

	 	//---------------------------------------------------------
	 	// Destroy image resource
	 	//---------------------------------------------------------

	 	@imagedestroy($this->image);
		exit;
	}


    /**
	 * Add watermark to image
	 *
	 * @access	public
	 * @param	string 		Watermark image path
	 * @param	integer		[Optional] Opacity 0-100
	 * @return	boolean		Watermark addition successful
	 */
	public function watermark($path, $locate_x=10, $locate_y=10, $opacity=100)
	{
	 	//---------------------------------------------------------
	 	// Verify input
	 	//---------------------------------------------------------

		if(!$path)
		{
			$this->error = $this->locale('NO_WATERMARK_PATH');
			return false;
		}

		$type = strtolower(pathinfo(basename($path), PATHINFO_EXTENSION));
		$opacity = $opacity > 100 ? 100 : ($opacity < 0 ? 1 : $opacity);

		if(!in_array($type, $this->_image_types))
		{
			$this->error = $this->locale('BAD_WATERMARK_TYPE');
			return false;
		}

	 	//---------------------------------------------------------
	 	// Create resource from watermark and verify
	 	//---------------------------------------------------------

		switch($type)
		{
			case 'gif':
				$mark = imagecreatefromgif($path);
			break;

			case 'jpeg':
			case 'jpg':
			case 'jpe':
				$mark = imagecreatefromjpeg($path);
			break;

			case 'png':
				$mark = imagecreatefrompng($path);
			break;
		}

		if(!$mark)
		{
	 		$this->error = $this->locale('IMAGE_CREATION_FAILED');
		 	return false;
	 	}

	 	//---------------------------------------------------------
	 	// Alpha blending..
	 	//---------------------------------------------------------

		switch($this->image_extension)
		{
			case 'jpeg':
			case 'jpg':
			case 'jpe':
			case 'png':
				@imagealphablending($this->image, TRUE);
			break;
		}

	 	//---------------------------------------------------------
	 	// Get dimensions of watermark
	 	//---------------------------------------------------------

	 	$img_info		= @getimagesize($path);
	 	$locate_x		= $this->cur_dimensions['width'] - $img_info[0]- $locate_x;
	 	$locate_y		= $this->cur_dimensions['height'] - $img_info[1]- $locate_y;

	 	//---------------------------------------------------------
	 	// Merge watermark image onto original image
	 	//---------------------------------------------------------

	 	if($type == 'png')
 		{
	 		@imagecopy($this->image, $mark, $locate_x, $locate_y, 0, 0,
					    $img_info[0], $img_info[1]);
 		}
 		else
		{
			@imagecopymerge($this->image, $mark, $locate_x, $locate_y, 0, 0,
							 $img_info[0], $img_info[1], $opacity);
		}

	 	//---------------------------------------------------------
	 	// And alpha blending again...
	 	//---------------------------------------------------------

		switch($this->image_extension)
		{
			case 'png':
				@imagealphablending($this->image, FALSE);
				@imagesavealpha($this->image, TRUE);
			break;
		}

	 	//---------------------------------------------------------
	 	// Destroy watermark image resource and return
	 	//---------------------------------------------------------

	 	imagedestroy($mark);
		$this->force_resize	= true;
	 	return true;
	}

    /**
	 * Add copyright text to image
	 *
	 * @access	public
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, halign, valign, padding, font [1-5])
	 * @return	boolean		Watermark addition successful
	 */
	public function copyrightText($text, $textOpts=array())
	{
	 	//---------------------------------------------------------
	 	// Verify input
	 	//---------------------------------------------------------

		if(!$text)
		{
	 		$this->error = $this->locale('NO_TEXT_FOR_COPYRIGHT');
		 	return false;
	 	}

		$font = (isset($textOpts['font']) && !empty($textOpts['font']))
				 ? $textOpts['font'] : 3;

		$size = (isset($textOpts['size']) && !empty($textOpts['size']))
				 ? $textOpts['size'] : 10;

		$angle = (isset($textOpts['angle']) && !empty($textOpts['angle']))
				 ? $textOpts['angle'] : 0;

		if(!isset($textOpts['family']) || empty($textOpts['family']))
		{
			$this->error = $this->locale('NO_FONT_FAMILY');
			return false;
		}
		$family =  $textOpts['family'];

	 	//---------------------------------------------------------
	 	// Colors input as hex...convert to rgb
	 	//---------------------------------------------------------

		$color	= (isset($textOpts['color']) && $textOpts['color']) ?
				  array(
					hexdec(substr(ltrim($textOpts['color'], '#'), 0, 2)),
					hexdec(substr(ltrim($textOpts['color'], '#'), 2, 2)),
					hexdec(substr(ltrim( $textOpts['color'], '#'), 4, 2))
				  ) : array(255, 255, 255);

		// image withd, use pixel
		$width		= $this->cur_dimensions['width'] - 10;

		// where to lay copytext, horizontal
		$halign		= (isset($textOpts['halign'])
					   && in_array($textOpts['halign'],
								   array('right', 'center', 'left'))
					  ) ? $textOpts['halign'] : 'right';

		// where to lay copytext, vertical
		$valign		= (isset($textOpts['valign'])
					   && in_array($textOpts['valign'],
								   array('top', 'middle', 'bottom'))
					  ) ? $textOpts['valign'] : 'bottom';

		// padding pixel
		$padding	= $textOpts['padding'] 	? $textOpts['padding'] : 5;

		// get dimension of text in-coming, use pixel
		$dmnins = @imagettfbbox($size, $angle, $family, Genv_Ascii::encode($text));
		$dmnin =  $dmnins[2];

	 	//---------------------------------------------------------
	 	// Get some size info and set properties
	 	//---------------------------------------------------------

		$fontwidth	= $dmnin/strlen($text);
		$fontheight	= 22;

		$margin 	= floor($padding / 2);
		if ($width > 0)
		{
			// $maxcharsperline	= floor(($width - ($margin * 2)) / $fontwidth);
			   $max_chars_perline = floor((($width - ($margin * 2)) / $dmnin) * strlen($text));

			   $real_max_chars_perline = strlen(Genv_String::substr($text, $max_chars_perline, false));

			   $text = wordwrap($text, $real_max_chars_perline, "\n", 1);
		}

		$lines 					= explode("\n", $text);

	 	//---------------------------------------------------------
	 	// Top, middle or bottom?
	 	//---------------------------------------------------------

		switch($valign)
		{
			case "middle":
				$y = (imagesy($this->image) - ($fontheight * count($lines)))/2;
				break;

			case "bottom":
				$y = imagesy($this->image) -
					(($fontheight * count($lines)) + $margin);
				break;

			default:
				$y = $margin;
				break;
		}

	 	//---------------------------------------------------------
	 	// Allocate colors for text/bg
	 	//---------------------------------------------------------

		$color		= imagecolorallocate($this->image, $color[0],
										 $color[1], $color[2] );
		$rect_back	= imagecolorallocate($this->image, 0,0,0);

	 	//---------------------------------------------------------
	 	// Switch on horizontal position and write text lines
	 	//---------------------------------------------------------

		switch($halign)
		{
			case "right":
				while(list($numl, $line) = each($lines))
				{
					 $x = (imagesx($this->image) - $fontwidth * strlen($line)) - $margin;
					//imagefilledrectangle($this->image, $x, $y, imagesx($this->image) - 1, $y+$fontheight, $rect_back);
					imagettftext($this->image, $size, $angle, $x, $y, $color,
								$family, Genv_Ascii::encode($line));
					// imagestring($this->image, $font, $x, $y, $line, $color);

					$y += $fontheight;
				}
				break;

			case "center":
				while(list($numl, $line) = each($lines))
				{
					$x = floor((imagesx($this->image) - $fontwidth * strlen($line)) / 2);
					//imagefilledrectangle($this->image, floor((imagesx($this->image) - $fontwidth * strlen($line)) / 2), $y, imagesx($this->image), imagesy($this->image), $rect_back);
					imagettftext($this->image, $size, $angle, $x, $y, $color,
								$family, Genv_Ascii::encode($line));
					//imagestring($this->image, $font, floor((imagesx($this->image) - $fontwidth * strlen($line)) / 2), $y, $line, $color);

					$y += $fontheight;
				}
			break;

			default:
				while(list($numl, $line) = each($lines))
				{
					//imagefilledrectangle($this->image, $margin, $y, imagesx($this->image), imagesy($this->image), $rect_back);
					imagettftext($this->image, $size, $angle, $margin, $y, $color,
								$family, Genv_Ascii::encode($line));
					//imagestring($this->image, $font, $margin, $y, $line, $color );
					$y += $fontheight;
				}
			break;
		}

		$this->force_resize	= true;

		return true;
	}
}