<?php
// ここではまだ PHP ロジックは使わず、
// 将来 MySQL に繋ぐときに育てていくイメージ。
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>VinMemo v1 – Home</title>

    <!-- Google Analytics GA4 -->
    <script
      async
      src="https://www.googletagmanager.com/gtag/js?id=G-6BXQJQF1K5"
    ></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag() {
        dataLayer.push(arguments);
      }
      gtag("js", new Date());
      gtag("config", "G-6BXQJQF1K5");
    </script>

</head>
<body>
  <h1>VinMemo Home</h1>
  <p id="user-info">ログイン確認中...</p>

  <button id="logout-btn">ログアウト</button>

  <hr>

  <h2>ここから先が VinMemo 本体</h2>
  <p>※まずはこのページにワイン会一覧やボトル登録へのリンクを置いていく。</p>

  <script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-app.js";
    import {
      getAuth,
      onAuthStateChanged,
      signOut
    } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-auth.js";

    // ★ index.html と同じ firebaseConfig をそのまま貼る（値は変えない）
    const firebaseConfig = {
      apiKey: "AIzaSyAWy-qFkXZM3tMSwoMYeTo2SrJHyVkuZ9c",
      authDomain: "vinmemo-v1.firebaseapp.com",
      projectId: "vinmemo-v1",
      storageBucket: "vinmemo-v1.firebasestorage.app",
      messagingSenderId: "559734690298",
      appId: "1:559734690298:web:171793d9abbe724f77d443",
      measurementId: "G-6BXQJQF1K5"
    };

    const app  = initializeApp(firebaseConfig);
    const auth = getAuth(app);

    const userInfoEl = document.getElementById('user-info');
    const logoutBtn  = document.getElementById('logout-btn');

    // ここでもログイン状態を監視
    onAuthStateChanged(auth, (user) => {
      if (user) {
        userInfoEl.textContent = `ログイン中：${user.email}`;
      } else {
        // ログインしていなければ index.html へ追い返す
        window.location.href = 'index.html';
      }
    });

    // ログアウトボタン
    logoutBtn.addEventListener('click', async () => {
      try {
        await signOut(auth);
        // ログアウトしたらログインページへ戻す
        window.location.href = 'index.html';
      } catch (err) {
        console.error(err);
        alert('ログアウトエラー：' + err.message);
      }
    });
  </script>
</body>
</html>
