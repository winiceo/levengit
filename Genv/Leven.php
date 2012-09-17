<?php
/*
系统钩子 hook;
加载文件;
*/
class Genv_Leven extends Genv_Base{
  
    protected static $_file;    
   
    protected function _postConstruct()    {
        parent::_postConstruct();
      
    }
	//视图加载前;
	public static function _preview(){	
		 $a=array("_public"=>APPPUBLIC,
					'_url'=>__URL__,
					'_action'=>__ACTION__,
					'_self'=>__SELF__,
					'_app'=>__APP__,
					'random_num'=>rand(),
					'mid'=>getgpc('mid'),
					'WEB_PUBLIC_URL'=>WEB_PUBLIC_URL				
		   );

		 
		  $view=Genv::factory('Genv_View');
		  $view->assign($a);
	
	}
	 
	 /**
     +----------------------------------------------------------
     * 编码转换函数,对整个文件进行编码转换
     * 支持以下转换
     * GB2312、UTF-8 WITH BOM转换为UTF-8
     * UTF-8、UTF-8 WITH BOM转换为GB2312    
     */
    static function DetectAndSwitch($filename,$out_charset)
    {
        $fpr = fopen($filename,"r");
        $char1 = fread($fpr,1);
        $char2 = fread($fpr,1);
        $char3 = fread($fpr,1);

        $originEncoding = "";

        if($char1==chr(239) && $char2==chr(187) && $char3==chr(191))//UTF-8 WITH BOM
            $originEncoding = "UTF-8 WITH BOM";
        elseif($char1==chr(255) && $char2==chr(254))//UNICODE LE
        {
            echo "不支持从UNICODE LE转换到UTF-8或GB编码<br>";
            fclose($fpr);
            return;
        }
        elseif($char1==chr(254) && $char2==chr(255))//UNICODE BE
        {
            echo "不支持从UNICODE BE转换到UTF-8或GB编码<br>";
            fclose($fpr);
            return;
        }
        else//没有文件头,可能是GB或UTF-8
        {
            if(rewind($fpr)===false)//回到文件开始部分,准备逐字节读取判断编码
            {
                echo $filename."文件指针后移失败<br>";
                fclose($fpr);
                return;
            }

            while(!feof($fpr))
            {
                $char = fread($fpr,1);
                //对于英文,GB和UTF-8都是单字节的ASCII码小于128的值
                if(ord($char)<128)
                    continue;

                //对于汉字GB编码第一个字节是110*****第二个字节是10******(有特例,比如联字)
                //UTF-8编码第一个字节是1110****第二个字节是10******第三个字节是10******
                //按位与出来结果要跟上面非星号相同,所以应该先判断UTF-8
                //因为使用GB的掩码按位与,UTF-8的111得出来的也是110,所以要先判断UTF-8
                if((ord($char)&224)==224)
                {
                    //第一个字节判断通过
                    $char = fread($fpr,1);
                    if((ord($char)&128)==128)
                    {
                        //第二个字节判断通过
                        $char = fread($fpr,1);
                        if((ord($char)&128)==128)
                        {
                            $originEncoding = "UTF-8";
                            break;
                        }
                    }
                }
                if((ord($char)&192)==192)
                {
                    //第一个字节判断通过
                    $char = fread($fpr,1);
                    if((ord($char)&128)==128)
                    {
                        //第二个字节判断通过
                        $originEncoding = "GB2312";
                        break;
                    }
                }
            }
        }

        if(strtoupper($out_charset)==$originEncoding)
        {
            echo "文件".$filename."转码检查完成,原始文件编码".$originEncoding."<br>";
            fclose($fpr);
        }
        else
        {
            //文件需要转码
            $originContent = "";

            if($originEncoding == "UTF-8 WITH BOM")
            {
                //跳过三个字节,把后面的内容复制一遍得到utf-8的内容
                fseek($fpr,3);
                $originContent = fread($fpr,filesize($filename)-3);
                fclose($fpr);
            }
            elseif(rewind($fpr)!=false)//不管是UTF-8还是GB2312,回到文件开始部分,读取内容
            {
                $originContent = fread($fpr,filesize($filename));
                fclose($fpr);
            }
            else
            {
                echo "文件编码不正确或指针后移失败<br>";
                fclose($fpr);
                return;
            }

            //转码并保存文件
            $content = iconv(str_replace(" WITH BOM","",$originEncoding),strtoupper($out_charset),$originContent);
            $fpw = fopen($filename,"w");
            fwrite($fpw,$content);
            fclose($fpw);

            if($originEncoding!="")
                echo "<font color=\"red\">对文件".$filename."转码完成,原始文件编码".$originEncoding.",转换后文件编码".strtoupper($out_charset)."</font><br>";
            elseif($originEncoding=="")
                echo "文件".$filename."中没有出现中文,但是可以断定不是带BOM的UTF-8编码,没有进行编码转换,不影响使用<br>";
        }
    }

    /**
     +----------------------------------------------------------
     * 目录遍历函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $path      要遍历的目录名
     * @param string $mode      遍历模式,一般取FILES,这样只返回带路径的文件名
     * @param array $file_types     文件后缀过滤数组
     * @param int $maxdepth     遍历深度,-1表示遍历到最底层
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function searchdir($path,$mode = "FULL",$file_types = array(".html",".php",".txt"),$maxdepth = -1,$d = 0)
    {
       if(substr($path,strlen($path)-1) != '/')
           $path .= '/';
       $dirlist = array();
       if($mode != "FILES")
            $dirlist[] = $path;
       if($handle = @opendir($path))
       {
           while(false !== ($file = readdir($handle)))
           {
               if($file != '.' && $file != '..')
               {
                   $file = $path.$file ;
                   if(!is_dir($file))
                   {
                        if($mode != "DIRS")
                        {
                            $extension = "";
                            $extpos = strrpos($file, '.');
                            if($extpos!==false)
                                $extension = substr($file,$extpos,strlen($file)-$extpos);
                            $extension=strtolower($extension);
                            if(in_array($extension, $file_types))
                                $dirlist[] = $file;
                        }
                   }
                   elseif($d >= 0 && ($d < $maxdepth || $maxdepth < 0))
                   {
                       $result = self::searchdir($file.'/',$mode,$file_types,$maxdepth,$d + 1) ;
                       $dirlist = array_merge($dirlist,$result);
                   }
               }
           }
           closedir ( $handle ) ;
       }
       if($d == 0)
           natcasesort($dirlist);

       return($dirlist) ;
    }

    /**
     +----------------------------------------------------------
     * 对整个项目目录中的PHP和HTML文件行进编码转换
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $app       要遍历的项目路径
     * @param string $mode      遍历模式,一般取FILES,这样只返回带路径的文件名
     * @param array $file_types     文件后缀过滤数组
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function CodingSwitch($app = "./",$mode = "FILES",$file_types = array(".html",".php"))
    {
        echo "<b>注意: 程序使用的文件编码检测算法可能对某些特殊字符不适用</b><br>";
        $filearr = self::searchdir($app,$mode,$file_types);
        foreach($filearr as $file)
            self::DetectAndSwitch($file,C("TEMPLATE_CHARSET"));
    }
	
	//发送通知;
	function sendmsg($id){
	
	   D()->query('update g_user_info set noticeids=CONCAT(noticeids,",'.$id.'"),new_notice=new_notice+1');  
	
	}
	//上传图片时 处理图片;
	public	function img_process($res){

		I('@.Lib.Images');
		I('@.Lib.Myimg');
		$file= end(explode('/',$res)); 
		$res=SYSPATH."/Public/Upload/".$file;//原图;

		$pic_water=SYSPATH."/Public/1/".$file; //水印图像;		 

		$pic_50=SYSPATH."/Public/50/".$file;

		$pic_213=SYSPATH."/Public/213/".$file;	

		$pic_290=SYSPATH."/Public/290/".$file;

		$water=SYSPATH."/Public/white.gif";


		/* $img = new Myimg();
		 $img->loadFile($res)->resize(50,50)->save($pic_50);
		 $img->loadFile($res)->resize(213,213)->save($pic_213);
		 $img->loadFile($res)->resize(213,213)->save($pic_213);
		 $img->loadFile($res)->resize(290)->save($pic_290);
		*/
		 
		$t = new Images();
		$t->setSrcImg($res);
		$t->setCutType(1);//这一句就OK了
		$t->setDstImg($pic_50);    
		$t->createImg(50,50);


 exit;

		$t = new Images();
		$t->setSrcImg($res);
		$t->setCutType(1);//这一句就OK了
		$t->setDstImg($pic_213);    
		$t->createImg(213,213);
			

		$img=new Genv_Image();				 
		$img->make_thumb($res,$pic_290,290);
		 
		if (copy($res, $pic_water)) {
			$img=new Genv_Image();
			$water1=SYSPATH."/Public/black.gif";
			$img->water_mark($pic_water, $water, $position = 'rb', $quality = 80, $pct = 30,$water1);	
		}	  
	}
 
    
}?>