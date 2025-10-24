<?php
$users = $data['users'] ?? [];
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
    <title>Admin Panel - Bug Tracker</title>
    
</head>
<body>
    <div class="header">
        <h1>Admin Panel</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?> (<?php echo htmlspecialchars($user['roleName']); ?>)</span>
            <a href="?action=logout" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
    
    <div class="nav">
        <a href="?action=dashboard">Dashboard</a>
        <a href="?action=bug_list">Bugs</a>
        <a href="?action=admin">Admin</a>
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
        
        <!-- User Management Section -->
        <div class="section">
            <h2>User Management</h2>
            
            <div style="margin-bottom: 1rem;">
                <button onclick="showUserForm()" class="btn btn-primary">Add New User</button>
            </div>
            
            <div id="userForm" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <h3>Add/Edit User</h3>
                <form method="POST" action="?action=create_user">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="roleId">Role:</label>
                            <select id="roleId" name="roleId" required>
                                <option value="1">Admin</option>
                                <option value="2">Manager</option>
                                <option value="3">User</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Create User</button>
                    <button type="button" onclick="hideUserForm()" class="btn btn-secondary">Cancel</button>
                </form>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="no-data">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['userId']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['Name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo strtolower($u['roleName']); ?>">
                                        <?php echo htmlspecialchars($u['roleName']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="?action=user_details&userId=<?php echo $u['userId']; ?>" class="btn btn-primary btn-sm">View</a>
                                        <a href="?action=update_user&userId=<?php echo $u['userId']; ?>" class="btn btn-success btn-sm">Edit</a>
                                        <?php if ($u['userId'] != $user['userId']): ?>
                                            <a href="?action=delete_user&userId=<?php echo $u['userId']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Project Management Section -->
        <div class="section">
            <h2>Project Management</h2>
            
            <div style="margin-bottom: 1rem;">
                <button onclick="showProjectForm()" class="btn btn-primary">Add New Project</button>
            </div>
            
            <div id="projectForm" style="display: none; background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <h3>Add/Edit Project</h3>
                <form method="POST" action="?action=create_project">
                    <div class="form-group">
                        <label for="projectName">Project Name:</label>
                        <input type="text" id="projectName" name="projectName" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Create Project</button>
                    <button type="button" onclick="hideProjectForm()" class="btn btn-secondary">Cancel</button>
                </form>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="3" class="no-data">No projects found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo $project['Id']; ?></td>
                                <td><?php echo htmlspecialchars($project['Project']); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?action=project_details&projectId=<?php echo $project['Id']; ?>" class="btn btn-primary btn-sm">View</a>
                                        <a href="?action=update_project&projectId=<?php echo $project['Id']; ?>" class="btn btn-success btn-sm">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function showUserForm() {
            document.getElementById('userForm').style.display = 'block';
        }
        
        function hideUserForm() {
            document.getElementById('userForm').style.display = 'none';
        }
        
        function showProjectForm() {
            document.getElementById('projectForm').style.display = 'block';
        }
        
        function hideProjectForm() {
            document.getElementById('projectForm').style.display = 'none';
        }
    </script>
</body>
</html>