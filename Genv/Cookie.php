<?php

class Genv_Cookie{
    

 // 获取某个Cookie值
   public static function get($name,$default = NULL) {
	   
        return isset($_COOKIE[COOKIE_PREFIX.$name]) ? unserialize(base64_decode($_COOKIE[COOKIE_PREFIX.$name])) : $default;
    }

    /**
     * 设置指定的COOKIE值
     *
     * @access public
     * @param string $key 指定的参数
     * @param mixed $value 设置的值
     * @param integer $expire 过期时间,默认为0,表示随会话时间结束
     * @param string $url 路径(可以是域名,也可以是地址)
     * @return void
     */
    public static function set($name,$value,$expire='',$path='',$domain=''){

        if($expire=='') {
            $expire = COOKIE_EXPIRE;
        }
        if(empty($path)) {
            $path = COOKIE_PATH;
        }
        if(empty($domain)) {
            $domain = COOKIE_DOMAIN;
        }
        $expire = !empty($expire)?    time()+$expire   :  0;

		//DUMP( $expire);
        $value =  base64_encode(serialize($value));
        setcookie(COOKIE_PREFIX.$name, $value,$expire,$path,$domain);
        $_COOKIE[COOKIE_PREFIX.$name]  =   $value;
    }

    /**
     * 删除指定的COOKIE值
     *
     * @access public
     * @param string $key 指定的参数
     * @return void
     */
    public static function delete($key){
       if(empty($path)) {
            $path = COOKIE_PATH;
        }
        if(empty($domain)) {
            $domain = COOKIE_DOMAIN;
        }     
        setcookie(COOKIE_PREFIX.$key, '', time() - 2592000,$path,$domain);       
    }
	// 清空Cookie值
    static function clear() {
        unset($_COOKIE);
    }
}
?>