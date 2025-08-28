<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Get report data
$total_alumni = fetchRow("SELECT COUNT(*) as count FROM alumni WHERE is_active = 1")['count'];
$total_colleges = fetchRow("SELECT COUNT(*) as count FROM colleges WHERE is_active = 1")['count'];
$total_programs = fetchRow("SELECT COUNT(*) as count FROM programs WHERE is_active = 1")['count'];
$total_batches = fetchRow("SELECT COUNT(*) as count FROM batches")['count'];

// Alumni by college
$alumni_by_college = fetchAll("
    SELECT c.name as college, c.code, COUNT(a.id) as alumni_count
    FROM colleges c
    LEFT JOIN alumni a ON c.id = a.college_id AND a.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id, c.name, c.code
    ORDER BY alumni_count DESC
");

// Alumni by batch year
$alumni_by_year = fetchAll("
    SELECT b.year, COUNT(a.id) as alumni_count
    FROM batches b
    LEFT JOIN alumni a ON b.id = a.batch_id AND a.is_active = 1
    GROUP BY b.year
    ORDER BY b.year DESC
    LIMIT 10
");

// Employment statistics
$employment_stats = fetchAll("
    SELECT c.name as college, COUNT(DISTINCT a.id) as total_alumni,
           COUNT(DISTINCT ae.alumni_id) as employed_alumni,
           ROUND((COUNT(DISTINCT ae.alumni_id) / COUNT(DISTINCT a.id)) * 100, 2) as employment_rate
    FROM colleges c
    LEFT JOIN alumni a ON c.id = a.college_id AND a.is_active = 1
    LEFT JOIN alumni_employment ae ON a.id = ae.alumni_id AND ae.is_current = 1
    WHERE c.is_active = 1
    GROUP BY c.id, c.name
    HAVING total_alumni > 0
    ORDER BY employment_rate DESC
");

// Latin honors statistics
$honors_stats = fetchAll("
    SELECT 
        COUNT(CASE WHEN latin_honor = 'Summa Cum Laude' THEN 1 END) as summa_count,
        COUNT(CASE WHEN latin_honor = 'Magna Cum Laude' THEN 1 END) as magna_count,
        COUNT(CASE WHEN latin_honor = 'Cum Laude' THEN 1 END) as cum_laude_count,
        COUNT(CASE WHEN latin_honor IS NULL OR latin_honor = '' THEN 1 END) as regular_count
    FROM alumni WHERE is_active = 1
");

// Recent events
$recent_events = fetchAll("
    SELECT e.title, e.event_type, e.start_date, COUNT(er.id) as registrations
    FROM events e
    LEFT JOIN event_registrations er ON e.id = er.event_id
    WHERE e.status = 'Published'
    GROUP BY e.id
    ORDER BY e.start_date DESC
    LIMIT 5
");

// Top programs by alumni count
$top_programs = fetchAll("
    SELECT p.name as program, c.name as college, COUNT(a.id) as alumni_count
    FROM programs p
    JOIN colleges c ON p.college_id = c.id
    LEFT JOIN alumni a ON p.id = a.program_id AND a.is_active = 1
    WHERE p.is_active = 1
    GROUP BY p.id, p.name, c.name
    ORDER BY alumni_count DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Reports - NISU Alumni System</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <h3 class="fw-bold mb-3">Reports & Analytics</h3>
                            <h6 class="op-7 mb-2">Comprehensive alumni system reports and statistics</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <button class="btn btn-primary btn-round" onclick="window.print()">
                                <i class="fa fa-print"></i> Print Report
                            </button>
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
                      
                        <div class="col-md-6">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Alumni Distribution by College</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="min-height: 300px">
                                        <canvas id="collegeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                         
                        <div class="col-md-6">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Alumni by Graduation Year</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="min-height: 300px">
                                        <canvas id="yearChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                 
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-title">Employment Statistics by College</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>College</th>
                                                    <th>Total Alumni</th>
                                                    <th>Employed</th>
                                                    <th>Employment Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($employment_stats as $stat): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($stat['college']) ?></td>
                                                    <td><?= number_format($stat['total_alumni']) ?></td>
                                                    <td><?= number_format($stat['employed_alumni']) ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: <?= $stat['employment_rate'] ?>%" 
                                                                 aria-valuenow="<?= $stat['employment_rate'] ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?= $stat['employment_rate'] ?>%
                                                            </div>
                                                        </div>
                                                    </td>
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
                                    <div class="card-title">Latin Honors Distribution</div>
                                </div>
                                <div class="card-body">
                                    <?php $honors = $honors_stats[0]; ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Summa Cum Laude</span>
                                            <span class="fw-bold"><?= $honors['summa_count'] ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-warning" style="width: <?= ($honors['summa_count'] / $total_alumni) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Magna Cum Laude</span>
                                            <span class="fw-bold"><?= $honors['magna_count'] ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-info" style="width: <?= ($honors['magna_count'] / $total_alumni) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Cum Laude</span>
                                            <span class="fw-bold"><?= $honors['cum_laude_count'] ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-secondary" style="width: <?= ($honors['cum_laude_count'] / $total_alumni) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Regular</span>
                                            <span class="fw-bold"><?= $honors['regular_count'] ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-primary" style="width: <?= ($honors['regular_count'] / $total_alumni) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                  
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-title">Top Programs by Alumni Count</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Program</th>
                                                    <th>College</th>
                                                    <th>Alumni</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_programs as $program): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($program['program']) ?></td>
                                                    <td><?= htmlspecialchars($program['college']) ?></td>
                                                    <td><span class="badge badge-success"><?= $program['alumni_count'] ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-title">Recent Events</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Event</th>
                                                    <th>Type</th>
                                                    <th>Date</th>
                                                    <th>Registrations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_events as $event): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($event['title']) ?></td>
                                                    <td><span class="badge badge-info"><?= htmlspecialchars($event['event_type']) ?></span></td>
                                                    <td><?= date('M j, Y', strtotime($event['start_date'])) ?></td>
                                                    <td><span class="badge badge-primary"><?= $event['registrations'] ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
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
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        // College Chart
        const collegeCtx = document.getElementById('collegeChart').getContext('2d');
        const collegeChart = new Chart(collegeCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($c) { return '"' . htmlspecialchars($c['code']) . '"'; }, $alumni_by_college)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($alumni_by_college, 'alumni_count')); ?>],
                    backgroundColor: [
                        '#1572e8',
                        '#f25961',
                        '#f39c12',
                        '#00c851',
                        '#9c27b0',
                        '#ff5722'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Year Chart
        const yearCtx = document.getElementById('yearChart').getContext('2d');
        const yearChart = new Chart(yearCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($y) { return '"' . $y['year'] . '"'; }, $alumni_by_year)); ?>],
                datasets: [{
                    label: 'Alumni Count',
                    data: [<?php echo implode(',', array_column($alumni_by_year, 'alumni_count')); ?>],
                    backgroundColor: '#1572e8',
                    borderColor: '#1572e8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
