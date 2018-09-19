<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/10
 * Time: 9:54
 */

namespace App\HttpController\Api;


use App\Model\TaskUser;
use App\Utility\FormatResultErrors;
use Carbon\Carbon;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Rules;

class Task extends Base
{
    /**
     * 添加任务
     */
    public function store()
    {
        if(($verfy_result = $this->verificationMethod('POST')) !== true){
            $this->returnJson($verfy_result);
        }
        //设置验证规则
        $rule = new Rules();
        $rule->add('title','title字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,60);
        $rule->add('content','content字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,2000);
        $rule->add('id_users','id_users字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,1)
            ->withRule(Rule::MAX_LEN,1000);
        $rule->add('id_project','id_project字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,1)
            ->withRule(Rule::MAX_LEN,11);
        $rule->add('id_sub_project','id_sub_project字段错误')
            ->withRule(Rule::MAX_LEN,11);

        //执行验证
        $v = $this->validateParams($rule);
        if(!$v->hasError()){
            //获取参数
            $task_title = $this->request()->getRequestParam('title');
            $task_content = htmlspecialchars($this->request()->getRequestParam('content'));
            $expire_time = $this->request()->getRequestParam('expire_time');
            $cost_time =(int) $this->request()->getRequestParam('cost_time');
            $id_project =(int) $this->request()->getRequestParam('id_project');
            $id_sub_project =(int) $this->request()->getRequestParam('id_sub_project');
            $id_users = $this->request()->getRequestParam('id_users');
            $emergency_rank = empty($this->request()->getRequestParam('emergency_rank')) ?
                \App\Model\Task::EMERGENCY_LOW :
                $this->request()->getRequestParam('emergency_rank');

            //判断紧急度合法性
            if(!in_array($emergency_rank, \App\Model\Task::EMERGENCY_RANK)){
                return $this->returnJson(FormatResultErrors::CODE_MAP['TASK.EMERGENCT.RANK.INVALID']);
            }

            //判断到期时间是否为日期格式
            if(!empty($expire_time) && date('Y-m-d H:i:s', strtotime($expire_time)) != $expire_time){
                return $this->returnJson(FormatResultErrors::CODE_MAP['TASK.EXPIRE.TIME.INVALID']);
            }

            //判断项目是否存在
            $project = \App\Model\Project::find($id_project);
            if(empty($project)){
                return $this->returnJson(FormatResultErrors::CODE_MAP['PROJECT.NOTFOUND']);
            }

            if(!empty($id_sub_project)){
                //判断子项目是否存在
                $sub_project = \App\Model\SubProject::find($id_sub_project);
                if(empty($sub_project)){
                    return $this->returnJson(FormatResultErrors::CODE_MAP['SUBPROJECT.NOTFOUND']);
                }
            }else{
                $id_sub_project = 0;
            }

            //判断id_users合法性
            $id_users = explode(',', $id_users);
            $users_count = \App\Model\User::whereIn('id_user', $id_users)->count('*');
            if(count($id_users) != $users_count){
                return $this->returnJson(FormatResultErrors::CODE_MAP['ID.USERS.NOTFOUND']);
            }

            //创建任务
            $time = Carbon::now()->toDateTimeString();
            $insert_data = [
                'title' => $task_title,
                'content' => $task_content,
                'emergency_rank' => $emergency_rank,
                'cost_time' => $cost_time,
                'expire_time' => $expire_time,
                'id_sub_project' => $id_sub_project,
                'id_project' => $id_project,
                'id_user_create' => $this->who->id_user,
                'create_time' => $time,
                'update_time' => $time
            ];
            $id_task = \App\Model\Task::insertGetId($insert_data);
            $insert_data['id_task'] = $id_task;
            //关联用户
            \App\Model\Task::find($id_task)->users()->sync($id_users);

            //推送异步ws消息
            \App\Model\ProjectUser::pushMsg($id_project, 'task', $insert_data);

            return $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'], [
                'id_task' => $id_task,
            ]);

        }else{
            $this->returnJson([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    public function getTasksByUid()
    {
        //限制请求方式
        if(($verfy_result = $this->verificationMethod('get')) !== true){
            return $this->returnJson($verfy_result);
        }

        $page = $this->request()->getRequestParam('page')??1;
        $pre_page = $this->request()->getRequestParam('pre_page')??6;

        $projects = $this->who->tasks()->paginate($pre_page)->toArray();


        return $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'],$projects);
    }
}