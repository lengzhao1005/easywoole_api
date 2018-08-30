<?php

namespace App\Utility;

class FormatResultErrors
{
    const CODE_MAP = [
        'SUCCESS' => ['code' => '100', 'message' => 'success'],
        'SYS.ERR' => ['code' => '101', 'message' => '系统错误', ],
        'SIGN.VERIFY.FAIL' => ['code' => '102', 'message' => '签名错误', ],
        'SIGN.BIZ_TYPE.INVALID' => ['code' => '103', 'message' => 'biz_type无效', ],
        'OUT_TRANT_NO.INVALID' => ['code' => '104', 'message' => '外部追踪号无效', ],

        'OUTMCHACCNTNO.REPEAT' => ['code' => '201', 'message' => '外部子商户号重复', ],
        'OUTMCHACCNTNO.INVALID' => ['code' => '202', 'message' => '外部子商户号非法', ],
        'MCHACCNTNO.NOTFOUND' => ['code' => '203', 'message' => '子商户帐号不存在', ],
        'SEND.OUTMCHACCNTNO.REPEAT' => ['code' => '204', 'message' => '请求数据中外部子商户号重复', ],

        'BANKCARD.REPEAT' =>  ['code' => '301', 'message' => '银行卡信息重复', ],
        'BANKCARD.AUTH.FAIL' =>  ['code' => '302', 'message' => '银行卡信息认证失败', ],
        'BINKCARD.NOTFOUND' =>  ['code' => '303', 'message' => '银行卡信息不存在', ],
        'CARDNO.CANNONULL' =>  ['code' => '304', 'message' => '卡号不能为空', ],
        'BINKCARD.ALLREADY.UNBING' =>  ['code' => '304', 'message' => '该银行卡已经解绑', ],

        'BATCHCREATE.ACCNT.NUM.INVALID' =>  ['code' => '401', 'message' => '单次批量开设子商户数目非法', ],
        'MCHSUB.BATCHCREATE.FAIL' =>  ['code' => '402', 'message' => '批量开设子商户失败', ],
        'NUM.INVALID' =>  ['code' => '403', 'message' => '数目非法', ],
        'MCHACCNT.WITHDARW.FAIL' =>  ['code' => '404', 'message' => '提现失败', ],
        'DISPATCH.FAIL' =>  ['code' => '405', 'message' => '分账失败', ],
        'DISPATCH.ORDER.INVALID' =>  ['code' => '406', 'message' => '分账失败，单号重复', ],
    ];
}