<?php

class Bugs {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function createBug($bugData) {
        // Validate required fields
        $validation = $this->validateBugData($bugData, false);
        if (!$validation['valid']) {
            throw new Exception("Invalid bug data: " . implode(', ', $validation['errors']));
        }
        
        $query = "INSERT INTO bugs (summary, description, ownerId, projectId, 
                  assignedToId, statusId, priorityId, targetDate, dateRaised) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $bugData['summary'],
            $bugData['description'],
            $bugData['ownerId'],
            $bugData['projectId'],
            $bugData['assignedToId'] ?? null,
            $bugData['statusId'] ?? 1, // Default to 'unassigned'
            $bugData['priorityId'] ?? 2, // Default to 'medium'
            $bugData['targetDate'] ?? null,
            $bugData['dateRaised'] ?? date('Y-m-d H:i:s')
        ];
        
        $bugId = $this->db->insert($query, $params);
        
        if ($bugId) {
            $this->logBugActivity($bugId, $bugData['ownerId'], 'CREATE_BUG', "Bug created");
            return $bugId;
        }
        
        return false;
    }
    
    public function updateBug($bugId, $bugData) {
        // Validate bug data
        $validation = $this->validateBugData($bugData, true);
        if (!$validation['valid']) {
            throw new Exception("Invalid bug data: " . implode(', ', $validation['errors']));
        }
        
        $query = "UPDATE bugs SET summary = ?, description = ?, assignedToId = ?, 
                  statusId = ?, priorityId = ?, targetDate = ? WHERE bugId = ?";
        
        $params = [
            $bugData['summary'],
            $bugData['description'],
            $bugData['assignedToId'] ?? null,
            $bugData['statusId'],
            $bugData['priorityId'],
            $bugData['targetDate'] ?? null,
            $bugId
        ];
        
        $result = $this->db->update($query, $params);
        
        if ($result) {
            $this->logBugActivity($bugId, $bugData['updatedBy'], 'UPDATE_BUG', "Bug updated");
            return true;
        }
        
        return false;
    }
    
    public function deleteBug($bugId) {
        // First delete related activity logs
        $this->db->delete("DELETE FROM bug_activity WHERE bugId = ?", [$bugId]);
        
        $query = "DELETE FROM bugs WHERE bugId = ?";
        return $this->db->delete($query, [$bugId]);
    }
    
    public function getBugById($bugId) {
        $query = "SELECT b.*, p.Project as projectName, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName,
                         u2.Name as assignedName
                  FROM bugs b
                  LEFT JOIN project p ON b.projectId = p.Id
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  LEFT JOIN user_details u2 ON b.assignedToId = u2.Id
                  WHERE b.id = ?";
        
        $result = $this->db->select($query, [$bugId]);
        return $result ? $result[0] : false;
    }
    
    public function getBugsByProject($projectId, $userId) {
        if (!$this->canUserViewProjectBugs($userId, $projectId)) {
            throw new Exception("Insufficient permissions to view project bugs");
        }
        
        $query = "SELECT b.*, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName,
                         u2.Name as assignedName
                  FROM bugs b
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  LEFT JOIN user_details u2 ON b.assignedToId = u2.Id
                  WHERE b.projectId = ?
                  ORDER BY b.dateRaised DESC";
        
        return $this->db->select($query, [$projectId]);
    }
    
    public function getOpenBugsByProject($projectId, $userId) {
        if (!$this->canUserViewProjectBugs($userId, $projectId)) {
            throw new Exception("Insufficient permissions to view project bugs");
        }
        
        $query = "SELECT b.*, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName,
                         u2.Name as assignedName
                  FROM bugs b
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  LEFT JOIN user_details u2 ON b.assignedToId = u2.Id
                  WHERE b.projectId = ? AND b.statusId != 3
                  ORDER BY b.dateRaised DESC";
        
        return $this->db->select($query, [$projectId]);
    }
    
    public function getOverdueBugsByProject($projectId, $userId) {
        if (!$this->canUserViewProjectBugs($userId, $projectId)) {
            throw new Exception("Insufficient permissions to view project bugs");
        }
        
        $query = "SELECT b.*, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName,
                         u2.Name as assignedName
                  FROM bugs b
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  LEFT JOIN user_details u2 ON b.assignedToId = u2.Id
                  WHERE b.projectId = ? AND b.statusId != 3 AND b.targetDate < NOW()
                  ORDER BY b.targetDate ASC";
        
        return $this->db->select($query, [$projectId]);
    }
    
    public function getBugsByAssignedUser($userId) {
        $query = "SELECT b.*, p.Project as projectName, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName
                  FROM bugs b
                  LEFT JOIN project p ON b.projectId = p.Id
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  WHERE b.assignedToId = ?
                  ORDER BY b.dateRaised DESC";
        
        return $this->db->select($query, [$userId]);
    }
    
    public function getUnassignedBugs($userId) {
        // Only managers and admins can view unassigned bugs
        $userRole = $this->getUserRole($userId);
        if (!in_array($userRole, ['Admin', 'Manager'])) {
            throw new Exception("Insufficient permissions to view unassigned bugs");
        }
        
        $query = "SELECT b.*, p.Project as projectName, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName
                  FROM bugs b
                  LEFT JOIN project p ON b.projectId = p.Id
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  WHERE b.assignedToId IS NULL
                  ORDER BY b.dateRaised DESC";
        
        return $this->db->select($query);
    }
    
    public function getAllBugs($userId) {
        $userRole = $this->getUserRole($userId);
        if (!in_array($userRole, ['Admin', 'Manager'])) {
            throw new Exception("Insufficient permissions to view all bugs");
        }
        
        $query = "SELECT b.*, p.Project as projectName, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName,
                         u2.Name as assignedName
                  FROM bugs b
                  LEFT JOIN project p ON b.projectId = p.Id
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  LEFT JOIN user_details u2 ON b.assignedToId = u2.Id
                  ORDER BY b.dateRaised DESC";
        
        return $this->db->select($query);
    }
    
    public function getAllOpenBugs($userId) {
        $userRole = $this->getUserRole($userId);
        if (!in_array($userRole, ['Admin', 'Manager'])) {
            throw new Exception("Insufficient permissions to view all bugs");
        }
        
        $query = "SELECT b.*, p.Project as projectName, s.Status as statusName, pr.Priority as priorityName,
                         u1.Name as ownerName,
                         u2.Name as assignedName
                  FROM bugs b
                  LEFT JOIN project p ON b.projectId = p.Id
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u1 ON b.ownerId = u1.Id
                  LEFT JOIN user_details u2 ON b.assignedToId = u2.Id
                  WHERE b.statusId != 3
                  ORDER BY b.dateRaised DESC";
        
        return $this->db->select($query);
    }
    
    public function assignBug($bugId, $userId) {
        if (!$this->canUserUpdateBug($userId, $bugId)) {
            throw new Exception("Insufficient permissions to assign bug");
        }
        
        $query = "UPDATE bugs SET assignedToId = ? WHERE id = ?";
        $result = $this->db->update($query, [$userId, $bugId]);
        
        if ($result) {
            $this->logBugActivity($bugId, $userId, 'ASSIGN_BUG', "Bug assigned to user");
            return true;
        }
        
        return false;
    }
    
    public function unassignBug($bugId) {
        $query = "UPDATE bugs SET assignedToId = NULL WHERE id = ?";
        $result = $this->db->update($query, [$bugId]);
        
        if ($result) {
            $this->logBugActivity($bugId, null, 'UNASSIGN_BUG', "Bug unassigned");
            return true;
        }
        
        return false;
    }
    
    public function closeBug($bugId, $fixDescription) {
        $query = "UPDATE bugs SET statusId = 3, dateClosed = NOW(), fixDescription = ? WHERE id = ?";
        $result = $this->db->update($query, [$fixDescription, $bugId]);
        
        if ($result) {
            $this->logBugActivity($bugId, null, 'CLOSE_BUG', "Bug closed: $fixDescription");
            return true;
        }
        
        return false;
    }
    
    public function canUserViewProjectBugs($userId, $projectId) {
        $userRole = $this->getUserRole($userId);
        
        // Admins and managers can view all project bugs
        if (in_array($userRole, ['Admin', 'Manager'])) {
            return true;
        }
        
        // Regular users can only view bugs from their assigned project
        $userProject = $this->getUserProject($userId);
        return $userProject && $userProject['projectId'] == $projectId;
    }
    
    public function canUserUpdateBug($userId, $bugId) {
        $userRole = $this->getUserRole($userId);
        
        // Admins and managers can update all bugs
        if (in_array($userRole, ['Admin', 'Manager'])) {
            return true;
        }
        
        // Regular users can only update bugs assigned to them
        $bug = $this->getBugById($bugId);
        return $bug && $bug['assignedToId'] == $userId;
    }
    
    public function getBugsByOwner($ownerId) {
        $query = "SELECT b.*, p.Project as projectName, s.Status as statusName, pr.Priority as priorityName,
                         u2.Name as assignedName
                  FROM bugs b
                  LEFT JOIN project p ON b.projectId = p.Id
                  LEFT JOIN bug_status s ON b.statusId = s.Id
                  LEFT JOIN priority pr ON b.priorityId = pr.Id
                  LEFT JOIN user_details u2 ON b.assignedToId = u2.Id
                  WHERE b.ownerId = ?
                  ORDER BY b.dateRaised DESC";
        
        return $this->db->select($query, [$ownerId]);
    }
    
    private function getUserRole($userId) {
        $query = "SELECT r.Role FROM user_details u 
                  LEFT JOIN role r ON u.RoleID = r.Id 
                  WHERE u.Id = ?";
        $result = $this->db->select($query, [$userId]);
        return $result ? $result[0]['Role'] : false;
    }
    
    private function getUserProject($userId) {
        $query = "SELECT p.* FROM project p 
                  INNER JOIN user_details u ON p.Id = u.ProjectId 
                  WHERE u.Id = ?";
        $result = $this->db->select($query, [$userId]);
        return $result ? $result[0] : false;
    }
    
    private function validateBugData($bugData, $isUpdate) {
        $errors = [];
        
        if (!$isUpdate || isset($bugData['summary'])) {
            if (empty($bugData['summary']) || strlen($bugData['summary']) < 3) {
                $errors[] = "Summary must be at least 3 characters";
            }
        }
        
        if (!$isUpdate || isset($bugData['description'])) {
            if (empty($bugData['description']) || strlen($bugData['description']) < 10) {
                $errors[] = "Description must be at least 10 characters";
            }
        }
        
        if (!$isUpdate && (empty($bugData['ownerId']) || !is_numeric($bugData['ownerId']))) {
            $errors[] = "Valid owner ID is required";
        }
        
        if (!$isUpdate && (empty($bugData['dateRaised']) || !strtotime($bugData['dateRaised']))) {
            $errors[] = "Valid date raised is required";
        }
        
        if (!$isUpdate && (empty($bugData['projectId']) || !is_numeric($bugData['projectId']))) {
            $errors[] = "Valid project ID is required";
        }
        
        if (isset($bugData['targetDate']) && !empty($bugData['targetDate'])) {
            if (!strtotime($bugData['targetDate']) || strtotime($bugData['targetDate']) <= time()) {
                $errors[] = "Target date must be a valid future date";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function logBugActivity($bugId, $userId, $action, $description) {
        $query = "INSERT INTO bug_activity (bugId, userId, action, description, timestamp) 
                  VALUES (?, ?, ?, ?, NOW())";
        $this->db->insert($query, [$bugId, $userId, $action, $description]);
    }
}

?>
