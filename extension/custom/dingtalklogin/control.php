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

        if(empty($code) || empty($state))
        {
            return $this->locate($this->createLink('user', 'login'));
        }

        $result = $this->dingtalkloginZen->handleCallback($code, $state);

        if($result['result'] === 'fail')
        {
            return $this->send(array('result' => 'fail', 'load' => array('alert' => $result['message'], 'locate' => $this->createLink('user', 'login'))));
        }

        return $this->send(array('result' => 'success', 'locate' => $result['locate']));
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
