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
     * 重写权限检查，钉钉登录相关方法直接放行。
     * Override privilege check to allow guest access.
     */
    public function checkPriv()
    {
        $module = $this->app->getModuleName();
        $method = $this->app->getMethodName();
        $openMethods = array('scan', 'callback', 'confirm', 'choose', 'sso');
        if($module === 'dingtalklogin' && in_array($method, $openMethods))
        {
            return true;
        }
        return parent::checkPriv();
    }


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
     * 钉钉扫码登录回调（调试模式）。
     * DingTalk scan login callback with debug info.
     *
     * @access public
     * @return void
     */
    public function callback()
    {
        /* 禅道 PATH_INFO 模式下 $_GET 被框架重置，必须从 QUERY_STRING 手动解析钉钉回调参数 */
        $queryParams = array();
        if(!empty($_SERVER['QUERY_STRING'])) parse_str($_SERVER['QUERY_STRING'], $queryParams);
        $code  = $queryParams['code']  ?? ($this->get->code  ?? '');
        $state = $queryParams['state'] ?? ($this->get->state ?? '');

        $this->view->title        = '钉钉登录回调调试';
        $this->view->code         = $code;
        $this->view->state        = $state;
        $this->view->sessionState = $this->session->dingtalkState ?? 'NULL';
        $this->view->error        = '';
        $this->view->userid       = '';
        $this->view->users        = array();

        $logFile = $this->app->logRoot . 'dingtalk_callback.log';
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logMsg  = date('Y-m-d H:i:s') . ' CALLBACK client=' . $clientIp
                 . ' query_string=' . ($_SERVER['QUERY_STRING'] ?? 'EMPTY')
                 . ' parsed_code=' . ($code ?? 'EMPTY')
                 . ' parsed_state=' . ($state ?? 'EMPTY')
                 . ' GET_code=' . ($_GET['code'] ?? 'EMPTY')
                 . ' GET_state=' . ($_GET['state'] ?? 'EMPTY')
                 . ' sessionState=' . ($this->session->dingtalkState ?? 'NULL')
                 . PHP_EOL;
        file_put_contents($logFile, $logMsg, FILE_APPEND | LOCK_EX);

        if(empty($code) || empty($state))
        {
            $this->view->error = '缺少授权参数 code 或 state';
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' ERROR: 缺少 code 或 state' . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->display('callbackdebug');
            return;
        }

        if($state !== $this->session->dingtalkState)
        {
            $this->view->error = 'state 校验失败：session 中的 state=' . ($this->session->dingtalkState ?? 'NULL') . '，传入的 state=' . $state;
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' ERROR: state 不匹配 sessionState=' . ($this->session->dingtalkState ?? 'NULL') . ' inputState=' . $state . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->display('callbackdebug');
            return;
        }

        $userid = $this->dingtalklogin->getUseridByCode('scan', $code);
        $this->view->userid = $userid === false ? '获取失败' : $userid;
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' DECODE userid=' . ($userid === false ? 'FAIL' : $userid) . PHP_EOL, FILE_APPEND | LOCK_EX);

        if($userid === false)
        {
            $this->view->error = '无法获取钉钉用户信息，请检查 appKey/appSecret 配置';
            $this->display('callbackdebug');
            return;
        }

        $users = $this->dingtalklogin->getBoundUsers($userid);
        $this->view->users = $users;
        $userAccounts = array_column($users, 'account');
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' BIND users=' . implode(',', $userAccounts) . ' count=' . count($users) . PHP_EOL, FILE_APPEND | LOCK_EX);

        if(empty($users))
        {
            $this->view->error = '未找到绑定的用户，请先在禅道后台 webhook 中绑定用户';
            $this->display('callbackdebug');
            return;
        }

        $this->display('callbackdebug');
    }

    /**
     * 确认登录（用户选择账号后提交）。
     * Confirm login after user selection.
     *
     * @access public
     * @return void
     */
    public function confirm()
    {
        $account = $this->post->account;
        if(empty($account))
        {
            $this->session->set('dingtalkError', '未选择登录账号');
            return $this->locate($this->createLink('user', 'login'));
        }

        $user = $this->loadModel('user')->getById($account, 'account');
        if(empty($user) || $user->deleted)
        {
            $this->session->set('dingtalkError', '账号不存在或已禁用');
            return $this->locate($this->createLink('user', 'login'));
        }

        $result = $this->loadModel('user')->login($user);
        if($result === false)
        {
            $this->session->set('dingtalkError', '登录失败，请检查禅道用户状态');
            return $this->locate($this->createLink('user', 'login'));
        }

        $this->loadModel('action')->create('user', (int)$user->id, 'login');
        session_write_close();
        return $this->locate($this->createLink('my', 'index'));
    }

    /**
     * 多账号选择登录（兼容旧入口）。
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

        $result = $this->loadModel('user')->login($user);
        if($result === false)
        {
            $this->session->set('dingtalkError', '登录失败，请检查禅道用户状态');
            return $this->locate($this->createLink('user', 'login'));
        }

        $this->loadModel('action')->create('user', (int)$user->id, 'login');
        session_write_close();
        return $this->locate($this->createLink('my', 'index'));
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
