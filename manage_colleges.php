<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_college':
            try {
                $data = [
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['description'] ?: null,
                    $_POST['dean_name'] ?: null
                ];
                
                query("INSERT INTO colleges (code, name, description, dean_name) VALUES (?, ?, ?, ?)", $data);
                echo json_encode(['success' => true, 'message' => 'College added successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'edit_college':
            try {
                $data = [
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['description'] ?: null,
                    $_POST['dean_name'] ?: null,
                    $_POST['id']
                ];
                
                query("UPDATE colleges SET code=?, name=?, description=?, dean_name=? WHERE id=?", $data);
                echo json_encode(['success' => true, 'message' => 'College updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_college':
            try {
                query("UPDATE colleges SET is_active = 0 WHERE id = ?", [$_POST['id']]);
                echo json_encode(['success' => true, 'message' => 'College deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_college':
            $college = fetchRow("SELECT * FROM colleges WHERE id = ?", [$_POST['id']]);
            echo json_encode($college);
            exit;
            
        case 'add_program':
            try {
                $data = [
                    $_POST['college_id'],
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['degree_type'],
                    $_POST['duration_years'] ?: 4.0,
                    $_POST['description'] ?: null
                ];
                
                query("INSERT INTO programs (college_id, code, name, degree_type, duration_years, description) VALUES (?, ?, ?, ?, ?, ?)", $data);
                echo json_encode(['success' => true, 'message' => 'Program added successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'edit_program':
            try {
                $data = [
                    $_POST['college_id'],
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['degree_type'],
                    $_POST['duration_years'] ?: 4.0,
                    $_POST['description'] ?: null,
                    $_POST['id']
                ];
                
                query("UPDATE programs SET college_id=?, code=?, name=?, degree_type=?, duration_years=?, description=? WHERE id=?", $data);
                echo json_encode(['success' => true, 'message' => 'Program updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_program':
            try {
                query("UPDATE programs SET is_active = 0 WHERE id = ?", [$_POST['id']]);
                echo json_encode(['success' => true, 'message' => 'Program deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_program':
            $program = fetchRow("SELECT * FROM programs WHERE id = ?", [$_POST['id']]);
            echo json_encode($program);
            exit;
    }
}

// Get colleges and programs
$colleges = fetchAll("
    SELECT c.*, COUNT(p.id) as program_count, COUNT(a.id) as alumni_count
    FROM colleges c 
    LEFT JOIN programs p ON c.id = p.college_id AND p.is_active = 1
    LEFT JOIN alumni a ON c.id = a.college_id AND a.is_active = 1
    WHERE c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.name
");

$programs = fetchAll("
    SELECT p.*, c.name as college_name, COUNT(a.id) as alumni_count
    FROM programs p 
    JOIN colleges c ON p.college_id = c.id 
    LEFT JOIN alumni a ON p.id = a.program_id AND a.is_active = 1
    WHERE p.is_active = 1 
    GROUP BY p.id 
    ORDER BY c.name, p.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Colleges & Programs - NISU Alumni System</title>
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
                            <h3 class="fw-bold mb-3">Colleges & Programs</h3>
                            <h6 class="op-7 mb-2">Manage colleges and academic programs</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <button class="btn btn-info btn-round me-2" data-bs-toggle="modal" data-bs-target="#collegeModal" onclick="openAddCollegeModal()">
                                <i class="fa fa-plus"></i> Add College
                            </button>
                            <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#programModal" onclick="openAddProgramModal()">
                                <i class="fa fa-plus"></i> Add Program
                            </button>
                        </div>
                    </div>

                     
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Colleges (<?= count($colleges) ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>College Name</th>
                                                    <th>Dean</th>
                                                    <th>Programs</th>
                                                    <th>Alumni</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($colleges as $college): ?>
                                                <tr>
                                                    <td><span class="badge badge-primary"><?= htmlspecialchars($college['code']) ?></span></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($college['name']) ?></strong>
                                                        <?php if ($college['description']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($college['description'], 0, 100)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($college['dean_name'] ?: 'Not assigned') ?></td>
                                                    <td><span class="badge badge-info"><?= $college['program_count'] ?></span></td>
                                                    <td><span class="badge badge-success"><?= $college['alumni_count'] ?></span></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" onclick="editCollege(<?= $college['id'] ?>)" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteCollege(<?= $college['id'] ?>)" title="Delete">
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

                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Programs (<?= count($programs) ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Program Name</th>
                                                    <th>College</th>
                                                    <th>Degree Type</th>
                                                    <th>Duration</th>
                                                    <th>Alumni</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($programs as $program): ?>
                                                <tr>
                                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($program['code']) ?></span></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($program['name']) ?></strong>
                                                        <?php if ($program['description']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($program['description'], 0, 80)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($program['college_name']) ?></td>
                                                    <td><span class="badge badge-warning"><?= htmlspecialchars($program['degree_type']) ?></span></td>
                                                    <td><?= $program['duration_years'] ?> years</td>
                                                    <td><span class="badge badge-success"><?= $program['alumni_count'] ?></span></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" onclick="editProgram(<?= $program['id'] ?>)" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteProgram(<?= $program['id'] ?>)" title="Delete">
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

     College Modal 
    <div class="modal fade" id="collegeModal" tabindex="-1" aria-labelledby="collegeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="collegeModalLabel">Add College</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="collegeForm">
                    <div class="modal-body">
                        <input type="hidden" id="college_id" name="id">
                        <input type="hidden" id="college_action" name="action" value="add_college">
                        
                        <div class="mb-3">
                            <label class="form-label">College Code *</label>
                            <input type="text" class="form-control" name="code" required placeholder="e.g., CAS, COE">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">College Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Dean Name</label>
                            <input type="text" class="form-control" name="dean_name" placeholder="Dr. John Doe">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the college"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save College</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     Program Modal 
    <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="programModalLabel">Add Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="programForm">
                    <div class="modal-body">
                        <input type="hidden" id="program_id" name="id">
                        <input type="hidden" id="program_action" name="action" value="add_program">
                        
                        <div class="mb-3">
                            <label class="form-label">College *</label>
                            <select class="form-select" name="college_id" required>
                                <option value="">Select College</option>
                                <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Program Code *</label>
                            <input type="text" class="form-control" name="code" required placeholder="e.g., BS-CS, BS-IT">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Program Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Degree Type *</label>
                                    <select class="form-select" name="degree_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Certificate">Certificate</option>
                                        <option value="Diploma">Diploma</option>
                                        <option value="Associate">Associate</option>
                                        <option value="Bachelor">Bachelor</option>
                                        <option value="Master">Master</option>
                                        <option value="Doctorate">Doctorate</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Duration (Years)</label>
                                    <input type="number" class="form-control" name="duration_years" step="0.5" min="1" max="10" value="4">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of the program"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Program</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     Core JS Files 
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        // College functions
        function openAddCollegeModal() {
            document.getElementById('collegeModalLabel').textContent = 'Add College';
            document.getElementById('college_action').value = 'add_college';
            document.getElementById('collegeForm').reset();
            document.getElementById('college_id').value = '';
        }
        
        function editCollege(id) {
            $.post('manage_colleges.php', {action: 'get_college', id: id}, function(data) {
                if (data) {
                    document.getElementById('collegeModalLabel').textContent = 'Edit College';
                    document.getElementById('college_action').value = 'edit_college';
                    document.getElementById('college_id').value = data.id;
                    
                    Object.keys(data).forEach(key => {
                        const field = document.querySelector('#collegeForm [name="' + key + '"]');
                        if (field) {
                            field.value = data[key] || '';
                        }
                    });
                    
                    $('#collegeModal').modal('show');
                }
            }, 'json');
        }
        
        function deleteCollege(id) {
            swal({
                title: "Are you sure?",
                text: "This will deactivate the college and all its programs!",
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
                    $.post('manage_colleges.php', {action: 'delete_college', id: id}, function(response) {
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

        // Program functions
        function openAddProgramModal() {
            document.getElementById('programModalLabel').textContent = 'Add Program';
            document.getElementById('program_action').value = 'add_program';
            document.getElementById('programForm').reset();
            document.getElementById('program_id').value = '';
        }
        
        function editProgram(id) {
            $.post('manage_colleges.php', {action: 'get_program', id: id}, function(data) {
                if (data) {
                    document.getElementById('programModalLabel').textContent = 'Edit Program';
                    document.getElementById('program_action').value = 'edit_program';
                    document.getElementById('program_id').value = data.id;
                    
                    Object.keys(data).forEach(key => {
                        const field = document.querySelector('#programForm [name="' + key + '"]');
                        if (field) {
                            field.value = data[key] || '';
                        }
                    });
                    
                    $('#programModal').modal('show');
                }
            }, 'json');
        }
        
        function deleteProgram(id) {
            swal({
                title: "Are you sure?",
                text: "This will deactivate the program!",
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
                    $.post('manage_colleges.php', {action: 'delete_program', id: id}, function(response) {
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
        
        // Handle form submissions
        $('#collegeForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('manage_colleges.php', $(this).serialize(), function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success").then(() => {
                        $('#collegeModal').modal('hide');
                        location.reload();
                    });
                } else {
                    swal("Error!", response.message, "error");
                }
            }, 'json');
        });

        $('#programForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('manage_colleges.php', $(this).serialize(), function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success").then(() => {
                        $('#programModal').modal('hide');
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
