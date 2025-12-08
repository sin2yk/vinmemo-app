<?php
// フォームだけのシンプルなページ
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ユーザー登録</title>
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
  
  <h1>ユーザー登録</h1>
  <form method="post" action="user_register_act.php">
    <div>
      名前: <input type="text" name="name">
    </div>
    <div>
      ログインID: <input type="text" name="lid">
    </div>
    <div>
      パスワード: <input type="password" name="lpw">
    </div>
    <div>
      ロール:
      <select name="role">
        <option value="guest">guest（参加者）</option>
        <option value="organizer">organizer（主催者）</option>
      </select>
    </div>
    <div>
      <button type="submit">登録する</button>
    </div>
  </form>
</body>
</html>
