<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/7
 * Time: 17:42
 */

namespace App\Model;


class Task extends LaravelBaseModel
{
    protected $table = 'task';
    protected $primaryKey = 'id_task';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    //任务紧急度
    const EMERGENCY_RANK = [
        'low', 'middle', 'high'
    ];
    //普通
    const EMERGENCY_LOW = 'low';
    //中
    const EMERGENCY_MIDDLE = 'middle';
    //高
    const EMERGENCY_HIGH = 'high';

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user', 'id_task', 'id_user')->withTimestamps();
    }

    public static function getEmergencyTxt($key)
    {
        $data = [
            'low' => '普通',
            'middle' => '紧急',
            'high' => '非常紧急',
        ];
        return $data[$key];
    }
}