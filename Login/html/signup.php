<?php
require "../../auth.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6eee5">
    <title>Sign up | little label</title>
    <link rel="stylesheet" href="../../bootstrap_template/css/bootstrap.css">
    <link rel="stylesheet" href="../css/signup.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <main class="login-page signup-page">
        <div class="container">
            <div class="row login-card signup-card">
                <section class="col-lg-12 form-panel signup-form">
                    <div class="form-content">
                        <a href="login.php">Return</a>
                        <p class="form-kicker">COME ON IN</p>
                        <h2 id="signup-title">Create account</h2>
                        <p class="form-intro">It only takes a minute to get started.</p>

                        <form action="#" method="post">
                            <div class="row g-3">
                                <div class="col-sm-12">
                                    <label for="first-name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="fullname" name="fullname" placeholder="John Doe" autocomplete="given-name" required>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Username</label>
                                    <input type="email" class="form-control" id="username" name="username" placeholder="example@gmail.com" autocomplete="email" required>
                                </div>
                                <div class="col-12">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                                </div>
                                <div class="col-12">
                                    <label for="confirm-password" class="form-label">Confirm password</label>
                                    <input type="password" class="form-control" id="confirm-password" name="confirm-password" placeholder="Type your password again" required>
                                </div>
                            </div><br>
                            <button class="btn login-button w-100 signup-button" type="submit" name="signup">Create my account <span aria-hidden="true">→</span></button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
