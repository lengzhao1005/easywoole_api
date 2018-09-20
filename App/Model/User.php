<?php

namespace App\Model;


use App\Utility\Redis;
use EasySwoole\Core\Utility\Random;

class User extends  LaravelBaseModel
{
    public $table = 'user';
    public $primaryKey = 'id_user';
    public $fillable = [
        'email', 'phone', 'password', 'avatar', 'nickname', 'create_time', 'update_time'
    ];
    public $hidden = ['token','password'];

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    //密码密文前缀
    const PASSWORD_PREFIX = 'XINGYE_PASSWD';
    //token过期时间
    const EXPIRED_SEC = 3600*2;
    //
    const SW_FD_PREFIX='sw:fd:';
    const SW_FD_TOKEN_PREFIX='sw:fd:token:';


    /**
     * 多对多关联项目表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class,'project_user', 'id_user','id_project')->withTimestamps();
    }

    /**
     * 多对多关联任务表
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class,'task_user','id_user', 'id_task');
    }

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

    /**
     * 获取密码密文
     * @param $value
     * @return string
     */
    public static function getMD5Password($value)
    {
        return md5(self::PASSWORD_PREFIX.$value);
    }

    /**
     * 设置身份认证token
     * @param $user
     * @return string
     */
    public static function setToken($user)
    {
        $token = md5($user->id_user.Random::randStr(6));

        Redis::getInstance()->setex($token, self::EXPIRED_SEC, $user->id_user);

        return $token;
    }

    /**
     * 重置token过期时间
     * @param $token
     * @return mixed
     */
    public static function resetTokenExpiredTime($token)
    {
        $id_user = Redis::getInstance()->get($token);
        Redis::getInstance()->setex($token, self::EXPIRED_SEC, $id_user);

        return $token;
    }

    /**
     * 认证token
     * @param $token
     * @return bool
     */
    public static function authToken($token)
    {
        $id_user = Redis::getInstance()->get($token);

        if(!empty($id_user) && $user = self::find($id_user)){
            return $user;
        }
        return false;
    }
}