<?php
/**
 * The html template file of scan method of dingtalklogin module.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @link        https://www.zentao.net
 */
?>
<?php include '../../common/view/header.lite.html.php'; ?>
<main id="main" class="fade no-padding">
  <div class="container" id="login">
    <div id="loginPanel">
      <header>
        <h2><?php echo $lang->dingtalklogin->scanTitle; ?></h2>
      </header>
      <div class="table-row text-center">
        <div id="login_container" style="margin: 20px auto;"></div>
      </div>
    </div>
  </div>
</main>
<script src="https://g.alicdn.com/dingding/dinglogin/0.0.5/ddLogin.js"></script>
<script>
(function() {
    var gotoUrl = 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid=<?php echo $appKey; ?>&response_type=code&scope=snsapi_login&state=<?php echo $state; ?>&redirect_uri=<?php echo urlencode($callbackUrl); ?>';
    var gotoEncoded = encodeURIComponent(gotoUrl);

    DDLogin({
        id: "login_container",
        goto: gotoEncoded,
        style: "border:none;background-color:#FFFFFF;",
        width: "365",
        height: "400"
    });

    var handleMessage = function(event) {
        var origin = event.origin;
        if(origin !== "https://login.dingtalk.com") return;

        var loginTmpCode = event.data;
        window.location.href = gotoUrl + "&loginTmpCode=" + loginTmpCode;
    };

    if(typeof window.addEventListener !== "undefined") {
        window.addEventListener("message", handleMessage, false);
    } else if(typeof window.attachEvent !== "undefined") {
        window.attachEvent("onmessage", handleMessage);
    }
})();
</script>
<?php include '../../common/view/footer.lite.html.php'; ?>
