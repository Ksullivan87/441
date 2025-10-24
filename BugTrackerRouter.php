<?php

require_once 'BugTrackerController.php';

class BugTrackerRouter {
    private $controller;
    private $currentUser;
    private $routes;
    
    public function __construct($controller) {
        $this->controller = $controller;
        $this->routes = $this->initializeRoutes();
    }
    
    public function setCurrentUser($user) {
        $this->currentUser = $user;
        $this->controller->setCurrentUser($user);
    }
    
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    public function route($action, $params = []) {
        $this->logRouteAccess($action, $params);
        
        switch ($action) {
            case 'login':
                return $this->handleLogin($params);
                
            case 'logout':
                return $this->handleLogout();
                
            case 'dashboard':
                return $this->handleDashboard();
                
            case 'create_bug':
                return $this->handleCreateBug($params);
                
            case 'update_bug':
                return $this->handleUpdateBug($params);
                
            case 'delete_bug':
                return $this->handleDeleteBug($params);
                
            case 'bug_list':
                return $this->handleBugList($params);
                
            case 'bug_details':
                return $this->handleBugDetails($params);
                
            case 'assign_bug':
                return $this->handleAssignBug($params);
                
            case 'update_bug_status':
                return $this->handleUpdateBugStatus($params);
                
            case 'close_bug':
                return $this->handleCloseBug($params);
                
            case 'create_user':
                return $this->handleCreateUser($params);
                
            case 'update_user':
                return $this->handleUpdateUser($params);
                
            case 'delete_user':
                return $this->handleDeleteUser($params);
                
            case 'user_list':
                return $this->handleUserList();
                
            case 'user_details':
                return $this->handleUserDetails($params);
                
            case 'create_project':
                return $this->handleCreateProject($params);
                
            case 'update_project':
                return $this->handleUpdateProject($params);
                
            case 'project_list':
                return $this->handleProjectList();
                
            case 'project_details':
                return $this->handleProjectDetails($params);
                
            case 'assign_user_to_project':
                return $this->handleAssignUserToProject($params);
                
            case 'remove_user_from_project':
                return $this->handleRemoveUserFromProject($params);
                
            case 'system_statistics':
                return $this->handleSystemStatistics();
                
            case 'activity_log':
                return $this->handleActivityLog();
                
            default:
                return $this->routeToUnauthorized();
        }
    }
    
    public function routeToLogin() {
        return [
            'view' => 'login',
            'data' => []
        ];
    }
    
    public function routeToAdmin() {
        if (!$this->controller->hasPermission('view_all_users')) {
            return $this->routeToUnauthorized();
        }
        
        return [
            'view' => 'admin',
            'data' => $this->getAdminData()
        ];
    }
    
    public function routeToBugTracker($params = []) {
        return [
            'view' => 'bug_tracker',
            'data' => $this->getBugTrackerData($params)
        ];
    }
    
    public function routeToSystemStatistics() {
        if (!$this->controller->hasPermission('view_system_statistics')) {
            return $this->routeToUnauthorized();
        }
        
        return [
            'view' => 'statistics',
            'data' => $this->getSystemStatisticsData()
        ];
    }
    
    public function routeToActivityLog() {
        return [
            'view' => 'activity_log',
            'data' => $this->getActivityLogData()
        ];
    }
    
    public function routeToUnauthorized() {
        return [
            'view' => 'unauthorized',
            'data' => [
                'message' => 'You do not have permission to access this resource.'
            ]
        ];
    }
    
    public function getAvailableRoutes() {
        return array_keys($this->routes);
    }
    
    public function redirect($route, $params = []) {
        $url = $this->buildUrl($route, $params);
        header("Location: $url");
        exit;
    }
    
    public function parseUrl($url) {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';
        
        // Parse query parameters
        parse_str($query, $params);
        
        // Extract action from path
        $pathParts = explode('/', trim($path, '/'));
        $action = $pathParts[0] ?? 'dashboard';
        
        return [
            'action' => $action,
            'params' => $params
        ];
    }
    
    private function initializeRoutes() {
        return [
            'login' => ['method' => 'POST', 'permission' => null],
            'logout' => ['method' => 'GET', 'permission' => null],
            'dashboard' => ['method' => 'GET', 'permission' => null],
            'create_bug' => ['method' => 'POST', 'permission' => null],
            'update_bug' => ['method' => 'POST', 'permission' => null],
            'delete_bug' => ['method' => 'POST', 'permission' => null],
            'bug_list' => ['method' => 'GET', 'permission' => null],
            'bug_details' => ['method' => 'GET', 'permission' => null],
            'assign_bug' => ['method' => 'POST', 'permission' => null],
            'update_bug_status' => ['method' => 'POST', 'permission' => null],
            'close_bug' => ['method' => 'POST', 'permission' => null],
            'create_user' => ['method' => 'POST', 'permission' => 'create_user'],
            'update_user' => ['method' => 'POST', 'permission' => null],
            'delete_user' => ['method' => 'POST', 'permission' => 'delete_user'],
            'user_list' => ['method' => 'GET', 'permission' => 'view_all_users'],
            'user_details' => ['method' => 'GET', 'permission' => null],
            'create_project' => ['method' => 'POST', 'permission' => 'create_project'],
            'update_project' => ['method' => 'POST', 'permission' => 'update_project'],
            'project_list' => ['method' => 'GET', 'permission' => null],
            'project_details' => ['method' => 'GET', 'permission' => null],
            'assign_user_to_project' => ['method' => 'POST', 'permission' => 'assign_user_to_project'],
            'remove_user_from_project' => ['method' => 'POST', 'permission' => 'remove_user_from_project'],
            'system_statistics' => ['method' => 'GET', 'permission' => 'view_system_statistics'],
            'activity_log' => ['method' => 'GET', 'permission' => null]
        ];
    }
    
    private function logRouteAccess($route, $params = []) {
        if ($this->currentUser) {
            $this->controller->getModel()->logActivity(
                $this->currentUser['userId'],
                'ROUTE_ACCESS',
                "Accessed route: $route"
            );
        }
    }
    
    // Route handlers
    private function handleLogin($params) {
        $username = $params['username'] ?? '';
        $password = $params['password'] ?? '';
        
        $result = $this->controller->handleLogin($username, $password);
        
        if ($result['success']) {
            $this->setCurrentUser($result['user']);
            return $this->routeToBugTracker();
        } else {
            return [
                'view' => 'login',
                'data' => ['error' => $result['message']]
            ];
        }
    }
    
    private function handleLogout() {
        $this->controller->handleLogout();
        $this->setCurrentUser(null);
        return $this->routeToLogin();
    }
    
    private function handleDashboard() {
        $dashboard = $this->controller->handleDashboard();
        return [
            'view' => 'dashboard',
            'data' => $dashboard
        ];
    }
    
    private function handleCreateBug($params) {
        $result = $this->controller->handleCreateBug($params);
        
        if ($result['success']) {
            return $this->routeToBugTracker(['message' => $result['message']]);
        } else {
            return $this->routeToBugTracker(['error' => $result['message']]);
        }
    }
    
    private function handleUpdateBug($params) {
        $bugId = $params['bugId'] ?? null;
        $bugData = $params;
        unset($bugData['bugId']);
        
        $result = $this->controller->handleUpdateBug($bugId, $bugData);
        
        if ($result['success']) {
            return $this->routeToBugTracker(['message' => $result['message']]);
        } else {
            return $this->routeToBugTracker(['error' => $result['message']]);
        }
    }
    
    private function handleDeleteBug($params) {
        $bugId = $params['bugId'] ?? null;
        
        $result = $this->controller->handleDeleteBug($bugId);
        
        if ($result['success']) {
            return $this->routeToBugTracker(['message' => $result['message']]);
        } else {
            return $this->routeToBugTracker(['error' => $result['message']]);
        }
    }
    
    private function handleBugList($params) {
        $projectId = $params['projectId'] ?? null;
        $result = $this->controller->handleBugList($projectId);
        
        return [
            'view' => 'bug_list',
            'data' => $result
        ];
    }
    
    private function handleBugDetails($params) {
        $bugId = $params['bugId'] ?? null;
        $result = $this->controller->handleBugDetails($bugId);
        
        return [
            'view' => 'bug_details',
            'data' => $result
        ];
    }
    
    private function handleAssignBug($params) {
        $bugId = $params['bugId'] ?? null;
        $userId = $params['userId'] ?? null;
        
        $result = $this->controller->handleAssignBug($bugId, $userId);
        
        if ($result['success']) {
            return $this->routeToBugTracker(['message' => $result['message']]);
        } else {
            return $this->routeToBugTracker(['error' => $result['message']]);
        }
    }
    
    private function handleUpdateBugStatus($params) {
        $bugId = $params['bugId'] ?? null;
        $statusId = $params['statusId'] ?? null;
        
        $result = $this->controller->handleUpdateBugStatus($bugId, $statusId);
        
        if ($result['success']) {
            return $this->routeToBugTracker(['message' => $result['message']]);
        } else {
            return $this->routeToBugTracker(['error' => $result['message']]);
        }
    }
    
    private function handleCloseBug($params) {
        $bugId = $params['bugId'] ?? null;
        $fixDescription = $params['fixDescription'] ?? '';
        
        $result = $this->controller->handleCloseBug($bugId, $fixDescription);
        
        if ($result['success']) {
            return $this->routeToBugTracker(['message' => $result['message']]);
        } else {
            return $this->routeToBugTracker(['error' => $result['message']]);
        }
    }
    
    private function handleCreateUser($params) {
        $result = $this->controller->handleCreateUser($params);
        
        if ($result['success']) {
            return $this->routeToAdmin(['message' => $result['message']]);
        } else {
            return $this->routeToAdmin(['error' => $result['message']]);
        }
    }
    
    private function handleUpdateUser($params) {
        $userId = $params['userId'] ?? null;
        $userData = $params;
        unset($userData['userId']);
        
        $result = $this->controller->handleUpdateUser($userId, $userData);
        
        if ($result['success']) {
            return $this->routeToAdmin(['message' => $result['message']]);
        } else {
            return $this->routeToAdmin(['error' => $result['message']]);
        }
    }
    
    private function handleDeleteUser($params) {
        $userId = $params['userId'] ?? null;
        
        $result = $this->controller->handleDeleteUser($userId);
        
        if ($result['success']) {
            return $this->routeToAdmin(['message' => $result['message']]);
        } else {
            return $this->routeToAdmin(['error' => $result['message']]);
        }
    }
    
    private function handleUserList() {
        $result = $this->controller->handleUserList();
        
        return [
            'view' => 'user_list',
            'data' => $result
        ];
    }
    
    private function handleUserDetails($params) {
        $userId = $params['userId'] ?? null;
        $result = $this->controller->handleUserDetails($userId);
        
        return [
            'view' => 'user_details',
            'data' => $result
        ];
    }
    
    private function handleCreateProject($params) {
        $result = $this->controller->handleCreateProject($params);
        
        if ($result['success']) {
            return $this->routeToAdmin(['message' => $result['message']]);
        } else {
            return $this->routeToAdmin(['error' => $result['message']]);
        }
    }
    
    private function handleUpdateProject($params) {
        $projectId = $params['projectId'] ?? null;
        $projectData = $params;
        unset($projectData['projectId']);
        
        $result = $this->controller->handleUpdateProject($projectId, $projectData);
        
        if ($result['success']) {
            return $this->routeToAdmin(['message' => $result['message']]);
        } else {
            return $this->routeToAdmin(['error' => $result['message']]);
        }
    }
    
    private function handleProjectList() {
        $result = $this->controller->handleProjectList();
        
        return [
            'view' => 'project_list',
            'data' => $result
        ];
    }
    
    private function handleProjectDetails($params) {
        $projectId = $params['projectId'] ?? null;
        $result = $this->controller->handleProjectDetails($projectId);
        
        return [
            'view' => 'project_details',
            'data' => $result
        ];
    }
    
    private function handleAssignUserToProject($params) {
        $userId = $params['userId'] ?? null;
        $projectId = $params['projectId'] ?? null;
        
        $result = $this->controller->handleAssignUserToProject($userId, $projectId);
        
        if ($result['success']) {
            return $this->routeToAdmin(['message' => $result['message']]);
        } else {
            return $this->routeToAdmin(['error' => $result['message']]);
        }
    }
    
    private function handleRemoveUserFromProject($params) {
        $userId = $params['userId'] ?? null;
        $projectId = $params['projectId'] ?? null;
        
        $result = $this->controller->handleRemoveUserFromProject($userId, $projectId);
        
        if ($result['success']) {
            return $this->routeToAdmin(['message' => $result['message']]);
        } else {
            return $this->routeToAdmin(['error' => $result['message']]);
        }
    }
    
    private function handleSystemStatistics() {
        $result = $this->controller->handleSystemStatistics();
        
        return [
            'view' => 'system_statistics',
            'data' => $result
        ];
    }
    
    private function handleActivityLog() {
        $result = $this->controller->handleActivityLog();
        
        return [
            'view' => 'activity_log',
            'data' => $result
        ];
    }
    
    // Data getters
    private function getAdminData() {
        $users = $this->controller->handleUserList();
        $projects = $this->controller->handleProjectList();
        
        return [
            'users' => $users['success'] ? $users['users'] : [],
            'projects' => $projects['success'] ? $projects['projects'] : []
        ];
    }
    
    private function getBugTrackerData($params) {
        $bugs = $this->controller->handleBugList($params['projectId'] ?? null);
        $projects = $this->controller->handleProjectList();
        
        return [
            'bugs' => $bugs['success'] ? $bugs['bugs'] : [],
            'projects' => $projects['success'] ? $projects['projects'] : []
        ];
    }
    
    private function getSystemStatisticsData() {
        $result = $this->controller->handleSystemStatistics();
        
        return [
            'statistics' => $result['success'] ? $result['statistics'] : []
        ];
    }
    
    private function getActivityLogData() {
        $result = $this->controller->handleActivityLog();
        
        return [
            'activities' => $result['success'] ? $result['activities'] : []
        ];
    }
    
    private function buildUrl($route, $params = []) {
        $url = "?action=$route";
        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }
        return $url;
    }
}

?>
