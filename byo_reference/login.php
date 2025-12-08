<?php
// login.php ログインフォーム画面
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン - BYOワイン会</title>
  <link rel="stylesheet" href="style.css">
</head>
    <body>
       <header class="header">
    <div class="header-inner">
      <div class="auth-wrapper">
    <h1>BYOログイン</h1>
    <form method="post" action="login_act.php" class="auth-form">
      <label>
        ログインID
        <input type="text" name="lid" required>
      </label>
      <label>
        パスワード
        <input type="password" name="lpw" required>
      </label>
      <button type="submit">ログイン</button>
    </form>
    <p><a href="user_register.php">ユーザー登録はこちら</a></p>
  </div>
    </div>
  </header>
</body>

</html>

