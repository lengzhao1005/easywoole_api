<?php

namespace App\Model;


class TaskUser extends LaravelBaseModel
{
    public $table = 'task_user';
    public $primaryKey = 'id_task_user';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
}