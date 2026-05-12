<?php
namespace zin;

$gotoUrl     = 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=' . $appKey . '&response_type=code&scope=snsapi_login&state=' . $state . '&redirect_uri=' . urlencode($callbackUrl);
$gotoEncoded = urlencode($gotoUrl);
$loginLink   = helper::createLink('user', 'login');

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
            setStyle(array('padding' => '40px', 'text-align' => 'center', 'background' => '#fff', 'border-radius' => '8px', 'box-shadow' => '0 2px 12px rgba(0,0,0,0.1)')),
            h2(setClass('font-bold'), $lang->dingtalklogin->scanTitle),
            div(setID('login_container'), setStyle(array('margin' => '20px auto', 'width' => '365px', 'min-height' => '400px'))),
            div
            (
                setStyle(array('margin-top' => '20px')),
                html('<a href="' . $loginLink . '" class="btn btn-wide">密码登录</a>')
            )
        )
    )
);

html(<<<HTML
<script src="https://g.alicdn.com/dingding/dinglogin/0.0.5/ddLogin.js"></script>
<script>
window.addEventListener('load', function() {
    var gotoUrl = "{$gotoUrl}";
    var gotoEncoded = encodeURIComponent(gotoUrl);
    var container = document.getElementById('login_container');
    if(typeof DDLogin === 'undefined') {
        if(container) container.innerHTML = '<p style="color:red;padding:20px;">二维码加载失败，请刷新页面重试</p>';
        return;
    }
    container.innerHTML = '';
    DDLogin({
        id: "login_container",
        goto: gotoEncoded,
        style: "border:none;background-color:#FFFFFF;",
        width: "365",
        height: "400"
    });
    var handleMessage = function(event) {
        if(event.origin !== "https://login.dingtalk.com") return;
        window.location.href = gotoUrl + "&loginTmpCode=" + encodeURIComponent(event.data);
    };
    if(window._dingtalkMsgHandler) {
        window.removeEventListener("message", window._dingtalkMsgHandler, false);
    }
    window._dingtalkMsgHandler = handleMessage;
    window.addEventListener("message", handleMessage, false);
});
</script>
HTML
);

render('pagebase');
