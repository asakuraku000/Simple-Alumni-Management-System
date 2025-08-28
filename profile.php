<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Handle profile update
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    header('Content-Type: application/json');
    
    try {
        $data = [
            $_POST['full_name'],
            $_POST['email'],
            $_POST['username'],
            $_SESSION['admin_id']
        ];
        
        query("UPDATE admins SET full_name=?, email=?, username=? WHERE id=?", $data);
        
        // Update session
        $_SESSION['admin_name'] = $_POST['full_name'];
        $_SESSION['admin_username'] = $_POST['username'];
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle password change
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    header('Content-Type: application/json');
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        exit;
    }
    
    // Verify current password
    if (!password_verify($current_password, $admin_info['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    
    try {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        query("UPDATE admins SET password_hash=? WHERE id=?", [$new_hash, $_SESSION['admin_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Profile - NISU Alumni System</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="default/logo.png" type="image/x-icon" />
    
    
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular", 
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

     
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
    <div class="wrapper">
        
         <?php include("include/sidebar.php"); ?>
      

        <div class="main-panel">
            <div class="main-header">
 <?php include("include/main-header.php"); ?>  
                
                
              <?php include("include/navbar.php"); ?>
              
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Profile</h3>
                            <h6 class="op-7 mb-2">Manage your account settings</h6>
                        </div>
                    </div>

                    <div class="row">
                        
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Profile Information</h4>
                                </div>
                                <div class="card-body">
                                    <form id="profileForm">
                                        <input type="hidden" name="action" value="update_profile">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($admin_info['full_name']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Username</label>
                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($admin_info['username']) ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($admin_info['email']) ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="<?= ucfirst(str_replace('_', ' ', $admin_info['role'])) ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Last Login</label>
                                            <input type="text" class="form-control" value="<?= $admin_info['last_login'] ? date('M j, Y g:i A', strtotime($admin_info['last_login'])) : 'Never' ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Account Created</label>
                                            <input type="text" class="form-control" value="<?= date('M j, Y g:i A', strtotime($admin_info['created_at'])) ?>" readonly>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Change Password</h4>
                                </div>
                                <div class="card-body">
                                    <form id="passwordForm">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" required minlength="6">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                        </div>
                                        
                                        <button type="submit" class="btn btn-warning">Change Password</button>
                                    </form>
                                </div>
                            </div>

                              
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4 class="card-title">Account Status</h4>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Account Status</span>
                                        <span class="badge badge-<?= $admin_info['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $admin_info['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Role Level</span>
                                        <span class="badge badge-info">
                                            <?= ucfirst(str_replace('_', ' ', $admin_info['role'])) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Member Since</span>
                                        <span class="text-muted">
                                            <?= date('M Y', strtotime($admin_info['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<?php include("include/footer.php"); ?>
        </div>
    </div>

       
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        // Handle profile form submission
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('profile.php', $(this).serialize(), function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success").then(() => {
                        location.reload();
                    });
                } else {
                    swal("Error!", response.message, "error");
                }
            }, 'json');
        });

        // Handle password form submission
        $('#passwordForm').on('submit', function(e) {
            e.preventDefault();
            
            const newPassword = $('[name="new_password"]').val();
            const confirmPassword = $('[name="confirm_password"]').val();
            
            if (newPassword !== confirmPassword) {
                swal("Error!", "New passwords do not match", "error");
                return;
            }
            
            $.post('profile.php', $(this).serialize(), function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success").then(() => {
                        $('#passwordForm')[0].reset();
                    });
                } else {
                    swal("Error!", response.message, "error");
                }
            }, 'json');
        });
    </script>
</body>
</html>
