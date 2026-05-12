<?php
declare(strict_types=1);
/**
 * The ui view file of sso method of dingtalklogin module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2023 禅道软件（青岛）有限公司(ZenTao Software (Qingdao) Co., Ltd. www.zentao.net)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Developer <dev@example.com>
 * @package     dingtalklogin
 * @version     $Id$
 * @link        https://www.zentao.net
 */
namespace zin;

div
(
    setID('main'),
    setClass('fade no-padding'),
    div
    (
        setID('login'),
        div
        (
            setID('loginPanel'),
            div
            (
                setClass('header'),
                h2(setClass('font-bold'), $lang->dingtalklogin->common)
            ),
            div
            (
                setClass('table-row text-center'),
                div
                (
                    setStyle(array('margin' => '40px auto')),
                    p($lang->dingtalklogin->common . '加载中...')
                )
            )
        )
    )
);

$ssoAction = helper::createLink('dingtalklogin', 'sso');

html
('
<script src="https://g.alicdn.com/dingding/dingtalk-jsapi/2.13.42/dingtalk.open.js"></script>
<script>
(function() {
    dd.ready(function() {
        dd.runtime.permission.requestAuthCode({
            corpId: "' . $appKey . '",
            onSuccess: function(result) {
                var form = document.createElement("form");
                form.method = "POST";
                form.action = "' . $ssoAction . '";
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "authCode";
                input.value = result.code;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            },
            onFail: function(err) {
                alert("免登失败：" + JSON.stringify(err));
            }
        });
    });
})();
</script>
');

render('pagebase');
