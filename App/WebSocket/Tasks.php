<?php

namespace App\WebSocket;

use App\Model\Task;
use App\Utility\FormatResultErrors;
use EasySwoole\Core\Utility\Validate\Rules;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Validate;

trait Tasks
{

    public function showTask($data = array())
    {
        //设置验证规则
        $rule = new Rules();
        $rule->add('id_task','id_task字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,1)
            ->withRule(Rule::MAX_LEN,11);

        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){

            //判断任务是否存在
            $task = \App\Model\Task::where('id_task', $data['id_task'])->with('users')->first();
            if(empty($task)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['TASK.NOTFOUND']);
            }

            $members_task = [];
            foreach ($task->users as $user){
                $members_task[] = [
                    'id_user' => $user->id_user,
                    'nickname' => $user->nickname,
                ];
            }

            $response_data['task'] = [
                'id_task' => $task->id_task,
                'id_project' => $task->id_project,
                'mine' => ($this->who->id_user === $task->id_user_create ? true :false),
                'completed' => $task->isFinished(),
                'name' => $task->title,
                'content' => $task->content,
                'members_task' => $members_task,
                'expire_time' => $task->expire_time,
                'priority' => [
                    'type' => $task->emergency_rank,
                    'txt' => Task::getEmergencyTxt($task->emergency_rank),
                ]
            ];
            //推送异步ws消息
            //\App\Model\ProjectUser::pushMsg($id_project, 'task', $insert_data);

            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], $response_data);

        }else{
            $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    /**
     * 添加/修改任务
     */
    public function saveTask($data = array())
    {
        $data = $data['task']??[];
        //设置验证规则
        $rule = new Rules();
        $rule->add('name','name字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,60);
        $rule->add('content','content字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,2000);
        $rule->add('members_task','id_users字段错误')->withRule(Rule::REQUIRED);
        $rule->add('id_project','id_project字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,1)
            ->withRule(Rule::MAX_LEN,11);
        /*$rule->add('id_sub_project','id_sub_project字段错误')
            ->withRule(Rule::MAX_LEN,11);*/

        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){
            //获取参数
            $task_title = $data['name'];
            $task_content = htmlspecialchars($data['content']);
            $expire_time = $data['expire_time'];
            //$cost_time =(int) $data['cost_time'];
            $id_project =(int) $data['id_project'];
            //$id_sub_project =(int) $data['id_sub_project'];
            $id_users = $data['members_task'];
            $emergency_rank = empty($data['priority']) ?
                \App\Model\Task::EMERGENCY_LOW :
                $data['priority'];
            $completed = (empty($data['completed']))?'1':'2';

            //判断紧急度合法性
            if(!in_array($emergency_rank, \App\Model\Task::EMERGENCY_RANK)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['TASK.EMERGENCT.RANK.INVALID']);
            }

            //判断到期时间是否为日期格式
            if(!empty($expire_time) && date('Y-m-d H:i:s', strtotime($expire_time)) == ''){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['TASK.EXPIRE.TIME.INVALID']);
            }
            $expire_time = date('Y-m-d H:i:s', strtotime($expire_time));
            //判断项目是否存在
            $project = \App\Model\Project::find($id_project);
            if(empty($project)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['PROJECT.NOTFOUND']);
            }

            /*if(!empty($id_sub_project)){
                //判断子项目是否存在
                $sub_project = \App\Model\SubProject::find($id_sub_project);
                if(empty($sub_project)){
                    return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUBPROJECT.NOTFOUND']);
                }
            }else{
                $id_sub_project = 0;
            }*/

            //判断id_users合法性
            //$id_users = explode(',', $id_users);
            $users_count = \App\Model\User::whereIn('id_user', $id_users)->count('*');
            if(count($id_users) != $users_count){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['ID.USERS.NOTFOUND']);
            }

            if(!empty($data['id_task'])){//修改
                $task = Task::find($data['id_task']);
                if(empty($task)){
                    return $this->_getResponseData(FormatResultErrors::CODE_MAP['TASK.NOTFOUND']);
                }
                $task->title = $task_title;
                $task->content = $task_content;
                $task->emergency_rank = $emergency_rank;
                $task->is_finished = $completed;
                //$task->cost_time = $cost_time;
                $task->expire_time = $expire_time;
                $task->save();
                $type = 'task_modify';//task_modify
            }else{//创建
                $task = new Task();
                $task->title = $task_title;
                $task->content = $task_content;
                $task->emergency_rank = $emergency_rank;
                //$task->cost_time = $cost_time;
                $task->expire_time = $expire_time;
                //$task->id_sub_project = $id_sub_project;
                $task->id_project = $id_project;
                $task->id_user_create = $this->who->id_user;
                $task->save();
                $type = 'task_new';
            }

            //关联用户
            $task->users()->sync($id_users);

            //推送异步ws消息
            $this->pushMsg($id_project, $type, array(
                'id_task' => $task->id_task,
                'id_project' => $task->id_project,
                'content' => $task->content,
                'name' => $task->title,
                'mine' => $this->who->mine($task->id_user_create),
                'completed' => $task->isFinished(),
                'priority' => [
                    'type' => $task->emergency_rank,
                    'txt' => Task::getEmergencyTxt($task->emergency_rank),
                ]
            ));

            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], [
                'task'=>[
                    'id_task' => $task->id_task,
                    'id_project' => $task->id_project,
                ]
            ]);

        }else{
            return $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }
}