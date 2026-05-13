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
        }
        elseif($state !== $this->session->dingtalkState)
        {
            $this->view->error = 'state 校验失败：session 中的 state=' . ($this->session->dingtalkState ?? 'NULL') . '，传入的 state=' . $state;
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' ERROR: state 不匹配 sessionState=' . ($this->session->dingtalkState ?? 'NULL') . ' inputState=' . $state . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        else
        {
            $userid = $this->dingtalklogin->getUseridByCode('scan', $code);
            $this->view->userid = $userid === false ? '获取失败' : $userid;
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' DECODE userid=' . ($userid === false ? 'FAIL' : $userid) . PHP_EOL, FILE_APPEND | LOCK_EX);

            if($userid === false)
            {
                $this->view->error = '无法获取钉钉用户信息，请检查 appKey/appSecret 配置';
            }
            else
            {
                $users = $this->dingtalklogin->getBoundUsers($userid);
                $this->view->users = $users;
                $userAccounts = array_column($users, 'account');
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' BIND users=' . implode(',', $userAccounts) . ' count=' . count($users) . PHP_EOL, FILE_APPEND | LOCK_EX);

                if(empty($users))
                {
                    $this->view->error = '未找到绑定的用户，请先在禅道后台 webhook 中绑定用户';
                }
            }
        }

        /* 直接输出调试页面，绕过 ZIN 视图系统 */
        $this->outputDebugPage();
    }

    /**
     * 直接输出回调调试 HTML 页面。
     * Output debug page directly without ZIN view system.
     */
    protected function outputDebugPage(): void
    {
        $code        = htmlspecialchars((string)$this->view->code);
        $state       = htmlspecialchars((string)$this->view->state);
        $sessionState = htmlspecialchars((string)$this->view->sessionState);
        $error       = htmlspecialchars((string)$this->view->error);
        $userid      = htmlspecialchars((string)$this->view->userid);
        $users       = $this->view->users;
        $loginLink   = helper::createLink('user', 'login');
        $confirmLink = helper::createLink('dingtalklogin', 'confirm');

        echo '<!DOCTYPE html><html lang="zh-cn"><head><meta charset="utf-8"><title>钉钉登录回调调试</title>';
        echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;}#panel{max-width:700px;margin:0 auto;background:#fff;padding:40px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);}h2{font-size:24px;margin-bottom:20px;}table{width:100%;border-collapse:collapse;margin:15px 0;}th,td{border:1px solid #ddd;padding:10px;text-align:left;}th{background:#f0f0f0;font-weight:bold;}.alert{padding:12px 15px;border-radius:4px;margin:15px 0;}.alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}.user-option{display:block;margin:8px 0;cursor:pointer;}button{background:#0c64eb;color:#fff;border:none;padding:10px 24px;border-radius:4px;cursor:pointer;font-size:14px;}button:hover{background:#0a4ebd;}a.btn{display:inline-block;padding:8px 20px;border:1px solid #ccc;border-radius:4px;text-decoration:none;color:#333;margin-left:8px;}a.btn:hover{background:#f0f0f0;}</style>';
        echo '</head><body><div id="panel"><h2>钉钉登录回调调试</h2>';

        echo '<h3>回调参数</h3><table><tr><th>参数</th><th>值</th></tr>';
        echo '<tr><td>code</td><td>' . $code . '</td></tr>';
        echo '<tr><td>state</td><td>' . $state . '</td></tr>';
        echo '<tr><td>sessionState</td><td>' . $sessionState . '</td></tr></table>';

        echo '<h3>解密信息</h3><table><tr><th>项目</th><th>值</th></tr>';
        echo '<tr><td>钉钉 userid</td><td>' . $userid . '</td></tr></table>';

        if($error)
        {
            echo '<div class="alert alert-danger">' . $error . '</div>';
            echo '<p><a href="' . $loginLink . '" class="btn">返回登录页</a></p>';
        }
        elseif(!empty($users))
        {
            echo '<h3>绑定用户列表（共 ' . count($users) . ' 个）</h3>';
            echo '<form action="' . $confirmLink . '" method="post">';
            foreach($users as $index => $user)
            {
                $checked = $index === 0 ? 'checked' : '';
                echo '<label class="user-option"><input type="radio" name="account" value="' . htmlspecialchars((string)$user->account) . '" ' . $checked . '> ' . htmlspecialchars((string)$user->realname) . ' (' . htmlspecialchars((string)$user->account) . ')' . ($user->role ? ' [' . htmlspecialchars((string)$user->role) . ']' : '') . '</label>';
            }
            echo '<div style="margin-top:20px;"><button type="submit">确认登录</button><a href="' . $loginLink . '" class="btn">返回登录页</a></div>';
            echo '</form>';
        }

        echo '</div></body></html>';
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
