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

    ///登陆
    public function login()
    {
        if($verfy_result = $this->verificationMethod('POST') !== true){
            $this->returnJson($verfy_result);
        }

        $request = $this->request();
        $rule = new Rules();
        $rule->add('name','name字段错误')->withRule(Rule::REQUIRED);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED);
        $v = $this->validateParams($rule);

        if(!$v->hasError()){
            $username = $request->getRequestParam('name');
            $credenttails['password'] = \App\Model\User::getMD5Password($request->getRequestParam('password'));

            filter_var($username, FILTER_VALIDATE_EMAIL) ?
                $credenttails['email'] = $username :
                $credenttails['phone'] = $username ;

            if(!empty($credenttails['email'])){
                $user = \App\Model\User::where('email', $credenttails['email'])
                                        ->where('password', $credenttails['password'])
                                        ->first();
            }else{
                $user = \App\Model\User::where('phone', $credenttails['phone'])
                    ->where('password', $credenttails['password'])
                    ->first();
            }
            if(!empty($user)){
                $token = \App\Model\User::setToken($user);
                $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'], [
                    'token' => $token,
                ]);
            }else{
                $this->returnJson(FormatResultErrors::CODE_MAP['AUTH.FAIL']);
            }

        }else{
            $this->returnJson([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }
}