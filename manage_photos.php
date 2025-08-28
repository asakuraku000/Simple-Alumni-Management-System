<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add_album':
            try {
                $data = [
                    $_SESSION['admin_id'],
                    $_POST['title'],
                    $_POST['description'] ?: null,
                    $_POST['album_type'],
                    $_POST['event_id'] ?: null,
                    $_POST['batch_id'] ?: null,
                    isset($_POST['is_public']) ? 1 : 0
                ];
                
                query("INSERT INTO photo_albums (admin_id, title, description, album_type, event_id, batch_id, is_public) VALUES (?, ?, ?, ?, ?, ?, ?)", $data);
                echo json_encode(['success' => true, 'message' => 'Album created successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'edit_album':
            try {
                $data = [
                    $_POST['title'],
                    $_POST['description'] ?: null,
                    $_POST['album_type'],
                    $_POST['event_id'] ?: null,
                    $_POST['batch_id'] ?: null,
                    isset($_POST['is_public']) ? 1 : 0,
                    $_POST['id']
                ];
                
                query("UPDATE photo_albums SET title=?, description=?, album_type=?, event_id=?, batch_id=?, is_public=? WHERE id=?", $data);
                echo json_encode(['success' => true, 'message' => 'Album updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_album':
            try {
                // Get album info for directory cleanup
                $album = fetchRow("SELECT * FROM photo_albums WHERE id = ?", [$_POST['id']]);
                
                // Delete photos from database and files
                $photos = fetchAll("SELECT * FROM photos WHERE album_id = ?", [$_POST['id']]);
                foreach ($photos as $photo) {
                    if (file_exists($photo['file_path'])) {
                        unlink($photo['file_path']);
                    }
                }
                
                query("DELETE FROM photos WHERE album_id = ?", [$_POST['id']]);
                query("DELETE FROM photo_albums WHERE id = ?", [$_POST['id']]);
                
                echo json_encode(['success' => true, 'message' => 'Album deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_album':
            $album = fetchRow("SELECT * FROM photo_albums WHERE id = ?", [$_POST['id']]);
            echo json_encode($album);
            exit;
            
        case 'upload_photos':
            try {
                $album_id = $_POST['album_id'];
                $album = fetchRow("SELECT pa.*, e.title as event_title, b.year, b.semester 
                                 FROM photo_albums pa 
                                 LEFT JOIN events e ON pa.event_id = e.id 
                                 LEFT JOIN batches b ON pa.batch_id = b.id 
                                 WHERE pa.id = ?", [$album_id]);
                
                if (!$album) {
                    throw new Exception('Album not found');
                }
                
                // Create directory path based on album type
                $base_path = 'uploads/photos/';
                switch ($album['album_type']) {
                    case 'Event':
                        $folder_name = $album['event_title'] ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $album['event_title']) : 'event_' . $album['event_id'];
                        $upload_path = $base_path . 'events/' . $folder_name . '/';
                        break;
                    case 'Batch':
                        $folder_name = $album['year'] . '_' . $album['semester'];
                        $upload_path = $base_path . 'batches/' . $folder_name . '/';
                        break;
                    case 'General':
                        $upload_path = $base_path . 'general/';
                        break;
                    case 'Achievement':
                        $upload_path = $base_path . 'achievements/';
                        break;
                    default:
                        $upload_path = $base_path . 'misc/';
                }
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }
                
                $uploaded_files = [];
                $errors = [];
                
                // Handle multiple file uploads
                if (isset($_FILES['photos'])) {
                    $files = $_FILES['photos'];
                    $file_count = count($files['name']);
                    
                    for ($i = 0; $i < $file_count; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $original_name = $files['name'][$i];
                            $tmp_name = $files['tmp_name'][$i];
                            $file_size = $files['size'][$i];
                            
                            // Validate file type
                            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                            $file_type = mime_content_type($tmp_name);
                            
                            if (!in_array($file_type, $allowed_types)) {
                                $errors[] = "File {$original_name}: Invalid file type";
                                continue;
                            }
                            
                            // Check file size (max 5MB)
                            if ($file_size > 5 * 1024 * 1024) {
                                $errors[] = "File {$original_name}: File too large (max 5MB)";
                                continue;
                            }
                            
                            // Generate unique filename
                            $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                            $unique_filename = uniqid('photo_' . date('Ymd_His') . '_') . '.' . strtolower($file_extension);
                            $full_path = $upload_path . $unique_filename;
                            
                            // Move uploaded file
                            if (move_uploaded_file($tmp_name, $full_path)) {
                                // Save to database
                                $photo_data = [
                                    $album_id,
                                    $_SESSION['admin_id'],
                                    $unique_filename,
                                    $original_name,
                                    $full_path,
                                    $_POST['titles'][$i] ?? null,
                                    $_POST['descriptions'][$i] ?? null,
                                    $_POST['captions'][$i] ?? null,
                                    $_POST['location'] ?? null
                                ];
                                
                                query("INSERT INTO photos (album_id, admin_id, filename, original_filename, file_path, title, description, caption, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", $photo_data);
                                
                                $uploaded_files[] = [
                                    'original_name' => $original_name,
                                    'filename' => $unique_filename,
                                    'path' => $full_path
                                ];
                            } else {
                                $errors[] = "File {$original_name}: Failed to upload";
                            }
                        } else {
                            $errors[] = "File {$files['name'][$i]}: Upload error code " . $files['error'][$i];
                        }
                    }
                }
                
                $response = [
                    'success' => true,
                    'message' => count($uploaded_files) . ' photos uploaded successfully',
                    'uploaded_files' => $uploaded_files
                ];
                
                if (!empty($errors)) {
                    $response['warnings'] = $errors;
                }
                
                echo json_encode($response);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_photo':
            try {
                $photo = fetchRow("SELECT * FROM photos WHERE id = ?", [$_POST['id']]);
                if ($photo && file_exists($photo['file_path'])) {
                    unlink($photo['file_path']);
                }
                query("DELETE FROM photos WHERE id = ?", [$_POST['id']]);
                echo json_encode(['success' => true, 'message' => 'Photo deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Get albums with photo count
$albums = fetchAll("
    SELECT pa.*, COUNT(p.id) as photo_count, a.full_name as admin_name,
           e.title as event_title, b.year as batch_year, b.semester as batch_semester
    FROM photo_albums pa 
    JOIN admins a ON pa.admin_id = a.id
    LEFT JOIN photos p ON pa.id = p.album_id
    LEFT JOIN events e ON pa.event_id = e.id
    LEFT JOIN batches b ON pa.batch_id = b.id
    GROUP BY pa.id 
    ORDER BY pa.created_at DESC
");

// Get events and batches for dropdowns
$events = fetchAll("SELECT id, title FROM events WHERE status = 'Published' ORDER BY title");
$batches = fetchAll("SELECT id, year, semester FROM batches ORDER BY year DESC, semester");

// Get photos for selected album
$selected_album_id = $_GET['album_id'] ?? null;
$photos = [];
if ($selected_album_id) {
    $photos = fetchAll("
        SELECT p.*, pa.title as album_title
        FROM photos p 
        JOIN photo_albums pa ON p.album_id = pa.id
        WHERE p.album_id = ?
        ORDER BY p.upload_date DESC
    ", [$selected_album_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Photo Gallery - NISU Alumni System</title>
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
    
    <style>
        .photo-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        .photo-upload-area:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .photo-upload-area.dragover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .photo-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            background: white;
        }
        .file-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 3px;
            margin-right: 10px;
        }
        .file-info {
            flex: 1;
        }
        .file-actions {
            display: flex;
            gap: 5px;
        }
    </style>
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
                            <h3 class="fw-bold mb-3">Photo Gallery</h3>
                            <h6 class="op-7 mb-2">Manage photo albums and images</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <button class="btn btn-info btn-round me-2" data-bs-toggle="modal" data-bs-target="#photoModal" onclick="openAddPhotoModal()">
                                <i class="fa fa-plus"></i> Upload Photos
                            </button>
                            <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#albumModal" onclick="openAddAlbumModal()">
                                <i class="fa fa-plus"></i> Create Album
                            </button>
                        </div>
                    </div>

                     
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Photo Albums (<?= count($albums) ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($albums as $album): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <div class="avatar avatar-xl mb-3">
                                                        <div class="avatar-title bg-primary rounded">
                                                            <i class="fas fa-images fa-2x"></i>
                                                        </div>
                                                    </div>
                                                    <h5 class="card-title"><?= htmlspecialchars($album['title']) ?></h5>
                                                    <p class="card-text text-muted">
                                                        <?= htmlspecialchars($album['album_type']) ?>
                                                        <?php if ($album['event_title']): ?>
                                                        <br><small>Event: <?= htmlspecialchars($album['event_title']) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($album['batch_year']): ?>
                                                        <br><small>Batch: <?= $album['batch_year'] ?> - <?= $album['batch_semester'] ?></small>
                                                        <?php endif; ?>
                                                    </p>
                                                    <div class="mb-3">
                                                        <span class="badge badge-info"><?= $album['photo_count'] ?> photos</span>
                                                        <?php if ($album['is_public']): ?>
                                                        <span class="badge badge-success">Public</span>
                                                        <?php else: ?>
                                                        <span class="badge badge-secondary">Private</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="btn-group" role="group">
                                                        <a href="?album_id=<?= $album['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fa fa-eye"></i> View
                                                        </a>
                                                        <button class="btn btn-sm btn-info" onclick="editAlbum(<?= $album['id'] ?>)">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteAlbum(<?= $album['id'] ?>)">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     
                    <?php if ($selected_album_id && !empty($photos)): ?>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Photos in "<?= htmlspecialchars($photos[0]['album_title']) ?>" (<?= count($photos) ?>)</h4>
                                    <div class="card-tools">
                                        <a href="manage_photos.php" class="btn btn-sm btn-secondary">Back to Albums</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($photos as $photo): ?>
                                        <div class="col-md-3 mb-4">
                                            <div class="card">
                                                <div class="card-img-top" style="height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                    <?php if (file_exists($photo['file_path'])): ?>
                                                    <img src="<?= htmlspecialchars($photo['file_path']) ?>" alt="<?= htmlspecialchars($photo['title'] ?: $photo['filename']) ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body p-2">
                                                    <h6 class="card-title"><?= htmlspecialchars($photo['title'] ?: $photo['filename']) ?></h6>
                                                    <?php if ($photo['description']): ?>
                                                    <p class="card-text small text-muted"><?= htmlspecialchars(substr($photo['description'], 0, 50)) ?>...</p>
                                                    <?php endif; ?>
                                                    <div class="btn-group btn-group-sm w-100">
                                                        <button class="btn btn-danger" onclick="deletePhoto(<?= $photo['id'] ?>)">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

<?php include("include/footer.php"); ?>
        </div>
    </div>

    
    <div class="modal fade" id="albumModal" tabindex="-1" aria-labelledby="albumModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="albumModalLabel">Create Album</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="albumForm">
                    <div class="modal-body">
                        <input type="hidden" id="album_id" name="id">
                        <input type="hidden" id="album_action" name="action" value="add_album">
                        
                        <div class="mb-3">
                            <label class="form-label">Album Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Album Type *</label>
                            <select class="form-select" name="album_type" required onchange="toggleAlbumFields()">
                                <option value="">Select Type</option>
                                <option value="Event">Event</option>
                                <option value="Batch">Batch</option>
                                <option value="General">General</option>
                                <option value="Achievement">Achievement</option>
                            </select>
                        </div>
                        
                        <div id="event_field" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Related Event</label>
                                <select class="form-select" name="event_id">
                                    <option value="">Select Event</option>
                                    <?php foreach ($events as $event): ?>
                                    <option value="<?= $event['id'] ?>"><?= htmlspecialchars($event['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div id="batch_field" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Related Batch</label>
                                <select class="form-select" name="batch_id">
                                    <option value="">Select Batch</option>
                                    <?php foreach ($batches as $batch): ?>
                                    <option value="<?= $batch['id'] ?>"><?= $batch['year'] ?> - <?= $batch['semester'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_public" id="is_public" checked>
                                <label class="form-check-label" for="is_public">
                                    Public Album
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Album</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Upload Photos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="photoForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload_photos">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Album *</label>
                            <select class="form-select" name="album_id" required>
                                <option value="">Select Album</option>
                                <?php foreach ($albums as $album): ?>
                                <option value="<?= $album['id'] ?>" <?= $selected_album_id == $album['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($album['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Photos *</label>
                            <div class="photo-upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Drag & Drop Photos Here</h5>
                                <p class="text-muted">or click to browse files</p>
                                <input type="file" id="photoFiles" name="photos[]" multiple accept="image/*" style="display: none;">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('photoFiles').click()">
                                    <i class="fa fa-plus"></i> Select Photos
                                </button>
                            </div>
                            <small class="text-muted">Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB per file.</small>
                        </div>
                        
                        <div id="selectedFiles" class="mb-3" style="display: none;">
                            <h6>Selected Files:</h6>
                            <div id="fileList"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location (Optional)</label>
                            <input type="text" class="form-control" name="location" placeholder="e.g., NISU Main Campus">
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Uploading... <span id="uploadStatus">0%</span></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fa fa-upload"></i> Upload Photos
                        </button>
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
        let selectedFiles = [];
        
        function openAddAlbumModal() {
            document.getElementById('albumModalLabel').textContent = 'Create Album';
            document.getElementById('album_action').value = 'add_album';
            document.getElementById('albumForm').reset();
            document.getElementById('album_id').value = '';
            document.getElementById('is_public').checked = true;
            toggleAlbumFields();
        }
        
        function openAddPhotoModal() {
            document.getElementById('photoForm').reset();
            selectedFiles = [];
            document.getElementById('selectedFiles').style.display = 'none';
            document.getElementById('fileList').innerHTML = '';
            document.querySelector('.progress-container').style.display = 'none';
        }
        
        function editAlbum(id) {
            $.post('manage_photos.php', {action: 'get_album', id: id}, function(data) {
                if (data) {
                    document.getElementById('albumModalLabel').textContent = 'Edit Album';
                    document.getElementById('album_action').value = 'edit_album';
                    document.getElementById('album_id').value = data.id;
                    
                    Object.keys(data).forEach(key => {
                        const field = document.querySelector('#albumForm [name="' + key + '"]');
                        if (field) {
                            if (field.type === 'checkbox') {
                                field.checked = data[key] == 1;
                            } else {
                                field.value = data[key] || '';
                            }
                        }
                    });
                    
                    toggleAlbumFields();
                    $('#albumModal').modal('show');
                }
            }, 'json');
        }
        
        function deleteAlbum(id) {
            swal({
                title: "Are you sure?",
                text: "This will delete the album and all its photos!",
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
                    $.post('manage_photos.php', {action: 'delete_album', id: id}, function(response) {
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
        
        function deletePhoto(id) {
            swal({
                title: "Are you sure?",
                text: "This will permanently delete the photo!",
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
                    $.post('manage_photos.php', {action: 'delete_photo', id: id}, function(response) {
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
        
        function toggleAlbumFields() {
            const albumType = document.querySelector('[name="album_type"]').value;
            document.getElementById('event_field').style.display = albumType === 'Event' ? 'block' : 'none';
            document.getElementById('batch_field').style.display = albumType === 'Batch' ? 'block' : 'none';
        }
        
        // File upload handling
        document.getElementById('photoFiles').addEventListener('change', handleFileSelect);
        
        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            handleFiles(files);
        });
        
        function handleFileSelect(e) {
            handleFiles(e.target.files);
        }
        
        function handleFiles(files) {
            selectedFiles = Array.from(files);
            displaySelectedFiles();
        }
        
        function displaySelectedFiles() {
            const fileList = document.getElementById('fileList');
            const selectedFilesDiv = document.getElementById('selectedFiles');
            
            if (selectedFiles.length === 0) {
                selectedFilesDiv.style.display = 'none';
                return;
            }
            
            selectedFilesDiv.style.display = 'block';
            fileList.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                
                const fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                fileInfo.innerHTML = `
                    <div><strong>${file.name}</strong></div>
                    <div class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                    <input type="text" class="form-control form-control-sm mt-1" placeholder="Photo title (optional)" data-index="${index}" name="titles[]">
                    <textarea class="form-control form-control-sm mt-1" placeholder="Description (optional)" rows="2" data-index="${index}" name="descriptions[]"></textarea>
                `;
                
                const fileActions = document.createElement('div');
                fileActions.className = 'file-actions';
                fileActions.innerHTML = `
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(${index})">
                        <i class="fa fa-times"></i>
                    </button>
                `;
                
                fileItem.appendChild(img);
                fileItem.appendChild(fileInfo);
                fileItem.appendChild(fileActions);
                fileList.appendChild(fileItem);
            });
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            displaySelectedFiles();
        }
        
        // Handle form submissions
        $('#albumForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('manage_photos.php', $(this).serialize(), function(response) {
                if (response.success) {
                    swal("Success!", response.message, "success").then(() => {
                        $('#albumModal').modal('hide');
                        location.reload();
                    });
                } else {
                    swal("Error!", response.message, "error");
                }
            }, 'json');
        });

        $('#photoForm').on('submit', function(e) {
            e.preventDefault();
            
            if (selectedFiles.length === 0) {
                swal("Error!", "Please select at least one photo to upload.", "error");
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'upload_photos');
            formData.append('album_id', $('[name="album_id"]').val());
            formData.append('location', $('[name="location"]').val());
            
            // Add files
            selectedFiles.forEach((file, index) => {
                formData.append('photos[]', file);
            });
            
            // Add titles and descriptions
            document.querySelectorAll('[name="titles[]"]').forEach((input, index) => {
                formData.append('titles[]', input.value);
            });
            
            document.querySelectorAll('[name="descriptions[]"]').forEach((textarea, index) => {
                formData.append('descriptions[]', textarea.value);
            });
            
            // Show progress
            const progressContainer = document.querySelector('.progress-container');
            const progressBar = document.querySelector('.progress-bar');
            const uploadStatus = document.getElementById('uploadStatus');
            const uploadBtn = document.getElementById('uploadBtn');
            
            progressContainer.style.display = 'block';
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Uploading...';
            
            $.ajax({
                url: 'manage_photos.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            progressBar.style.width = percentComplete + '%';
                            uploadStatus.textContent = Math.round(percentComplete) + '%';
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Upload Photos';
                    progressContainer.style.display = 'none';
                    
                    if (response.success) {
                        let message = response.message;
                        if (response.warnings && response.warnings.length > 0) {
                            message += '\n\nWarnings:\n' + response.warnings.join('\n');
                        }
                        
                        swal("Success!", message, "success").then(() => {
                            $('#photoModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        swal("Error!", response.message, "error");
                    }
                },
                error: function() {
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Upload Photos';
                    progressContainer.style.display = 'none';
                    swal("Error!", "Upload failed. Please try again.", "error");
                }
            });
        });
    </script>
</body>
</html>
