<?php
/**
 * Hook to inject DingTalk login button into user login page.
 */

/* Debug mark - check page source for this comment */
echo "<!-- DINGTALK_HOOK_START -->\n";

/* Try to load lang. If fails, use hardcoded text. */
$dingBtnText = '钉钉登录';
if(method_exists($this->app, 'loadLang'))
{
    $this->app->loadLang('dingtalklogin');
    if(isset($lang->dingtalklogin->loginWithDing)) $dingBtnText = $lang->dingtalklogin->loginWithDing;
}

/* Check webhook - if not configured, still show button for debugging */
$hasWebhook = false;
try
{
    $webhook = $this->dao->select('*')->from(TABLE_WEBHOOK)
        ->where('type')->eq('dinguser')
        ->andWhere('deleted')->eq('0')
        ->fetch();
    if(!empty($webhook)) $hasWebhook = true;
}
catch(Exception $e)
{
    $hasWebhook = false;
}

echo "<!-- DINGTALK_WEBHOOK_CHECK_RESULT: " . ($hasWebhook ? 'FOUND' : 'NOT_FOUND') . " -->\n";
?>
<script>
$(function() {
    var $formActions = $('#loginPanel form .form-actions');
    if($formActions.length === 0)
    {
        /* Fallback: try broader selector */
        $formActions = $('.form-actions');
    }
    if($formActions.length === 0)
    {
        console.log('[DingTalkLogin] form-actions not found');
        return;
    }

    var dingLink = '<?php echo helper::createLink('dingtalklogin', 'scan'); ?>';
    var dingBtn = '<a href="' + dingLink + '" class="btn" style="margin-left: 8px;"><i class="icon-dingtalk"></i> <?php echo $dingBtnText; ?></a>';
    $formActions.append(dingBtn);
    console.log('[DingTalkLogin] button injected');
});
</script>
<!-- DINGTALK_HOOK_END -->
