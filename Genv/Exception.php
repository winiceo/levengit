<?php

class Genv_Exception extends Exception{
    /**
     * 
     * User-defined information array.
     * 
     * @var array
     * 
     */
    protected $_info = array();
    
    /**
     * 
     * Class where the exception originated.
     * 
     * @var array
     * 
     */
    protected $_class;
    
    /**
     * 
     * Constructor.
     * 
     * @param array $config Configuration value overrides, if any.
     * for 'class', 'code', 'text', and 'info'.
     * 
     */
    public function __construct($config = null)
    {
        $default = array(
            'class' => '',
            'code'  => '',
            'text'  => '',
            'info'  => array(),
        );
        $config = array_merge($default, (array) $config);
        
        parent::__construct($config['text']);
        $this->code = $config['code'];
        $this->_class = $config['class'];
        $this->_info = (array) $config['info'];
    }
    
    /**
     * 
     * Returnes the exception as a string.
     * 
     * @return void
     * 
     */
    public function __toString()
    {
        $class_code = $this->_class . "::" . $this->code;
        
        // basic string
        $str = "exception '" . get_class($this) . "'\n"
             . "class::code '$class_code' \n"
             . "with message '" . $this->message . "' \n"
             . "information " . var_export($this->_info, true) . " \n"
             . "Stack trace:\n"
             . "  " . str_replace("\n", "\n  ", $this->getTraceAsString());
        
        // at the CLI, repeat the message so it shows up as the last line
        // of output, not the trace.
        if (PHP_SAPI == 'cli') {
            $str .= "\n\n{$this->message}";
        }
		log(get_class($this),'',$str);

		//echo 333;
		// return  "333";
        
        // done
        return $str;
    }
    
    /**
     * 
     * Returns user-defined information.
     * 
     * @param string $key A particular info key to return; if empty, returns
     * all info.
     * 
     * @return array
     * 
     */
    final public function getInfo($key = null)
    {
        if (empty($key)) {
            return $this->_info;
        } else {
            return $this->_info[$key];
        }
    }
    
    /**
     * 
     * Returns the class name that threw the exception.
     * 
     * @return string
     * 
     */
    final public function getClass()
    {
        return $this->_class;
    }
    
    /**
     * 
     * Returns the class name and code together.
     * 
     * @return string
     * 
     */
    final public function getClassCode()
    {
        return $this->_class . '::' . $this->code;
    }
}


ini_set('html_errors', 0);
set_error_handler('setErrorHandler');
set_exception_handler('setExceptionHandler');
register_shutdown_function('shutdown');


function setExceptionHandler($e){
	//echo 'Uncaught '.get_class($e).', code: ' . $e->getCode() . "<br />Message: " . htmlentities($e->getMessage());
	//Doo::loadHelper('Genv_Html');
	$err = printVar($e);
	global $errTrace;
	$errTrace = Genv_Html::highlightPHP($err);
	if(preg_match_all('/\[file\] \=\> (.+)\n/', $err, $matches)){
		//print_r($matches);
		$last = $matches[sizeof($matches)-1];
		$lastfile = $last[sizeof($last)-1];
		
		preg_match_all('/\[line\] \=\> (.+)\n/', $err, $matches);
		$last2 = $matches[sizeof($matches)-1];
		$lastline = $last2[sizeof($last2)-1];
		
		if($e instanceof PDOException){
			foreach($last as $k=>$l){
				if(strpos(str_replace('\\','/',$l), str_replace('\\','/',APPPATH ))===0){
					$lastfile = $l;
					break;
				}
			}
			$lastline = $last2[$k];
		}
		
		setErrorHandler('Exception', $e->getMessage(), $lastfile, $lastline);
	}
}

function setErrorHandler($errno, $errstr, $errfile, $errline, $errcontext=null){
  

	if($errno==2 && strpos($errstr, 'require_once(')===0 && strpos(str_replace('\\','/',$errfile), str_replace('\\','/',GenvPath ).'Genv.php')===0){
		$dmsg = debug_backtrace();
		foreach($dmsg as $k=>$a){
			//if is user file, and must be a load method
			if(strpos(str_replace('\\','/',$a['file']), str_replace('\\','/',APPPATH ))===0
				&& stripos($a['function'], 'load')===0 ){
				//print_r($a);exit;
				$errfile = $a['file'];
				$errline = $a['line'];
				break;
			}
		}
	}
    else if($errno==2 && strpos(str_replace('\\','/',$errfile), str_replace('\\','/',C('BASE_PATH')).'view/DooView.php')===0){
		$dmsg = debug_backtrace();
		foreach($dmsg as $k=>$a){
            if(strpos(str_replace('\\','/',$a['file']), str_replace('\\','/',APPPATH))===0
                && stripos(str_replace('\\','/',$a['file']), '/controller')!==false){
                    $errfile = $a['file'];
                    $errline = $a['line'];
                    if(strpos($errstr, 'file_get_contents')!==false){
                       $errstr = 'Template not found ' . str_replace('[<a href=\'function.file-get-contents\'>function.file-get-contents</a>]: failed to open stream: ','',str_replace('file_get_contents','',$errstr));
                    }
                    break;
            }
        }
    }
	//是系统的就不显示球 了;要不也太不安全了;
	//if(strpos(str_replace('\\','/',$errfile), str_replace('\\','/',C('BASE_PATH')))===0){
		$dmsg = debug_backtrace();
		$last = array_pop($dmsg);
		if(isset($last['file'])){
//			$errfile = $last['file'];
//			$errline = $last['line'];
		}else{
			$errfile = $dmsg[0]['args'][2];
			$errline = $dmsg[0]['args'][3];
		}
	//}
	
    $script = file_get_contents($errfile);
    $script = Genv_Html::highlightPHP($script);

	$pre = '';
    $Xpre = '';

	if(!empty($script)){
        //if template file addon the <pre>
       // if(strpos(str_replace('\\','/',$errfile), str_replace('\\','/',APPPATH.C('PROTECTED_FOLDER')).'view')===0){
            $pre = '<pre>';
            $Xpre = '</pre>';
       // }

        $lines = explode('<br />', $script);

        $errLineContent = '<div class="errorbig" onclick="javascript:viewCode();">'.$pre.$lines[$errline-1].$Xpre.'</div>';

        //highlight the error line
		$lines[$errline-1] = '<div id="eerrline" class="errline" onclick="javascript:closeCode();" title="Click to close"><a id="'.$errline.'" name="'.$errline.'"></a>' . $lines[$errline-1] . '</div>';

		//remove the code tag from 1st and last line
		$lines[0] = str_replace('<code>','',$lines[0]);
		$lines[sizeof($lines)-1] = str_replace('</code>','',$lines[sizeof($lines)-1]);
	}
	
    $imgloader = C('SUBFOLDER') . 'index.php?doodiagnostic_pic=';

	if (ob_get_level() !== 0) {
		ob_clean();
	}
	$confData = traceVar(C());
	$getData = traceVar($_GET);
	$postData = traceVar($_POST);
    if(isset ($_SESSION))
        $sessionData = traceVar($_SESSION);
    if(isset ($_COOKIE))
        $cookieData = traceVar($_COOKIE);


    echo "<html><head><title>Genv - $errstr</title>";
	echo '<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />';
	
    //css
    echo '<style>
            html{padding:0px;margin:0px;}
            body{
                background-color:#62C4E1;padding:0px;margin:0px;font-family:"Consolas","Monaco","Bitstream Vera Sans Mono","Courier New",Courier,monospace;
            }
            #logo{
                height:130px;width:260px;text-indent:-9999px;
                float:left;cursor:pointer;
                background:transparent url('. $imgloader .'logo-b2.png) no-repeat center left;
            }
            #header {
                min-height:130px;
                background:#142830 url('.$imgloader.'bg-footer.gif) repeat-x scroll 0 0;
            }
            #header h1, #content h2{margin:0px;letter-spacing:-1px;}
            #header h1{
                letter-spacing:-2px;font-size:26px;color:#FFD000;padding:40px 0px 20px 10px;
            }
            #errorFile{font-family:Georgia,serif;font-weight:normal;padding:10px;}
            #errorLine{font-family:Georgia;font-size:18px;padding:0px;}

            #content{
                padding:20px 20px 0px 20px;
                background:#62C4E1 url('.$imgloader.'bg-down.png) repeat-x scroll 0 0;
            }
            .errorbig, .errline{
                background:#6CE26C;
            }
            .errorbig{background:#fff;border:1px solid #c0ff0e;padding:20px;margin:12px;cursor:pointer;}
            #eerrline{cursor:pointer;}
            .scriptcode{
                display:none;padding:30px;border:1px solid #d9d9d9;margin:20px;padding-top:15px;
                background:transparent url('.$imgloader.'bgcode.png);
            }
            hr{color:#BDE6F2;}
            ol, pre{background:#fff;border:1px solid #c0ff0e;padding:20px 20px 20px 20px;margin:20px;}
            li{font-size:14px;line-height:20px;margin-left:30px;font-family:Georgia;}
            .error_backtrace{font-family:Georgia;font-size:18px;padding:0px;margin:0px;}
			
			.back{
				background:#8FD5E9;
				color:#FFFFFF;
				font-size:1em;
				font-weight:bold;
				letter-spacing:2px;
				line-height:2;
				margin:0 0 20px;
				text-shadow:0 1px 0 #51A5C0;
				text-transform:uppercase;
				width:100%;height:30px;
				text-align:center;
			}
			.back a{
				color:#fff;text-decoration:none;
				display:block;
			}
			.back a:hover{background:#4370A4;}
			.var a{color:#fff;text-decoration:none;display:inline;font-size:18px;}
			.var a:hover{background:#8FD5E9;}
			#panelConf,#panelGet,#panelPost,#panelSession,#panelCookie,#goVar{display:none;}
        </style>';

    //script
    echo '<script>
        function viewCode(){
            document.getElementById("scriptcode").style.display = "block";
            window.location.hash = "'.$errline.'";
        }
        function closeCode(){
			window.location.hash = "#top"
            document.getElementById("scriptcode").style.display = "none";
        }
		var lastcode="";
		function code(n){
			var arr = ["panelConf","panelGet","panelPost","panelSession","panelCookie"];
			if(arr[n]==lastcode){
                var onOrOff = (document.getElementById(arr[n]).style.display == "none")?"block":"none";
				document.getElementById(arr[n]).style.display = onOrOff;
				document.getElementById("goVar").style.display = onOrOff;
                if(onOrOff=="none")
                    document.getElementById("b"+arr[n]).style.backgroundColor = "";
                return;
			}else{
				document.getElementById(arr[n]).style.display = "block";
				document.getElementById("goVar").style.display = "block";
                document.getElementById("b"+arr[n]).style.backgroundColor = "#8FD5E9";
			}
			if(lastcode!=""){
				document.getElementById(lastcode).style.display = "none";
                document.getElementById("b"+lastcode).style.backgroundColor = "";
			}
			lastcode = arr[n];
		}
		function goVisit(){
			//document.location.href="http://doophp.com/";
		}
    </script>';

    //headings
    echo "</head><body>";
    echo '<div id="header">';
    //echo "Unknown error type: [$errno] $errstr<br />\n";
    echo "<span id=\"logo\" onclick=\"javascript:goVisit();\">Genv</span><h1>$errstr</h1>";
    echo '</div><div id="content">';
    echo "<h2 id=\"errorFile\">See file <a href=\"javascript:viewCode();\" title=\"Click to see code\">$errfile</a></h2><br/>";
    echo "<h2 id=\"errorLine\"> + Error on line <a href=\"javascript:viewCode();\" title=\"Click to see code\">$errline</a></h2>";

    if(isset($errLineContent)){
		echo "<h3>".$errLineContent."</h3>";
	}
	
    //error code script
    echo $pre.'<code id="scriptcode" class="scriptcode">';
	if(isset($lines)){
		echo implode('<br />', $lines);
    }
	echo '</code>'.$Xpre;

    echo '<br/><hr/><h2 style="background-color:#4370A4;color:#fff;width:320px;padding:5px;"> * Stack Trace...</h2>';
	global $errTrace;
	if(!empty($errTrace)){
		echo '<pre>';
		echo $errTrace;
		echo '</pre>';
	}else{
	    errorBacktrace();
	}
    
    echo '<br/><hr/><h2 class="var" style="background-color:#4370A4;color:#fff;width:760px;padding:5px;"> * Variables... <a id="bpanelConf" href="javascript:code(0);">&nbsp;Conf </a> . <a id="bpanelGet" href="javascript:code(1);">&nbsp;GET&nbsp;</a> . <a id="bpanelPost" href="javascript:code(2);">&nbsp;POST&nbsp;</a> . <a id="bpanelSession" href="javascript:code(3);">&nbsp;Session&nbsp;</a> . <a id="bpanelCookie" href="javascript:code(4);">&nbsp;Cookie&nbsp;</a></h2>';
	
	//config data
    echo '<pre id="goVar">';
	
	$confData = str_replace(']=&gt;<br />&nbsp;&nbsp;','] =>&nbsp;',Genv_Html::highlightPHP($confData));
	echo str_replace('<code>', '<code id="panelConf">',$confData);
	
	if(!empty($_GET)){
		$getData = str_replace(']=&gt;<br />&nbsp;&nbsp;','] =>&nbsp;',Genv_Html::highlightPHP($getData));
		echo str_replace('<code>', "<code id=\"panelGet\"><span style=\"color:#0000BB;\">\$_GET Variables</span>", $getData);
	}
	
	if(!empty($_POST)){
		$postData = str_replace(']=&gt;<br />&nbsp;&nbsp;','] =>&nbsp;',Genv_Html::highlightPHP($postData));
		echo str_replace('<code>', "<code id=\"panelPost\"><span style=\"color:#0000BB;\">\$_POST Variables</span>", $postData);
	}

	if(isset($sessionData)){
		$sessionData = str_replace(']=&gt;<br />&nbsp;&nbsp;','] =>&nbsp;',Genv_Html::highlightPHP($sessionData));
		echo str_replace('<code>', "<code id=\"panelSession\"><span style=\"color:#0000BB;\">\$_SESSION Variables</span>", $sessionData);
	}

	if(isset($_COOKIE)){
		$cookieData = str_replace(']=&gt;<br />&nbsp;&nbsp;','] =>&nbsp;',Genv_Html::highlightPHP($cookieData));
		echo str_replace('<code>', "<code id=\"panelCookie\"><span style=\"color:#0000BB;\">\$_COOKIE Variables</span>", $cookieData);
	}
	
    echo '</pre><br/>';
	
    echo "</div><div class=\"back\"><a href=\"#top\">BACK TO TOP </a></div></body></html>";
    exit;
}

function traceVar($var){
    ob_start();
    var_dump($var);
	$var = ob_get_contents();
    ob_end_clean();
	return $var;
}

function printVar($var){
    ob_start();
    print_r($var);
	$var = ob_get_contents();
    ob_end_clean();
	return $var;
}

//only call this if debug on
function shutdown(){
    $isError = false;
    if ($error = error_get_last()){
        switch($error['type']){
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $isError = true;
                break;
        }
    }
    if($isError){
		//print_r($error);exit;
		setErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
    }
}

	
function errorBacktrace() {
    $trace = array_reverse(debug_backtrace());
	array_pop($trace);
	array_pop($trace);
    echo '<ol>';
    foreach($trace as $item)
        echo '<li><span style="color:#3A66CC">' . (isset($item['file']) ? $item['file'] : '<unknown file>') . '</span><strong style="color:#DD0000">(' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ')</strong> calling <span style="color:#0000BB">' . $item['function'] . '()</span></li>';
    echo '</ol>';    
}
function logs($class,$event,$msg){
 $config = array(
           'adapter' => 'Genv_Log_Adapter_File',
           'events'  => '*',
           'file'    => APPPATH.'/Data/Logs/file.log',
      );
     $log = Genv::factory('Genv_Log', $config);
     $log->save($class, $event, $msg);


}