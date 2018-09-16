<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/3
 * Time: 16:26
 */

namespace App\HttpController\Api;


use App\Utility\FormatResultErrors;
use App\Utility\SysConst;
use \App\Model\User;

class Base extends AbstractBase
{
    protected $who;

    /**
     * 验证token
     * @param $action
     * @return bool|null
     */
    public function onRequest($action): ?bool
    {
        //参数中传token
        $token = $this->request()->getRequestParam('token');

        //请求头中传token
        /*if(empty($token)){
            $token = empty($this->request()->getHeader('token')) ?'':$this->request()->getHeader('token')['0'];
        }*/

        //请求头authorization方式传token
        if(empty($token)){
            $token = empty($this->request()->getHeader('authorization')) ?'':substr($this->request()->getHeader('authorization')['0'],7);
        }

        //验证token
        if(empty($token)){
            $this->returnJson(FormatResultErrors::CODE_MAP['TOKEN.INVALID']);
            return false;
        }

        $user = User::authToken($token);

        if(!$user){
            $this->returnJson(FormatResultErrors::CODE_MAP['TOKEN.INVALID']);
            return false;
        }

        //频率限制
        if($this->requestLimit(md5($token. $this->request()->getUri()), 'auth_limits')){
            //自动刷新token过期时间
            User::resetTokenExpiredTime($token);
            $this->response()->setCookie(SysConst::COOKIE_USER_SESSION_NAME,$token,time()+User::EXPIRED_SEC);
            $this->who = $user;
            return true;
        }
        return false;

    }
}