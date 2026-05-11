<?php
declare(strict_types=1);
/**
 * Test getBoundUser method of dingtalklogin model.
 */
include dirname(__DIR__, 7) . '/test/lib/init.php';

$model = $tester->loadModel('dingtalklogin');

/* Mock: unbound userid should return false. */
$result = $model->getBoundUser('non_existent_userid');
if($result !== false) die("FAIL: unbound userid should return false\n");

/* TODO: Insert mock OAuth record and test bound user retrieval. */

echo "PASS\n";
