<?php
/**
 * Hook to inject DingTalk login button into user login page (ZIN ui mode).
 * In ZIN mode $this points to zin\context, use $this->control to access baseControl.
 */

$control = $this->control;
$control->app->loadLang('dingtalklogin');

$dingWebhook = $control->dao->select('*')->from(\TABLE_WEBHOOK)
    ->where('type')->eq('dinguser')
    ->andWhere('deleted')->eq('0')
    ->fetch();
if(empty($dingWebhook)) return;

$dingLink    = $control->createLink('dingtalklogin', 'scan');
$dingBtnText = isset($control->lang->dingtalklogin->loginWithDing) ? $control->lang->dingtalklogin->loginWithDing : '钉钉登录';
?>
<script>
$(function() {
    var $target = $('#loginPanel form .form-actions');
    if($target.length === 0) $target = $('.form-actions');
    if($target.length === 0) $target = $('#loginForm').closest('.cell').find('.form-group:last');
    if($target.length === 0) return;

    var dingLink = '<?php echo $dingLink; ?>';
    var dingBtn  = '<a href="' + dingLink + '" class="btn" style="margin-left:8px;"><i class="icon-dingtalk"></i> <?php echo $dingBtnText; ?></a>';
    $target.append(dingBtn);
});
</script>
