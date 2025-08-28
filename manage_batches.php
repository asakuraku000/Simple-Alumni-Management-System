<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add':
            try {
                $data = [
                    $_POST['year'],
                    $_POST['semester'],
                    $_POST['graduation_date'],
                    $_POST['theme'] ?: null,
                    $_POST['description'] ?: null,
                    $_POST['total_graduates'] ?: 0
                ];
                
                query("INSERT INTO batches (year, semester, graduation_date, theme, description, total_graduates) VALUES (?, ?, ?, ?, ?, ?)", $data);
                echo json_encode(['success' => true, 'message' => 'Batch added successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'edit':
            try {
                $data = [
                    $_POST['year'],
                    $_POST['semester'],
                    $_POST['graduation_date'],
                    $_POST['theme'] ?: null,
                    $_POST['description'] ?: null,
                    $_POST['total_graduates'] ?: 0,
                    $_POST['id']
                ];
                
                query("UPDATE batches SET year=?, semester=?, graduation_date=?, theme=?, description=?, total_graduates=? WHERE id=?", $data);
                echo json_encode(['success' => true, 'message' => 'Batch updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete':
            try {
                query("DELETE FROM batches WHERE id = ?", [$_POST['id']]);
                echo json_encode(['success' => true, 'message' => 'Batch deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get':
            $batch = fetchRow("SELECT * FROM batches WHERE id = ?", [$_POST['id']]);
            echo json_encode($batch);
            exit;
    }
}

// Get batches with statistics
$batches = fetchAll("
    SELECT b.*, COUNT(a.id) as actual_graduates,
           COUNT(CASE WHEN a.latin_honor = 'Summa Cum Laude' THEN 1 END) as summa_count,
           COUNT(CASE WHEN a.latin_honor = 'Magna Cum Laude' THEN 1 END) as magna_count,
           COUNT(CASE WHEN a.latin_honor = 'Cum Laude' THEN 1 END) as cum_laude_count
    FROM batches b 
    LEFT JOIN alumni a ON b.id = a.batch_id AND a.is_active = 1
    GROUP BY b.id 
    ORDER BY b.year DESC, b.semester
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Batch Management - NISU Alumni System</title>
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
                            <h3 class="fw-bold mb-3">Batch Management</h3>
                            <h6 class="op-7 mb-2">Manage graduation batches and ceremonies</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#batchModal" onclick="openAddModal()">
                                <i class="fa fa-plus"></i> Add Batch
                            </button>
                        </div>
                    </div>

                     
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Graduation Batches (<?= count($batches) ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Semester</th>
                                                    <th>Graduation Date</th>
                                                    <th>Theme</th>
                                                    <th>Graduates</th>
                                                    <th>Latin Honors</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($batches as $batch): ?>
                                                <tr>
                                                    <td><span class="badge badge-primary"><?= $batch['year'] ?></span></td>
                                                    <td><span class="badge badge-info"><?= $batch['semester'] ?></span></td>
                                                    <td><?= date('M j, Y', strtotime($batch['graduation_date'])) ?></td>
                                                    <td>
                                                        <?php if ($batch['theme']): ?>
                                                        <strong><?= htmlspecialchars($batch['theme']) ?></strong>
                                                        <?php else: ?>
                                                        <span class="text-muted">No theme</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-success"><?= $batch['actual_graduates'] ?></span>
                                                        <?php if ($batch['total_graduates'] != $batch['actual_graduates']): ?>
                                                        <small class="text-muted">(Expected: <?= $batch['total_graduates'] ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($batch['summa_count'] > 0): ?>
                                                        <span class="badge badge-warning">Summa: <?= $batch['summa_count'] ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($batch['magna_count'] > 0): ?>
                                                        <span class="badge badge-info">Magna: <?= $batch['magna_count'] ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($batch['cum_laude_count'] > 0): ?>
                                                        <span class="badge badge-secondary">Cum Laude: <?= $batch['cum_laude_count'] ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" onclick="editBatch(<?= $batch['id'] ?>)" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteBatch(<?= $batch['id'] ?>)" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
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
                    </div>
                </div>
            </div>

<?php include("include/footer.php"); ?>
        </div>
    </div>

  
    <div class="modal fade" id="batchModal" tabindex="-1" aria-labelledby="batchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="batchModalLabel">Add Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="batchForm">
                    <div class="modal-body">
                        <input type="hidden" id="batch_id" name="id">
                        <input type="hidden" id="form_action" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Year *</label>
                                    <input type="number" class="form-control" name="year" required min="2000" max="2050" value="<?= date('Y') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Semester *</label>
                                    <select class="form-select" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="1st">1st Semester</option>
                                        <option value="2nd">2nd Semester</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Graduation Date *</label>
                            <input type="date" class="form-control" name="graduation_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Theme</label>
                            <input type="text" class="form-control" name="theme" placeholder="e.g., Excellence in Innovation">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Expected Total Graduates</label>
                            <input type="number" class="form-control" name="total_graduates" min="0" placeholder="0">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the graduation ceremony"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Batch</button>
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
            document.getElementById('batchModalLabel').textContent = 'Add Batch';
            document.getElementById('form_action').value = 'add';
            document.getElementById('batchForm').reset();
            document.getElementById('batch_id').value = '';
            // Set default year
            document.querySelector('[name="year"]').value = new Date().getFullYear();
        }
        
        function editBatch(id) {
            $.post('manage_batches.php', {action: 'get', id: id}, function(data) {
                if (data) {
                    document.getElementById('batchModalLabel').textContent = 'Edit Batch';
                    document.getElementById('form_action').value = 'edit';
                    document.getElementById('batch_id').value = data.id;
                    
                    Object.keys(data).forEach(key => {
                        const field = document.querySelector(`[name="${key}"]`);
                        if (field) {
                            field.value = data[key] || '';
                        }
                    });
                    
                    $('#batchModal').modal('show');
                }
            }, 'json');
        }
        
        function deleteBatch(id) {
            swal({
                title: "Are you sure?",
                text: "This will permanently delete the batch record!",
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
                    $.post('manage_batches.php', {action: 'delete', id: id}, function(response) {
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
        
        // Handle form submission
        $('#batchForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('manage_batches.php', $(this).serialize(), function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success").then(() => {
                        $('#batchModal').modal('hide');
                        location.reload();
                    });
                } else {
                    swal("Error!", response.message, "error");
                }
            }, 'json');
        });
    </script>
</body>
</html>
