<?php
session_start();

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'database' => 'bugtracker',
    'username' => 'root',
    'password' => ''
];

// Include required files
require_once 'BugTrackerModel.php';
require_once 'BugTrackerController.php';
require_once 'BugTrackerRouter.php';

try {
    // Initialize the application
    $model = new BugTrackerModel($dbConfig['host'], $dbConfig['database'], $dbConfig['username'], $dbConfig['password']);
    $controller = new BugTrackerController($model);
    $router = new BugTrackerRouter($controller);
    
    // Check for existing session
    if (isset($_SESSION['user'])) {
        $router->setCurrentUser($_SESSION['user']);
    }
    
    // Get action and parameters from URL
    $action = $_GET['action'] ?? 'dashboard';
    $params = array_merge($_GET, $_POST);
    unset($params['action']);
    
    // Route the request
    $result = $router->route($action, $params);
    
    // Update session if user is logged in
    if ($router->getCurrentUser()) {
        $_SESSION['user'] = $router->getCurrentUser();
    } elseif ($action === 'logout') {
        session_destroy();
    }
    
    // Render the appropriate view
    $view = $result['view'];
    $data = $result['data'] ?? [];
    
    // Include the view file
    $viewFile = "views/{$view}View.php";
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        // Fallback to login if view doesn't exist
        include 'views/LoginView.php';
    }
    
} catch (Exception $e) {
    // Handle errors gracefully
    error_log("Application error: " . $e->getMessage());
    
    // Show error page or redirect to login
    if (isset($_SESSION['user'])) {
        echo "<h1>Error</h1><p>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='?action=logout'>Logout</a>";
    } else {
        include 'views/LoginView.php';
    }
}
?>

