<?php

namespace App\HttpController\Api;

use \App\Model\User\User as UserModel;
use App\Model\User\Bean;
use App\Utility\SysConst;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Rules;

class Authorizations extends AbstractBase
{
    //第三方登录
    public function socialLogin()
    {
        $this->returnJson(['code'=>1, 'message'=>'32'],['dwe','dwed','dwed']);
    }

    ///登陆
    public function login()
    {
        $rule = new Rules();
        $rule->add('account','account字段错误')->withRule(Rule::REQUIRED);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED);
        $v = $this->validateParams($rule);
        if(!$v->hasError()){
            $bean = new Bean($v->getRuleData());
            $model = new UserModel();
            $ret = $model->login($bean);
            if($ret){
                $this->response()->setCookie(SysConst::COOKIE_USER_SESSION_NAME,$bean->getSession(),time()+SysConst::COOKIE_USER_SESSION_TTL);
                $this->writeJson(Status::CODE_OK,$bean->toArray());
            }else{
                $this->writeJson(Status::CODE_UNAUTHORIZED,null,'账户或密码错误');
            }
        }else{
            $this->writeJson(Status::CODE_BAD_REQUEST,null,$v->getErrorList()->first()->getMessage());
        }
    }

    protected function responseWithToken($token)
    {

    }

    public function update()
    {

    }

    public function destory()
    {

    }
}