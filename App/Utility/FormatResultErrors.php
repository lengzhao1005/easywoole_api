<?php

namespace App\Utility;

class FormatResultErrors
{
    const CODE_MAP = [
        'SUCCESS' => ['code' => '100', 'message' => 'success'],
        'SYS.ERR' => ['code' => '101', 'message' => '系统错误', ],
        'TOKEN.INVALID' => ['code' => '102', 'message' => '无效的token', ],
        'METHOD.NOTALLOW' => ['code' => '103', 'message' => '传输方式不被允许', ],

        'FIELD.INVALID' => ['code' => '201', 'message' => '字段非法', ],
        'USERNAME.NOTNULL' => ['code' => '202', 'message' => '用户名不能为空', ],
        'USER.ALLREADY.EXITS' => ['code' => '203', 'message' => '用户已存在', ],
        'VERIFY.CODE.EXPIRED' => ['code' => '204', 'message' => '验证码已失效', ],
        'AUTH.FAIL' => ['code' => '205', 'message' => '用户名或密码错误', ],
        'PASSWORD.NOT.SAME' => ['code' => '206', 'message' => '两次密码不一致', ],
        'EMAIL.INVALID' => ['code' => '206', 'message' => '两次密码不一致', ],
        'PHONE.INVALID' => ['code' => '206', 'message' => '两次密码不一致', ],

        'SUBORDINATE.INVALID' => ['code' => '301', 'message' => 'subordinate非法', ],
        'PROJECT.NOTFOUND' => ['code' => '302', 'message' => '未找到项目', ],

        'TASK.EMERGENCT.RANK.INVALID' => ['code' => '401', 'message' => '任务紧急度非法', ],
        'TASK.EXPIRE.TIME.INVALID' => ['code' => '401', 'message' => '任务到期时间格式错误', ],
        'SUBPROJECT.NOTFOUND' => ['code' => '401', 'message' => '未找到子项目项目', ],
        'ID.USERS.NOTFOUND' => ['code' => '401', 'message' => '关联的用户未找到', ],

    ];
}