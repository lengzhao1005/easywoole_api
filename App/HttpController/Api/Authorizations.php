<?php

namespace App\HttpController\Api;

use \App\Model\User\User as UserModel;
use App\Model\User\Bean;
use App\Utility\FormatResultErrors;
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

    //登陆
    public function login()
    {
        //限制传输方式为post
        if(($verfy_result = $this->verificationMethod('post')) !== true){
            return $this->returnJson($verfy_result);
        }

        //验证字段是否正合法
        $request = $this->request();
        $rule = new Rules();
        $rule->add('username','username字段错误')->withRule(Rule::REQUIRED);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED);
        $v = $this->validateParams($rule);

        if(!$v->hasError()){//合法
            $username = $request->getRequestParam('username');
            $credenttails['password'] = \App\Model\User::getMD5Password($request->getRequestParam('password'));

            //判断用户名是邮箱还是手机号
            filter_var($username, FILTER_VALIDATE_EMAIL) ?
                $credenttails['email'] = $username :
                $credenttails['phone'] = $username ;

            //查找用户是否存在
            if(!empty($credenttails['email'])){
                $user = \App\Model\User::where('email', $credenttails['email'])
                                        ->where('password', $credenttails['password'])
                                        ->first();
            }else{
                $user = \App\Model\User::where('phone', $credenttails['phone'])
                    ->where('password', $credenttails['password'])
                    ->first();
            }

            //窜在则获取用户token
            if(!empty($user)){
                $token = \App\Model\User::setToken($user);
                $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'], [
                    'auth_token' => $token,
                ]);
            }else{
                $this->returnJson(FormatResultErrors::CODE_MAP['AUTH.FAIL']);
            }

        }else{//非法
            $this->returnJson([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }
}