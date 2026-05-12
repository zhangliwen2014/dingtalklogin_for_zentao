<?php
declare(strict_types=1);
/**
 * The zen file of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @link        https://www.zentao.net
 */
class dingtalkloginZen extends dingtalklogin
{
    /**
     * 处理扫码登录回调。
     * Handle scan login callback.
     *
     * @param  string $code   authorization code from DingTalk
     * @param  string $state  CSRF state parameter
     * @access public
     * @return array
     */
    public function handleCallback(string $code, string $state): array
    {
        if($state !== $this->session->dingtalkState)
        {
            return array('result' => 'fail', 'message' => $this->lang->dingtalklogin->error->stateInvalid);
        }

        $userid = $this->dingtalklogin->getUseridByCode('scan', $code);
        if($userid === false)
        {
            return array('result' => 'fail', 'message' => $this->lang->dingtalklogin->error->getUserFail);
        }

        return $this->processLogin($userid);
    }

    /**
     * 处理免登登录。
     * Handle SSO login.
     *
     * @param  string $authCode  authCode from DingTalk JSAPI
     * @access public
     * @return array
     */
    public function handleSso(string $authCode): array
    {
        $userid = $this->dingtalklogin->getUseridByCode('sso', $authCode);
        if($userid === false)
        {
            return array('result' => 'fail', 'message' => $this->lang->dingtalklogin->error->getUserFail);
        }

        return $this->processLogin($userid);
    }

    /**
     * 处理登录逻辑：查询绑定关系并写入禅道登录态。
     * Process login: check binding and write Zentao session.
     *
     * @param  string $userid  DingTalk userid
     * @access public
     * @return array
     */
    public function processLogin(string $userid): array
    {
        $user = $this->dingtalklogin->getBoundUser($userid);
        if($user === false)
        {
            return array('result' => 'fail', 'message' => $this->lang->dingtalklogin->error->notBind);
        }

        $this->loadModel('user')->login($user);
        $this->loadModel('action')->create('user', (int)$user->id, 'login');

        return array('result' => 'success', 'locate' => $this->config->webRoot);
    }

    /**
     * 生成 CSRF state 并存入 session。
     * Generate CSRF state and store in session.
     *
     * @access public
     * @return string
     */
    public function generateState(): string
    {
        $state = md5(uniqid((string)mt_rand(), true));
        $this->session->set('dingtalkState', $state);
        return $state;
    }

    /**
     * 构建扫码登录回调地址。
     * Build scan login callback URL.
     *
     * @access public
     * @return string
     */
    public function getCallbackUrl(): string
    {
        return common::getSysURL() . $this->createLink('dingtalklogin', 'callback');
    }
}
