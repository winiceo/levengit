<?php
 


define('ERR_INVALID_IMAGE',             1);
define('ERR_NO_GD',                     2);
define('ERR_IMAGE_NOT_EXISTS',          3);
define('ERR_DIRECTORY_READONLY',        4);
define('ERR_UPLOAD_FAILURE',            5);
define('ERR_INVALID_PARAM',             6);
define('ERR_INVALID_IMAGE_TYPE',        7);
$rootpath=Genv_Config::get('Genv', 'system');
define('ROOTPATH',$rootpath);
$lang=array(

 'directory_readonly' => '目录 % 不存在或不可写',
  'invalid_upload_image_type' => '不是允许的图片格式',
  'upload_failure' => '文件 %s 上传失败。',
  'missing_gd' => '没有安装GD库',
  'missing_orgin_image' => '找不到原始图片 %s ',
  'nonsupport_type' => '不支持该图像格式 %s ',
);
$GLOBALS['_LANG']=$lang;
class Genv_Image extends Genv_Base{
    var $error_no    = 0;
    var $error_msg   = '';
    var $images_dir  = 'images';
    var $data_dir    = 'data';
    var $bgcolor     = '';
    var $type_maping = array(1 => 'image/gif', 2 => 'image/jpeg', 3 => 'image/png');

    function __construct($bgcolor=''){
        $this->Images($bgcolor);
    }
 

    function Images($bgcolor=''){
        if ($bgcolor){
            $this->bgcolor = $bgcolor;
        }else{
            $this->bgcolor = "#FFFFFF";
        }

		 
    } 
	 /**
     * 图片上传的处理函数
     *
     * @access      public
     * @param       array       upload       包含上传的图片文件信息的数组
     * @param       array       dir          文件要上传在$this->data_dir下的目录名。如果为空图片放在则在$this->images_dir下以当月命名的目录下
     * @param       array       img_name     上传图片名称，为空则随机生成
     * @return      mix         如果成功则返回文件名，否则返回false
     */
    function upload_image($upload, $dir = '', $img_name = ''){
        /* 没有指定目录默认为根目录images */
        if (empty($dir)){
            /* 创建当月目录 */
            $dir = date('Ym');
            $dir = ROOTPATH . '/Public/' . $dir . '/';
        }
        else
        {
            /* 创建目录 */
            $dir = ROOTPATH .  '/Public/' . $dir . '/';
            if ($img_name)
            {
                $img_name = $dir . $img_name; // 将图片定位到正确地址
            }
        }


		  $locale = Genv_Registry::get('locale');
        
       //   $rs=  $locale->fetch('Genv_Image', 'ok', 1);
      //  dump($rs);

        /* 如果目标目录不存在，则创建它 */
        if (!file_exists($dir))
        {
		 
            if (!dirs_mk($dir))
            {
                /* 创建目录失败 */
                $this->error_msg = sprintf($GLOBALS['_LANG']['directory_readonly'], $dir);
                $this->error_no  = ERR_DIRECTORY_READONLY;

                return false;
            }
        }

	 
        if (empty($img_name)){
            $img_name = $this->unique_name($dir);
            $img_name = $dir . $img_name . $this->get_filetype($upload['name']);
        }

        if (!$this->check_img_type($upload['type']))
        {
            $this->error_msg = $GLOBALS['_LANG']['invalid_upload_image_type'];
            $this->error_no  =  ERR_INVALID_IMAGE_TYPE;
            return false;
        }

        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|JEPG|PNG|BMP|SWF|';
        if (!check_file_type($upload['tmp_name'], $img_name, $allow_file_types))
        {
            $this->error_msg = $GLOBALS['_LANG']['invalid_upload_image_type'];
            $this->error_no  =  ERR_INVALID_IMAGE_TYPE;
            return false;
        }
		
         if ($this->move_file($upload, $img_name)){
			 
            return str_replace(ROOTPATH, '', $img_name);
        }
        else
        {
            $this->error_msg = sprintf($GLOBALS['_LANG']['upload_failure'], $upload['name']);
            $this->error_no  = ERR_UPLOAD_FAILURE;

            return false;
        }
    }
	 /**
     * 创建图片的缩略图
     *
     * @access  public
     * @param   string      $src    原始图片的路径 
	 * @param   string      $dst    目标图片的路径
     * @param   int         $thumb_width  缩略图宽度
     * @param   int         $thumb_height 缩略图高度
     * @param   strint      $path         指定生成图片的目录名
     * @return  mix         如果成功返回缩略图的路径，失败则返回false
     */

	function make_thumb($src, $dst, $thumb_width, $thumb_height = 0, $quality = 100){
		if (function_exists('imagejpeg'))  {
        $func_imagecreate = function_exists('imagecreatetruecolor') ? 'imagecreatetruecolor' : 'imagecreate';
        $func_imagecopy = function_exists('imagecopyresampled') ? 'imagecopyresampled' : 'imagecopyresized';
        $dirpath = dirname($dst);
        if (!dirs_mk($dirpath, 0777)){
            return false;
        }
        $data = getimagesize($src);
		//dump($data);
        $src_width = $data[0];
        $src_height = $data[1];
        if ($thumb_height == 0)
        {
            if ($src_width > $src_height)
            {
                $thumb_height = $src_height * $thumb_width / $src_width;
            }
            else
            {
                $thumb_height = $thumb_width;
                $thumb_width = $src_width * $thumb_height / $src_height;
            }
            $dst_x = 0;
            $dst_y = 0;
            $dst_w = $thumb_width;
            $dst_h = $thumb_height;
        }
        else
        {
            if ($src_width / $src_height > $thumb_width / $thumb_height)
            {
                $dst_w = $thumb_width;
                $dst_h = ($dst_w * $src_height) / $src_width;
                $dst_x = 0;
                $dst_y = ($thumb_height - $dst_h) / 2;
            }
            else
            {
                $dst_h = $thumb_height;
                $dst_w = ($src_width * $dst_h) / $src_height;
                $dst_y = 0;
                $dst_x = ($thumb_width - $dst_w) / 2;
            }
        }

        switch ($data[2])
        {
            case 1:
                $im = imagecreatefromgif($src);
                break;
            case 2:
                $im = imagecreatefromjpeg($src);
                break;
            case 3:
                $im = imagecreatefrompng($src);
                break;
            default:
                trigger_error("Cannot process this picture format: " .$data['mime']);
                break;
        }
        $ni = $func_imagecreate($thumb_width, $thumb_height);
		//dump($ni);
        if ($func_imagecreate == 'imagecreatetruecolor')
        {
            imagefill($ni, 0, 0, imagecolorallocate($ni, 255, 255, 255));
        }
        else
        {
            imagecolorallocate($ni, 255, 255, 255);
        }
        $func_imagecopy($ni, $im, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $src_width, $src_height);
        imagejpeg($ni, $dst, $quality);
        return is_file($dst) ? $dst : false;
    }
    else{
        trigger_error("Unable to process picture.", E_USER_ERROR);
    }
  }
		/**
		 * 给图片添加水印
		 * @param filepath $src 待处理图片
		 * @param filepath $mark_img 水印图片路径
		 * @param string $position 水印位置 lt左上  rt右上  rb右下  lb左下 其余取值为中间
		 * @param int $quality jpg图片质量，仅对jpg有效 默认85 取值 0-100之间整数
		 * @param int $pct 水印图片融合度(透明度)
		 *
		 * @return void
		 */
		function water_mark($src, $mark_img, $position = 'rb', $quality = 85, $pct = 80,$mark_black) {


			if(function_exists('imagecopy') && function_exists('imagecopymerge')) {
				$data = getimagesize($src);
				if ($data[2] > 3)
				{
					return false;
				}
				
				$src_width = $data[0];
				$src_height = $data[1];
				$src_type = $data[2];
		
				$data = getimagesize($mark_img);
				$mark_width = $data[0];
				$mark_height = $data[1];
				$mark_type = $data[2];
 
				if ($src_width < ($mark_width + 20) || $src_width < ($mark_height + 20)){
					return false;
				}
				 
				switch ($src_type)
				{
					case 1:
						$src_im = imagecreatefromgif($src);
						$imagefunc = function_exists('imagejpeg') ? 'imagejpeg' : '';
						break;
					case 2:
						$src_im = imagecreatefromjpeg($src);
						$imagefunc = function_exists('imagegif') ? 'imagejpeg' : '';
						break;
					case 3:
						$src_im = imagecreatefrompng($src);
						$imagefunc = function_exists('imagepng') ? 'imagejpeg' : '';
						break;
				}



				switch ($position)
				{
					case 'lt':
						$x = 10;
						$y = 10;
						break;
					case 'rt':
						$x = $src_width - $mark_width - 10;
						$y = 10;
						break;
					case 'rb':
						$x = $src_width - $mark_width - 10;
						$y = $src_height - $mark_height - 10;
						break;
					case 'lb':
						$x = 10;
						$y = $src_height - $mark_height - 10;
						break;
					default:
						$x = ($src_width - $mark_width - 10) / 2;
						$y = ($src_height - $mark_height - 10) / 2;
						break;
				}

				$aa=$this->getAvgGray($src_im,$x,$y,152,76);
				 
				if($aa>128){
				   $mark_img=$mark_black;
				
				}
				 

				switch ($mark_type)
				{
					case 1:
						$mark_im = imagecreatefromgif($mark_img);
						break;
					case 2:
						$mark_im = imagecreatefromjpeg($mark_img);
						break;
					case 3:
						$mark_im = imagecreatefrompng($mark_img);
						break;
				}

				

				if (function_exists('imagealphablending')) imageAlphaBlending($mark_im, true);
				imageCopyMerge($src_im, $mark_im, $x, $y, 0, 0, $mark_width, $mark_height, $pct);

				if ($src_type == 2)
				{
					$imagefunc($src_im, $src, $quality);
				}
				else
				{
					$imagefunc($dst_photo, $src);
				}
				return $src;
			}
	}
    /**
     *  检查水印图片是否合法
     *
     * @access  public
     * @param   string      $path       图片路径
     *
     * @return boolen
     */
    function validate_image($path)
    {
        if (empty($path))
        {
            $this->error_msg = $GLOBALS['_LANG']['empty_watermark'];
            $this->error_no  = ERR_INVALID_PARAM;

            return false;
        }

        /* 文件是否存在 */
        if (!is_file($path))
        {
            $this->error_msg = sprintf($GLOBALS['_LANG']['missing_watermark'], $path);
            $this->error_no = ERR_IMAGE_NOT_EXISTS;
			 
            return false;
        }

        // 获得文件以及源文件的信息
        $image_info     = @getimagesize($path);

        if (!$image_info)
        {
            $this->error_msg = sprintf($GLOBALS['_LANG']['invalid_image_type'], $path);
            $this->error_no = ERR_INVALID_IMAGE;
            return false;
        }

        /* 检查处理函数是否存在 */
        if (!$this->check_img_function($image_info[2]))
        {
            $this->error_msg = sprintf($GLOBALS['_LANG']['nonsupport_type'], $this->type_maping[$image_info[2]]);
            $this->error_no  =  ERR_NO_GD;
            return false;
        }

        return true;
    }

    /**
     * 返回错误信息
     *
     * @return  string   错误信息
     */
    function error_msg()
    {
        return $this->error_msg;
    }

    /*------------------------------------------------------ */
    //-- 工具函数
    /*------------------------------------------------------ */

    /**
     * 检查图片类型
     * @param   string  $img_type   图片类型
     * @return  bool
     */
    function check_img_type($img_type)
    {
        return $img_type == 'image/pjpeg' ||
               $img_type == 'image/x-png' ||
               $img_type == 'image/png'   ||
               $img_type == 'image/gif'   ||
               $img_type == 'image/jpeg';
    }

    /**
     * 检查图片处理能力
     *
     * @access  public
     * @param   string  $img_type   图片类型
     * @return  void
     */
    function check_img_function($img_type)
    {
        switch ($img_type)
        {
            case 'image/gif':
            case 1:

                if (PHP_VERSION >= '4.3')
                {
                    return function_exists('imagecreatefromgif');
                }
                else
                {
                    return (imagetypes() & IMG_GIF) > 0;
                }
            break;

            case 'image/pjpeg':
            case 'image/jpeg':
            case 2:
                if (PHP_VERSION >= '4.3')
                {
                    return function_exists('imagecreatefromjpeg');
                }
                else
                {
                    return (imagetypes() & IMG_JPG) > 0;
                }
            break;

            case 'image/x-png':
            case 'image/png':
            case 3:
                if (PHP_VERSION >= '4.3'){
                     return function_exists('imagecreatefrompng');
                }else{
                    return (imagetypes() & IMG_PNG) > 0;
                }
            break;

            default:
                return false;
        }
    }

    /**
     * 生成随机的数字串
     *
     * @author: weber liu
     * @return string
     */
    function random_filename()
    {
        $str = '';
        for($i = 0; $i < 9; $i++)
        {
            $str .= mt_rand(0, 9);
        }

        return gmtime() . $str;
    }

    /**
     *  生成指定目录不重名的文件名
     *
     * @access  public
     * @param   string      $dir        要检查是否有同名文件的目录
     *
     * @return  string      文件名
     */
    function unique_name($dir)
    {
        $filename = '';
        while (empty($filename))
        {
            $filename = Genv_Image::random_filename();
            if (is_file($dir . $filename . '.jpg') || file_exists($dir . $filename . '.gif') || is_file($dir . $filename . '.png'))
            {
                $filename = '';
            }
        }

        return $filename;
    }

    /**
     *  返回文件后缀名，如‘.php’
     *
     * @access  public
     * @param
     *
     * @return  string      文件后缀名
     */
    function get_filetype($path)
    {
        $pos = strrpos($path, '.');
        if ($pos !== false)
        {
            return substr($path, $pos);
        }
        else
        {
            return '';
        }
    }

     /**
     * 根据来源文件的文件类型创建一个图像操作的标识符
     *
     * @access  public
     * @param   string      $img_file   图片文件的路径
     * @param   string      $mime_type  图片文件的文件类型
     * @return  resource    如果成功则返回图像操作标志符，反之则返回错误代码
     */
    function img_resource($img_file, $mime_type)
    {
        switch ($mime_type)
        {
            case 1:
            case 'image/gif':
                $res = imagecreatefromgif($img_file);
                break;

            case 2:
            case 'image/pjpeg':
            case 'image/jpeg':
                $res = imagecreatefromjpeg($img_file);
                break;

            case 3:
            case 'image/x-png':
            case 'image/png':
                $res = imagecreatefrompng($img_file);
                break;

            default:
                return false;
        }

        return $res;
    }

    /**
     * 获得服务器上的 GD 版本
     *
     * @return      int         可能的值为0，1，2
     */
    function gd_version(){
        static $version = -1;
        if ($version >= 0){
            return $version;
        }
        if (!extension_loaded('gd')){
            $version = 0;
        }else{
            // 尝试使用gd_info函数
            if (PHP_VERSION >= '4.3'){
                if (function_exists('gd_info')){
                    $ver_info = gd_info();
                    preg_match('/\d/', $ver_info['GD Version'], $match);
                    $version = $match[0];
                }else{
                    if (function_exists('imagecreatetruecolor')){
                        $version = 2;
                    }
                    elseif (function_exists('imagecreate')){
                        $version = 1;
                    }
                }
            }
            else
            {
                if (preg_match('/phpinfo/', ini_get('disable_functions')))
                {
                    /* 如果phpinfo被禁用，无法确定gd版本 */
                    $version = 1;
                }
                else
                {
                  // 使用phpinfo函数
                   ob_start();
                   phpinfo(8);
                   $info = ob_get_contents();
                   ob_end_clean();
                   $info = stristr($info, 'gd version');
                   preg_match('/\d/', $info, $match);
                   $version = $match[0];
                }
             }
        }

        return $version;
     }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function move_file($upload, $target){
        if (isset($upload['error']) && $upload['error'] > 0){
            return false;
        }
		 
        if (!move_uploaded_file($upload['tmp_name'], $target)){
			
             return false;
        } 
        return true;
    }

	
 /**
* 获取($x, $y)坐标处颜色的灰度
*
* @param resource $im
* @param int $x
* @param int $y
* @return int 0-255 灰度值
*/
function getgray($im, $x, $y)
{
	//获取($x, $y)处的rgb值
	$rgb = imagecolorat($im, $x, $y);
	//计算灰度
	$red = ($rgb >> 16) & 0xFF;
	$green = ($rgb >> 8 )&  0xFF;
	$blue = $rgb & 0xFF;
	$gray = round(.299*$red + .587*$green + .114*$blue);
	return $gray;
}

/**
* 获取图像区域的平均灰度
* 总的灰度值/总的像素数
*
* @param resource $im
* @param int $x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 起始坐标（水印的起始位置或结束位置）
* @param int $y
* @param int $width&nbsp;&nbsp;&nbsp; 水印宽
* @param int $height
* @param bool $end&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 水印是否为结束位置
* @return int 0-255
*/
function getAvgGray($im, $x, $y, $width, $height, $end=false)
{
	$avggray = $gray = 0;
	if ($end) {
	//当传入的($x, $y)坐标为结束位置时 则结束位置为($x,$y)
	$x_width = $x;
	$y_height= $y;
	//开始位置 (结束位置 - 水印宽高)
	$x = $x - $width;
	$y = $y - $height;
	} else {
	$x_width = $x+$width;
	$y_height= $y+$height;
	}
	for ($i = $x; $i <= $x_width; $i++) {
	for ($j = $y; $j <= $y_height; $j++) {
	$gray += $this->getgray($im, $i, $j);
	}
	}
	$avggray = round($gray/($width*$height));
	return $avggray;
}
}


?>