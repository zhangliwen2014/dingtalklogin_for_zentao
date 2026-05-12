<?php
/**
 * The model file of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @version     $Id$
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
     * 查询钉钉用户绑定的所有禅道账号（支持一对多）。
     * Get all Zentao accounts bound to a DingTalk user.
     *
     * @param  string $userid  DingTalk userid
     * @access public
     * @return array
     */
    public function getBoundUsers(string $userid): array
    {
        $oauths = $this->dao->select('*')->from(TABLE_OAUTH)
            ->where('openID')->eq($userid)
            ->andWhere('providerType')->eq('webhook')
            ->fetchAll();

        if(empty($oauths)) return array();

        $users = array();
        foreach($oauths as $oauth)
        {
            $user = $this->loadModel('user')->getById($oauth->account, 'account');
            if(!empty($user) && !$user->deleted)
            {
                $users[] = $user;
            }
        }
        return $users;
    }

    /**
     * 查询钉钉用户是否已绑定禅道账号（兼容旧接口，只返回第一个）。
     * Check if a DingTalk user is bound to a Zentao account.
     *
     * @param  string $userid  DingTalk userid
     * @access public
     * @return object|false
     */
    public function getBoundUser(string $userid): object|false
    {
        $users = $this->getBoundUsers($userid);
        return empty($users) ? false : $users[0];
    }
}
