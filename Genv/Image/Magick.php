<?php
/**
 * ImageMagick Example
 * <code>
 * $image = new classImageImagemagick();
 * $image->init( array(
 * 					'image_path'	=> "/path/to/images/",
 * 					'image_file'	=> "image_filename.jpg",
 *					'im_path'		=> '/path/to/imagemagick/folder/',
 *					'temp_path'		=> '/tmp/',
 * 				));
 *
 * if( $image->error )
 * {
 * 	print $image->error;exit;
 * }
 *
 * Set max width and height $image->resize( 600, 480 );
 * // Add a watermark $image->watermark(
 * "/path/to/watermark/trans.png" ); //$image->copyrightText(
 * "Hello World!", array( 'color' => '#ffffff', 'font' => 3 ) );
 * $image->fetch();
 * </code>
 */

class Genv_Image_Magick extends Genv_Image_Abstract
{
	protected $_Genv_Image_Magick = array(

			'image_path' => '',

			'image_file' => '',

			// ImageMagick Path
			// Path to imagemagick binary (folder, no trailing slash)
			'im_path' => '',
			// Temp file (directory, name, .temp)
			// Image quality settings
			'temp_file' => '',
	);

	private $_im_path = '';
	private $_temp_file = '';
	private $_temp_path = '';
	private $_quality	= array( 'png' => 8, 'jpg' => 75 );

	protected function _postConfig()
    {
		// do some preparing for image path
		$this->_image_path = $this->_cleanPaths(
										$this->_config['image_path']
									);

		// get the real file name of param in-coming
		$this->_image_file = basename($this->_config['image_file']);

		// get full path of image
		$this->_image_full = $this->_config['image_path'] . '/' .
							 $this->_config['image_file'];

		// do some preparing for image magick path
		$this->_im_path = $this->_cleanPaths(
										$this->_config['im_path']
									);

		// do some preparing for temp path
		$this->_temp_path = $this->_cleanPaths(
										$this->_config['temp_path']
									);

		// get temp file with combination of temp_path and imgae_file
		// with extension .temp
		$this->_temp_file = $this->_config['temp_path'] . '/' .
									  $this->_image_file . '.temp';

		//---------------------------------------------------------
		// Quality values
		//---------------------------------------------------------

		if(isset($this->_config['jpg_quality'])
		   && $this->_config['jpg_quality'])
		{
			$this->_quality['jpg'] = $this->_config['jpg_quality'];
		}

		if(isset($this->_config['png_quality'])
		   && $this->_config['png_quality'])
		{
			$this->_quality['png'] = $this->_quality['png_quality'];
		}

    }
	/**
	 * @author ROY (2010-08-06)
	 * @access  protected
	 * @since 2010-08-06
	 * @version $1.0$
	 * @package Genv_Image
	 * @copyright Genv Frmaework for PHP 5
	 */
	protected function _postConstruct()
	{
		//---------------------------------------------------------
	 	// Check paths and files
	 	//---------------------------------------------------------
		if(!is_dir($this->_im_path))
		{
			$this->error = $this->locale('BAD_IMAGE_MAGICK_PATH');
			return false;
		}

		if(!is_dir( $this->_temp_path ))
		{
			$this->error = $this->locale('BAD_TEMP_PATH');
			return false;
		}

		if(!is_writable($this->_temp_path))
		{
			$this->error = $this->locale('TEMP_PATH_NOT_WRITABLE');
			return false;
		}

		if(file_exists($this->_temp_file))
		{
			@unlink($this->_temp_file);
		}
	 	//---------------------------------------------------------
	 	// Get image extension
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
	 	// Get and store dimensions
	 	//---------------------------------------------------------

		$dimensions = getimagesize($this->_image_full);

		$this->_orig_dimensions	= array('width' => $dimensions[0],
										'height' => $dimensions[1]);

		$this->cur_dimensions = $this->_orig_dimensions;
	}

    /**
	 * Resize image proportionately
	 *
	 * @access	public
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	array		Dimensons of the original image and the
	 *  	   resized dimensions
	 */
	public function resize($width, $height)
	{
	 	//---------------------------------------------------------
	 	// Grab proportionate dimensions and remember
	 	//---------------------------------------------------------

	 	$new_dims = $this->_getResizeDimensions($width, $height);

		if( !is_array($new_dims)
			|| !count($new_dims)
			|| !$new_dims['img_width'])
		{
			if( !$this->force_resize )
			{
				return array();
			}
			else
			{
				$new_dims['width']	= $width;
				$new_dims['height']	= $height;
			}
		}

		$this->cur_dimensions = array('width' => $new_dims['img_width'],
									  'height' => $new_dims['img_height']);

		//---------------------------------------------------------
		// Need image type for quality setting
		//---------------------------------------------------------

		$type = strtolower(pathinfo(basename($this->_image_full),
									PATHINFO_EXTENSION));
		$quality = '';

		if($type == 'jpg' || $type == 'jpeg')
		{
			$quality	= " -quality {$this->_quality['jpg']}";
		}
		else if($type == 'png')
		{
			$quality	= " -quality {$this->_quality['png']}";
		}

	 	//---------------------------------------------------------
	 	// Resize image to temp file
	 	//---------------------------------------------------------

	 	system("{$this->_im_path}/convert{$quality} -geometry {$new_dims['img_width']}x{$new_dims['img_height']} {$this->_image_full} {$this->_temp_file}");

	 	//---------------------------------------------------------
	 	// Successful?
	 	//---------------------------------------------------------

	 	if(file_exists($this->_temp_file))
	 	{
		 	return array(
				'originalWidth'  => $this->_orig_dimensions['width'],
				'originalHeight' => $this->_orig_dimensions['height'],
				'newWidth'       => $new_dims['img_width'],
			    'newHeight'      => $new_dims['img_height'],
			);
	 	}
	 	else
	 	{
		 	return array();
	 	}
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
	 	// Temp file doesn't exist
	 	//---------------------------------------------------------

		if(!file_exists($this->_temp_file))
		{
	 		$this->error = $this->locale('TEMP_IMAGE_NOT_EXISTS');
		 	return false;
		}

	 	//---------------------------------------------------------
	 	// Rename temp file to final destination
	 	//---------------------------------------------------------

		rename($this->_temp_file, $path);

		if(!is_file($path) || !file_exists($path))
		{
	 		$this->error = $this->locale('UNABLE_TO_WRITE_IMAGE');
		 	return false;
	 	}

	 	//---------------------------------------------------------
	 	// Chmod 777 and return
	 	//---------------------------------------------------------
	 	@chmod($path, 0777);
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
	 	// Print appropriate header
	 	//---------------------------------------------------------

		switch($this->image_extension)
		{
			case 'gif':
				@header('Content-type: image/gif');
			break;

			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@header('Content-Type: image/jpeg' );
			break;

			case 'png':
				@header('Content-Type: image/png' );
			break;
		}

	 	//---------------------------------------------------------
	 	// Print file contents and exit
	 	//---------------------------------------------------------

		print file_get_contents($this->_temp_file);
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

		$type = strtolower( pathinfo( basename($path), PATHINFO_EXTENSION ) );
		$opacity	= $opacity > 100 ? 100 : ( $opacity < 0 ? 1 : $opacity );

		if(!in_array($type, $this->_image_types))
		{
			$this->error = $this->locale('BAD_WATERMARK_TYPE');
			return false;
		}

	 	//---------------------------------------------------------
	 	// Get dimensions
	 	//---------------------------------------------------------

	 	$img_info	= @getimagesize( $path );
	 	$locate_x	= $this->cur_dimensions['width'] - $img_info[0] - $locate_x;
	 	$locate_y	= $this->cur_dimensions['height'] - $img_info[1] - $locate_y;

	 	//---------------------------------------------------------
	 	// Working with original file or temp file?
	 	//---------------------------------------------------------

		$file = file_exists($this->_temp_file)?
				$this->_temp_file:$this->_image_full;

	 	//---------------------------------------------------------
	 	// Apply watermark and verify
	 	//---------------------------------------------------------

		system("{$this->_im_path}/composite -geometry +{$locate_x}+{$locate_y} {$path} {$file} {$this->_temp_file}");

	 	if(file_exists($this->_temp_file))
	 	{
	 		$this->force_resize	= true;

		 	return true;
	 	}
	 	else
	 	{
		 	return false;
	 	}
	}

    /**
	 * Add copyright text to image
	 *
	 * @access	public
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, halign, valign, font [1-5])
	 * @return	boolean		Watermark addition successful
	 */
	public function copyrightText($text, $textOpts=array())
	{
	 	//---------------------------------------------------------
	 	// Have text?
	 	//---------------------------------------------------------

		if(!$text)
		{
	 		$this->error = $this->locale('NO_TEXT_COPYRIGHT');
		 	return false;
	 	}

	 	//---------------------------------------------------------
	 	// @ causes IM to try to read text from file specified by @
	 	//---------------------------------------------------------
	 	$text = ltrim($text, '@');

	 	//---------------------------------------------------------
	 	// Verify options
	 	//---------------------------------------------------------

		$font = $textOpts['font']?$textOpts['font']:3;

		$family = $textOpts['family']?
							$textOpts['family']:'YouYuan';

		$color = $textOpts['color']?$textOpts['color'] : '#ffffff';

		$width	= $this->cur_dimensions['width'] - 10;

		$halign	= (isset($textOpts['halign'])
				   && in_array($textOpts['halign'],
							   array('right','center', 'left'))
				   )?$textOpts['halign'] : 'right';

		$valign	= (isset($textOpts['valign'])
				   && in_array($textOpts['valign'],
							   array('top', 'middle', 'bottom'))
				   )?$textOpts['valign']:'bottom';

	 	//---------------------------------------------------------
	 	// Working with orig file or temp file?
	 	//---------------------------------------------------------

		$file = file_exists($this->_temp_file)?
				$this->_temp_file:$this->_image_full;

	 	//---------------------------------------------------------
	 	// Set gravity (location of text)
	 	//---------------------------------------------------------

		$gravity = "";

		switch($valign)
		{
			case 'top':
				$gravity = "North";
			break;

			case 'bottom':
				$gravity = "South";
			break;
		}

		if($valign == 'middle' && $halign == 'center')
		{
			$gravity = "Center";
		}

		switch($halign)
		{
			case 'right':
				$gravity .= "East";
			break;

			case 'left':
				$gravity .= "West";
			break;
		}

	 	//---------------------------------------------------------
	 	// Apply annotation to image and verify
	 	//---------------------------------------------------------

		system("{$this->_im_path}/convert {$file} -font {$family} -fill ".
			"{$color} -undercolor #ffffff -gravity {$gravity} -annotate +0+5 ".
			"{$text} {$this->_temp_file}");

	 	if(file_exists($this->_temp_file))
	 	{
	 		$this->force_resize	= true;

		 	return true;
	 	}
	 	else
	 	{
		 	return false;
	 	}
	}

    /**
	 * Image handler desctructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
	 	//---------------------------------------------------------
	 	// Remove temp file if it hasn't been saved
	 	//---------------------------------------------------------

		if(file_exists($this->_temp_file))
		{
			@unlink($this->_temp_file);
		}

		parent::__destruct();
	}

}
