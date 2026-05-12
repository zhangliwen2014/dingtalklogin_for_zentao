<?php
namespace zin;

div
(
    setID('main'),
    setClass('no-padding'),
    setStyle(array('opacity' => '1', 'min-height' => '100vh')),
    div
    (
        setID('login'),
        setStyle(array('display' => 'flex', 'justify-content' => 'center', 'align-items' => 'center', 'min-height' => '100vh')),
        div
        (
            setID('loginPanel'),
            setStyle(array('padding' => '40px', 'text-align' => 'center', 'background' => '#fff', 'border-radius' => '8px', 'box-shadow' => '0 2px 12px rgba(0,0,0,0.1)', 'min-width' => '360px')),
            h2(setClass('font-bold'), '请选择要登录的账号'),
            p(setStyle(array('color' => '#666', 'margin-bottom' => '20px')), '该钉钉账号绑定了多个禅道用户'),
            form
            (
                set::action($control->createLink('dingtalklogin', 'choose')),
                set::method('post'),
                div
                (
                    setClass('form-group w-full'),
                    setStyle(array('text-align' => 'left')),
                    div
                    (
                        setClass('radio-list'),
                        setStyle(array('text-align' => 'left')),
                        html('<?php foreach($users as $index => $user): ?>' .
                            '<div class="radio-primary" style="margin-bottom:12px;">' .
                            '<input type="radio" name="account" id="user_' . $user->id . '" value="' . $user->account . '"' . ($index === 0 ? ' checked' : '') . '>' .
                            '<label for="user_' . $user->id . '">' . $user->realname . ' (' . $user->account . ')</label>' .
                            '</div>' .
                            '<?php endforeach; ?>')
                    )
                ),
                div
                (
                    setClass('form-group w-full'),
                    setStyle(array('text-align' => 'center', 'margin-top' => '20px')),
                    button(setClass('btn btn-primary btn-wide'), set::type('submit'), '登录')
                ),
                div
                (
                    setStyle(array('margin-top' => '15px')),
                    html('<a href="' . $control->createLink('user', 'login') . '" class="btn btn-wide">返回密码登录</a>')
                )
            )
        )
    )
);

render('pagebase');
