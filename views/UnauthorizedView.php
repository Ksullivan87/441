<?php
$message = $data['message'] ?? 'You do not have permission to access this resource.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - Bug Tracker</title>
    
</head>
<body>
    <div class="unauthorized-container">
        <h1>403</h1>
        <h2>Access Denied</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        
        <div>
            <a href="?action=dashboard" class="btn btn-primary">Go to Dashboard</a>
            <a href="?action=logout" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</body>
</html>