<?php
/**
 * Hook to inject embedded DingTalk QR code scan into user login page (traditional view mode).
 */

$this->app->loadLang('dingtalklogin');

/* 直接查询 webhook，不依赖 $this->dingtalklogin（Hook 中 Model 加载可能为 null） */
$webhook = $this->dao->select('*')->from(TABLE_WEBHOOK)
    ->where('type')->eq('dinguser')
    ->andWhere('deleted')->eq('0')
    ->fetch();
if(empty($webhook)) return;

$webhook->secret = json_decode($webhook->secret);
if(empty($webhook->secret->appKey) || empty($webhook->secret->appSecret)) return;

$appKey      = $webhook->secret->appKey;
$state       = md5(uniqid((string)mt_rand(), true));
$this->session->set('dingtalkState', $state);
$callbackUrl = $this->createLink('dingtalklogin', 'callback');
/* 动态构建完整 URL：兼容 nginx 反向代理、负载均衡等场景，不硬编码 */
$scheme = 'http';
if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
   (isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https'))
{
    $scheme = 'https';
}
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
if(isset($_SERVER['HTTP_X_FORWARDED_HOST'])) $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
$fullCallbackUrl = $scheme . '://' . $host . $callbackUrl;
$dingBtnText = isset($lang->dingtalklogin->loginWithDing) ? $lang->dingtalklogin->loginWithDing : '钉钉登录';
?><script src="https://g.alicdn.com/dingding/dinglogin/0.0.5/ddLogin.js"></script>
<script>
$(function() {
    var $form = $('#loginForm, #loginPanel form').first();
    if(!$form.length) return;

    /* 显示钉钉登录错误提示（如有） */
    <?php if(isset($this->session->dingtalkError) && $this->session->dingtalkError): ?>
    $form.before('<div class="alert alert-danger"><?php echo $this->session->dingtalkError; ?></div>');
    <?php $this->session->set('dingtalkError', null); ?>
    <?php endif; ?>

    /* 显示前端调试信息（如有） */
    var debugInfo = localStorage.getItem('dingtalkDebug');
    if(debugInfo) {
        try {
            var d = JSON.parse(debugInfo);
            $form.before('<div class="alert alert-info">钉钉调试: origin=' + d.origin + ', dataType=' + d.dataType + ', data=' + d.data + '</div>');
        } catch(e) {}
    }

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

        var state = <?php echo json_encode($state); ?>;
        var callbackUrl = <?php echo json_encode($callbackUrl); ?>;
        var fullCallbackUrl = <?php echo json_encode($fullCallbackUrl); ?>;

        if(typeof DDLogin === 'undefined') {
            $('#dingtalk_login_container').html('<p style="color:red;padding:20px;">二维码加载失败，请刷新页面重试</p>');
            return;
        }

        $('#dingtalk_login_container').html('');
        /* goto 中的 redirect_uri 必须是完整 URL（含 https://域名），钉钉服务端用它校验回调域名配置 */
        var gotoUrl = 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=<?php echo $appKey; ?>&response_type=code&scope=snsapi_login&state=' + encodeURIComponent(state) + '&redirect_uri=' + encodeURIComponent(fullCallbackUrl);
        DDLogin({
            id: "dingtalk_login_container",
            goto: encodeURIComponent(gotoUrl),
            style: "border:none;background-color:#FFFFFF;",
            width: "365",
            height: "400"
        });

        var handleMessage = function(event) {
            /* 前端调试：记录所有 postMessage 事件到 localStorage */
            var debugInfo = {
                time: new Date().toISOString(),
                origin: event.origin,
                dataType: typeof event.data,
                data: typeof event.data === 'string' ? event.data.substring(0, 100) : JSON.stringify(event.data).substring(0, 100)
            };
            localStorage.setItem('dingtalkDebug', JSON.stringify(debugInfo));

            if(event.origin !== "https://login.dingtalk.com") return;
            /* 扫码成功后直接用 loginTmpCode 跳转到 callback，不走钉钉 sns_authorize 重定向 */
            window.location.href = callbackUrl + '?code=' + encodeURIComponent(event.data) + '&state=' + encodeURIComponent(state);
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
