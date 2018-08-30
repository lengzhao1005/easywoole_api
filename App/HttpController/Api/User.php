<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/3
 * Time: 下午6:21
 */

namespace App\HttpController\Api;

use App\Model\User\Bean;
use App\Utility\SysConst;
use EasySwoole\Core\Http\Message\Status;
use \App\Model\User\User as UserModel;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Rules;

class User extends AbstractBase
{
/*    //onRequest返回false的时候，为拦截请求，不再往下执行方法
    protected $who;
    protected function onRequest($action): ?bool
    {
        $token = $this->request()->getCookieParams(SysConst::COOKIE_USER_SESSION_NAME);
        $bean = new Bean([
            'session'=>$token
        ]);
        $model = new UserModel();
        $bean = $model->sessionExist($bean);
        if($bean){
            $this->who = $bean;
            return true;
        }else{
            $this->writeJson(Status::CODE_UNAUTHORIZED,null,'权限验证失败');
            return false;
        }
    }*/

    function register()
    {
        $rule = new Rules();
        $rule->add('account','account字段错误')->withRule(Rule::REQUIRED);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,16);
        $v = $this->validateParams($rule);
        if(!$v->hasError()){
            $bean = new Bean($v->getRuleData());
            $model = new UserModel();
            $ret = $model->register($bean);
            if($ret){
                $this->writeJson(Status::CODE_OK, [
                    'userId'=>$ret
                ],'注册成功');
            }else{
                $this->writeJson(Status::CODE_BAD_REQUEST, null,'注册失败，账户可能已经存在');
            }
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST,null,$v->getErrorList()->first()->getMessage());
        }
    }
    /*
     * 测试url路径/api/user/info/index.html
     * 测试前请执行登录
     */
    function info()
    {
        $this->writeJson(Status::CODE_OK,$this->who->toArray());
    }
}