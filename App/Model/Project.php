<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/7
 * Time: 17:34
 */

namespace App\Model;


class Project extends LaravelBaseModel
{
    protected $table = 'project';
    protected $primaryKey = 'id_project';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    const SUBORDINATE = [
        'private',
        'protected',
        'public'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user_create');
    }
}