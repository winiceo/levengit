<?php
/**
 * Abstract class for image
 *
 * @author ROY (2010-08-06)
 */
abstract class Genv_Image_Abstract extends Genv_Base
{
	/**
	 * Image Path
	 *
	 * @access	protected
	 * @var		string		Path to image
	 */
	protected $_image_path		= '';

	/**
	 * Image File
	 *
	 * @access	protected
	 * @var		string		Image file to work with
	 */
	protected $_image_file		= '';

	/**
	 * Image path + file
	 *
	 * @access	protected
	 * @var		string		Full image path and filename
	 */
	protected $_image_full		= '';

	/**
	 * Image dimensions
	 *
	 * @access	protected
	 * @var		array		Original Image Dimensions
	 */
	protected $_orig_dimensions	= array( 'width' => 0, 'height' => 0 );

	/**
	 * Image Types Supported
	 *
	 * @access	protected
	 * @var		array		Image types we can work with
	 */
	protected $_image_types	= array('gif', 'jpeg', 'jpg', 'jpe', 'png');

	/**
	 * Error encountered
	 *
	 * @access	public
	 * @var		string		Error Message
	 */
	public $error = '';
	/**
	 * Image current dimensions
	 *
	 * @access	public
	 * @var		array		Curernt/New Image Dimensions
	 */
	public $cur_dimensions = array( 'width' => 0, 'height' => 0 );

	/**
	 * Extension of image
	 *
	 * @access	public
	 * @var		string		Image extension
	 */
	public $image_extension	= '';

	/**
	 * Resize image anyways (e.g. if we have added watermark)
	 *
	 * @access	public
	 * @var		bool
	 */
	public $force_resize = false;

	/**
	 * Cleans up paths, generates var $in_file_complete
	 *
	 * @access	protected
	 * @param	string		Path to clean
	 * @return 	string		Cleaned path
	 */
	protected function _cleanPaths($path='')
	{
	 	//---------------------------------------------------------
	 	// Remove trailing slash
	 	//---------------------------------------------------------

		if(!empty($path))
		{
			$path = rtrim(Genv_Dir::fix($path), DIRECTORY_SEPARATOR);
		}
		return $path;
	}

	/**
	 *
	 *
	 * @author ROY
	 * @access  public
	 * @since 2010-08-06
	 * @version $$
	 * @package Genv_Image
	 * @copyright
	 */
	public function __destruct()
	{
	}
    /**
	 * Add a supported image type (assumes you have properly extended the class to add the support)
	 *
	 * @access	public
	 * @param	string 		Image extension type to add support for
	 * @return	boolean		Addition successful
	 */
	public function addType($ext)
	{
	 	//---------------------------------------------------------
	 	// Add a supported image extension
	 	//---------------------------------------------------------

		if(!in_array($ext, $this->_image_types))
		{
			$this->_image_types[] = $ext;
		}
		return true;
	}

    /**
	 * Get new dimensions for resizing
	 *
	 * @access	protected
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	array		[img_width,img_height]
	 */
	protected function _getResizeDimensions($width, $height)
	{
	 	//---------------------------------------------------------
	 	// Verify width and height are valid and > 0
	 	//---------------------------------------------------------

		$width	= intval($width);
		$height	= intval($height);

		if(!$width || !$height)
		{
			$this->error = $this->locale('BAD_DIMENSIONS');
			return false;
		}

	 	//---------------------------------------------------------
	 	// Is the current image already smaller?
	 	//---------------------------------------------------------

		if($width > $this->cur_dimensions['width']
		   && $height > $this->cur_dimensions['height'])
		{
			$this->error = $this->locale('ALREADY_SMALLER');
			return false;
		}

	 	//---------------------------------------------------------
	 	// Return new dimensions
	 	//---------------------------------------------------------

		return $this->_scale(
					array(
						'cur_height'	=> $this->cur_dimensions['height'],
						'cur_width'		=> $this->cur_dimensions['width'],
						'max_height'	=> $height,
						'max_width'		=> $width,
					));
	}

	/**
	 * Return proportionate image dimensions based on current and max dimension settings
	 *
	 * @access	protected
	 * @param	array 		[ cur_height, cur_width, max_width, max_height ]
	 * @return	array		[ img_height, img_width ]
	 */
	protected function _scale($arg)
	{
		$ret = array('img_width'  => $arg['cur_width'],
					 'img_height' => $arg['cur_height'],);

		if ($arg['cur_width'] > $arg['max_width'])
		{
			$ret['img_width']  = $arg['max_width'];
			$ret['img_height'] = ceil(($arg['cur_height'] * (($arg['max_width'] * 100) / $arg['cur_width'] )) / 100);
			$arg['cur_height'] = $ret['img_height'];
			$arg['cur_width']  = $ret['img_width'];
		}

		if ($arg['cur_height'] > $arg['max_height'])
		{
			$ret['img_height'] = $arg['max_height'];
			$ret['img_width'] = ceil(($arg['cur_width'] * (($arg['max_height'] * 100) / $arg['cur_height'])) / 100);
		}
		return $ret;
	}

    /**
	 * Resize image proportionately
	 *
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	array		Dimensons of the original image and the resized dimensions
	 */
	abstract public function resize($width, $height);

    /**
	 * Write image to file
	 *
	 * @param	string 		File location (including file name)
	 * @return	boolean		File write successful
	 */
	abstract public function write($path);

    /**
	 * Print image to screen
	 *
	 * @return	void		Image printed and script exits
	 */
	abstract public function fetch();

    /**
	 * Add watermark to image
	 *
	 * @param	string 		Watermark image path
	 * @param	integer		[Optional] Opacity 0-100
	 * @return	boolean		Watermark addition successful
	 */
	abstract public function watermark($path, $locate_x=10, $locate_y=10, $opacity = 100);

    /**
	 * Add copyright text to image
	 *
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, background color, font [1-5])
	 * @return	boolean		Watermark addition successful
	 */
	abstract public function copyrightText($text, $textOpts = array());

}
