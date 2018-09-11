<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/7
 * Time: 17:34
 */

namespace App\Model;


class ProjectUser extends LaravelBaseModel
{
    protected $table = 'project_user';
    protected $primaryKey = 'id_project_user';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
}