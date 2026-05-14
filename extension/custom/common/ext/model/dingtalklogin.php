<?php
/**
 * Hook commonModel to allow guest access to dingtalklogin module methods.
 * This file is loaded by ZenTao's extension mechanism automatically.
 */

/**
 * Hook isOpenMethod (for older ZenTao versions or compatibility).
 */
public function isOpenMethod($module = '', $method = '')
{
    $openMethods = array('scan', 'callback', 'confirm', 'choose', 'sso');
    if($module === 'dingtalklogin' && in_array($method, $openMethods))
    {
        return true;
    }
    return parent::isOpenMethod($module, $method);
}

/**
 * Hook checkPriv (for ZenTao 20.x).
 */
public function checkPriv($module = '', $method = '')
{
    $openMethods = array('scan', 'callback', 'confirm', 'choose', 'sso');
    if($module === 'dingtalklogin' && in_array($method, $openMethods))
    {
        return true;
    }
    return parent::checkPriv($module, $method);
}
