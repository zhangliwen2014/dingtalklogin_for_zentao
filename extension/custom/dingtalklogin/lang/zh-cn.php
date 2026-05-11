<?php
$lang->dingtalklogin = new stdclass();
$lang->dingtalklogin->common          = '钉钉登录';
$lang->dingtalklogin->scanTitle       = '钉钉扫码登录';
$lang->dingtalklogin->loginWithDing   = '钉钉登录';

$lang->dingtalklogin->error = new stdclass();
$lang->dingtalklogin->error->notBind      = '您的钉钉账号尚未绑定禅道账号，请联系管理员在【后台-通知-webhook-钉钉工作消息】中进行绑定。';
$lang->dingtalklogin->error->noConfig     = '未配置钉钉工作消息通知，无法使用钉钉登录。';
$lang->dingtalklogin->error->apiFail      = '钉钉服务异常，请稍后重试。';
$lang->dingtalklogin->error->stateInvalid = '登录验证失败，请重新尝试。';
$lang->dingtalklogin->error->getUserFail  = '无法获取钉钉用户信息，请稍后重试。';
