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
    <title>Sign Up - Kinoverse</title>
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <main class="signup-container">
        <section class="form-section">
            <div class="logo">
                Kinoverse
            </div>

            <div class="form-content">
                <h1>Welcome!</h1>
                <p class="subtitle">Create your account to get started</p>

                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="error-container">' . 
                         htmlspecialchars($_SESSION['error']) . 
                         '</div>';
                    unset($_SESSION['error']);
                }
                ?>

                <form class="signup-form" method="POST" action="../includes/auth/signup_process.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input 
                                type="text" 
                                id="firstName" 
                                name="firstName" 
                                placeholder="Enter your first name"
                                value="<?php echo isset($_SESSION['form_data']['firstName']) ? 
                                      htmlspecialchars($_SESSION['form_data']['firstName']) : ''; ?>"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input 
                                type="text" 
                                id="lastName" 
                                name="lastName" 
                                placeholder="Enter your last name"
                                value="<?php echo isset($_SESSION['form_data']['lastName']) ? 
                                      htmlspecialchars($_SESSION['form_data']['lastName']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username"
                            value="<?php echo isset($_SESSION['form_data']['username']) ? 
                                  htmlspecialchars($_SESSION['form_data']['username']) : ''; ?>"
                            required
                        >
                    </div>

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
                                placeholder="Create a password"
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

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="password-field">
                            <input 
                                type="password" 
                                id="confirmPassword" 
                                name="confirmPassword" 
                                placeholder="Confirm your password"
                                required
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword('confirmPassword')" 
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

                    <button type="submit" class="submit-btn">Sign Up</button>
                </form>

                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Log in</a></p>
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

    <!-- Add this script before closing body tag -->
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