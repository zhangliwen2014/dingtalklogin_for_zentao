<?php
declare(strict_types=1);
/**
 * The model file of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @link        https://www.zentao.net
 */
class dingtalkloginModel extends model
{
    /**
     * 根据授权码获取钉钉 userid。
     * Get DingTalk userid by authorization code.
     *
     * @param  string $type  'scan' | 'sso'
     * @param  string $code  authorization code
     * @access public
     * @return string|false
     */
    public function getUseridByCode(string $type, string $code): string|false
    {
        $webhook = $this->getDingWebhook();
        if(empty($webhook)) return false;

        if($type === 'scan')
        {
            return $this->getUseridByScanCode($code, $webhook->secret->appKey, $webhook->secret->appSecret);
        }

        if($type === 'sso')
        {
            return $this->getUseridByAuthCode($code, $webhook->secret->appKey, $webhook->secret->appSecret);
        }

        return false;
    }

    /**
     * 查询钉钉用户是否已绑定禅道账号。
     * Check if a DingTalk user is bound to a Zentao account.
     *
     * @param  string $userid  DingTalk userid
     * @access public
     * @return object|false
     */
    public function getBoundUser(string $userid): object|false
    {
        $oauth = $this->dao->select('*')->from(TABLE_OAUTH)
            ->where('openID')->eq($userid)
            ->andWhere('providerType')->eq('webhook')
            ->fetch();

        if(empty($oauth)) return false;

        $user = $this->loadModel('user')->getById($oauth->account, 'account');
        if(empty($user) || $user->deleted) return false;

        return $user;
    }

    /**
     * 获取钉钉 webhook 配置。
     * 实际实现在 dingtalkloginTao::getDingWebhook() 中。
     *
     * @access public
     * @return object|false
     */
    public function getDingWebhook(): object|false
    {
        /* 由 dingtalkloginTao 覆盖实现 */
        return false;
    }
}
