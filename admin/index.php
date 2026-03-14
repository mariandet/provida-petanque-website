<?php
session_start();
$isLoggedIn = isset($_SESSION["uid"]); // session set after login.php success
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Provida Pétanque Club</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="admin-body">

    <!-- Admin Navigation -->
    <nav class="admin-navbar">
        <div class="admin-navbar__container">
            <h1 class="admin-navbar__title">PROVIDA Admin Panel</h1>

            <!-- <?php if($isLoggedIn): ?>
                <a href="logout.php" class="btn btn--outline">Logout</a>
            <?php endif; ?> -->
        </div>
    </nav>

    <!-- Login Screen -->
    <!-- <div class="admin-login" id="adminLogin" style="<?= $isLoggedIn ? 'display:none;' : '' ?>"> -->
    <div class="admin-login" id="adminLogin"    >

        <div class="login-container">
            <h2>Admin Login</h2>

            <form id="loginForm">
                <div class="form-group">
                    <input type="text" id="adminUsername" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" id="adminPassword" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn--primary">Login</button>
            </form>

           
            <p id="loginMsg" style="margin-top:10px;color:red;"></p>
        </div>
    </div>

 
    <script>
    // Tabs
    document.querySelectorAll(".admin-tab").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".admin-tab").forEach(b => b.classList.remove("active"));
            document.querySelectorAll(".admin-content").forEach(c => c.classList.remove("active"));

            btn.classList.add("active");
            document.getElementById(btn.dataset.tab).classList.add("active");
        });
    });

    // Login AJAX -> login.php (expects JSON SUCCESS)
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function(e){
            e.preventDefault();
            const username = document.getElementById("adminUsername").value;
            const password = document.getElementById("adminPassword").value;
            const msg = document.getElementById("loginMsg");
            msg.textContent = "Logging in...";

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "login.php", true);
            xhr.setRequestHeader("Content-Type", "application/json");

            xhr.onreadystatechange = function(){
                if(xhr.readyState === 4){
                    let data = {};
                    try { data = JSON.parse(xhr.responseText); } catch(e){}

                    if(xhr.status === 200 && data.status === "SUCCESS"){
                        // session now exists -> reload page to show dashboard
                        location.reload();
                        window.location = "admin.php";
                    } else {
                        msg.textContent = data.message || ("Login failed. HTTP " + xhr.status);
                    }
                }
            };

            xhr.send(JSON.stringify({ username, password }));
        });
    }
    </script>
</body>
</html>


<!-- <!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login</title>
</head>
<body>

<h2>Admin Login</h2>

<form id="f">
  <input name="username" placeholder="username" required><br><br>
  <input name="password" type="password" placeholder="password" required><br><br>
  <button>Login</button>
</form>

<div id="m" style="color:red;"></div>
<div id="debug" style="margin-top:20px;color:blue;"></div>

<script>
document.getElementById("f").onsubmit = function(e) {
    e.preventDefault();

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "login.php", true);
    xhr.setRequestHeader("Content-Type", "application/json");


   xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {

            if (xhr.status === 200) {

                var data = JSON.parse(xhr.responseText);

                if (data.status === "SUCCESS") {
                    window.location = "admin/admin.php";
                } else {
                    document.getElementById("m").innerText = data.message;
                }

            } else {
                document.getElementById("m").innerText =
                    "Server Error: " + xhr.status;
            }
        }
    };

    var data = JSON.stringify({
        username: document.getElementById("f").username.value,
        password: document.getElementById("f").password.value
    });

    xhr.send(data);
};
</script>

</body>
</html> -->
