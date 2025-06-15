<?php
$pageTitle = 'Submit Report';
require_once '../config/config.php';
requireRole('student');

$studentId = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'];
$error = '';
$success = '';

// Get schools for dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

if ($_POST) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $selectedSchoolId = (int)$_POST['school_id'];
    
    if (empty($title) || empty($description) || empty($selectedSchoolId)) {
        $error = 'Please fill in all required fields.';
    } elseif (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        // Verify selected school exists
        $school = $db->fetchOne("SELECT id FROM schools WHERE id = ? AND status = 'active'", [$selectedSchoolId]);
        
        if (!$school) {
            $error = 'Selected school is not valid.';
        } else {
            // Handle file upload with support for images and videos
            $uploadResult = uploadReportFile($_FILES['report_file']);
            
            if (!$uploadResult['success']) {
                $error = $uploadResult['error'];
            } else {
                // Save report to database
                $reportData = [
                    'title' => $title,
                    'description' => $description,
                    'student_id' => $studentId,
                    'school_id' => $selectedSchoolId,
                    'file_path' => $uploadResult['file_path'],
                    'file_name' => $uploadResult['file_name'],
                    'file_size' => $uploadResult['file_size'],
                    'file_type' => $uploadResult['file_type'],
                    'status' => 'submitted'
                ];
                
                $reportId = $db->insert('reports', $reportData);
                
                if ($reportId) {
                    logActivity($db, $studentId, $_SESSION['role'], 'submit_report', "Submitted report: $title");
                    
                    redirect('my_reports.php', 'Report submitted successfully! Your report is now awaiting review.', 'success');
                } else {
                    // Delete uploaded file if database insert failed
                    deleteFile($uploadResult['file_path']);
                    $error = 'Failed to submit report. Please try again.';
                }
            }
        }
    }
}

// Enhanced file upload function for reports
function uploadReportFile($file, $uploadDir = '../uploads/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'No file was uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'File is too large'];
        default:
            return ['success' => false, 'error' => 'Unknown upload error'];
    }
    
    // Check file size (100MB limit for videos)
    $maxSize = 100 * 1024 * 1024; // 100MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File is too large (max 100MB)'];
    }
    
    // Check file type and determine category
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    $documentTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
    $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $videoTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
    
    $fileType = 'document';
    if (in_array($extension, $imageTypes)) {
        $fileType = 'image';
    } elseif (in_array($extension, $videoTypes)) {
        $fileType = 'video';
    } elseif (!in_array($extension, $documentTypes)) {
        return ['success' => false, 'error' => 'File type not allowed. Allowed types: PDF, DOC, DOCX, TXT, RTF, JPG, PNG, GIF, MP4, AVI, MOV, WMV'];
    }
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileName = uniqid() . '_' . $file['name'];
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    return [
        'success' => true,
        'file_name' => $fileName,
        'file_path' => $filePath,
        'file_size' => $file['size'],
        'file_type' => $fileType
    ];
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-plus me-2"></i>Submit Capstone Report
                </h4>
                <p class="text-muted mb-0 mt-2">Upload your capstone report for review and evaluation</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="title" class="form-label">Report Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($title ?? ''); ?>"
                               placeholder="Enter a descriptive title for your capstone report" required>
                        <div class="invalid-feedback">Please enter a report title.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Provide a brief description of your capstone project..." required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        <div class="invalid-feedback">Please provide a description.</div>
                        <div class="form-text">Describe the main objectives, methodology, and key findings of your capstone project.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="school_id" class="form-label">Select School *</label>
                        <select class="form-select" id="school_id" name="school_id" required>
                            <option value="">Choose the school for submission</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo $school['id']; ?>" 
                                        <?php echo (isset($selectedSchoolId) && $selectedSchoolId == $school['id']) ? 'selected' : 
                                                  (isset($_SESSION['school_id']) && $_SESSION['school_id'] == $school['id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($school['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a school.</div>
                        <div class="form-text">Select the school where you want to submit your capstone report.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_file" class="form-label">Report File *</label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <h6 class="mt-2 mb-1">Choose File or Drag & Drop</h6>
                            <p class="text-muted mb-2">Documents: PDF, DOC, DOCX, TXT, RTF</p>
                            <p class="text-muted mb-2">Images: JPG, PNG, GIF, BMP, WEBP</p>
                            <p class="text-muted mb-0">Videos: MP4, AVI, MOV, WMV, FLV, WEBM, MKV</p>
                            <p class="text-muted mb-0"><small>Maximum file size: 100MB</small></p>
                        </div>
                        <input type="file" class="form-control d-none" id="report_file" name="report_file" 
                               accept=".pdf,.doc,.docx,.txt,.rtf,.jpg,.jpeg,.png,.gif,.bmp,.webp,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv" required>
                        <div class="invalid-feedback">Please select a file to upload.</div>
                    </div>
                    
                    <div class="academic-section">
                        <h6><i class="fas fa-info-circle me-2"></i>Submission Guidelines</h6>
                        <ul class="mb-0">
                            <li>Ensure your report is in final format before submission</li>
                            <li>Include all required sections as per your institution's guidelines</li>
                            <li>File should be properly formatted and readable</li>
                            <li>For videos: Ensure good quality and clear audio if applicable</li>
                            <li>For images: Use high resolution for better clarity</li>
                            <li>Once submitted, you cannot modify the file until review is complete</li>
                            <li>You will receive notifications about the review status</li>
                        </ul>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirm_submission" required>
                        <label class="form-check-label" for="confirm_submission">
                            I confirm that this is my original work and I understand that it will be reviewed according to academic standards. *
                        </label>
                        <div class="invalid-feedback">You must confirm the submission terms.</div>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Report
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced file upload area functionality
    const fileUploadArea = document.querySelector('.file-upload-area');
    const fileInput = document.querySelector('#report_file');
    
    if (fileUploadArea && fileInput) {
        // Click to upload
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });
        
        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                updateFileDisplay(this.files[0]);
            }
        });
    }
    
    function updateFileDisplay(file) {
        const allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExtension)) {
            showAlert('Invalid file type. Please upload a supported file format.', 'danger');
            return;
        }
        
        if (file.size > 100 * 1024 * 1024) { // 100MB limit
            showAlert('File size exceeds 100MB limit.', 'danger');
            return;
        }
        
        // Determine file type icon
        let icon = 'fas fa-file-alt';
        let typeText = 'Document';
        
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExtension)) {
            icon = 'fas fa-image';
            typeText = 'Image';
        } else if (['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'].includes(fileExtension)) {
            icon = 'fas fa-video';
            typeText = 'Video';
        }
        
        fileUploadArea.innerHTML = `
            <div class="file-selected">
                <i class="${icon} file-upload-icon text-success"></i>
                <h6 class="mt-2 mb-1">${file.name}</h6>
                <p class="text-muted mb-0">${formatFileSize(file.size)} - ${typeText}</p>
                <small class="text-success">File ready for upload</small>
            </div>
        `;
    }
    
    function formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    }
    
    // Character counter for description
    const description = document.getElementById('description');
    const charCounter = document.createElement('div');
    charCounter.className = 'form-text text-end';
    charCounter.id = 'charCounter';
    description.parentNode.appendChild(charCounter);
    
    function updateCharCounter() {
        const length = description.value.length;
        charCounter.textContent = `${length} characters`;
        
        if (length < 50) {
            charCounter.className = 'form-text text-end text-warning';
        } else {
            charCounter.className = 'form-text text-end text-muted';
        }
    }
    
    description.addEventListener('input', updateCharCounter);
    updateCharCounter();
    
    // Form submission confirmation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (this.checkValidity()) {
            const confirmed = confirm('Are you sure you want to submit this report? Once submitted, you cannot modify it until the review is complete.');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Re-enable button after some time in case of errors
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 30000);
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>