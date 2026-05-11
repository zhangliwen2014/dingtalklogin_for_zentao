<?php
declare(strict_types=1);
/**
 * Test getUseridByCode method of dingtalklogin model.
 */
include dirname(__DIR__, 7) . '/test/lib/init.php';

$model = $tester->loadModel('dingtalklogin');

/* Mock: empty code should return false. */
$result = $model->getUseridByCode('scan', '');
if($result !== false) die("FAIL: empty code should return false\n");

/* Mock: invalid code should return false (no real API call in test). */
/* Note: Without real DingTalk credentials, this will return false due to missing webhook. */
$result = $model->getUseridByCode('scan', 'invalid_code');
if($result !== false) die("FAIL: invalid code without config should return false\n");

/* Mock: unsupported type should return false. */
$result = $model->getUseridByCode('unknown', 'code');
if($result !== false) die("FAIL: unknown type should return false\n");

echo "PASS\n";
