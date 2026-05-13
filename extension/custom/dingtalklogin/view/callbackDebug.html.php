<?php
$loginLink   = helper::createLink('user', 'login');
$confirmLink = helper::createLink('dingtalklogin', 'confirm');
?>
<div id="main" class="no-padding" style="opacity:1;min-height:100vh;">
    <div id="login" style="display:flex;justify-content:center;align-items:center;min-height:100vh;">
        <div id="loginPanel" style="padding:40px;background:#fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);max-width:700px;width:100%;">
            <h2 style="font-weight:bold;margin-bottom:20px;">钉钉登录回调调试</h2>

            <!-- 回调参数 -->
            <div class="panel" style="margin-bottom:15px;">
                <div class="panel-heading" style="font-weight:bold;">回调参数</div>
                <div class="panel-body">
                    <table class="table table-bordered table-hover">
                        <tr><th>参数</th><th>值</th></tr>
                        <tr><td>code</td><td><?php echo empty($code) ? '空' : htmlspecialchars((string)$code); ?></td></tr>
                        <tr><td>state</td><td><?php echo empty($state) ? '空' : htmlspecialchars((string)$state); ?></td></tr>
                        <tr><td>sessionState</td><td><?php echo htmlspecialchars((string)$sessionState); ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- 解密信息 -->
            <div class="panel" style="margin-bottom:15px;">
                <div class="panel-heading" style="font-weight:bold;">解密信息</div>
                <div class="panel-body">
                    <table class="table table-bordered table-hover">
                        <tr><th>项目</th><th>值</th></tr>
                        <tr><td>钉钉 userid</td><td><?php echo htmlspecialchars((string)$userid); ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- 错误提示 -->
            <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- 绑定用户列表 + 确认表单 -->
            <?php if(empty($error) && !empty($users)): ?>
            <div class="panel" style="margin-bottom:15px;">
                <div class="panel-heading" style="font-weight:bold;">绑定用户列表（共 <?php echo count($users); ?> 个）</div>
                <div class="panel-body">
                    <form action="<?php echo $confirmLink; ?>" method="post">
                        <div style="margin:10px 0;">
                            <?php foreach($users as $index => $user): ?>
                            <label style="display:block;margin:8px 0;cursor:pointer;">
                                <input type="radio" name="account" value="<?php echo $user->account; ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                <?php echo $user->realname . ' (' . $user->account . ')' . ($user->role ? ' [' . $user->role . ']' : ''); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:20px;">
                            <button type="submit" class="btn btn-primary btn-wide">确认登录</button>
                            <a href="<?php echo $loginLink; ?>" class="btn btn-wide" style="margin-left:8px;">返回登录页</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 无用户时的返回按钮 -->
            <?php if(!empty($error) && empty($users)): ?>
            <div style="margin-top:20px;">
                <a href="<?php echo $loginLink; ?>" class="btn btn-wide">返回登录页</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
