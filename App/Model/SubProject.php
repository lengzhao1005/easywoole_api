<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/7
 * Time: 17:39
 */

namespace App\Model;


class SubProject extends LaravelBaseModel
{
    protected $table = 'subproject';
    protected $primaryKey = 'id_subproject';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    const SUBORDINATE = [
        'private',
        'protected',
        'public'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class,'id_project', 'project');
    }
}