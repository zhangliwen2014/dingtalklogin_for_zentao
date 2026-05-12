<?php
/**
 * The html template file of sso method of dingtalklogin module.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @version     $Id$
 * @link        https://www.zentao.net
 */
?>
<?php include '../../common/view/header.lite.html.php'; ?>
<main id="main" class="fade no-padding">
  <div class="container" id="login">
    <div id="loginPanel">
      <header>
        <h2><?php echo $lang->dingtalklogin->common; ?></h2>
      </header>
      <div class="table-row text-center">
        <div style="margin: 40px auto;">
          <p><?php echo $lang->dingtalklogin->common; ?>加载中...</p>
        </div>
      </div>
    </div>
  </div>
</main>
<script src="https://g.alicdn.com/dingding/dingtalk-jsapi/2.13.42/dingtalk.open.js"></script>
<script>
(function() {
    dd.ready(function() {
        dd.runtime.permission.requestAuthCode({
            corpId: '<?php echo $appKey; ?>',
            onSuccess: function(result) {
                var authCode = result.code;
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo helper::createLink('dingtalklogin', 'sso'); ?>';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'authCode';
                input.value = authCode;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            },
            onFail: function(err) {
                alert('免登失败：' + JSON.stringify(err));
            }
        });
    });
})();
</script>
<?php include '../../common/view/footer.lite.html.php'; ?>
