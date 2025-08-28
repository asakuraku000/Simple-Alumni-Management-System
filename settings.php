<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Handle settings update
if ($_POST && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_settings':
            try {
                $settings = [
                    'site_name' => $_POST['site_name'],
                    'site_description' => $_POST['site_description'],
                    'university_address' => $_POST['university_address'],
                    'university_phone' => $_POST['university_phone'],
                    'university_email' => $_POST['university_email'],
                    'alumni_registration_enabled' => $_POST['alumni_registration_enabled'] ?? '0',
                    'email_notifications' => $_POST['email_notifications'] ?? '0',
                    'maintenance_mode' => $_POST['maintenance_mode'] ?? '0',
                    'social_facebook' => $_POST['social_facebook'],
                    'social_twitter' => $_POST['social_twitter']
                ];
                
                foreach ($settings as $key => $value) {
                    query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Get current settings
$settings_data = fetchAll("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($settings_data as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Settings - NISU Alumni System</title>
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
                            <h3 class="fw-bold mb-3">System Settings</h3>
                            <h6 class="op-7 mb-2">Configure system-wide settings</h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">General Settings</h4>
                                </div>
                                <div class="card-body">
                                    <form id="settingsForm">
                                        <input type="hidden" name="action" value="update_settings">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5 class="mb-3">Site Information</h5>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Site Name</label>
                                                    <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Site Description</label>
                                                    <textarea class="form-control" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">University Address</label>
                                                    <textarea class="form-control" name="university_address" rows="2"><?= htmlspecialchars($settings['university_address'] ?? '') ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">University Phone</label>
                                                    <input type="text" class="form-control" name="university_phone" value="<?= htmlspecialchars($settings['university_phone'] ?? '') ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">University Email</label>
                                                    <input type="email" class="form-control" name="university_email" value="<?= htmlspecialchars($settings['university_email'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <h5 class="mb-3">System Configuration</h5>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="alumni_registration_enabled" value="1" <?= ($settings['alumni_registration_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label">
                                                            Enable Alumni Registration
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Allow new alumni to register online</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="email_notifications" value="1" <?= ($settings['email_notifications'] ?? '0') == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label">
                                                            Email Notifications
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Send email notifications for announcements</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label">
                                                            Maintenance Mode
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">Enable maintenance mode to disable public access</small>
                                                </div>
                                                
                                                <h5 class="mb-3 mt-4">Social Media</h5>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Facebook Page URL</label>
                                                    <input type="url" class="form-control" name="social_facebook" value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Twitter Account URL</label>
                                                    <input type="url" class="form-control" name="social_twitter" value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">Save Settings</button>
                                        </div>
                                    </form>
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
        // Handle settings form submission
        $('#settingsForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('settings.php', $(this).serialize(), function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success");
                } else {
                    swal("Error!", response.message, "error");
                }
            }, 'json');
        });
    </script>
</body>
</html>
