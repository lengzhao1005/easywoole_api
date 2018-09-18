<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/7
 * Time: 17:34
 */

namespace App\Model;


use App\Utility\Redis;
use Carbon\Carbon;
use EasySwoole\Core\Utility\Random;

class Project extends LaravelBaseModel
{
    protected $table = 'project';
    protected $primaryKey = 'id_project';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    //加入项目缓存前缀
    const JOIN_PROJECT_PREFIX = 'JOIN_PROJECT';
    //加入项目缓存过去时间 1小时
    const JOIN_PROJECT_EXPIRE = 1*60*60;

    const SUBORDINATE = [
        'private',
        'protected',
        'public'
    ];

    /**
     * 一对多关联创建用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user_create', 'id_user');
    }

    /**
     * 多对多关联一个项目下的所用用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'id_project', 'id_user');
    }

    /**
     * 获取加入项目code
     * @return array
     */
    public function getJoinCode()
    {
        $id_project = $this->id_project;

        $code = $this->getJoinStr($id_project);

        Redis::getInstance()->setex(self::JOIN_PROJECT_PREFIX.$code, self::JOIN_PROJECT_EXPIRE, $id_project);

        return [
            'join_code' => $code,
            'expire_at' => Carbon::now()->addHour()->toDateTimeString(),
        ];
    }

    /**
     * 获取随机字符串
     * @return bool|string
     */
    protected function getJoinStr($id_project)
    {
        $rand_str = strtoupper(Random::randStr(8));

        $id_project_save = Redis::getInstance()->get(self::JOIN_PROJECT_PREFIX.$rand_str);

        if(empty($id_project_save) || $id_project == $id_project_save){
            return $rand_str;
        }
        unset($rand_str);
        $this->getJoinStr($id_project);
    }
}