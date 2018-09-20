<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/20
 * Time: 17:13
 */

namespace App\WebSocket;

use App\Model\Project;
use App\Model\Task;
use App\Utility\FormatResultErrors;
use Carbon\Carbon;
use EasySwoole\Core\Utility\Validate\Rules;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Validate;

trait Projects
{

    public function showProject($data = array())
    {
        //建立验证规则
        $rule = new Rules();
        $rule->add('id_project','id_project不能为空')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,1);
        //执行验证
        var_dump(111);
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){
            $id_user = $this->who->id_user;
            $project_task = Project::where('id_project', $data['id_project'])->with('tasks')->first();

            if(!empty($project_task)){
                $rep_data['id_project'] = $project_task->id_project;
                $rep_data['project_name'] = $project_task->name;
                foreach ($project_task->tasks as $k=>$task){
                    $rep_data['tasks'][$k]['id_task'] = $task->id_task;
                    $rep_data['tasks'][$k]['content'] = $task->content;
                    $rep_data['tasks'][$k]['mine'] = ($id_user == $task->id_user_create ? true :false);
                    $rep_data['tasks'][$k]['completed'] = ($task->is_finished == 2 ? true :false);
                    $rep_data['tasks'][$k]['priority'] = [
                        'type' => $task->emergency_rank,
                        'txt' => Task::getEmergencyTxt($task->emergency_rank),
                    ];
                }
                //返回数据
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], $rep_data);
            }else{
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['PROJECT.NOTFOUND']);
            }
        }else{
            return $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }
    /**
     * 创建任务
     * @param array $data
     * @param $fd
     * @return mixed
     */
    public function createProject($data = array())
    {
        //建立验证规则
        $rule = new Rules();
        $rule->add('name','项目名称不能为空')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,100);
        $rule->add('subordinate','项目所用状态不能为空')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,30);
        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){
            $project_name = $data['name'];
            $subordinate = $data['subordinate'];

            if(!in_array($subordinate, \App\Model\Project::SUBORDINATE)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUBORDINATE.INVALID']);
            }

            //创建任务
            $time = Carbon::now()->toDateTimeString();
            $id_project = \App\Model\Project::insertGetId([
                'name' => $project_name,
                'subordinate' => $subordinate,
                'id_user_create' => $this->who->id_user,
                'create_time' => $time,
                'update_time' => $time
            ]);
            //关联到中间表
            $this->who->projects()->attach($id_project);
            //设置user-projectproject
            \App\Model\ProjectUser::setUserProjectList($id_project,$this->who->id_user);
            //返回数据
            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], [
                'id_project' => $id_project,
            ]);

        }else{
            return $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    public function update()
    {

    }

    public function destory()
    {

    }

    /**
     * 获取项目下所有的用户
     */
    public function getUsersByIdProject($data = array())
    {
        //建立验证规则
        $rule = new Rules();
        $rule->add('id_project','id_project不能为空')->withRule(Rule::REQUIRED);
        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){
            $id_project = $data['id_project'];

            $project = \App\Model\Project::find($id_project);
            if(empty($project)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['PROJECT.NOTFOUND']);
            }

            $users = $project->users()->get()->map(function ($item){
                unset($item['id_user']);
                unset($item['pivot']);
                return $item;
            })->toArray();

            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'],[
                'users' => $users,
            ]);


        }else{
            return $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    /**
     * 获取加入项目的code
     */
    public function getJoinProjectCode($data = array())
    {
        //建立验证规则
        $rule = new Rules();
        $rule->add('id_project','id_project不能为空')->withRule(Rule::REQUIRED);
        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){
            $id_project = $data['id_project'];

            $project = \App\Model\Project::find($id_project);
            if(empty($project)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['PROJECT.NOTFOUND']);
            }

            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'],$project->getJoinCode());

        }else{
            return $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    public function getProjectsList()
    {

        $projects = $this->who->projects()->toArray();

        return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'],$projects);
    }
}