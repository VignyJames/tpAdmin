<?php
/**
 * tpAdmin [a web admin based ThinkPHP5]
 *
 * @author yuan1994 <tianpian0805@gmail.com>
 * @link http://tpadmin.yuan1994.com/
 * @copyright 2016 yuan1994 all rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

//------------------------
// 分组管理验证器
//-------------------------

namespace app\admin\validate;

use think\Validate;

class AdminGroup extends Validate
{
    protected $rule = [
        "name|分组名称" => "require|unique:admin_group",
    ];
}