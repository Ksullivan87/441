# Bug Tracker Application

A comprehensive bug tracking system built with PHP using the MVC architecture pattern. This application allows users to track bugs, manage projects, and handle user administration based on role-based access control.

## Features

### Core Functionality
- **User Authentication**: Secure login with password hashing
- **Role-Based Access Control**: Admin, Manager, and User roles with different permissions
- **Bug Management**: Create, update, assign, and track bugs
- **Project Management**: Create and manage projects
- **User Administration**: Add, edit, and delete users (Admin only)
- **Activity Logging**: Track all user activities and system changes

### Role Permissions

#### Admin
- Full system access
- User management (create, edit, delete users)
- Project management
- View all bugs across all projects
- System statistics
- Assign users to projects

#### Manager
- Project management
- View all bugs across all projects
- Assign users to projects
- Cannot manage users

#### User
- View bugs only from assigned project
- Create new bugs
- Update only bugs assigned to them
- Cannot view other users' bugs

## Technical Implementation

### Architecture
- **MVC Pattern**: Model-View-Controller separation
- **PDO Database Layer**: Secure database operations with prepared statements
- **Session Management**: Secure session-based authentication
- **Input Validation**: Server-side validation and sanitization
- **Password Security**: PHP password_hash() with PASSWORD_DEFAULT

### Database Schema
The application uses the provided BugTracker.sql schema with the following tables:
- **user_details**: User accounts with role-based access (Id, Username, RoleID, ProjectId, Password, Name)
- **project**: Project information (Id, Project)
- **role**: User roles (Id, Role)
- **bugs**: Bug tracking with status, priority, and assignment
- **bug_status**: Bug statuses (Unassigned, Assigned, Closed)
- **priority**: Priority levels (Low, Medium, High, Urgent)
- **Relationships**: Proper foreign key constraints as defined in the schema

## Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. **Clone or download the project files**

2. **Set up the database**:
   ```sql
   -- Use the provided BugTracker.sql file
   -- Change 'databasename' to your RIT userid in the SQL file
   -- Then run the SQL file to create tables and sample data
   mysql -u username -p < BugTracker.sql
   ```

3. **Configure database connection**:
   Edit `index.php` and update the database configuration:
   ```php
   $dbConfig = [
       'host' => 'localhost',
       'database' => 'bugtracker',
       'username' => 'your_username',
       'password' => 'your_password'
   ];
   ```

4. **Set up web server**:
   - Point document root to the project directory
   - Ensure PHP is enabled
   - Configure URL rewriting if needed

5. **Access the application**:
   - Navigate to your web server URL
   - Use the demo credentials to log in

### Demo Credentials

- **Admin**: username: `admin`, password: `admin123`
- **Manager**: username: `manager`, password: `manager123`  
- **User**: username: `user`, password: `user123`

## File Structure

```
├── index.php                 # Main entry point
├── Database.class.php        # Database abstraction layer
├── Users.class.php          # User management class
├── Bugs.class.php           # Bug management class
├── BugTrackerModel.php      # Model layer
├── BugTrackerController.php # Controller layer
├── BugTrackerRouter.php     # Router layer
├── views/                   # View templates
│   ├── LoginView.php
│   ├── BugTrackerView.php
│   ├── AdminView.php
│   ├── LogView.php
│   └── UnauthorizedView.php
├── setup_database.sql       # Database setup script
└── README.md               # This file
```

## Usage

### For Administrators
1. Log in with admin credentials
2. Access Admin panel to manage users and projects
3. View system statistics
4. Assign users to projects
5. Manage all bugs across the system

### For Managers
1. Log in with manager credentials
2. Create and manage projects
3. View all bugs and assign them to users
4. Cannot manage users (Admin only)

### For Regular Users
1. Log in with user credentials
2. View bugs from assigned project only
3. Create new bugs
4. Update bugs assigned to them

## Security Features

- **Password Hashing**: All passwords are hashed using PHP's password_hash()
- **Input Sanitization**: All user input is sanitized using htmlspecialchars()
- **SQL Injection Prevention**: All database queries use prepared statements
- **Session Security**: Secure session management with proper validation
- **Role-Based Access**: Strict permission checking for all operations

## Requirements Compliance

This implementation meets all project requirements:

✅ **Database**: PDO with prepared statements  
✅ **MVC Architecture**: Proper separation of concerns  
✅ **Role-Based Access**: Admin, Manager, User roles  
✅ **Bug Tracking**: Full CRUD operations with filtering  
✅ **User Management**: Admin-only user administration  
✅ **Project Management**: Manager/Admin project management  
✅ **Input Validation**: Server-side validation and sanitization  
✅ **Session Management**: Secure authentication system  
✅ **Activity Logging**: Comprehensive activity tracking  

## Additional Features

- **Responsive Design**: Modern, mobile-friendly interface
- **Error Handling**: Graceful error handling and user feedback
- **Activity Logging**: Detailed activity tracking for audit trails
- **Filtering**: Bug filtering by project, status, and priority
- **Status Management**: Bug status tracking (Unassigned, Assigned, In Progress, Closed)
- **Priority Levels**: Bug priority management (Low, Medium, High)

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check database credentials in `index.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Session Issues**:
   - Check PHP session configuration
   - Ensure session directory is writable

3. **Permission Errors**:
   - Check file permissions on web server
   - Ensure PHP has proper access to files

### Support

For technical support or questions about the implementation, refer to the code comments and documentation within each file.
