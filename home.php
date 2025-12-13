<?php
// home.php
require_once 'layout/header.php';
?>

<div class="card">
  <h2 class="section-title" style="margin-top:0;">Welcome to VinMemo</h2>
  <p id="user-info" style="color:var(--text-muted);">Confirming login...</p>

  <div style="margin-top: 30px; margin-bottom: 30px;">
    <h3 style="font-size:1.2rem; margin-bottom:15px; border-bottom:1px solid var(--border); padding-bottom:5px;">Menu
    </h3>
    <div style="display:flex; flex-direction:column; gap:15px; align-items:flex-start;">
      <a href="events.php" class="vm-btn vm-btn--primary" style="font-size:1.1rem; padding: 0.8rem 1.5rem;">
        Event List / イベント一覧
      </a>
      <a href="mypage.php" class="vm-btn vm-btn--primary" style="font-size:1.1rem; padding: 0.8rem 1.5rem;">
        My Page / マイページ
      </a>
    </div>
  </div>

  <div style="margin-top:40px; text-align:right; border-top:1px solid var(--border); padding-top:20px;">
    <button id="logout-btn" class="vm-btn vm-btn--secondary">Logout (Firebase)</button>
  </div>
</div>

<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-app.js";
  import {
    getAuth,
    onAuthStateChanged,
    signOut
  } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-auth.js";

  // ★ Firebase 設定
  const firebaseConfig = {
    apiKey: "AIzaSyAWy-qFkXZM3tMSwoMYeTo2SrJHyVkuZ9c",
    authDomain: "vinmemo-v1.firebaseapp.com",
    projectId: "vinmemo-v1",
    storageBucket: "vinmemo-v1.firebasestorage.app",
    messagingSenderId: "559734690298",
    appId: "1:559734690298:web:171793d9abbe724f77d443",
    measurementId: "G-6BXQJQF1K5"
  };

  const app = initializeApp(firebaseConfig);
  const auth = getAuth(app);

  const userInfoEl = document.getElementById('user-info');
  const logoutBtn = document.getElementById('logout-btn');

  // ★ ログイン状態監視
  onAuthStateChanged(auth, (user) => {
    if (user) {
      // ログイン中 → メール表示
      userInfoEl.textContent = `Logged in as: ${user.email}`;
    } else {
      // 未ログイン → index.html へ戻す
      window.location.href = 'index.html';
    }
  });

  // ★ 共通ログアウト関数（Firebase → PHPセッション）
  async function vinmemoLogout() {
    try {
      await signOut(auth);
    } catch (err) {
      console.error('Firebase signOut error:', err);
      alert('Logout error: ' + err.message);
    } finally {
      // PHP セッションも殺す
      window.location.href = 'logout.php';
    }
  }

  // 下の「Logout (Firebase)」ボタン
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      vinmemoLogout();
    });
  }

  // nav の Logout リンク（header.php 側で id="nav-logout-link" を付けておく）
  const navLogout = document.getElementById('nav-logout-link');
  if (navLogout) {
    navLogout.addEventListener('click', (e) => {
      // home.php 上でクリックされたとき用
      e.preventDefault();
      // ハッシュを #logout にしておけば、どこから来ても挙動が揃う
      if (window.location.hash !== '#logout') {
        window.location.hash = '#logout';
      }
      // すぐにログアウト発火
      vinmemoLogout();
    });
  }

  // ★ URL のハッシュを見て自動ログアウト（他ページから home.php#logout で来たとき）
  function handleLogoutHash() {
    if (window.location.hash === '#logout') {
      vinmemoLogout();
    }
  }

  // 1回目：他ページから home.php#logout に飛んできた場合
  document.addEventListener('DOMContentLoaded', () => {
    handleLogoutHash();
  });

  // 2回目：home.php 上でハッシュだけ変わった場合（home.php の nav Logout）
  window.addEventListener('hashchange', () => {
    handleLogoutHash();
  });
</script>

<?php require_once 'layout/footer.php'; ?>