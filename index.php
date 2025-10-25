<?php
session_start();

$dbConfig = [
    'host' => 'localhost',
    'database' => 'bugtracker',
    'username' => 'root',
    'password' => ''
];

require_once 'BugTrackerModel.php';
require_once 'BugTrackerController.php';
require_once 'BugTrackerRouter.php';

try {
    $model = new BugTrackerModel($dbConfig['host'], $dbConfig['database'], $dbConfig['username'], $dbConfig['password']);
    $controller = new BugTrackerController($model);
    $router = new BugTrackerRouter($controller);
    
    // Session Checker
    if (isset($_SESSION['user'])) {
        $router->setCurrentUser($_SESSION['user']);
    }
    
    $action = $_GET['action'] ?? 'dashboard';
    $params = array_merge($_GET, $_POST);
    unset($params['action']);
    
    $result = $router->route($action, $params);
    
    if ($router->getCurrentUser()) {
        $_SESSION['user'] = $router->getCurrentUser();
    } elseif ($action === 'logout') {
        session_destroy();
    }
    
    $view = $result['view'];
    $data = $result['data'] ?? [];
    
    // Get the right view for client side
    $viewFile = "views/{$view}View.php";
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        // Catch if they try to get a view that doessnt exist
        include 'views/LoginView.php';
    }
    
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    
    if (isset($_SESSION['user'])) {
        echo "<h1>Error</h1><p>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='?action=logout'>Logout</a>";
    } else {
        include 'views/LoginView.php';
    }
}
?>

