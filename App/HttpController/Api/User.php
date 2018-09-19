<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/3
 * Time: 下午6:21
 */

namespace App\HttpController\Api;

use App\Utility\FormatResultErrors;
use App\Utility\Redis;
use App\Utility\SysConst;
use EasySwoole\Core\Component\Pool\PoolManager;
use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Rules;

class User extends Base
{

    protected $_auth_rules = [
        'token' => []
    ];

    public function index()
    {
        $this->actionNotFound('index');
    }

    public function register()
    {
        /*if(($verfy_result = $this->verificationMethod('POST')) !== true){
            $this->returnJsonCROS($verfy_result);
        }*/
        $rule = new Rules();
        $rule->add('email','email字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,60);
        $rule->add('phone','phone字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,60);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,30);
        $rule->add('password_confirm','password_confirm字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,30);
        /*$rule->add('code','code字段错误')->withRule(Rule::REQUIRED);
        $rule->add('verification_key','code字段错误')->withRule(Rule::REQUIRED);*/

        $v = $this->validateParams($rule);
        if(!$v->hasError()){
            $user_data['password'] = $this->request()->getRequestParam('password');
            $user_data['email'] = $this->request()->getRequestParam('email');
            $user_data['phone'] = $this->request()->getRequestParam('phone');
            //$code = $this->request()->getRequestParam('code');
            $confirm_password = $this->request()->getRequestParam('password_confirm');

            if($user_data['password'] !== $confirm_password){
                return $this->returnJson(FormatResultErrors::CODE_MAP['PASSWORD.NOT.SAME']);
            }

            if(!preg_match("/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/",$user_data['email'])){
                return $this->returnJson(FormatResultErrors::CODE_MAP['EMAIL.INVALID']);
            }

            if(!preg_match("/^1[34578]\d{9}$/",$user_data['phone'])){
                return $this->returnJson(FormatResultErrors::CODE_MAP['PHONE.INVALID']);
            }
                //获取缓存验证码
           /* $key = $this->request()->getRequestParam('verification_key');
            $hkey = $hkey = 'verify:'.$key;
            $verify_code = Redis::getInstance()->hGet($hkey,$username);

            if(!$verify_code){
                return $this->returnJson(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
            }
            if(!hash_equals($verify_code, $code)){
                return $this->returnJson(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
            }*/

           $user = \App\Model\User::where('email', $user_data['email'])->find();

           if(!empty($user)){
               return $this->returnJson(FormatResultErrors::CODE_MAP['USER.EMAIL.EXITS']);
           }

           $user = \App\Model\User::where('phone', $user_data['phone'])->find();

            if(!empty($user)){
                return $this->returnJson(FormatResultErrors::CODE_MAP['USER.PHONE.EXITS']);
            }

            try{
                $user = \App\Model\User::create($user_data);
            }catch (\Exception $e){
                return $this->returnJson(FormatResultErrors::CODE_MAP['USER.ALLREADY.EXITS']);
            }

            $token = \App\Model\User::setToken($user);
            return $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'], [
                'auth_token' => $token,
            ]);

        }else{
            return $this->returnJson([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    /**
     * 获取登录用户信息
     */
    function info()
    {
        return $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'], $this->who->toArray());
    }
}