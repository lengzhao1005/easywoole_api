<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/3
 * Time: 下午6:48
 */

namespace App\Model\User;


use EasySwoole\Core\Component\Spl\SplBean;

class Bean extends SplBean
{
    protected $id_user;
    protected $email;
    protected $phone;
    protected $password;
    protected $token;
    protected $update_time;
    protected $create_time;

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->id_user;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId): void
    {
        $this->id_user = $userId;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone): void
    {
        $this->email = $phone;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @param mixed $create_time
     */
    public function setCreateTime($create_time): void
    {
        $this->addTime = $create_time;
    }

    protected function initialize(): void
    {
        $time = date('Y-m-d H:i:s');
        if(empty($this->create_time)){
            $this->create_time = $time;
        }
        if(empty($this->update_time)){
            $this->update_time = $time;
        }
        //默认md5是32 位，当从数据库中读出数据恢复为bean的时候，不对密码做md5
        if(strlen($this->password) == 32){
            $this->password = md5($this->password);
        }
    }
}