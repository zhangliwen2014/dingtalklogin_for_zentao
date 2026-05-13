<?php
namespace zin;

$loginLink   = helper::createLink('user', 'login');
$confirmLink = helper::createLink('dingtalklogin', 'confirm');

/* 构建用户单选列表 */
$userOptions = array();
if(!empty($users))
{
    foreach($users as $index => $user)
    {
        $userOptions[] = div(setClass('form-group'),
            html('<label style="display:block;margin:8px 0;cursor:pointer;"><input type="radio" name="account" value="' . $user->account . '" ' . ($index === 0 ? 'checked' : '') . '> ' . $user->realname . ' (' . $user->account . ')' . ($user->role ? ' [' . $user->role . ']' : '') . '</label>')
        );
    }
}

div(
    setID('main'), setClass('no-padding'),
    setStyle(array('opacity' => '1', 'min-height' => '100vh')),
    div(
        setID('login'),
        setStyle(array('display' => 'flex', 'justify-content' => 'center', 'align-items' => 'center', 'min-height' => '100vh')),
        div(
            setID('loginPanel'),
            setStyle(array('padding' => '40px', 'background' => '#fff', 'border-radius' => '8px', 'box-shadow' => '0 2px 12px rgba(0,0,0,0.1)', 'max-width' => '700px', 'width' => '100%')),
            h2(setClass('font-bold'), '钉钉登录回调调试'),

            /* 回调参数 */
            div(setClass('panel panel-default'), setStyle(array('margin-bottom' => '15px')),
                div(setClass('panel-heading font-bold'), '回调参数'),
                div(setClass('panel-body'),
                    table(setClass('table table-bordered table-hover'),
                        tr(th('参数'), th('值')),
                        tr(td('code'), td(empty($code) ? '空' : htmlspecialchars((string)$code))),
                        tr(td('state'), td(empty($state) ? '空' : htmlspecialchars((string)$state))),
                        tr(td('sessionState'), td(htmlspecialchars((string)$sessionState)))
                    )
                )
            ),

            /* 解密信息 */
            div(setClass('panel panel-default'), setStyle(array('margin-bottom' => '15px')),
                div(setClass('panel-heading font-bold'), '解密信息'),
                div(setClass('panel-body'),
                    table(setClass('table table-bordered table-hover'),
                        tr(th('项目'), th('值')),
                        tr(td('钉钉 userid'), td(htmlspecialchars((string)$userid)))
                    )
                )
            ),

            /* 错误提示 */
            !empty($error) ? div(setClass('alert alert-danger'), $error) : null,

            /* 绑定用户列表 + 确认表单 */
            empty($error) && !empty($users) ? div(setClass('panel panel-default'), setStyle(array('margin-bottom' => '15px')),
                div(setClass('panel-heading font-bold'), '绑定用户列表（共 ' . count($users) . ' 个）'),
                div(setClass('panel-body'),
                    form(
                        set::action($confirmLink),
                        set::method('post'),
                        div(setStyle(array('margin' => '10px 0')), $userOptions),
                        div(setStyle(array('margin-top' => '20px')),
                            html('<button type="submit" class="btn btn-primary btn-wide">确认登录</button>'),
                            html('<a href="' . $loginLink . '" class="btn btn-wide" style="margin-left:8px;">返回登录页</a>')
                        )
                    )
                )
            ) : null,

            /* 无用户时的返回按钮 */
            !empty($error) && empty($users) ? div(setStyle(array('margin-top' => '20px')),
                html('<a href="' . $loginLink . '" class="btn btn-wide">返回登录页</a>')
            ) : null
        )
    )
);

render('pagebase');
