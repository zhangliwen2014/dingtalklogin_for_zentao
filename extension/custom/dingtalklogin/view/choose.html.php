<?php include '../../common/view/header.lite.html.php'; ?>
<main id="main" class="no-padding" style="opacity:1;min-height:100vh;">
  <div class="container" id="login" style="display:flex;justify-content:center;align-items:center;min-height:100vh;">
    <div id="loginPanel" style="padding:40px;text-align:center;background:#fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);min-width:360px;">
      <header><h2>请选择要登录的账号</h2></header>
      <p style="color:#666;margin-bottom:20px;">该钉钉账号绑定了多个禅道用户</p>
      <form action="<?php echo helper::createLink('dingtalklogin', 'choose'); ?>" method="post">
        <div class="form-group" style="text-align:left;">
          <?php foreach($users as $index => $user): ?>
          <div class="radio-primary" style="margin-bottom:12px;">
            <input type="radio" name="account" id="user_<?php echo $user->id; ?>" value="<?php echo $user->account; ?>" <?php if($index === 0) echo 'checked'; ?>>
            <label for="user_<?php echo $user->id; ?>"><?php echo $user->realname; ?> (<?php echo $user->account; ?>)</label>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="form-group" style="text-align:center;margin-top:20px;">
          <button type="submit" class="btn btn-primary btn-wide">登录</button>
        </div>
        <div style="margin-top:15px;">
          <a href="<?php echo helper::createLink('user', 'login'); ?>" class="btn btn-wide">返回密码登录</a>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include '../../common/view/footer.lite.html.php'; ?>
