<?php
$error = $data['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Tracker - Login</title>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Bug Tracker</h1>
            <p>Please log in to continue</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="?action=login">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="demo-credentials">
            <h3>Demo Credentials:</h3>
            <ul>
                <li><strong>Admin:</strong> admin / admin123</li>
                <li><strong>Manager:</strong> manager / manager123</li>
                <li><strong>User:</strong> user / user123</li>
            </ul>
        </div>
    </div>
</body>
</html>