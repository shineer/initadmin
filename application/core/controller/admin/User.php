<?php
/**
 * +----------------------------------------------------------------------
 * | InitAdmin/actionphp [ InitAdmin渐进式模块化通用后台 ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2018-2019 http://initadmin.net All rights reserved.
 * +----------------------------------------------------------------------
 * | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 * +----------------------------------------------------------------------
 * | Author: jry <ijry@qq.com>
 * +----------------------------------------------------------------------
*/

namespace app\core\controller\admin;

use think\Db;
use think\Validate;
use think\facade\Request;
use app\core\controller\common\Admin;
use app\core\util\Tree;

/**
 * 用户
 */
class User extends Admin
{
    private $core_user;

    protected function initialize()
    {
        parent::initialize();
        $this->core_user = Db::name('core_user');
    }

    /**
     * 用户列表
     *
     * @return \think\Response
     */
    public function lists()
    {
        //用户列表
        $data_list = $this->core_user
            ->where(['delete_time' => 0])
            ->select();
        $tree      = new Tree();
        $data_list = $tree->list2tree($data_list);

        //构造动态页面数据
        $ia_dylist      = new \app\core\util\iadypage\IaDylist();
        $dynamic_data = $ia_dylist->init()
            ->addTopButton('add', '添加用户', ['api' => '/v1/admin/core/user/add'])
            ->addRightButton('edit', '修改', ['api' => '/v1/admin/core/user/edit', 'title' => '修改用户信息'])
            ->addRightButton('delete', '删除', [
                'api' => '/v1/admin/core/user/delete',
                'title' => '确认要删除该用户吗？',
                'modal_type' => 'confirm',
                'width' => '600',
                'okText' => '确认删除',
                'cancelText' => '取消操作',
                'content' => '<p>删除后将清空绑定的所有登录验证记录</p>',
            ])
            ->addColumn('id' , 'ID', ['width' => '50px'])
            ->addColumn('nickname', '昵称', ['width' => '120px'])
            ->addColumn('username', '用户名', ['width' => '120px'])
            ->addColumn('mobile', '手机号', ['width' => '120px'])
            ->addColumn('email', '邮箱', ['width' => '120px'])
            ->addColumn('sortnum', '排序', ['width' => '50px'])
            ->addColumn('right_button_list', '操作', [
                'minWidth' => '50px',
                'type' => 'template',
                'template' => 'right_button_list'
            ])
            ->getData();
        
        //返回数据
        return json([
                'code' => 200, 'msg' => '成功', 'data' => [
                    'data_list' => $data_list,
                    'dynamic_data' => $dynamic_data
                ]
            ]);
    }

    /**
     * 添加
     *
     * @return \think\Response
     */
    public function add()
    {
        if (request()->isPost()) {
            // 数据验证
            $validate = Validate::make([
                'nickname'  => 'require',
                'username' => 'require',
                'password' => 'require'
            ],
            [
                'nickname.require' => '昵称必须',
                'username.require' => '用户名必须',
                'password.require' => '密码必须'
            ]);
            $data = input('post.');
            if (!$validate->check($data)) {
                return json(['code' => 200, 'msg' => $validate->getError(), 'data' => []]);
            }
            
            // 数据构造
            $data_db = [];
            $data_db['nickname'] = $data['nickname'];
            $data_db['username'] = $data['username'];
            $data_db['password'] = user_md5($data['password']); // 密码不能明文需要加密存储
            $data_db['avatar']   = isset($data['avatar']) ? $data['avatar'] : '';
            $data_db['status']   = 1;
            $data_db['register_time']   = time();

            // 存储数据
            $ret = $this->core_user->insert($data_db);
            if ($ret) {
                return json(['code' => 200, 'msg' => '添加用户成功', 'data' => []]);
            } else {
                return json(['code' => 0, 'msg' => '添加用户失败', 'data' => []]);
            }
        } else {
            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'form_data' => [
                        'form_method' => 'post',
                        'form_items' => [
                            [
                                'name' => 'nickname',
                                'title' => '昵称',
                                'type' => 'text',
                                'placeholder' => '请输入昵称',
                                'tip' => '昵称类似微信昵称可以重复不能用来登录系统'
                            ],
                            [
                                'name' => 'username',
                                'title' => '用户名',
                                'type' => 'text',
                                'placeholder' => '请输入用户名',
                                'tip' => '用户名唯一不重复，可以用来登录系统'
                            ],
                            [
                                'name' => 'password',
                                'title' => '密码',
                                'type' => 'text',
                                'placeholder' => '请输入用户密码',
                                'tip' => '密码必须要包含字母数字和符号中的两种'
                            ]
                        ],
                        'form_values' => [
                            'nickname' => '',
                            'username' => '',
                            'password' => '',
                        ],
                        'form_rules' => [
                            'nickname' =>  [
                                [
                                    'required' => true,
                                    'message' => '请填写昵称',
                                    'trigger' => 'change'
                                ]
                            ],
                            'username' =>  [
                                [
                                    'required' => true,
                                    'message' => '请填写用户名',
                                    'trigger' => 'change'
                                ]
                            ],
                            'password' =>  [
                                [
                                    'required' => true,
                                    'message' => '请填写密码',
                                    'trigger' => 'change'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        } 
    }

    /**
     * 修改
     *
     * @return \think\Response
     */
    public function edit($id)
    {
        if (request()->isPut()) {
            // 数据验证
            $validate = Validate::make([
                    'nickname'  => 'require',
                    'username' => 'require',
                ],
                [
                    'nickname.require' => '昵称必须',
                    'username.require' => '用户名必须',
                ]);
            $data = input('post.');
            if (!$validate->check($data)) {
                return json(['code' => 200, 'msg' => $validate->getError(), 'data' => []]);
            }

            // 数据构造
            $data_db = $data;
            if (isset($data_db['password'])) {
                $data_db['password'] = user_md5($data_db['password']); // 密码不能明文需要加密存储
            }
            if (count($data_db) <= 0 ) {
                return json(['code' => 0, 'msg' => '无数据修改提交', 'data' => []]);
            }

            // 存储数据
            $ret = $this->core_user
                ->where('id', $id)
                ->update($data_db);
            if ($ret) {
                return json(['code' => 200, 'msg' => '修改用户信息成功', 'data' => []]);
            } else {
                return json(['code' => 0, 'msg' => '修改用户信息失败', 'data' => []]);
            }
        } else {
            $info = $this->core_user
                ->where('id', $id)
                ->find();
            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'form_data' => [
                        'form_method' => 'put',
                        'form_items' => [
                            [
                                'name' => 'nickname',
                                'title' => '昵称',
                                'type' => 'text',
                                'placeholder' => '请输入昵称',
                                'tip' => '昵称类似微信昵称可以重复不能用来登录系统'
                            ],
                            [
                                'name' => 'username',
                                'title' => '用户名',
                                'type' => 'text',
                                'placeholder' => '请输入用户名',
                                'tip' => '用户名唯一不重复，可以用来登录系统'
                            ],
                            [
                                'name' => 'password',
                                'title' => '密码',
                                'type' => 'text',
                                'placeholder' => '不填则不修改密码',
                                'tip' => '密码必须要包含字母数字和符号中的两种'
                            ]
                            
                        ],
                        'form_values' => [
                            'nickname' => $info['nickname'],
                            'username' => $info['username'],
                            'password' => '',
                        ],
                        'form_rules' => [
                            'nickname' =>  [
                                [
                                    'required' => true,
                                    'message' => '请填写昵称',
                                    'trigger' => 'change'
                                ]
                            ],
                            'username' =>  [
                                [
                                    'required' => true,
                                    'message' => '请填写用户名',
                                    'trigger' => 'change'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        } 
    }

    /**
     * 删除
     * 
     * @return \think\Response
     */
    public function delete($id)
    {
        $ret = Db::name('core_user')
            ->where(['id' => $id])
            ->useSoftDelete('delete_time', time())
            ->delete();
        if ($ret) {
            return json(['code' => 200, 'msg' => '删除成功', 'data' => []]);
        } else {
            return json(['code' => 200, 'msg' => '删除错误', 'data' => []]);
        }
    }
}
