<?php
// Start session
session_start();

// Include database connection
require_once "../config/database.php";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/feed.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kinoverse</title>
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <main class="signup-container">
        <section class="form-section">
            <div class="logo">
                Kinoverse
            </div>

            <div class="form-content">
                <h1>Welcome back!</h1>
                <p class="subtitle">Log in to your account to continue</p>

                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="error-container">' . 
                         htmlspecialchars($_SESSION['error']) . 
                         '</div>';
                    unset($_SESSION['error']);
                }

                if (isset($_SESSION['success'])) {
                    echo '<div class="success-container">' . 
                         htmlspecialchars($_SESSION['success']) . 
                         '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <form class="signup-form" method="POST" action="../includes/auth/login_process.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your email"
                            value="<?php echo isset($_SESSION['form_data']['email']) ? 
                                  htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword('password')" 
                                aria-label="Toggle password visibility"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                    <path class="hide-password" d="M2 2l20 20" style="display: none;"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Log In</button>
                </form>

                <div class="login-link">
                    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                </div>
            </div>
        </section>
        
        <section class="image-section">
            <img src="../assets/bg.jpg" alt="Decorative background">
        </section>
    </main>

    <?php
    // Clear any stored form data
    unset($_SESSION['form_data']);
    ?>

    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
        const hideLine = button.querySelector('.hide-password');
        
        if (input.type === 'password') {
            input.type = 'text';
            hideLine.style.display = 'block';
        } else {
            input.type = 'password';
            hideLine.style.display = 'none';
        }
    }
    </script>
</body>
</html>