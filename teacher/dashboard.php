<?php
$pageTitle = 'Teacher Dashboard';
require_once '../config/config.php';
requireRole('teacher');

$teacherId = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'];

// Get teacher statistics
$stats = [
    'total_reports' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ?", [$teacherId])['count'],
    'submitted' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ? AND status = 'submitted'", [$teacherId])['count'],
    'approved' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ? AND status = 'approved'", [$teacherId])['count'],
    'under_review' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ? AND status IN ('under_review', 'revision_required')", [$teacherId])['count']
];

// Get recent reports
$recentReports = $db->fetchAll(
    "SELECT r.*, s.name as school_name 
     FROM reports r 
     JOIN schools s ON r.school_id = s.id 
     WHERE r.student_id = ? 
     ORDER BY r.submission_date DESC 
     LIMIT 5",
    [$teacherId]
);

// Get school information
$school = $db->fetchOne("SELECT name FROM schools WHERE id = ?", [$schoolId]);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-chalkboard-teacher text-primary me-2"></i>
            Teacher Dashboard
        </h1>
        <p class="text-muted mb-0">
            Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! 
            <?php if ($school): ?>
                <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($school['name']); ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="col-auto">
        <a href="../student/submit_report.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Submit New Report
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid mb-4">
    <div class="dashboard-card">
        <div class="icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <h3><?php echo $stats['total_reports']; ?></h3>
        <p>Total Reports</p>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, var(--warning-color) 0%, #F97316 100%);">
        <div class="icon">
            <i class="fas fa-clock"></i>
        </div>
        <h3><?php echo $stats['submitted']; ?></h3>
        <p>Awaiting Review</p>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, var(--accent-color) 0%, #8B5CF6 100%);">
        <div class="icon">
            <i class="fas fa-eye"></i>
        </div>
        <h3><?php echo $stats['under_review']; ?></h3>
        <p>Under Review</p>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, var(--secondary-color) 0%, #10B981 100%);">
        <div class="icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3><?php echo $stats['approved']; ?></h3>
        <p>Approved</p>
    </div>
</div>

<div class="row">
    <!-- Recent Reports -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Reports
                </h5>
                <a href="../student/my_reports.php" class="btn btn-sm btn-outline-primary">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentReports)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No reports submitted yet</h6>
                        <p class="text-mute mb-3">Start by submitting your first research report</p>
                        <a href="../student/submit_report.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Submit Report
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentReports as $report): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-school me-1"></i>
                                        <?php echo htmlspecialchars($report['school_name']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDate($report['submission_date']); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge status-<?php echo $report['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                    <?php if ($report['grade']): ?>
                                        <div class="mt-1">
                                            <small class="text-success fw-bold">
                                                Grade: <?php echo htmlspecialchars($report['grade']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Tips -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../student/submit_report.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Submit New Report
                    </a>
                    <a href="../student/my_reports.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-file-alt me-1"></i>View My Reports
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Submission Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Teacher Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Upload research papers, lesson plans, or educational content</small>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Support for documents, images, and videos</small>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Maximum file size: 100MB</small>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Include detailed descriptions of your work</small>
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Select appropriate school for submission</small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>