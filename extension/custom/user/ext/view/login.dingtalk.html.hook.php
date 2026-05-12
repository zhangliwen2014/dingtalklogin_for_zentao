<?php
/**
 * Hook to inject embedded DingTalk QR code scan into user login page (traditional view mode).
 */

$this->app->loadLang('dingtalklogin');

$webhook = $this->dingtalklogin->getDingWebhook();
if(empty($webhook)) return;

$appKey      = $webhook->secret->appKey;
$state       = md5(uniqid((string)mt_rand(), true));
$this->session->set('dingtalkState', $state);
$callbackUrl = common::getSysURL() . $this->createLink('dingtalklogin', 'callback');
$gotoUrl     = 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=' . $appKey . '&response_type=code&scope=snsapi_login&state=' . $state . '&redirect_uri=' . urlencode($callbackUrl);
$dingBtnText = isset($lang->dingtalklogin->loginWithDing) ? $lang->dingtalklogin->loginWithDing : '钉钉登录';
?><script src="https://g.alicdn.com/dingding/dinglogin/0.0.5/ddLogin.js"></script>
<script>
$(function() {
    var $form = $('#loginForm, #loginPanel form').first();
    if(!$form.length) return;

    // 在表单底部添加"或"分隔线 + 钉钉登录按钮
    var $lastRow = $form.find('tr:last, .form-group:last').first();
    if(!$lastRow.length) $lastRow = $form;

    var dingHtml =
        '<div class="dingtalk-login-wrap" style="text-align:center;margin-top:15px;">' +
        '<div style="margin:12px 0;position:relative;">' +
        '<span style="background:#fff;padding:0 12px;position:relative;z-index:1;color:#999;font-size:12px;">或</span>' +
        '<div style="position:absolute;top:50%;left:0;right:0;border-top:1px solid #e5e5e5;"></div>' +
        '</div>' +
        '<button type="button" id="dingtalkLoginBtn" class="btn btn-primary btn-wide" style="background:#0089ff;border-color:#0089ff;">' +
        <?php echo json_encode('<i class="icon icon-dingtalk"></i> ' . $dingBtnText); ?> +
        '</button>' +
        '</div>';

    if($lastRow.is('tr')) {
        $lastRow.after('<tr><td colspan="2">' + dingHtml + '</td></tr>');
    } else {
        $lastRow.after(dingHtml);
    }

    // 创建二维码容器（与表单同级，默认隐藏）
    var qrcodeHtml =
        '<div id="dingtalkQrcodeWrap" style="display:none;text-align:center;padding:20px 0;">' +
        '<h3 style="margin-bottom:20px;font-weight:bold;font-size:18px;"><?php echo $lang->dingtalklogin->scanTitle; ?></h3>' +
        '<div id="dingtalk_login_container" style="margin:0 auto;width:365px;min-height:400px;"></div>' +
        '<div style="margin-top:20px;">' +
        '<button type="button" id="backToPasswordBtn" class="btn btn-wide">密码登录</button>' +
        '</div>' +
        '</div>';
    $form.after(qrcodeHtml);

    // 点击钉钉登录：隐藏表单，显示二维码
    $('#dingtalkLoginBtn').on('click', function() {
        $form.hide();
        $('.dingtalk-login-wrap').hide();
        $('#dingtalkQrcodeWrap').show();

        var gotoUrl = <?php echo json_encode($gotoUrl); ?>;
        var gotoEncoded = encodeURIComponent(gotoUrl);

        if(typeof DDLogin === 'undefined') {
            $('#dingtalk_login_container').html('<p style="color:red;padding:20px;">二维码加载失败，请刷新页面重试</p>');
            return;
        }

        $('#dingtalk_login_container').html('');
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
    $(document).on('click', '#backToPasswordBtn', function() {
        $('#dingtalkQrcodeWrap').hide();
        $form.show();
        $('.dingtalk-login-wrap').show();
        $('#dingtalk_login_container').html('');
    });
});
</script>
