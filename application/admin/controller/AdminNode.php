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
// 节点控制器
//-------------------------

namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;
use think\Db;
use think\Loader;

class AdminNode extends Controller
{
    use \app\admin\traits\controller\Controller;

//    protected static $blacklist = ['recyclebin', 'delete', 'deleteforever', 'clear', 'recycle'];

    protected function filter(&$map)
    {
        if ($this->request->action() == 'index') {
            $map['pid'] = $this->request->param('pid', 0);
        }

        if ($this->request->param('title')) $map['title'] = ["like", "%" . $this->request->param('title') . "%"];
        if ($this->request->param('name')) $map['name'] = ["like", "%" . $this->request->param('name') . "%"];
    }

    protected function beforeIndex()
    {
        $group_list = Loader::model('AdminGroup')->getList();
        $this->view->assign('group_list', reset_by_key($group_list, "id"));
    }

    protected function beforeRecycleBin()
    {
        $this->beforeIndex();
    }

    protected function beforeAdd()
    {
        //分组
        $group_list = Loader::model('AdminGroup')->getList();
        $this->view->assign('group_list', $group_list);

        //父节点和层级
        $node = Db::name("AdminNode")->where("id", $this->request->param('pid/d'))->field("id,level")->find();
        $vo['pid'] = $node['id'];
        $vo['level'] = intval($node['level']) + 1;
        $this->view->assign('vo', $vo);
    }

    protected function beforeEdit()
    {
        // 分组
        $group_list = Loader::model('AdminGroup')->getList();
        $this->view->assign('group_list', $group_list);
    }

    /**
     * 禁用限制
     */
    protected function beforeForbid()
    {
        // 禁止禁用 Admin 模块,权限设置节点
        $this->filterId([1, 2, 3, 4, 5, 6], '该记录不能被禁用');
    }

    /**
     * 删除限制
     */
    protected function beforeDelete()
    {
        // 禁止删除 Admin 模块,权限设置节点
        $this->filterId([1, 2, 3, 4, 5, 6], '该节点不能被删除');
    }

    /**
     * 删除限制
     */
    protected function beforeDeleteForever()
    {
        // 禁止删除 Admin 模块,权限设置节点
        $this->filterId([1, 2, 3, 4, 5, 6], '该节点不能被删除');
    }

    /**
     * 节点快速导入
     */
    public function load()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $node_template = isset($data['node']) ? $data['node'] : [];
            $node_detect = isset($data['node_name']) ? $data['node_name'] : [];
            unset($data['node'], $data['node_name']);

            $error = Loader::model('AdminNode', 'logic')->insertLoad($node_template, $node_detect, $data);

            if ($error) {
                //拼接错误信息
                $errormsg = "部分节点导入失败：";
                foreach ($error as $err) {
                    $errormsg .= "<br>{$err['data']['title']}({$err['data']['name']})：{$err['error']}";
                }
                $errormsg .= "<p class='c-red'>请手动刷新页面</p>";

                return ajax_return_adv('', '', $errormsg);
            }

            return ajax_return_adv("批量导入成功");
        } else {
            // 分组
            $group_list = Loader::model('AdminGroup')->getList();
            $this->view->assign('group_list', $group_list);

            // 父节点和层级
            $db_node = Db::name("AdminNode");
            $node = $db_node->where("id", $this->request->param('pid/d'))->field("id,pid,name,level")->find();
            $vo['pid'] = $node['id'];
            $vo['level'] = intval($node['level']) + 1;
            $this->view->assign('vo', $vo);

            // 模板库
            $node_template = Db::name("AdminNodeLoad")->field("id,name,title")->where("status=1")->select();
            $this->view->assign("node_template", $node_template);

            // 公共方法
            $node_public = \ReadClass::method("\\app\\admin\\Controller");
            $this->view->assign("node_public", $node_public ?: []);

            // 当前方法
            // 递归获取所有父级节点
            $parent_node = "";
            $pid = $node['pid'];
            while ($pid > 1) {
                if ($current_node = $db_node->where("id", $pid)->field("id,pid,name")->find()) {
                    $parent_node = "\\" . $current_node['name'] . $parent_node;
                    $pid = $current_node['pid'];
                } else {
                    break;
                }
            }
            // 方法生成
            $node_current_name = "\\app\\admin\\controller" . strtolower($parent_node) . "\\" .
                \think\Loader::parseName($node['name'], 1);
            $node_current = \ReadClass::method($node_current_name);
            $this->view->assign("node_current", $node_current ?: []);

            return $this->view->fetch();
        }
    }
}
