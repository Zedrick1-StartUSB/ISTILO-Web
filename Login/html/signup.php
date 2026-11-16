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
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/signup.css?v=2">
</head>
<body>
    <main class="login-page signup-page">
        <div class="container">
            <div class="row login-card signup-card g-0">
                <section class="col-lg-6 welcome-panel signup-welcome" aria-labelledby="welcome-title">
                    <div class="welcome-copy">
                        <p class="eyebrow">YOUR NEW FAVOURITE CORNER</p>
                        <h1 id="welcome-title">Good clothes.<br><span>Good moods.</span></h1>
                        <p class="welcome-description">A little bit of everything you love to wear. Come on in!</p>
                    </div>

                    <div class="outfit-art signup-art" aria-hidden="true">
                        <div class="product-card product-card-back">
                            <span class="product-emoji">🧢</span>
                            <span class="product-name">weekend cap</span>
                        </div>
                        <div class="product-card product-card-front">
                            <span class="product-emoji">👕</span>
                            <span class="product-name">the everyday tee</span>
                        </div>
                    </div>
                </section>

                <section class="col-lg-6 form-panel signup-form" aria-labelledby="signup-title">
                    <div class="form-content">
                        <p class="form-kicker">COME ON IN</p>
                        <h2 id="signup-title">Create account</h2>
                        <p class="form-intro">Add your details to get started.</p>

                        <?php if ($signup_error !== ""): ?>
                            <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($signup_error, ENT_QUOTES, "UTF-8") ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-2">
                                <label for="fullname" class="form-label">Full name</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" value="<?= htmlspecialchars($signup_fullname, ENT_QUOTES, "UTF-8") ?>" placeholder="Your full name" autocomplete="name" maxlength="255" required>
                            </div>
                            <div class="mb-2">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($signup_username, ENT_QUOTES, "UTF-8") ?>" placeholder="Choose a username" autocomplete="username" maxlength="255" required>
                            </div>
                            <div class="mb-2">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter a password" autocomplete="new-password" required>
                            </div>
                            <div class="mb-2">
                                <label for="confirm-password" class="form-label">Confirm password</label>
                                <input type="password" class="form-control" id="confirm-password" name="confirm-password" placeholder="Enter your password again" autocomplete="new-password" required>
                            </div>
                            <div class="mb-2">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" placeholder="Department"required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" name="role" value="<?= htmlspecialchars($signup_role, ENT_QUOTES, "UTF-8") ?>" placeholder="layout, production, or qa" pattern="(layout|production|qa)" aria-describedby="roleHelp" required>
                            </div>
                            <button class="btn login-button w-100 signup-button" type="submit" name="signup">Create account <span aria-hidden="true">→</span></button>
                        </form>

                        <p class="signup-prompt existing-account">Already have an account? <a class="text-link" href="login.php">Log in</a></p>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
