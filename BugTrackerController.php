<?php

require_once 'BugTrackerModel.php';

class BugTrackerController {
    private $model;
    private $currentUser;
    
    public function __construct($model) {
        $this->model = $model;
    }
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
    }
    
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    public function handleLogin($username, $password) {
        try {
            // Sanitize input
            $username = $this->sanitizeInput($username);
            $password = $this->sanitizeInput($password);
            
            // Validate input
            if (empty($username) || empty($password)) {
                throw new Exception("Username and password are required");
            }
            
            $user = $this->model->loginUser($username, $password);
            
            if ($user) {
                $this->setCurrentUser($user);
                $this->model->logActivity($user['userId'], 'LOGIN', 'User logged in');
                return [
                    'success' => true,
                    'user' => $user,
                    'message' => 'Login successful'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleLogout() {
        if ($this->currentUser) {
            $this->model->logoutUser($this->currentUser['userId']);
            $this->setCurrentUser(null);
        }
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }
    
    public function handleDashboard() {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        return $this->model->getUserDashboard($this->currentUser['userId']);
    }
    
    public function handleCreateBug($bugData) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            // Sanitize and validate input
            $bugData = $this->sanitizeBugData($bugData);
            $this->validateBugData($bugData);
            
            $bugId = $this->model->createBug($bugData, $this->currentUser['userId']);
            
            if ($bugId) {
                return [
                    'success' => true,
                    'bugId' => $bugId,
                    'message' => 'Bug created successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create bug'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleUpdateBug($bugId, $bugData) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            // Sanitize and validate input
            $bugData = $this->sanitizeBugData($bugData);
            $this->validateBugData($bugData);
            
            $result = $this->model->updateBug($bugId, $bugData, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Bug updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update bug'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleDeleteBug($bugId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $result = $this->model->deleteBug($bugId, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Bug deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete bug'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleBugList($projectId = null) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            if ($projectId) {
                $bugs = $this->model->getBugs()->getBugsByProject($projectId, $this->currentUser['userId']);
            } else {
                $bugs = $this->model->getBugsForUser($this->currentUser['userId']);
            }
            
            return [
                'success' => true,
                'bugs' => $bugs
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleBugDetails($bugId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $bug = $this->model->getBugDetails($bugId, $this->currentUser['userId']);
            
            if ($bug) {
                return [
                    'success' => true,
                    'bug' => $bug
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Bug not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleAssignBug($bugId, $userId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $result = $this->model->assignBugToUser($bugId, $userId, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Bug assigned successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to assign bug'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleUpdateBugStatus($bugId, $statusId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $bugData = ['statusId' => $statusId];
            $result = $this->model->updateBug($bugId, $bugData, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Bug status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update bug status'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleCloseBug($bugId, $fixDescription) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $result = $this->model->getBugs()->closeBug($bugId, $fixDescription);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Bug closed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to close bug'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleCreateUser($userData) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('create_user')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            // Sanitize and validate input
            $userData = $this->sanitizeUserData($userData);
            $this->validateUserData($userData);
            
            $userId = $this->model->createUser($userData, $this->currentUser['userId']);
            
            if ($userId) {
                return [
                    'success' => true,
                    'userId' => $userId,
                    'message' => 'User created successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create user'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleUpdateUser($userId, $userData) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            // Sanitize and validate input
            $userData = $this->sanitizeUserData($userData);
            $this->validateUserData($userData);
            
            $result = $this->model->updateUser($userId, $userData, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update user'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function handleDeleteUser($userId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('delete_user')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            $result = $this->model->deleteUser($userId, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete user'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleUserList() {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('view_all_users')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            $users = $this->model->getAllUsers($this->currentUser['userId']);
            return [
                'success' => true,
                'users' => $users
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleUserDetails($userId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $user = $this->model->getUserDetails($userId, $this->currentUser['userId']);
            
            if ($user) {
                return [
                    'success' => true,
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleCreateProject($projectData) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('create_project')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            // Sanitize and validate input
            $projectData = $this->sanitizeProjectData($projectData);
            $this->validateProjectData($projectData);
            
            $projectId = $this->model->createProject($projectData, $this->currentUser['userId']);
            
            if ($projectId) {
                return [
                    'success' => true,
                    'projectId' => $projectId,
                    'message' => 'Project created successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create project'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleUpdateProject($projectId, $projectData) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('update_project')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            // Sanitize and validate input
            $projectData = $this->sanitizeProjectData($projectData);
            $this->validateProjectData($projectData);
            
            $result = $this->model->updateProject($projectId, $projectData, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Project updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update project'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleProjectList() {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $projects = $this->model->getProjectsForUser($this->currentUser['userId']);
            return [
                'success' => true,
                'projects' => $projects
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleProjectDetails($projectId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $project = $this->model->getProjectDetails($projectId, $this->currentUser['userId']);
            
            if ($project) {
                return [
                    'success' => true,
                    'project' => $project
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Project not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleAssignUserToProject($userId, $projectId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('assign_user_to_project')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            $result = $this->model->assignUserToProject($userId, $projectId, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User assigned to project successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to assign user to project'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleRemoveUserFromProject($userId, $projectId) {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('remove_user_from_project')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            $result = $this->model->removeUserFromProject($userId, $projectId, $this->currentUser['userId']);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User removed from project successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to remove user from project'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleSystemStatistics() {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        if (!$this->hasPermission('view_system_statistics')) {
            throw new Exception("Insufficient permissions");
        }
        
        try {
            $stats = $this->model->getSystemStatistics($this->currentUser['userId']);
            return [
                'success' => true,
                'statistics' => $stats
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function handleActivityLog() {
        if (!$this->validateSession()) {
            throw new Exception("Session validation failed");
        }
        
        try {
            $activities = $this->model->getActivityLog($this->currentUser['userId']);
            return [
                'success' => true,
                'activities' => $activities
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function validateSession() {
        return $this->currentUser !== null && $this->model->validateUserPermission($this->currentUser['userId']);
    }
    
    public function hasPermission($action) {
        if (!$this->currentUser) {
            return false;
        }
        
        $userRole = $this->currentUser['roleName'];
        
        switch ($action) {
            case 'create_user':
            case 'delete_user':
            case 'view_all_users':
            case 'view_system_statistics':
                return $userRole === 'Admin';
                
            case 'create_project':
            case 'update_project':
            case 'assign_user_to_project':
            case 'remove_user_from_project':
                return in_array($userRole, ['Admin', 'Manager']);
                
            case 'view_all_bugs':
            case 'update_all_bugs':
                return in_array($userRole, ['Admin', 'Manager']);
                
            default:
                return false;
        }
    }
    
    // Input sanitization methods
    private function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    private function sanitizeBugData($bugData) {
        $sanitized = [];
        foreach ($bugData as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    private function sanitizeUserData($userData) {
        $sanitized = [];
        foreach ($userData as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    private function sanitizeProjectData($projectData) {
        $sanitized = [];
        foreach ($projectData as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    // Input validation methods
    private function validateBugData($bugData) {
        $errors = [];
        
        if (empty($bugData['summary']) || strlen($bugData['summary']) < 3) {
            $errors[] = "Summary must be at least 3 characters";
        }
        
        if (empty($bugData['description']) || strlen($bugData['description']) < 10) {
            $errors[] = "Description must be at least 10 characters";
        }
        
        if (empty($bugData['projectId']) || !is_numeric($bugData['projectId'])) {
            $errors[] = "Valid project ID is required";
        }
        
        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode(', ', $errors));
        }
    }
    
    private function validateUserData($userData) {
        $errors = [];
        
        if (empty($userData['username']) || strlen($userData['username']) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }
        
        if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
        
        if (empty($userData['firstName'])) {
            $errors[] = "First name is required";
        }
        
        if (empty($userData['lastName'])) {
            $errors[] = "Last name is required";
        }
        
        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode(', ', $errors));
        }
    }
    
    private function validateProjectData($projectData) {
        $errors = [];
        
        if (empty($projectData['projectName']) || strlen($projectData['projectName']) < 3) {
            $errors[] = "Project name must be at least 3 characters";
        }
        
        if (empty($projectData['description'])) {
            $errors[] = "Project description is required";
        }
        
        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode(', ', $errors));
        }
    }
}

?>
