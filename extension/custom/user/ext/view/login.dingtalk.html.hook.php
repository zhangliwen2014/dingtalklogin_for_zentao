<?php
/**
 * Hook to inject DingTalk login button into user login page.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     user
 * @link        https://www.zentao.net
 */

/* Load dingtalklogin language pack. */
$this->app->loadLang('dingtalklogin');

/* Check if dinguser webhook is configured. */
$dingWebhook = $this->dao->select('*')->from(TABLE_WEBHOOK)
    ->where('type')->eq('dinguser')
    ->andWhere('deleted')->eq('0')
    ->fetch();

if(empty($dingWebhook)) return;
?>
<script>
$(function() {
    var $formActions = $('#loginPanel form .form-actions');
    if($formActions.length === 0) return;

    var dingLink = '<?php echo helper::createLink('dingtalklogin', 'scan'); ?>';
    var dingBtn = '<a href="' + dingLink + '" class="btn" style="margin-left: 8px;"><i class="icon-dingtalk"></i> <?php echo $lang->dingtalklogin->loginWithDing; ?></a>';
    $formActions.append(dingBtn);
});
</script>
