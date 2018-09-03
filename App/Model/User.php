<?php

namespace App\Model;


use App\Utility\Helper;
use App\Utility\Redis;
use EasySwoole\Core\Utility\Random;

class User extends  LaravelBaseModel
{
    public $table = 'user';
    public $primaryKey = 'id_user';
    public $fillable = [
        'email', 'phone', 'password', 'avatar', 'nickname', 'create_time', 'update_time'
    ];

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    const PASSWORD_PREFIX = 'XINGYE_PASSWD';
    const EXPIRED_SEC = 60*60*2;


    /**
     * 设置密码
     *
     * @param  string  $value
     * @return string
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = md5(self::PASSWORD_PREFIX.$value);
    }

    public static function getMD5Password($value)
    {
        return md5(self::PASSWORD_PREFIX.$value);
    }

    public static function setToken($user)
    {
        $token = md5($user->id_user.time().Random::randStr(6));

        Redis::getInstance()->setex($token, self::EXPIRED_SEC, $user->id);

        return $token;
    }

    public static function refreshToken($token)
    {
        $id_user = Redis::getInstance()->get($token);
        Redis::getInstance()->setex($token, self::EXPIRED_SEC, $id_user);

        return $token;
    }

    public static function authToken($token)
    {
        $id_user = Redis::getInstance()->get($token);
        if(!empty($id_user) && $user = self::find($id_user)){
            return $user;
        }
        return false;
    }
}