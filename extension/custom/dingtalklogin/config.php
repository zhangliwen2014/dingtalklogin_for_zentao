<?php
declare(strict_types=1);
/**
 * The config file of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @version     $Id$
 * @link        https://www.zentao.net
 */
$config->dingtalklogin = new stdclass();

/* 移除浏览器发送的 Sec-Fetch-Mode header，避免禅道对页面导航请求走特殊权限逻辑 */
if(isset($_SERVER['HTTP_SEC_FETCH_MODE'])) unset($_SERVER['HTTP_SEC_FETCH_MODE']);

/* 未登录用户可以访问的方法。Methods that do not require login。 */
$config->openMethods[] = 'dingtalklogin.scan';
$config->openMethods[] = 'dingtalklogin.callback';
$config->openMethods[] = 'dingtalklogin.confirm';
$config->openMethods[] = 'dingtalklogin.choose';
$config->openMethods[] = 'dingtalklogin.sso';
