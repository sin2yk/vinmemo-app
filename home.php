<?php
// home.php
require_once 'layout/header.php';
?>

<div class="card">
  <h2>Welcome to VinMemo</h2>
  <p id="user-info">Confirming login...</p>

  <hr>

  <h3>Menu</h3>
  <ul>
    <li><a href="events.php">Event List</a></li>
    <li><a href="entry.php">Bottle Entry (Legacy)</a></li>
    <li><a href="mypage.php">My Page</a></li>
  </ul>

  <div style="margin-top:20px;">
    <button id="logout-btn">Logout (Firebase)</button>
  </div>
</div>

<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-app.js";
  import {
    getAuth,
    onAuthStateChanged,
    signOut
  } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-auth.js";

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

  onAuthStateChanged(auth, (user) => {
    if (user) {
      userInfoEl.textContent = `Logged in as: ${user.email}`;
    } else {
      window.location.href = 'index.html';
    }
  });

  logoutBtn.addEventListener('click', async () => {
    try {
      await signOut(auth);
      window.location.href = 'index.html';
    } catch (err) {
      console.error(err);
      alert('Logout error: ' + err.message);
    }
  });
</script>

<?php require_once 'layout/footer.php'; ?>