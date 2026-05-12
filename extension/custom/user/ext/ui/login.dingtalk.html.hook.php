<?php
/**
 * Hook to inject embedded DingTalk QR code scan into user login page (ZIN ui mode).
 *
 * In ZIN mode $this points to zin\context, use $this->control to access baseControl.
 * Use window.waitDom() (ZIN built-in helper) instead of DOMContentLoaded
 * to safely manipulate DOM elements that are rendered asynchronously by ZIN widgets.
 */

$control = $this->control;
$control->app->loadLang('dingtalklogin');

/* 直接查询 webhook，不依赖 $control->dingtalklogin（Hook 中 Model 加载可能为 null） */
$webhook = $control->dao->select('*')->from(\TABLE_WEBHOOK)
    ->where('type')->eq('dinguser')
    ->andWhere('deleted')->eq('0')
    ->fetch();
if(empty($webhook)) return;

$webhook->secret = json_decode($webhook->secret);
if(empty($webhook->secret->appKey) || empty($webhook->secret->appSecret)) return;

$appKey      = $webhook->secret->appKey;
$state       = md5(uniqid((string)mt_rand(), true));
$control->session->set('dingtalkState', $state);
$callbackUrl = common::getSysURL() . $control->createLink('dingtalklogin', 'callback');
$gotoUrl     = 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=' . $appKey . '&response_type=code&scope=snsapi_login&state=' . $state . '&redirect_uri=' . urlencode($callbackUrl);
$dingBtnText = isset($control->lang->dingtalklogin->loginWithDing) ? $control->lang->dingtalklogin->loginWithDing : '钉钉登录';
?><script src="https://g.alicdn.com/dingding/dinglogin/0.0.5/ddLogin.js"></script>
<script>
window.waitDom('#loginForm', function() {
    var loginForm = document.getElementById('loginForm');
    if(!loginForm) return;

    /* 显示钉钉登录错误提示（如有） */
    <?php if(isset($control->session->dingtalkError) && $control->session->dingtalkError): ?>
    var errorDiv = document.createElement('div');
    errorDiv.className = 'form-group w-full';
    errorDiv.innerHTML = '<div class="alert alert-danger"><?php echo $control->session->dingtalkError; ?></div>';
    loginForm.insertBefore(errorDiv, loginForm.firstChild);
    <?php $control->session->set('dingtalkError', null); ?>
    <?php endif; ?>

    // 在表单底部添加"或"分隔线 + 钉钉登录按钮
    var lastGroup = loginForm.querySelector('.form-group:last-child');
    var dingWrap = document.createElement('div');
    dingWrap.className = 'form-group w-full dingtalk-login-wrap';
    dingWrap.innerHTML =
        '<div style="text-align:center;margin-top:8px;">' +
        '<div style="margin:12px 0;position:relative;">' +
        '<span style="background:#fff;padding:0 12px;position:relative;z-index:1;color:#999;font-size:12px;">或</span>' +
        '<div style="position:absolute;top:50%;left:0;right:0;border-top:1px solid #e5e5e5;"></div>' +
        '</div>' +
        '<button type="button" id="dingtalkLoginBtn" class="btn btn-primary btn-wide" style="background:#0089ff;border-color:#0089ff;">' +
        <?php echo json_encode('<i class="icon icon-dingtalk"></i> ' . $dingBtnText); ?> +
        '</button>' +
        '</div>';
    if(lastGroup) {
        lastGroup.after(dingWrap);
    } else {
        loginForm.appendChild(dingWrap);
    }

    // 创建二维码容器（与表单同级，默认隐藏）
    var qrcodeWrap = document.createElement('div');
    qrcodeWrap.id = 'dingtalkQrcodeWrap';
    qrcodeWrap.style.cssText = 'display:none;';
    qrcodeWrap.innerHTML =
        '<div style="text-align:center;padding:20px 0;">' +
        '<h3 style="margin-bottom:20px;font-weight:bold;font-size:18px;"><?php echo $control->lang->dingtalklogin->scanTitle; ?></h3>' +
        '<div id="dingtalk_login_container" style="margin:0 auto;width:365px;min-height:400px;"></div>' +
        '<div style="margin-top:20px;">' +
        '<button type="button" id="backToPasswordBtn" class="btn btn-wide">密码登录</button>' +
        '</div>' +
        '</div>';
    loginForm.after(qrcodeWrap);

    // 点击钉钉登录：隐藏表单，显示二维码
    document.getElementById('dingtalkLoginBtn').addEventListener('click', function() {
        loginForm.style.display = 'none';
        document.querySelector('.dingtalk-login-wrap').style.display = 'none';
        qrcodeWrap.style.display = 'block';

        var gotoUrl = <?php echo json_encode($gotoUrl); ?>;
        var gotoEncoded = encodeURIComponent(gotoUrl);
        var container = document.getElementById('dingtalk_login_container');

        if(typeof DDLogin === 'undefined') {
            container.innerHTML = '<p style="color:red;padding:20px;">二维码加载失败，请刷新页面重试</p>';
            return;
        }

        container.innerHTML = '';
        DDLogin({
            id: "dingtalk_login_container",
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

    // 点击密码登录：隐藏二维码，显示表单
    document.getElementById('backToPasswordBtn').addEventListener('click', function() {
        qrcodeWrap.style.display = 'none';
        loginForm.style.display = '';
        document.querySelector('.dingtalk-login-wrap').style.display = '';
        document.getElementById('dingtalk_login_container').innerHTML = '';
    });
});
</script>
