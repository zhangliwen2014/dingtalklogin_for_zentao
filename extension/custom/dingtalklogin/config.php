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

/* 未登录用户可以访问的方法。Methods that do not require login. */
$config->openMethods[] = 'dingtalklogin.scan';
$config->openMethods[] = 'dingtalklogin.callback';
$config->openMethods[] = 'dingtalklogin.choose';
$config->openMethods[] = 'dingtalklogin.sso';
