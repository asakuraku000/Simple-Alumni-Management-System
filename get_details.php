<?php
require_once 'db_con.php';

header('Content-Type: application/json');

if ($_POST['type'] && $_POST['id']) {
    $type = $_POST['type'];
    $id = intval($_POST['id']);
    
    try {
        switch($type) {
            case 'alumni':
                $query = "
                    SELECT a.*, 
                           CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as name,
                           c.name as college, 
                           p.name as program, 
                           b.year as batch_year, 
                           b.semester
                    FROM alumni a
                    JOIN colleges c ON a.college_id = c.id
                    JOIN programs p ON a.program_id = p.id
                    JOIN batches b ON a.batch_id = b.id
                    WHERE a.id = ? AND a.is_active = 1
                ";
                break;
                
            case 'announcement':
                $query = "
                    SELECT * FROM announcements 
                    WHERE id = ? AND status = 'Published' AND is_public = 1
                    AND (expires_at IS NULL OR expires_at > NOW())
                ";
                break;
                
            case 'event':
                $query = "
                    SELECT * FROM events 
                    WHERE id = ? AND status = 'Published'
                ";
                break;
                
            case 'photo':
                $query = "
                    SELECT p.*, pa.title as album_title 
                    FROM photos p
                    JOIN photo_albums pa ON p.album_id = pa.id
                    WHERE p.id = ? AND pa.is_public = 1
                ";
                break;
                
            default:
                throw new Exception('Invalid type');
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            // Handle default images
            switch($type) {
                case 'alumni':
                    if (empty($data['profile_picture']) || !file_exists($data['profile_picture'])) {
                        $data['profile_picture'] = 'default/default-alumni.jpg';
                    }
                    break;
                case 'announcement':
                    if (empty($data['featured_image']) || !file_exists($data['featured_image'])) {
                        $data['featured_image'] = 'default/default-announcement.jpg';
                    }
                    break;
                case 'event':
                    if (empty($data['featured_image']) || !file_exists($data['featured_image'])) {
                        $data['featured_image'] = 'default/default-event.jpg';
                    }
                    break;
                case 'photo':
                    if (empty($data['file_path']) || !file_exists($data['file_path'])) {
                        $data['file_path'] = 'default/default-photo.jpg';
                    }
                    break;
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}
?>
