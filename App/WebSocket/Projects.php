<?php

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

    public function showTasksByIdProject($data = array())
    {
        //建立验证规则
        $rule = new Rules();
        $rule->add('id_project','id_project不能为空')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,1);
        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        var_dump(222);
        if(!$v->hasError()){
            $id_user = $this->who->id_user;
            var_dump('showTasksByIdProject_id_user:'.$id_user);

            $project_task = Project::where('id_project', $data['id_project'])->with('tasks')->first();

            if(!empty($project_task)){
                $rep_data = [];
                foreach ($project_task->tasks as $k=>$task){
                    $rep_data[] = array(
                        'id_task' => $task->id_task,
                        'id_project' => $task->id_project,
                        'content' => $task->content,
                        'name' => $task->title,
                        'mine' => ($id_user == $task->id_user_create ? true :false),
                        'completed' => ($task->is_finished == 2 ? true :false),
                        'priority' => [
                            'type' => $task->emergency_rank,
                            'txt' => Task::getEmergencyTxt($task->emergency_rank),
                        ]
                    );
                }
                var_dump('showTasksByIdProject_id_user');
                //返回数据
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], ['tasks' => $rep_data]);
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
            var_dump('id_project'.$data['id_project']);
            $project = Project::find($data['id_project']);

            if(!empty($project)){

                //返回数据
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], ['project' => $project->toArray()]);
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

    public function saveProject($data)
    {
        //建立验证规则
        $rule = new Rules();
        $rule->add('name','项目名称不能为空')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,100);
        /*$rule->add('subordinate','项目所用状态不能为空')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,30);*/
        $rule->add('description','description最多300个字符')
            ->withRule(Rule::MAX_LEN,300);
        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){
            $project_name = $data['name'];
            //$subordinate = $data['subordinate'];
            $desc = $data['description']??'';

            /*if(!in_array($subordinate, \App\Model\Project::SUBORDINATE)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUBORDINATE.INVALID']);
            }*/
            if(!empty($data['id_project'])){
                $project = Project::find($data['id_project']);

                if(empty($project)){
                    return $this->_getResponseData(FormatResultErrors::CODE_MAP['PROJECT.NOTFOUND']);
                }
                if(!$this->who->police($project->id_user_create)){
                    return $this->_policeFail();
                }

                $project->name = $data['name'];
                $project->description = $desc;
                $project->save();
            }else{
                //创建项目
                $project = new Project();
                $project->name = $data['name'];
                $project->description = $desc;
                $project->id_user_create = $this->who->id_user;
                $project->save();
                var_dump($project);
                //关联到中间表
                $this->who->projects()->attach($project->id_project);
                //设置user-projectproject
                \App\Model\ProjectUser::setUserProjectList($project->id_project,$this->who->id_user);
            }

            //返回数据
            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], ['project' => $project->toArray()]);

        }else{
            return $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    public function destoryProject($data)
    {
        //建立验证规则
        $rule = new Rules();
        $rule->add('id_project','id_project字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,1);

        //执行验证
        $validate = new Validate();
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){

            $project = Project::find($data['id_project']);

            if(empty($project)){
                return $this->_getResponseData(FormatResultErrors::CODE_MAP['PROJECT.NOTFOUND']);
            }

            if(!$this->who->police($project->id_user_create)){
                return $this->_policeFail();
            }

            $project->delete();

            //返回数据
            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS']);

        }else{
            return $this->_getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
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
                $user = [
                    'id_user' => $item->id_user,
                    'nickname' => $item->nickname,
                ];
                return $user;
            })->toArray();

            return $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'],[
                'members_project' => $users,
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