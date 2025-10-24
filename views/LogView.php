<?php
$activities = $data['activities'] ?? [];
$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Bug Tracker</title>
    
</head>
<body>
    <div class="header">
        <h1>Activity Log</h1>
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
        <div class="activity-log">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Action</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="3" class="no-data">No activity found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($activity['timestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>