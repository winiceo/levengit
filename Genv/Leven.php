<?php
/*
ϵͳ���� hook;
�����ļ�;
*/
class Genv_Leven extends Genv_Base{
  
    protected static $_file;    
   
    protected function _postConstruct()    {
        parent::_postConstruct();
      
    }
	//��ͼ����ǰ;
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
     * ����ת������,�������ļ����б���ת��
     * ֧������ת��
     * GB2312��UTF-8 WITH BOMת��ΪUTF-8
     * UTF-8��UTF-8 WITH BOMת��ΪGB2312    
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
            echo "��֧�ִ�UNICODE LEת����UTF-8��GB����<br>";
            fclose($fpr);
            return;
        }
        elseif($char1==chr(254) && $char2==chr(255))//UNICODE BE
        {
            echo "��֧�ִ�UNICODE BEת����UTF-8��GB����<br>";
            fclose($fpr);
            return;
        }
        else//û���ļ�ͷ,������GB��UTF-8
        {
            if(rewind($fpr)===false)//�ص��ļ���ʼ����,׼�����ֽڶ�ȡ�жϱ���
            {
                echo $filename."�ļ�ָ�����ʧ��<br>";
                fclose($fpr);
                return;
            }

            while(!feof($fpr))
            {
                $char = fread($fpr,1);
                //����Ӣ��,GB��UTF-8���ǵ��ֽڵ�ASCII��С��128��ֵ
                if(ord($char)<128)
                    continue;

                //���ں���GB�����һ���ֽ���110*****�ڶ����ֽ���10******(������,��������)
                //UTF-8�����һ���ֽ���1110****�ڶ����ֽ���10******�������ֽ���10******
                //��λ��������Ҫ��������Ǻ���ͬ,����Ӧ�����ж�UTF-8
                //��Ϊʹ��GB�����밴λ��,UTF-8��111�ó�����Ҳ��110,����Ҫ���ж�UTF-8
                if((ord($char)&224)==224)
                {
                    //��һ���ֽ��ж�ͨ��
                    $char = fread($fpr,1);
                    if((ord($char)&128)==128)
                    {
                        //�ڶ����ֽ��ж�ͨ��
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
                    //��һ���ֽ��ж�ͨ��
                    $char = fread($fpr,1);
                    if((ord($char)&128)==128)
                    {
                        //�ڶ����ֽ��ж�ͨ��
                        $originEncoding = "GB2312";
                        break;
                    }
                }
            }
        }

        if(strtoupper($out_charset)==$originEncoding)
        {
            echo "�ļ�".$filename."ת�������,ԭʼ�ļ�����".$originEncoding."<br>";
            fclose($fpr);
        }
        else
        {
            //�ļ���Ҫת��
            $originContent = "";

            if($originEncoding == "UTF-8 WITH BOM")
            {
                //���������ֽ�,�Ѻ�������ݸ���һ��õ�utf-8������
                fseek($fpr,3);
                $originContent = fread($fpr,filesize($filename)-3);
                fclose($fpr);
            }
            elseif(rewind($fpr)!=false)//������UTF-8����GB2312,�ص��ļ���ʼ����,��ȡ����
            {
                $originContent = fread($fpr,filesize($filename));
                fclose($fpr);
            }
            else
            {
                echo "�ļ����벻��ȷ��ָ�����ʧ��<br>";
                fclose($fpr);
                return;
            }

            //ת�벢�����ļ�
            $content = iconv(str_replace(" WITH BOM","",$originEncoding),strtoupper($out_charset),$originContent);
            $fpw = fopen($filename,"w");
            fwrite($fpw,$content);
            fclose($fpw);

            if($originEncoding!="")
                echo "<font color=\"red\">���ļ�".$filename."ת�����,ԭʼ�ļ�����".$originEncoding.",ת�����ļ�����".strtoupper($out_charset)."</font><br>";
            elseif($originEncoding=="")
                echo "�ļ�".$filename."��û�г�������,���ǿ��Զ϶����Ǵ�BOM��UTF-8����,û�н��б���ת��,��Ӱ��ʹ��<br>";
        }
    }

    /**
     +----------------------------------------------------------
     * Ŀ¼��������
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $path      Ҫ������Ŀ¼��
     * @param string $mode      ����ģʽ,һ��ȡFILES,����ֻ���ش�·�����ļ���
     * @param array $file_types     �ļ���׺��������
     * @param int $maxdepth     �������,-1��ʾ��������ײ�
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
     * ��������ĿĿ¼�е�PHP��HTML�ļ��н�����ת��
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $app       Ҫ��������Ŀ·��
     * @param string $mode      ����ģʽ,һ��ȡFILES,����ֻ���ش�·�����ļ���
     * @param array $file_types     �ļ���׺��������
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function CodingSwitch($app = "./",$mode = "FILES",$file_types = array(".html",".php"))
    {
        echo "<b>ע��: ����ʹ�õ��ļ��������㷨���ܶ�ĳЩ�����ַ�������</b><br>";
        $filearr = self::searchdir($app,$mode,$file_types);
        foreach($filearr as $file)
            self::DetectAndSwitch($file,C("TEMPLATE_CHARSET"));
    }
	
	//����֪ͨ;
	function sendmsg($id){
	
	   D()->query('update g_user_info set noticeids=CONCAT(noticeids,",'.$id.'"),new_notice=new_notice+1');  
	
	}
	//�ϴ�ͼƬʱ ����ͼƬ;
	public	function img_process($res){

		I('@.Lib.Images');
		I('@.Lib.Myimg');
		$file= end(explode('/',$res)); 
		$res=SYSPATH."/Public/Upload/".$file;//ԭͼ;

		$pic_water=SYSPATH."/Public/1/".$file; //ˮӡͼ��;		 

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
		$t->setCutType(1);//��һ���OK��
		$t->setDstImg($pic_50);    
		$t->createImg(50,50);


 exit;

		$t = new Images();
		$t->setSrcImg($res);
		$t->setCutType(1);//��һ���OK��
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