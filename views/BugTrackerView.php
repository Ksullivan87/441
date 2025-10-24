<?php
$bugs = $data['bugs'] ?? [];
$projects = $data['projects'] ?? [];
$message = $data['message'] ?? '';
$error = $data['error'] ?? '';
$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Tracker</title>
    
</head>
<body>
    <div class="header">
        <h1>Bug Tracker</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?> (<?php echo htmlspecialchars($user['roleName']); ?>)</span>
            <a href="?action=logout" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
    
    <div class="nav">
        <a href="?action=dashboard">Dashboard</a>
        <a href="?action=bug_list">Bugs</a>
        <?php if ($user['roleName'] === 'Admin' || $user['roleName'] === 'Manager'): ?>
            <a href="?action=admin">Admin</a>
        <?php endif; ?>
        <?php if ($user['roleName'] === 'Admin'): ?>
            <a href="?action=system_statistics">Statistics</a>
        <?php endif; ?>
        <a href="?action=activity_log">Activity Log</a>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="filters">
            <h3>Filter Bugs</h3>
            <form method="GET" action="">
                <input type="hidden" name="action" value="bug_list">
                <div class="filter-group">
                    <select name="projectId">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['projectId']; ?>" 
                                    <?php echo (isset($_GET['projectId']) && $_GET['projectId'] == $project['projectId']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['projectName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="open" <?php echo (isset($_GET['status']) && $_GET['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                        <option value="overdue" <?php echo (isset($_GET['status']) && $_GET['status'] === 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?action=bug_list" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
        
        <div class="bugs-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Summary</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Date Raised</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bugs)): ?>
                        <tr>
                            <td colspan="8" class="no-data">No bugs found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bugs as $bug): ?>
                            <tr>
                                <td><?php echo $bug['bugId']; ?></td>
                                <td><?php echo htmlspecialchars($bug['summary']); ?></td>
                                <td><?php echo htmlspecialchars($bug['projectName'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $bug['statusName'] ?? 'unknown')); ?>">
                                        <?php echo htmlspecialchars($bug['statusName'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-<?php echo strtolower($bug['priorityName'] ?? 'medium'); ?>">
                                        <?php echo htmlspecialchars($bug['priorityName'] ?? 'Medium'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($bug['assignedName']): ?>
                                        <?php echo htmlspecialchars($bug['assignedName']); ?>
                                    <?php else: ?>
                                        <em>Unassigned</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($bug['dateRaised'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?action=bug_details&bugId=<?php echo $bug['bugId']; ?>" class="btn btn-primary btn-sm">View</a>
                                        <?php if ($user['roleName'] === 'Admin' || $user['roleName'] === 'Manager' || $bug['assignedToId'] == $user['userId']): ?>
                                            <a href="?action=update_bug&bugId=<?php echo $bug['bugId']; ?>" class="btn btn-success btn-sm">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="?action=create_bug" class="btn btn-primary">Create New Bug</a>
        </div>
    </div>
</body>
</html>