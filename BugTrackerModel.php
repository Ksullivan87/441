<?php

require_once 'Database.class.php';
require_once 'Bugs.class.php';
require_once 'Users.class.php';

class BugTrackerModel extends Database {
    private $bugs;
    private $users;
    
    public function __construct($host, $database, $username, $password) {
        parent::__construct($host, $database, $username, $password);
        $this->initialize();
    }
    
    public function initialize() {
        $this->bugs = new Bugs($this);
        $this->users = new Users($this);
    }
    
    public function getBugs() {
        return $this->bugs;
    }
    
    public function getUsers() {
        return $this->users;
    }
    
    public function loginUser($username, $password) {
        return $this->users->authenticate($username, $password);
    }
    
    public function logoutUser($userId) {
        // Log logout activity
        $this->users->logUserActivity($userId, 'LOGOUT', 'User logged out');
        return true;
    }
    
    public function createBug($bugData, $userId) {
        // Add owner and updatedBy to bug data
        $bugData['ownerId'] = $userId;
        $bugData['updatedBy'] = $userId;
        
        return $this->bugs->createBug($bugData);
    }
    
    public function updateBug($bugId, $bugData, $userId) {
        if (!$this->bugs->canUserUpdateBug($userId, $bugId)) {
            throw new Exception("Insufficient permissions to update bug", 401);
        }
        
        $bugData['updatedBy'] = $userId;
        return $this->bugs->updateBug($bugId, $bugData);
    }
    
    public function deleteBug($bugId, $userId) {
        if (!$this->bugs->canUserUpdateBug($userId, $bugId)) {
            throw new Exception("Insufficient permissions to delete bug", 401);
        }
        
        return $this->bugs->deleteBug($bugId);
    }
    
    public function getBugsForUser($userId) {
        $userRole = $this->users->getUserRole($userId);
        
        if (in_array($userRole, ['Admin', 'Manager'])) {
            return $this->bugs->getAllBugs($userId);
        } else {
            // Regular users can only see bugs from their assigned project
            $userProject = $this->users->getUserProject($userId);
            if ($userProject) {
                return $this->bugs->getBugsByProject($userProject['projectId'], $userId);
            }
            return [];
        }
    }
    
    public function createUser($userData, $adminId) {
        return $this->users->createUser($userData, $adminId);
    }
    
    public function updateUser($userId, $userData, $requesterId) {
        return $this->users->updateUser($userId, $userData, $requesterId);
    }
    
    public function deleteUser($userId, $adminId) {
        return $this->users->deleteUser($userId, $adminId);
    }
    
    public function createProject($projectData, $creatorId) {
        return $this->users->createProject($projectData, $creatorId);
    }
    
    public function updateProject($projectId, $projectData, $requesterId) {
        return $this->users->updateProject($projectId, $projectData, $requesterId);
    }
    
    public function getProjectsForUser($userId) {
        $userRole = $this->users->getUserRole($userId);
        
        if (in_array($userRole, ['Admin', 'Manager'])) {
            return $this->users->getAllProjects($userId);
        } else {
            $userProject = $this->users->getUserProject($userId);
            return $userProject ? [$userProject] : [];
        }
    }
    
    public function getAllUsers($adminId) {
        return $this->users->getAllUsers($adminId);
    }
    
    public function assignUserToProject($userId, $projectId, $assignerId) {
        if (!$this->users->isAdmin($assignerId) && !$this->users->isManager($assignerId)) {
            throw new Exception("Insufficient permissions to assign users to projects", 401);
        }
        
        return $this->users->assignUserToProject($userId, $projectId);
    }
    
    public function removeUserFromProject($userId, $projectId, $removerId) {
        if (!$this->users->isAdmin($removerId) && !$this->users->isManager($removerId)) {
            throw new Exception("Insufficient permissions to remove users from projects", 401);
        }
        
        return $this->users->removeUserFromProject($userId, $projectId);
    }
    
    public function assignBugToUser($bugId, $userId, $assignerId) {
        if (!$this->bugs->canUserUpdateBug($assignerId, $bugId)) {
            throw new Exception("Insufficient permissions to assign bug", 401);
        }
        
        return $this->bugs->assignBug($bugId, $userId);
    }
    
    public function getUserDashboard($userId) {
        $user = $this->users->getUserById($userId);
        $userRole = $user['roleName'];
        
        $dashboard = [
            'user' => $user,
            'role' => $userRole,
            'bugs' => [],
            'projects' => [],
            'statistics' => []
        ];
        
        // Get bugs based on role
        if (in_array($userRole, ['Admin', 'Manager'])) {
            $dashboard['bugs'] = $this->bugs->getAllBugs($userId);
            $dashboard['projects'] = $this->users->getAllProjects($userId);
        } else {
            $userProject = $this->users->getUserProject($userId);
            if ($userProject) {
                $dashboard['bugs'] = $this->bugs->getBugsByProject($userProject['projectId'], $userId);
                $dashboard['projects'] = [$userProject];
            }
        }
        
        $dashboard['statistics'] = $this->getUserStatistics($userId);
        
        return $dashboard;
    }
    
    public function getSystemStatistics($adminId) {
        if (!$this->users->isAdmin($adminId)) {
            throw new Exception("Only administrators can view system statistics", 401);
        }
        
        $stats = [];
        
        $stats['totalUsers'] = count($this->users->getAllUsers($adminId));
        
        $stats['totalProjects'] = count($this->users->getAllProjects($adminId));
        
        $stats['totalBugs'] = count($this->bugs->getAllBugs($adminId));
        
        $stats['openBugs'] = count($this->bugs->getAllOpenBugs($adminId));
        
        $overdueQuery = "SELECT COUNT(*) as count FROM bugs WHERE targetDate < CURDATE() AND statusId != 4";
        $overdueResult = $this->select($overdueQuery);
        $stats['overdueBugs'] = $overdueResult ? $overdueResult[0]['count'] : 0;
        
        return $stats;
    }
    
    public function getBugDetails($bugId, $userId) {
        $bug = $this->bugs->getBugById($bugId);
        
        if (!$bug) {
            return false;
        }
        
        if (!$this->bugs->canUserViewProjectBugs($userId, $bug['projectId'])) {
            throw new Exception("Insufficient permissions to view bug details", 401);
        }
        
        return $bug;
    }
    
    public function getUserDetails($userId, $requesterId) {
        if (!$this->users->isAdmin($requesterId) && $userId != $requesterId) {
            throw new Exception("Insufficient permissions to view user details", 401);
        }
        
        return $this->users->getUserById($userId);
    }
    
    public function getProjectDetails($projectId, $requesterId) {
        if (!$this->users->canViewAllProjects($requesterId)) {
            $userProject = $this->users->getUserProject($requesterId);
            if (!$userProject || $userProject['projectId'] != $projectId) {
                throw new Exception("Insufficient permissions to view project details", 401);
            }
        }
        
        return $this->users->getProjectById($projectId);
    }
    
    public function validateUserPermission($userId) {
        return $this->users->getUserById($userId) !== false;
    }
    
    public function logActivity($userId, $action, $description) {
        return $this->users->logUserActivity($userId, $action, $description);
    }
    
    public function getActivityLog($userId) {
        return $this->users->getUserActivityLog($userId);
    }
    
    public function close() {
        $this->disconnect();
    }
    
    private function getUserStatistics($userId) {
        $userRole = $this->users->getUserRole($userId);
        $stats = [];
        
        if (in_array($userRole, ['Admin', 'Manager'])) {
            $stats['totalBugs'] = count($this->bugs->getAllBugs($userId));
            $stats['openBugs'] = count($this->bugs->getAllOpenBugs($userId));
            $stats['unassignedBugs'] = count($this->bugs->getUnassignedBugs($userId));
        } else {
            $userProject = $this->users->getUserProject($userId);
            if ($userProject) {
                $stats['totalBugs'] = count($this->bugs->getBugsByProject($userProject['projectId'], $userId));
                $stats['openBugs'] = count($this->bugs->getOpenBugsByProject($userProject['projectId'], $userId));
                $stats['overdueBugs'] = count($this->bugs->getOverdueBugsByProject($userProject['projectId'], $userId));
            }
        }
        
        return $stats;
    }
}

?>
