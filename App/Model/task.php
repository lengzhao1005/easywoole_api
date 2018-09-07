<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/7
 * Time: 17:42
 */

namespace App\Model;


class task extends LaravelBaseModel
{
    protected $table = 'task';
    protected $primaryKey = 'id_task';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }
}