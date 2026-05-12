<?php
declare(strict_types=1);
/**
 * The tao file of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @version     $Id$
 * @link        https://www.zentao.net
 */
class dingtalkloginTao extends dingtalkloginModel
{
    /**
     * 根据扫码登录临时授权码获取钉钉 userid。
     * Get DingTalk userid by scan login tmp auth code.
     *
     * @param  string $code       tmp_auth_code from scan callback
     * @param  string $appKey     DingTalk app key
     * @param  string $appSecret  DingTalk app secret
     * @access protected
     * @return string|false
     */
    protected function getUseridByScanCode(string $code, string $appKey, string $appSecret): string|false
    {
        $this->app->loadClass('dingapi', true);
        $dingapi = new dingapi($appKey, $appSecret, '');
        if($dingapi->isError()) return false;

        $token = $dingapi->getToken();
        if(!$token) return false;

        $url = $this->config->webhook->dingapiUrl . "sns/getuserinfo_bycode?access_token={$token}&tmp_auth_code=" . urlencode($code);
        $response = common::http($url);
        $response = json_decode($response);

        if(isset($response->errcode) && $response->errcode === 0 && isset($response->user_info->userid))
        {
            return $response->user_info->userid;
        }

        return false;
    }

    /**
     * 根据免登授权码获取钉钉 userid。
     * Get DingTalk userid by SSO auth code.
     *
     * @param  string $authCode   authCode from JSAPI dd.runtime.permission.requestAuthCode
     * @param  string $appKey     DingTalk app key
     * @param  string $appSecret  DingTalk app secret
     * @access protected
     * @return string|false
     */
    protected function getUseridByAuthCode(string $authCode, string $appKey, string $appSecret): string|false
    {
        $this->app->loadClass('dingapi', true);
        $dingapi = new dingapi($appKey, $appSecret, '');
        if($dingapi->isError()) return false;

        $token = $dingapi->getToken();
        if(!$token) return false;

        $url  = $this->config->webhook->dingapiUrl . "topapi/user/getuserinfo?access_token={$token}";
        $data = json_encode(array('code' => $authCode));
        $response = common::http($url, $data, array('Content-Type: application/json'));
        $response = json_decode($response);

        if(isset($response->errcode) && $response->errcode === 0 && isset($response->userid))
        {
            return $response->userid;
        }

        return false;
    }

    /**
     * 获取第一个有效的 dinguser webhook 配置。
     * Get the first valid dinguser webhook config.
     *
     * @access public
     * @return object|false
     */
    public function getDingWebhook(): object|false
    {
        $webhook = $this->dao->select('*')->from(TABLE_WEBHOOK)
            ->where('type')->eq('dinguser')
            ->andWhere('deleted')->eq('0')
            ->orderBy('id_asc')
            ->limit(1)
            ->fetch();

        if(empty($webhook)) return false;

        $webhook->secret = json_decode($webhook->secret);
        if(empty($webhook->secret->appKey) || empty($webhook->secret->appSecret))
        {
            return false;
        }

        return $webhook;
    }
}
