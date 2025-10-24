<?php

class Users {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function createUser($userData, $adminId) {
        // Validate admin permissions
        if (!$this->isAdmin($adminId)) {
            throw new Exception("Only administrators can create users");
        }
        
        // Validate user data
        $validation = $this->validateUserData($userData, false);
        if (!$validation['valid']) {
            throw new Exception("Invalid user data: " . implode(', ', $validation['errors']));
        }
        
        // Hash password
        $hashedPassword = $this->hashPassword($userData['password']);
        
        $query = "INSERT INTO user_details (Username, Password, RoleID, ProjectId, Name) 
                  VALUES (?, ?, ?, ?, ?)";
        $params = [
            $userData['username'],
            $hashedPassword,
            $userData['roleId'],
            $userData['projectId'] ?? null,
            $userData['name']
        ];
        
        $userId = $this->db->insert($query, $params);
        
        if ($userId) {
            $this->logUserActivity($adminId, 'CREATE_USER', "Created user: {$userData['username']}");
            return $userId;
        }
        
        return false;
    }
    
    public function updateUser($userId, $userData, $requesterId) {
        // Check permissions
        if (!$this->isAdmin($requesterId) && $userId != $requesterId) {
            throw new Exception("Insufficient permissions to update user");
        }
        
        // Validate user data
        $validation = $this->validateUserData($userData, true);
        if (!$validation['valid']) {
            throw new Exception("Invalid user data: " . implode(', ', $validation['errors']));
        }
        
        $query = "UPDATE user_details SET Username = ?, Name = ?";
        $params = [$userData['username'], $userData['name']];
        
        // Only admins can change roles and project assignments
        if ($this->isAdmin($requesterId)) {
            if (isset($userData['roleId'])) {
                $query .= ", RoleID = ?";
                $params[] = $userData['roleId'];
            }
            if (isset($userData['projectId'])) {
                $query .= ", ProjectId = ?";
                $params[] = $userData['projectId'];
            }
        }
        
        // Update password if provided
        if (!empty($userData['password'])) {
            $query .= ", Password = ?";
            $params[] = $this->hashPassword($userData['password']);
        }
        
        $query .= " WHERE Id = ?";
        $params[] = $userId;
        
        $result = $this->db->update($query, $params);
        
        if ($result) {
            $this->logUserActivity($requesterId, 'UPDATE_USER', "Updated user: {$userData['username']}");
            return true;
        }
        
        return false;
    }
    
    public function deleteUser($userId, $adminId) {
        if (!$this->isAdmin($adminId)) {
            throw new Exception("Only administrators can delete users");
        }
        
        // Remove user from all assignments
        $this->removeUserFromAllAssignments($userId);
        
        $query = "DELETE FROM user_details WHERE Id = ?";
        $result = $this->db->delete($query, [$userId]);
        
        if ($result) {
            $this->logUserActivity($adminId, 'DELETE_USER', "Deleted user ID: $userId");
            return true;
        }
        
        return false;
    }
    
    public function getUserById($userId) {
        $query = "SELECT u.*, r.Role FROM user_details u 
                  LEFT JOIN role r ON u.RoleID = r.Id 
                  WHERE u.Id = ?";
        $result = $this->db->select($query, [$userId]);
        return $result ? $result[0] : false;
    }
    
    public function getAllUsers($adminId) {
        if (!$this->isAdmin($adminId)) {
            throw new Exception("Only administrators can view all users");
        }
        
        $query = "SELECT u.*, r.Role FROM user_details u 
                  LEFT JOIN role r ON u.RoleID = r.Id 
                  ORDER BY u.Name";
        return $this->db->select($query);
    }
    
    public function authenticate($username, $password) {
        $query = "SELECT u.*, r.Role FROM user_details u 
                  LEFT JOIN role r ON u.RoleID = r.Id 
                  WHERE u.Username = ?";
        $result = $this->db->select($query, [$username]);
        
        if ($result && $this->verifyPassword($password, $result[0]['Password'])) {
            return $result[0];
        }
        
        return false;
    }
    
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public function getUserRole($userId) {
        $user = $this->getUserById($userId);
        return $user ? $user['Role'] : false;
    }
    
    public function isAdmin($userId) {
        $role = $this->getUserRole($userId);
        return $role === 'Admin';
    }
    
    public function isManager($userId) {
        $role = $this->getUserRole($userId);
        return $role === 'Manager';
    }
    
    public function isRegularUser($userId) {
        $role = $this->getUserRole($userId);
        return $role === 'User';
    }
    
    public function assignUserToProject($userId, $projectId) {
        // Check if user is already assigned to a project
        $currentProject = $this->getUserProject($userId);
        if ($currentProject && $currentProject['Id'] != $projectId) {
            throw new Exception("User is already assigned to another project");
        }
        
        $query = "UPDATE user_details SET ProjectId = ? WHERE Id = ?";
        $result = $this->db->update($query, [$projectId, $userId]);
        
        return $result !== false;
    }
    
    public function removeUserFromProject($userId, $projectId) {
        $query = "UPDATE user_details SET ProjectId = NULL WHERE Id = ? AND ProjectId = ?";
        return $this->db->update($query, [$userId, $projectId]);
    }
    
    public function getUserProject($userId) {
        $query = "SELECT p.* FROM project p 
                  INNER JOIN user_details u ON p.Id = u.ProjectId 
                  WHERE u.Id = ?";
        $result = $this->db->select($query, [$userId]);
        return $result ? $result[0] : false;
    }
    
    public function createProject($projectData, $creatorId) {
        if (!$this->canCreateProjects($creatorId)) {
            throw new Exception("Insufficient permissions to create projects");
        }
        
        $validation = $this->validateProjectData($projectData, false);
        if (!$validation['valid']) {
            throw new Exception("Invalid project data: " . implode(', ', $validation['errors']));
        }
        
        $query = "INSERT INTO project (Project) VALUES (?)";
        $params = [$projectData['projectName']];
        
        $projectId = $this->db->insert($query, $params);
        
        if ($projectId) {
            $this->logUserActivity($creatorId, 'CREATE_PROJECT', "Created project: {$projectData['projectName']}");
            return $projectId;
        }
        
        return false;
    }
    
    public function updateProject($projectId, $projectData, $requesterId) {
        if (!$this->canCreateProjects($requesterId)) {
            throw new Exception("Insufficient permissions to update projects");
        }
        
        $validation = $this->validateProjectData($projectData, true);
        if (!$validation['valid']) {
            throw new Exception("Invalid project data: " . implode(', ', $validation['errors']));
        }
        
        $query = "UPDATE project SET Project = ? WHERE Id = ?";
        $params = [$projectData['projectName'], $projectId];
        
        $result = $this->db->update($query, $params);
        
        if ($result) {
            $this->logUserActivity($requesterId, 'UPDATE_PROJECT', "Updated project: {$projectData['projectName']}");
            return true;
        }
        
        return false;
    }
    
    public function getProjectById($projectId) {
        $query = "SELECT * FROM project WHERE Id = ?";
        $result = $this->db->select($query, [$projectId]);
        return $result ? $result[0] : false;
    }
    
    public function getAllProjects($requesterId) {
        if (!$this->canViewAllProjects($requesterId)) {
            throw new Exception("Insufficient permissions to view all projects");
        }
        
        $query = "SELECT * FROM project ORDER BY Project";
        return $this->db->select($query);
    }
    
    public function canCreateProjects($userId) {
        return $this->isAdmin($userId) || $this->isManager($userId);
    }
    
    public function canViewAllProjects($userId) {
        return $this->isAdmin($userId) || $this->isManager($userId);
    }
    
    public function validateUserData($userData, $isUpdate) {
        $errors = [];
        
        if (!$isUpdate || isset($userData['username'])) {
            if (empty($userData['username']) || strlen($userData['username']) < 3) {
                $errors[] = "Username must be at least 3 characters";
            }
        }
        
        if (!$isUpdate || isset($userData['name'])) {
            if (empty($userData['name'])) {
                $errors[] = "Name is required";
            }
        }
        
        if (!$isUpdate && (empty($userData['password']) || strlen($userData['password']) < 6)) {
            $errors[] = "Password must be at least 6 characters";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public function validateProjectData($projectData, $isUpdate) {
        $errors = [];
        
        if (empty($projectData['projectName']) || strlen($projectData['projectName']) < 3) {
            $errors[] = "Project name must be at least 3 characters";
        }
        
        if (empty($projectData['description'])) {
            $errors[] = "Project description is required";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public function removeUserFromAllAssignments($userId) {
        // Remove from projects
        $this->db->delete("DELETE FROM user_projects WHERE userId = ?", [$userId]);
        
        // Unassign from bugs
        $this->db->update("UPDATE bugs SET assignedToId = NULL WHERE assignedToId = ?", [$userId]);
    }
    
    public function getUserActivityLog($userId) {
        $query = "SELECT * FROM activity_log WHERE userId = ? ORDER BY timestamp DESC";
        return $this->db->select($query, [$userId]);
    }
    
    public function logUserActivity($userId, $action, $description) {
        $query = "INSERT INTO activity_log (userId, action, description, timestamp) VALUES (?, ?, ?, NOW())";
        $this->db->insert($query, [$userId, $action, $description]);
    }
}

?>
