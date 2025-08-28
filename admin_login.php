<?php
session_start();
require_once 'db_con.php';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check admin credentials
        $admin = fetchRow("SELECT * FROM admins WHERE username = ? AND is_active = 1", [$username]);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Update last login
            query("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
            
            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NISU Alumni System - Admin Login</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
       <link rel="icon" href="default/logo.png" type="image/x-icon" />
    
    <style>
        body {
            background:url('default/sample_school.jpg') center/cover no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        /* Fallback background if image doesn't load */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .login-header {
            background: linear-gradient(135deg, #1572e8 0%, #0d5cb5 100%);
            color: white;
            padding: 35px 30px;
            text-align: center;
            position: relative;
        }
        
        .school-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .school-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        
        /* Placeholder logo if image doesn't load */
        .school-logo::before {
            content: 'NISU';
            font-weight: bold;
            font-size: 18px;
            color: #1572e8;
            position: absolute;
        }
        
        .school-logo img {
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 35px 30px;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 14px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #1572e8;
            box-shadow: 0 0 0 0.2rem rgba(21, 114, 232, 0.25);
            transform: translateY(-2px);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #1572e8;
        }
        
        .password-toggle:focus {
            outline: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1572e8 0%, #0d5cb5 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(21, 114, 232, 0.3);
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #0d5cb5 0%, #0a4d96 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(21, 114, 232, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 15px;
        }
        
        .school-info {
            background: rgba(21, 114, 232, 0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            color: #1572e8;
            font-weight: 500;
            margin-top: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-card {
                margin: 20px;
                max-width: none;
            }
            
            .login-header {
                padding: 25px 20px;
            }
            
            .login-body {
                padding: 25px 20px;
            }
            
            .school-logo {
                width: 70px;
                height: 70px;
                margin-bottom: 15px;
            }
            
            .school-logo img {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="school-logo">
                <img src="default/logo.png" alt="NISU Logo" onerror="this.style.display='none'">
            </div>
            <h3 class="mb-2">NISU Alumni System</h3>
            <p class="mb-0 opacity-75">Administrator Login</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user me-1"></i>
                        Username
                    </label>
                    <input type="text" 
                           name="username" 
                           class="form-control" 
                           required 
                           placeholder="Enter your username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-lock me-1"></i>
                        Password
                    </label>
                    <div class="password-container">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control" 
                               required 
                               placeholder="Enter your password">
                        <button type="button" 
                                class="password-toggle" 
                                onclick="togglePassword()"
                                aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Login to Dashboard
                </button>
            </form>
            
            <div class="school-info">
                <i class="fas fa-university me-2"></i>
                <strong>Northern Iloilo State University</strong><br>
                <small class="opacity-75">Alumni Management System</small>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add some interactive feedback
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('.btn-login');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
            submitBtn.disabled = true;
        });

        // Focus first empty field on load
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.querySelector('input[name="username"]');
            const passwordField = document.querySelector('input[name="password"]');
            
            if (!usernameField.value) {
                usernameField.focus();
            } else {
                passwordField.focus();
            }
        });
    </script>
</body>
</html>