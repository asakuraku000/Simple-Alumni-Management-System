<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Get dashboard statistics
$total_alumni = fetchRow("SELECT COUNT(*) as count FROM alumni WHERE is_active = 1")['count'];
$total_colleges = fetchRow("SELECT COUNT(*) as count FROM colleges WHERE is_active = 1")['count'];
$total_programs = fetchRow("SELECT COUNT(*) as count FROM programs WHERE is_active = 1")['count'];
$total_batches = fetchRow("SELECT COUNT(*) as count FROM batches")['count'];
$total_announcements = fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'Published'")['count'];
$total_events = fetchRow("SELECT COUNT(*) as count FROM events WHERE status = 'Published'")['count'];

// Recent alumni
$recent_alumni = fetchAll("
    SELECT a.*, c.name as college_name, p.name as program_name, b.year as batch_year
    FROM alumni a 
    JOIN colleges c ON a.college_id = c.id 
    JOIN programs p ON a.program_id = p.id 
    JOIN batches b ON a.batch_id = b.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");

// College statistics
$college_stats = fetchAll("
    SELECT c.name, c.code, COUNT(a.id) as alumni_count
    FROM colleges c
    LEFT JOIN alumni a ON c.id = a.college_id AND a.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id, c.name, c.code
    ORDER BY alumni_count DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>NISU Alumni System - Dashboard</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
 
    
     
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
       <link rel="icon" href="default/logo.png" type="image/x-icon" />
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
                            <h3 class="fw-bold mb-3">Dashboard</h3>
                            <h6 class="op-7 mb-2">NISU Alumni Management System</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <a href="manage_alumni.php" class="btn btn-label-info btn-round me-2">Manage Alumni</a>
                            <a href="manage_alumni.php?action=add" class="btn btn-primary btn-round">Add Alumni</a>
                        </div>
                    </div>

                     
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Alumni</p>
                                                <h4 class="card-title"><?= number_format($total_alumni) ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-university"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Colleges</p>
                                                <h4 class="card-title"><?= $total_colleges ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Programs</p>
                                                <h4 class="card-title"><?= $total_programs ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                                <i class="fas fa-calendar"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Batches</p>
                                                <h4 class="card-title"><?= $total_batches ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                         
                        <div class="col-md-8">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Recent Alumni Registrations</div>
                                        <div class="card-tools">
                                            <a href="manage_alumni.php" class="btn btn-label-success btn-round btn-sm">
                                                <span class="btn-label">
                                                    <i class="fa fa-users"></i>
                                                </span>
                                                View All
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table align-items-center mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Student ID</th>
                                                    <th scope="col">College</th>
                                                    <th scope="col">Batch</th>
                                                    <th scope="col">Registered</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_alumni as $alumni): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm me-3">
                                                                <div class="avatar-title bg-primary rounded-circle">
                                                                    <?= strtoupper(substr($alumni['first_name'], 0, 1)) ?>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <strong><?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></strong>
                                                                <br><small class="text-muted"><?= htmlspecialchars($alumni['email']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($alumni['student_id']) ?></td>
                                                    <td><?= htmlspecialchars($alumni['college_name']) ?></td>
                                                    <td><?= $alumni['batch_year'] ?></td>
                                                    <td><?= date('M j, Y', strtotime($alumni['created_at'])) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="col-md-4">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-title">Alumni by College</div>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($college_stats as $stat): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($stat['code']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($stat['name']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0"><?= number_format($stat['alumni_count']) ?></h5>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
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
    <script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>
