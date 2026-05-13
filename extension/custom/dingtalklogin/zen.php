<?php
declare(strict_types=1);
/**
 * The zen file of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @version     $Id$
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
     * @access protected
     * @return array
     */
    protected function processLogin(string $userid): array
    {
        $users = $this->dingtalklogin->getBoundUsers($userid);
        if(empty($users))
        {
            return array('result' => 'fail', 'message' => $this->lang->dingtalklogin->error->notBind);
        }

        if(count($users) > 1)
        {
            return array('result' => 'multi', 'users' => $users);
        }

        $loginResult = $this->doLogin($users[0]);
        if(!$loginResult)
        {
            return array('result' => 'fail', 'message' => '登录失败，请检查禅道用户状态');
        }

        /* 使用明确的 my-index 跳转，避免 webRoot 为空或指向根路径时再次触发登录检查 */
        return array('result' => 'success', 'locate' => $this->createLink('my', 'index'));
    }

    /**
     * 执行禅道登录并记录日志。
     * Perform Zentao login and create action log.
     *
     * @param  object $user  user object
     * @access protected
     * @return bool
     */
    protected function doLogin(object $user): bool
    {
        $result = $this->loadModel('user')->login($user);
        if($result === false)
        {
            $logFile = $this->app->logRoot . 'dingtalk_debug.log';
            $logMsg  = date('Y-m-d H:i:s') . ' doLogin FAILED: account=' . ($user->account ?? 'NULL') . ', id=' . ($user->id ?? 'NULL') . PHP_EOL;
            file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);
            return false;
        }

        $this->loadModel('action')->create('user', (int)$user->id, 'login');

        /* 强制写入 session，避免 locate() 抛异常后 session 未及时落盘 */
        session_write_close();

        return true;
    }

    /**
     * 生成 CSRF state 并存入 session。
     * Generate CSRF state and store in session.
     *
     * @access protected
     * @return string
     */
    protected function generateState(): string
    {
        $state = md5(uniqid((string)mt_rand(), true));
        $this->session->set('dingtalkState', $state);
        return $state;
    }

    /**
     * 构建扫码登录回调地址。
     * Build scan login callback URL.
     *
     * @access protected
     * @return string
     */
    protected function getCallbackUrl(): string
    {
        return common::getSysURL() . $this->createLink('dingtalklogin', 'callback');
    }
}
