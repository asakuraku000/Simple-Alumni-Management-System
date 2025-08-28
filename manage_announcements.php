<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_announcement':
            try {
                $data = [
                    $_SESSION['admin_id'],
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['excerpt'] ?: substr($_POST['content'], 0, 200) . '...',
                    $_POST['announcement_type'],
                    $_POST['priority'],
                    $_POST['target_batches'] ?: null,
                    $_POST['target_programs'] ?: null,
                    $_POST['target_colleges'] ?: null,
                    isset($_POST['is_public']) ? 1 : 0,
                    $_POST['expires_at'] ?: null,
                    $_POST['status'] ?: 'Draft'
                ];
                
                query("INSERT INTO announcements (admin_id, title, content, excerpt, announcement_type, priority, target_batches, target_programs, target_colleges, is_public, expires_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $data);
                echo json_encode(['success' => true, 'message' => 'Announcement created successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'edit_announcement':
            try {
                $data = [
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['excerpt'] ?: substr($_POST['content'], 0, 200) . '...',
                    $_POST['announcement_type'],
                    $_POST['priority'],
                    $_POST['target_batches'] ?: null,
                    $_POST['target_programs'] ?: null,
                    $_POST['target_colleges'] ?: null,
                    isset($_POST['is_public']) ? 1 : 0,
                    $_POST['expires_at'] ?: null,
                    $_POST['status'] ?: 'Draft',
                    $_POST['id']
                ];
                
                query("UPDATE announcements SET title=?, content=?, excerpt=?, announcement_type=?, priority=?, target_batches=?, target_programs=?, target_colleges=?, is_public=?, expires_at=?, status=? WHERE id=?", $data);
                echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_announcement':
            try {
                query("DELETE FROM announcements WHERE id = ?", [$_POST['id']]);
                echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_announcement':
            $announcement = fetchRow("SELECT * FROM announcements WHERE id = ?", [$_POST['id']]);
            echo json_encode($announcement);
            exit;
            
        case 'toggle_status':
            try {
                $current_status = fetchRow("SELECT status FROM announcements WHERE id = ?", [$_POST['id']]);
                $new_status = $current_status['status'] === 'Published' ? 'Draft' : 'Published';
                
                // Set published_at when publishing
                if ($new_status === 'Published') {
                    query("UPDATE announcements SET status = ?, published_at = NOW() WHERE id = ?", [$new_status, $_POST['id']]);
                } else {
                    query("UPDATE announcements SET status = ?, published_at = NULL WHERE id = ?", [$new_status, $_POST['id']]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Status updated to ' . $new_status]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($type_filter) {
    $where_conditions[] = "a.announcement_type = ?";
    $params[] = $type_filter;
}

if ($search) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get announcements with pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$announcements = fetchAll("
    SELECT a.*, ad.full_name as admin_name,
           CASE 
               WHEN a.expires_at IS NOT NULL AND a.expires_at < NOW() THEN 'Expired'
               ELSE a.status
           END as display_status
    FROM announcements a 
    JOIN admins ad ON a.admin_id = ad.id 
    $where_clause
    ORDER BY a.created_at DESC 
    LIMIT $limit OFFSET $offset
", $params);

// Get total count for pagination
$total_count = fetchRow("SELECT COUNT(*) as count FROM announcements a $where_clause", $params)['count'];
$total_pages = ceil($total_count / $limit);

// Get statistics
$stats = [
    'total' => fetchRow("SELECT COUNT(*) as count FROM announcements")['count'],
    'published' => fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'Published'")['count'],
    'draft' => fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'Draft'")['count'],
    'expired' => fetchRow("SELECT COUNT(*) as count FROM announcements WHERE expires_at < NOW() AND expires_at IS NOT NULL")['count'],
    'archived' => fetchRow("SELECT COUNT(*) as count FROM announcements WHERE status = 'Archived'")['count']
];

// Get colleges, programs, and batches for dropdowns
$colleges = fetchAll("SELECT id, name FROM colleges WHERE is_active = 1 ORDER BY name");
$programs = fetchAll("SELECT id, name, college_id FROM programs WHERE is_active = 1 ORDER BY name");
$batches = fetchAll("SELECT id, CONCAT(year, ' - ', semester, ' Semester') as display_name FROM batches ORDER BY year DESC, semester");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Announcements - NISU Alumni System</title>
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
                            <h3 class="fw-bold mb-3">Announcements</h3>
                            <h6 class="op-7 mb-2">Manage announcements and notifications</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#announcementModal" onclick="openAddModal()">
                                <i class="fa fa-plus"></i> Create Announcement
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
                                                <i class="fas fa-bullhorn"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total</p>
                                                <h4 class="card-title"><?= $stats['total'] ?></h4>
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
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Published</p>
                                                <h4 class="card-title"><?= $stats['published'] ?></h4>
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
                                            <div class="icon-big text-center icon-warning bubble-shadow-small">
                                                <i class="fas fa-edit"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Draft</p>
                                                <h4 class="card-title"><?= $stats['draft'] ?></h4>
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
                                                <i class="fas fa-archive"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Archived</p>
                                                <h4 class="card-title"><?= $stats['archived'] ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Filter Announcements</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="">All Status</option>
                                                <option value="Published" <?= $status_filter === 'Published' ? 'selected' : '' ?>>Published</option>
                                                <option value="Draft" <?= $status_filter === 'Draft' ? 'selected' : '' ?>>Draft</option>
                                                <option value="Archived" <?= $status_filter === 'Archived' ? 'selected' : '' ?>>Archived</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Type</label>
                                            <select name="type" class="form-select">
                                                <option value="">All Types</option>
                                                <option value="General" <?= $type_filter === 'General' ? 'selected' : '' ?>>General</option>
                                                <option value="Event" <?= $type_filter === 'Event' ? 'selected' : '' ?>>Event</option>
                                                <option value="Job" <?= $type_filter === 'Job' ? 'selected' : '' ?>>Job</option>
                                                <option value="Achievement" <?= $type_filter === 'Achievement' ? 'selected' : '' ?>>Achievement</option>
                                                <option value="Memorial" <?= $type_filter === 'Memorial' ? 'selected' : '' ?>>Memorial</option>
                                                <option value="Urgent" <?= $type_filter === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Search</label>
                                            <input type="text" name="search" class="form-control" placeholder="Search title or content..." value="<?= htmlspecialchars($search) ?>">
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
                                    <h4 class="card-title">Announcements (<?= $total_count ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Type</th>
                                                    <th>Priority</th>
                                                    <th>Status</th>
                                                    <th>Views</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($announcements as $announcement): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                                            <?php if (!$announcement['is_public']): ?>
                                                            <span class="badge badge-secondary ms-1">Private</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($announcement['excerpt'] ?: substr($announcement['content'], 0, 100) . '...') ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info"><?= htmlspecialchars($announcement['announcement_type']) ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $priority_class = [
                                                            'Low' => 'badge-secondary',
                                                            'Normal' => 'badge-primary',
                                                            'High' => 'badge-warning',
                                                            'Critical' => 'badge-danger'
                                                        ];
                                                        ?>
                                                        <span class="badge <?= $priority_class[$announcement['priority']] ?? 'badge-secondary' ?>">
                                                            <?= htmlspecialchars($announcement['priority']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'Published' => 'badge-success',
                                                            'Draft' => 'badge-warning',
                                                            'Archived' => 'badge-secondary',
                                                            'Expired' => 'badge-dark'
                                                        ];
                                                        ?>
                                                        <span class="badge <?= $status_class[$announcement['display_status']] ?? 'badge-secondary' ?>">
                                                            <?= htmlspecialchars($announcement['display_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= number_format($announcement['view_count']) ?></td>
                                                    <td>
                                                        <small>
                                                            <?= date('M j, Y', strtotime($announcement['created_at'])) ?><br>
                                                            <span class="text-muted">by <?= htmlspecialchars($announcement['admin_name']) ?></span>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" onclick="editAnnouncement(<?= $announcement['id'] ?>)" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-<?= $announcement['status'] === 'Published' ? 'warning' : 'success' ?>" 
                                                                    onclick="toggleStatus(<?= $announcement['id'] ?>)" 
                                                                    title="<?= $announcement['status'] === 'Published' ? 'Unpublish' : 'Publish' ?>">
                                                                <i class="fa fa-<?= $announcement['status'] === 'Published' ? 'eye-slash' : 'eye' ?>"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(<?= $announcement['id'] ?>)" title="Delete">
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
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&type=<?= $type_filter ?>&search=<?= urlencode($search) ?>">Previous</a>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&type=<?= $type_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                            </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&type=<?= $type_filter ?>&search=<?= urlencode($search) ?>">Next</a>
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

   
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="announcementModalLabel">Create Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="announcementForm">
                    <div class="modal-body">
                        <input type="hidden" id="announcement_id" name="id">
                        <input type="hidden" id="announcement_action" name="action" value="add_announcement">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Priority *</label>
                                    <select class="form-select" name="priority" required>
                                        <option value="Normal">Normal</option>
                                        <option value="Low">Low</option>
                                        <option value="High">High</option>
                                        <option value="Critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Type *</label>
                                    <select class="form-select" name="announcement_type" required>
                                        <option value="">Select Type</option>
                                        <option value="General">General</option>
                                        <option value="Event">Event</option>
                                        <option value="Job">Job</option>
                                        <option value="Achievement">Achievement</option>
                                        <option value="Memorial">Memorial</option>
                                        <option value="Urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="Draft">Draft</option>
                                        <option value="Published">Published</option>
                                        <option value="Archived">Archived</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Expires At (Optional)</label>
                                    <input type="datetime-local" class="form-control" name="expires_at">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content *</label>
                            <textarea class="form-control" name="content" rows="6" required placeholder="Enter announcement content..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Excerpt (Optional)</label>
                            <textarea class="form-control" name="excerpt" rows="2" placeholder="Brief summary (auto-generated if empty)"></textarea>
                        </div>
                        
                         Target Audience 
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Target Colleges</label>
                                    <select class="form-select" name="target_colleges" multiple>
                                        <?php foreach ($colleges as $college): ?>
                                        <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl to select multiple</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Target Programs</label>
                                    <select class="form-select" name="target_programs" multiple>
                                        <?php foreach ($programs as $program): ?>
                                        <option value="<?= $program['id'] ?>"><?= htmlspecialchars($program['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl to select multiple</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Target Batches</label>
                                    <select class="form-select" name="target_batches" multiple>
                                        <?php foreach ($batches as $batch): ?>
                                        <option value="<?= $batch['id'] ?>"><?= htmlspecialchars($batch['display_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl to select multiple</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_public" id="is_public" checked>
                                <label class="form-check-label" for="is_public">
                                    Public Announcement (visible to all)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Announcement</button>
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

    <script>
        function openAddModal() {
            document.getElementById('announcementModalLabel').textContent = 'Create Announcement';
            document.getElementById('announcement_action').value = 'add_announcement';
            document.getElementById('announcementForm').reset();
            document.getElementById('announcement_id').value = '';
            document.getElementById('is_public').checked = true;
        }
        
        function editAnnouncement(id) {
            $.post('manage_announcements.php', {action: 'get_announcement', id: id}, function(data) {
                if (data) {
                    document.getElementById('announcementModalLabel').textContent = 'Edit Announcement';
                    document.getElementById('announcement_action').value = 'edit_announcement';
                    document.getElementById('announcement_id').value = data.id;
                    
                    // Fill form fields
                    Object.keys(data).forEach(key => {
                        const field = document.querySelector('#announcementForm [name="' + key + '"]');
                        if (field) {
                            if (field.type === 'checkbox') {
                                field.checked = data[key] == 1;
                            } else if (field.type === 'datetime-local' && data[key]) {
                                const date = new Date(data[key]);
                                field.value = date.toISOString().slice(0, 16);
                            } else if (field.multiple && data[key]) {
                                // Handle multiple select fields
                                const values = data[key].split(',');
                                Array.from(field.options).forEach(option => {
                                    option.selected = values.includes(option.value);
                                });
                            } else {
                                field.value = data[key] || '';
                            }
                        }
                    });
                    
                    $('#announcementModal').modal('show');
                }
            }, 'json');
        }
        
        function deleteAnnouncement(id) {
            swal({
                title: "Are you sure?",
                text: "This will permanently delete the announcement!",
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
                    $.post('manage_announcements.php', {action: 'delete_announcement', id: id}, function(response) {
                        if (response.success) {
                            swal("Deleted!", response.message, "success").then(() => {
                                location.reload();
                            });
                        } else {
                            swal("Error!", response.message, "error");
                        }
                    }, 'json');
                }
            });
        }
        
        function toggleStatus(id) {
            $.post('manage_announcements.php', {action: 'toggle_status', id: id}, function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success").then(() => {
                        location.reload();
                    });
                } else {
                    swal("Error!", response.message, "error");
                }
            }, 'json');
        }
        
        // Handle form submission
        $('#announcementForm').on('submit', function(e) {
            e.preventDefault();
            
            // Handle multiple select fields
            const formData = new FormData(this);
            
            // Convert multiple select values to comma-separated strings
            ['target_colleges', 'target_programs', 'target_batches'].forEach(field => {
                const select = document.querySelector(`[name="${field}"]`);
                if (select && select.multiple) {
                    const values = Array.from(select.selectedOptions).map(option => option.value);
                    formData.set(field, values.join(','));
                }
            });
            
            $.ajax({
                url: 'manage_announcements.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        swal("Success!", response.message, "success").then(() => {
                            $('#announcementModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        swal("Error!", response.message, "error");
                    }
                }
            });
        });
    </script>
</body>
</html>
