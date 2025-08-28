<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    // Clear any previous output and set proper headers
    ob_clean();
    header('Content-Type: application/json');
    
    // Turn off error display to prevent non-JSON output
    ini_set('display_errors', 0);
    
    try {
        switch ($_POST['action']) {
            case 'add':
                // Validate required fields
                if (empty($_POST['student_id']) || empty($_POST['first_name']) || empty($_POST['last_name']) || 
                    empty($_POST['college_id']) || empty($_POST['program_id']) || empty($_POST['batch_id'])) {
                    throw new Exception('Required fields are missing');
                }

                // Handle profile picture upload
                $profile_picture = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/alumni/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_info = getimagesize($_FILES['profile_picture']['tmp_name']);
                    if ($file_info === false) {
                        throw new Exception('Invalid image file');
                    }
                    
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($file_info['mime'], $allowed_types)) {
                        throw new Exception('Only JPG, PNG, and GIF images are allowed');
                    }
                    
                    if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) { // 2MB limit
                        throw new Exception('Image file too large. Maximum 2MB allowed');
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                    $filename = uniqid('alumni_') . '.' . $file_extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                        throw new Exception('Failed to upload image');
                    }
                    
                    $profile_picture = $filepath;
                }

                $data = [
                    $_POST['student_id'],
                    $_POST['first_name'],
                    $_POST['middle_name'] ?: null,
                    $_POST['last_name'],
                    $_POST['suffix'] ?: null,
                    $_POST['email'] ?: null,
                    $_POST['phone'] ?: null,
                    $_POST['birth_date'] ?: null,
                    $_POST['gender'] ?: null,
                    $_POST['civil_status'] ?: null,
                    $_POST['present_address'] ?: null,
                    $_POST['permanent_address'] ?: null,
                    $_POST['city'] ?: null,
                    $_POST['province'] ?: null,
                    $_POST['country'] ?: 'Philippines',
                    $_POST['postal_code'] ?: null,
                    (int)$_POST['college_id'],
                    (int)$_POST['program_id'],
                    (int)$_POST['batch_id'],
                    $_POST['gpa'] ? (float)$_POST['gpa'] : null,
                    $_POST['latin_honor'] ?: null,
                    $_POST['bio'] ?: null,
                    $profile_picture
                ];

                $result = query("INSERT INTO alumni (student_id, first_name, middle_name, last_name, suffix, email, phone, birth_date, gender, civil_status, present_address, permanent_address, city, province, country, postal_code, college_id, program_id, batch_id, gpa, latin_honor, bio, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Alumni added successfully']);
                } else {
                    throw new Exception('Failed to add alumni to database');
                }
                break;
                
            case 'edit':
                if (empty($_POST['id'])) {
                    throw new Exception('Alumni ID is required');
                }

                $alumni_id = (int)$_POST['id'];
                
                // Handle profile picture upload for edit
                $profile_picture_update = '';
                $profile_picture_params = [];

                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/alumni/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_info = getimagesize($_FILES['profile_picture']['tmp_name']);
                    if ($file_info === false) {
                        throw new Exception('Invalid image file');
                    }
                    
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($file_info['mime'], $allowed_types)) {
                        throw new Exception('Only JPG, PNG, and GIF images are allowed');
                    }
                    
                    if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) { // 2MB limit
                        throw new Exception('Image file too large. Maximum 2MB allowed');
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                    $filename = uniqid('alumni_') . '.' . $file_extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                        throw new Exception('Failed to upload image');
                    }
                    
                    $profile_picture_update = ', profile_picture=?';
                    $profile_picture_params = [$filepath];
                }

                $base_data = [
                    $_POST['student_id'],
                    $_POST['first_name'],
                    $_POST['middle_name'] ?: null,
                    $_POST['last_name'],
                    $_POST['suffix'] ?: null,
                    $_POST['email'] ?: null,
                    $_POST['phone'] ?: null,
                    $_POST['birth_date'] ?: null,
                    $_POST['gender'] ?: null,
                    $_POST['civil_status'] ?: null,
                    $_POST['present_address'] ?: null,
                    $_POST['permanent_address'] ?: null,
                    $_POST['city'] ?: null,
                    $_POST['province'] ?: null,
                    $_POST['country'] ?: 'Philippines',
                    $_POST['postal_code'] ?: null,
                    (int)$_POST['college_id'],
                    (int)$_POST['program_id'],
                    (int)$_POST['batch_id'],
                    $_POST['gpa'] ? (float)$_POST['gpa'] : null,
                    $_POST['latin_honor'] ?: null,
                    $_POST['bio'] ?: null
                ];
                
                $data = array_merge($base_data, $profile_picture_params, [$alumni_id]);
                
                $sql = "UPDATE alumni SET student_id=?, first_name=?, middle_name=?, last_name=?, suffix=?, email=?, phone=?, birth_date=?, gender=?, civil_status=?, present_address=?, permanent_address=?, city=?, province=?, country=?, postal_code=?, college_id=?, program_id=?, batch_id=?, gpa=?, latin_honor=?, bio=?" . $profile_picture_update . " WHERE id=?";
                
                $result = query($sql, $data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Alumni updated successfully']);
                } else {
                    throw new Exception('Failed to update alumni');
                }
                break;
                
            case 'delete':
                if (empty($_POST['id'])) {
                    throw new Exception('Alumni ID is required');
                }
                
                $result = query("UPDATE alumni SET is_active = 0 WHERE id = ?", [(int)$_POST['id']]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Alumni deleted successfully']);
                } else {
                    throw new Exception('Failed to delete alumni');
                }
                break;
                
            case 'get':
                if (empty($_POST['id'])) {
                    throw new Exception('Alumni ID is required');
                }
                
                $alumni = fetchRow("SELECT * FROM alumni WHERE id = ?", [(int)$_POST['id']]);
                
                if ($alumni) {
                    echo json_encode($alumni);
                } else {
                    throw new Exception('Alumni not found');
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Rest of your existing code for displaying the page...
// Get filter data
$colleges = fetchAll("SELECT * FROM colleges WHERE is_active = 1 ORDER BY name");
$programs = fetchAll("SELECT p.*, c.name as college_name FROM programs p JOIN colleges c ON p.college_id = c.id WHERE p.is_active = 1 ORDER BY c.name, p.name");
$batches = fetchAll("SELECT * FROM batches ORDER BY year DESC, semester");

// Build search query
$where = "WHERE a.is_active = 1";
$params = [];

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.student_id LIKE ? OR a.email LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
}

if (!empty($_GET['college_id'])) {
    $where .= " AND a.college_id = ?";
    $params[] = $_GET['college_id'];
}

if (!empty($_GET['batch_id'])) {
    $where .= " AND a.batch_id = ?";
    $params[] = $_GET['batch_id'];
}

// Get alumni with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total_query = "SELECT COUNT(*) as count FROM alumni a $where";
$total_result = fetchRow($total_query, $params);
$total_records = $total_result['count'];
$total_pages = ceil($total_records / $limit);

$alumni_query = "
    SELECT a.*, c.name as college_name, p.name as program_name, b.year as batch_year, b.semester as batch_semester
    FROM alumni a 
    JOIN colleges c ON a.college_id = c.id 
    JOIN programs p ON a.program_id = p.id 
    JOIN batches b ON a.batch_id = b.id 
    $where
    ORDER BY a.created_at DESC 
    LIMIT $limit OFFSET $offset
";

$alumni_list = fetchAll($alumni_query, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Alumni Management - NISU Alumni System</title>
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
      
        <?php include("include/sidebar.php");?>
       

        <div class="main-panel">
            <div class="main-header">
                <?php include("include/main-header.php"); ?>  
                
                 
                         <?php include("include/navbar.php"); ?>
              
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Alumni Management</h3>
                            <h6 class="op-7 mb-2">Manage alumni records and information</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#alumniModal" onclick="openAddModal()">
                                <i class="fa fa-plus"></i> Add Alumni
                            </button>
                        </div>
                    </div>

                     
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Search</label>
                                            <input type="text" name="search" class="form-control" placeholder="Name, Student ID, Email..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">College</label>
                                            <select name="college_id" class="form-select">
                                                <option value="">All Colleges</option>
                                                <?php foreach ($colleges as $college): ?>
                                                <option value="<?= $college['id'] ?>" <?= ($_GET['college_id'] ?? '') == $college['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($college['name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Batch</label>
                                            <select name="batch_id" class="form-select">
                                                <option value="">All Batches</option>
                                                <?php foreach ($batches as $batch): ?>
                                                <option value="<?= $batch['id'] ?>" <?= ($_GET['batch_id'] ?? '') == $batch['id'] ? 'selected' : '' ?>>
                                                    <?= $batch['year'] ?> - <?= $batch['semester'] ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">&nbsp;</label>
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                     
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Alumni List (<?= number_format($total_records) ?> records)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>College</th>
                                                    <th>Program</th>
                                                    <th>Batch</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alumni_list as $alumni): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($alumni['student_id']) ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm me-3">
                                                                <?php if ($alumni['profile_picture'] && file_exists($alumni['profile_picture'])): ?>
                                                                    <img src="<?= htmlspecialchars($alumni['profile_picture']) ?>" alt="Profile" class="avatar-img rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <img src="default/default-alumni.jpg" alt="Default Profile" class="avatar-img rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                                    <div class="avatar-title bg-primary rounded-circle" style="display: none; width: 40px; height: 40px; align-items: center; justify-content: center;">
                                                                        <?= strtoupper(substr($alumni['first_name'], 0, 1)) ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div>
                                                                <strong><?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></strong>
                                                                <?php if ($alumni['latin_honor']): ?>
                                                                <br><small class="text-success"><?= htmlspecialchars($alumni['latin_honor']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($alumni['email'] ?: 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($alumni['college_name']) ?></td>
                                                    <td><?= htmlspecialchars($alumni['program_name']) ?></td>
                                                    <td><?= $alumni['batch_year'] ?> - <?= $alumni['batch_semester'] ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" onclick="editAlumni(<?= $alumni['id'] ?>)" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteAlumni(<?= $alumni['id'] ?>)" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                     
                                    <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                            </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<?php include("include/footer.php"); ?>
        </div>
    </div>

  
    <div class="modal fade" id="alumniModal" tabindex="-1" aria-labelledby="alumniModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alumniModalLabel">Add Alumni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="alumniForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="alumni_id" name="id">
                        <input type="hidden" id="form_action" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Student ID *</label>
                                    <input type="text" class="form-control" name="student_id" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" name="middle_name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Suffix</label>
                                    <input type="text" class="form-control" name="suffix" placeholder="Jr., Sr., III">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Birth Date</label>
                                    <input type="date" class="form-control" name="birth_date">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">College *</label>
                                    <select class="form-select" name="college_id" required onchange="loadPrograms(this.value)">
                                        <option value="">Select College</option>
                                        <?php foreach ($colleges as $college): ?>
                                        <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Program *</label>
                                    <select class="form-select" name="program_id" required>
                                        <option value="">Select Program</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Batch *</label>
                                    <select class="form-select" name="batch_id" required>
                                        <option value="">Select Batch</option>
                                        <?php foreach ($batches as $batch): ?>
                                        <option value="<?= $batch['id'] ?>"><?= $batch['year'] ?> - <?= $batch['semester'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Civil Status</label>
                                    <select class="form-select" name="civil_status">
                                        <option value="">Select Status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">GPA</label>
                                    <input type="number" class="form-control" name="gpa" step="0.01" min="1" max="4">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Latin Honor</label>
                                    <select class="form-select" name="latin_honor">
                                        <option value="">None</option>
                                        <option value="Cum Laude">Cum Laude</option>
                                        <option value="Magna Cum Laude">Magna Cum Laude</option>
                                        <option value="Summa Cum Laude">Summa Cum Laude</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Present Address</label>
                                    <textarea class="form-control" name="present_address" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Permanent Address</label>
                                    <textarea class="form-control" name="permanent_address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Province</label>
                                    <input type="text" class="form-control" name="province">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control" name="country" value="Philippines">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3" placeholder="Brief biography or description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*" onchange="previewImage(this)">
                            <small class="form-text text-muted">Accepted formats: JPG, JPEG, PNG, GIF. Max size: 2MB</small>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img id="preview" src="/placeholder.svg" alt="Preview" style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 8px;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Alumni</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
<script>// Programs data for dynamic loading
const programs = <?= json_encode($programs) ?>;

function loadPrograms(collegeId) {
    const programSelect = document.querySelector('select[name="program_id"]');
    programSelect.innerHTML = '<option value="">Select Program</option>';
    
    if (collegeId) {
        const collegePrograms = programs.filter(p => p.college_id == collegeId);
        collegePrograms.forEach(program => {
            const option = document.createElement('option');
            option.value = program.id;
            option.textContent = program.name;
            programSelect.appendChild(option);
        });
    }
}

function openAddModal() {
    document.getElementById('alumniModalLabel').textContent = 'Add Alumni';
    document.getElementById('form_action').value = 'add';
    document.getElementById('alumniForm').reset();
    document.getElementById('alumni_id').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('preview').src = '';
    
    // Clear program dropdown
    const programSelect = document.querySelector('select[name="program_id"]');
    programSelect.innerHTML = '<option value="">Select Program</option>';
}

function editAlumni(id) {
    // Show loading state
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    $.ajax({
        url: 'manage_alumni.php',
        type: 'POST',
        data: {action: 'get', id: id},
        dataType: 'json',
        timeout: 10000, // 10 second timeout
        success: function(data) {
            try {
                if (data && typeof data === 'object') {
                    document.getElementById('alumniModalLabel').textContent = 'Edit Alumni';
                    document.getElementById('form_action').value = 'edit';
                    document.getElementById('alumni_id').value = data.id;
                    
                    // Fill form fields safely
                    const formFields = [
                        'student_id', 'first_name', 'middle_name', 'last_name', 'suffix',
                        'email', 'phone', 'birth_date', 'gender', 'civil_status',
                        'present_address', 'permanent_address', 'city', 'province', 
                        'country', 'postal_code', 'gpa', 'latin_honor', 'bio'
                    ];
                    
                    formFields.forEach(fieldName => {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        if (field) {
                            field.value = data[fieldName] || '';
                        }
                    });
                    
                    // Handle select fields
                    ['college_id', 'batch_id', 'gender', 'civil_status', 'latin_honor'].forEach(fieldName => {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        if (field && data[fieldName]) {
                            field.value = data[fieldName];
                        }
                    });
                    
                    // Load programs for selected college
                    if (data.college_id) {
                        loadPrograms(data.college_id);
                        setTimeout(() => {
                            const programSelect = document.querySelector('[name="program_id"]');
                            if (programSelect && data.program_id) {
                                programSelect.value = data.program_id;
                            }
                        }, 100);
                    }

                    // Handle profile picture preview
                    const imagePreview = document.getElementById('imagePreview');
                    const preview = document.getElementById('preview');
                    
                    if (data.profile_picture && data.profile_picture !== 'default/default-alumni.jpg') {
                        imagePreview.style.display = 'block';
                        preview.src = data.profile_picture;
                        preview.onerror = function() {
                            imagePreview.style.display = 'none';
                        };
                    } else {
                        imagePreview.style.display = 'none';
                        preview.src = '';
                    }
                    
                    $('#alumniModal').modal('show');
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                console.error('Error processing alumni data:', error);
                swal("Error!", "Failed to load alumni data: " + error.message, "error");
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {xhr, status, error});
            let errorMessage = 'Failed to load alumni data';
            
            if (status === 'timeout') {
                errorMessage = 'Request timed out. Please try again.';
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    errorMessage = 'Server error occurred';
                }
            }
            
            swal("Error!", errorMessage, "error");
        },
        complete: function() {
            // Restore button state
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    });
}

function deleteAlumni(id) {
    swal({
        title: "Are you sure?",
        text: "This will deactivate the alumni record!",
        type: "warning",
        buttons: {
            confirm: {
                text: "Yes, delete it!",
                className: "btn btn-success",
            },
            cancel: {
                visible: true,
                className: "btn btn-danger",
            },
        },
    }).then((Delete) => {
        if (Delete) {
            $.ajax({
                url: 'manage_alumni.php',
                type: 'POST',
                data: {action: 'delete', id: id},
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        swal("Deleted!", response.message, "success").then(() => {
                            location.reload();
                        });
                    } else {
                        swal("Error!", response.message, "error");
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete Error:', {xhr, status, error});
                    swal("Error!", "An error occurred while deleting the record.", "error");
                }
            });
        }
    });
}

function previewImage(input) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            swal("Error!", "Image file is too large. Maximum 2MB allowed.", "error");
            input.value = '';
            previewContainer.style.display = 'none';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            swal("Error!", "Only JPG, PNG, and GIF images are allowed.", "error");
            input.value = '';
            previewContainer.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.onerror = function() {
            swal("Error!", "Failed to read the image file.", "error");
            previewContainer.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
        preview.src = '';
    }
}

// Handle form submission with file upload
$('#alumniForm').on('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    
    // Show loading state
    submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
    
    const formData = new FormData(this);
    
    $.ajax({
        url: 'manage_alumni.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 30000, // 30 second timeout for file uploads
        success: function(response) {
            if (response.success) {
                swal("Success!", response.message, "success").then(() => {
                    $('#alumniModal').modal('hide');
                    location.reload();
                });
            } else {
                swal("Error!", response.message, "error");
            }
        },
        error: function(xhr, status, error) {
            console.error('Form Submit Error:', {xhr, status, error});
            let errorMessage = "An error occurred while processing your request.";
            
            if (status === 'timeout') {
                errorMessage = "Request timed out. Please try again with a smaller image.";
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch (e) {
                    // If response is not JSON, it might be a PHP error
                    if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Parse error')) {
                        errorMessage = "Server configuration error. Please contact administrator.";
                    }
                }
            }
            
            swal("Error!", errorMessage, "error");
        },
        complete: function() {
            // Restore button state
            submitBtn.text(originalText).prop('disabled', false);
        }
    });
});

// Prevent double-clicking on action buttons
$(document).on('click', '.btn-group .btn', function(e) {
    const button = $(this);
    if (button.prop('disabled')) {
        e.preventDefault();
        return false;
    }
});</script>
</body>
</html>
