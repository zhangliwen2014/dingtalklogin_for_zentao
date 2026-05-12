<?php
declare(strict_types=1);
/**
 * The control file of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @version     $Id$
 * @link        https://www.zentao.net
 */
class dingtalklogin extends control
{
    /**
     * 扫码登录跳转页。
     * Scan login landing page.
     *
     * @access public
     * @return void
     */
    public function scan()
    {
        $webhook = $this->dingtalklogin->getDingWebhook();
        if(empty($webhook))
        {
            return $this->send(array('result' => 'fail', 'message' => $this->lang->dingtalklogin->error->noConfig));
        }

        $state       = $this->dingtalkloginZen->generateState();
        $appKey      = $webhook->secret->appKey;
        $callbackUrl = $this->dingtalkloginZen->getCallbackUrl();

        $this->view->title       = $this->lang->dingtalklogin->scanTitle;
        $this->view->appKey      = $appKey;
        $this->view->callbackUrl = $callbackUrl;
        $this->view->state       = $state;

        $this->display();
    }

    /**
     * 钉钉扫码登录回调。
     * DingTalk scan login callback.
     *
     * @access public
     * @return void
     */
    public function callback()
    {
        $code  = $this->get->code;
        $state = $this->get->state;

        /* 文件级调试日志，不受框架日志配置影响 */
        $logFile = $this->app->logRoot . 'dingtalk_debug.log';
        $logMsg  = date('Y-m-d H:i:s') . ' callback reached, code=' . (empty($code) ? 'EMPTY' : substr($code, 0, 20)) . ', state=' . (empty($state) ? 'EMPTY' : $state) . ', sessionState=' . ($this->session->dingtalkState ?? 'NULL') . PHP_EOL;
        file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);

        if(empty($code) || empty($state))
        {
            $this->session->set('dingtalkError', '缺少授权参数 code 或 state');
            return $this->locate($this->createLink('user', 'login'));
        }

        $result = $this->dingtalkloginZen->handleCallback($code, $state);

        if($result['result'] === 'fail')
        {
            $this->session->set('dingtalkError', $result['message']);
            return $this->locate($this->createLink('user', 'login'));
        }

        if($result['result'] === 'multi')
        {
            $this->view->users = $result['users'];
            $this->display('choose');
            return;
        }

        return $this->locate($result['locate']);
    }

    /**
     * 多账号选择登录。
     * Choose account when multiple Zentao users are bound to one DingTalk user.
     *
     * @access public
     * @return void
     */
    public function choose()
    {
        $account = $this->post->account;
        if(empty($account))
        {
            return $this->locate($this->createLink('user', 'login'));
        }

        $user = $this->loadModel('user')->getById($account, 'account');
        if(empty($user) || $user->deleted)
        {
            $this->session->set('dingtalkError', $this->lang->dingtalklogin->error->notBind);
            return $this->locate($this->createLink('user', 'login'));
        }

        $this->loadModel('user')->login($user);
        $this->loadModel('action')->create('user', (int)$user->id, 'login');
        return $this->locate($this->config->webRoot);
    }

    /**
     * 企业内部应用免登。
     * Enterprise internal app SSO.
     *
     * @access public
     * @return void
     */
    public function sso()
    {
        if($this->get->authCode)
        {
            $authCode = $this->get->authCode;
        }
        elseif($this->post->authCode)
        {
            $authCode = $this->post->authCode;
        }
        else
        {
            $webhook = $this->dingtalklogin->getDingWebhook();
            if(empty($webhook))
            {
                return $this->send(array('result' => 'fail', 'message' => $this->lang->dingtalklogin->error->noConfig));
            }

            $this->view->title  = $this->lang->dingtalklogin->common;
            $this->view->appKey = $webhook->secret->appKey;
            $this->display();
            return;
        }

        $result = $this->dingtalkloginZen->handleSso($authCode);

        if($result['result'] === 'fail')
        {
            return $this->send(array('result' => 'fail', 'load' => array('alert' => $result['message'], 'locate' => $this->createLink('user', 'login'))));
        }

        return $this->send(array('result' => 'success', 'locate' => $result['locate']));
    }
}
