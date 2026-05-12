<?php include '../../common/view/header.lite.html.php'; ?>
<main id="main" class="no-padding" style="opacity:1;min-height:100vh;">
  <div class="container" id="login" style="display:flex;justify-content:center;align-items:center;min-height:100vh;">
    <div id="loginPanel" style="padding:40px;text-align:center;background:#fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);">
      <header><h2><?php echo $lang->dingtalklogin->scanTitle; ?></h2></header>
      <div id="login_container" style="margin:20px auto;width:365px;min-height:400px;"></div>
      <div style="margin-top:20px;">
        <a href="<?php echo helper::createLink('user', 'login'); ?>" class="btn btn-wide">密码登录</a>
      </div>
    </div>
  </div>
</main>
<script src="https://g.alicdn.com/dingding/dinglogin/0.0.5/ddLogin.js"></script>
<script>
window.addEventListener('load', function() {
    var gotoUrl = "<?php echo 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=' . $appKey . '&response_type=code&scope=snsapi_login&state=' . $state . '&redirect_uri=' . urlencode($callbackUrl); ?>";
    var gotoEncoded = encodeURIComponent(gotoUrl);
    if(typeof DDLogin === 'undefined') {
        document.getElementById('login_container').innerHTML = '<p style="color:red;padding:20px;">二维码加载失败，请刷新页面重试</p>';
        return;
    }
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
<?php include '../../common/view/footer.lite.html.php'; ?>
