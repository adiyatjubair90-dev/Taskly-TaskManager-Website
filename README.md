# Taskly

A modern, feature-rich task management web application built with PHP, MySQL, and vanilla JavaScript. Taskly helps you organize, track, and manage your tasks efficiently with multiple viewing options and powerful filtering capabilities.
## VIDEO DEMO:
https://github.com/user-attachments/assets/4b382b7a-d0fd-4448-91d9-9270b9d5c638

## Features

### 🔐 User Authentication
- Secure user registration with email validation
- Password hashing for enhanced security
- Session-based authentication
- Real-time email availability checking

### 📋 Task Management
- **Create Tasks** with:
  - Title and description
  - Due dates
  - Custom categories (or choose from predefined: General, School, Work, Personal, Errands)
  - Status tracking (Not started, In progress, Completed)
  - Special grading feature for School tasks

### 🎨 Multiple View Modes
- **Cards View**: Visual card-based layout for easy task browsing
- **Table View**: Comprehensive tabular view with all task details
- **Calendar View**: Monthly calendar showing tasks by due date with AJAX navigation

### 🔍 Advanced Filtering
- Filter by category
- Filter by status
- Real-time task counters (Active, Completed, Total)
- Sidebar navigation with category and status overview

### ✨ Additional Features
- Custom category creation
- Task status updates without page refresh
- Delete tasks functionality
- Responsive design with modern UI
- Password reveal/hide toggle

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL (via MySQLi)
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache (WAMP/XAMPP compatible)

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server (or WAMP/XAMPP)
- Modern web browser

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/Taskly.git
cd Taskly
```

### 2. Database Setup

Create a MySQL database named `taskly`:

```sql
CREATE DATABASE taskly;
```

Then, create the required tables:

```sql
USE taskly;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    category VARCHAR(100) DEFAULT 'General',
    status VARCHAR(50) DEFAULT 'Not started',
    grading VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 3. Configure Database Connection

Edit `db.php` and update the database credentials if needed:

```php
$host = "localhost";
$user = "root";        // Your MySQL username
$pass = "";            // Your MySQL password
$db = "taskly";        // Database name
```

### 4. Deploy to Web Server

**For WAMP:**
- Copy the project folder to `C:\wamp64\www\Taskly\`
- Start WAMP server
- Access via `http://localhost/Taskly/`

**For XAMPP:**
- Copy the project folder to `C:\xampp\htdocs\Taskly\`
- Start Apache and MySQL in XAMPP Control Panel
- Access via `http://localhost/Taskly/`

**For Linux/Mac with Apache:**
- Copy to `/var/www/html/Taskly/` or your web root
- Ensure proper permissions
- Access via `http://localhost/Taskly/`

## Usage

1. **Register a New Account**
   - Navigate to the registration page
   - Enter your email, username, and password (minimum 8 characters)
   - Confirm your password
   - Click "Create account"

2. **Login**
   - Use your registered email and password to sign in
   - You'll be redirected to the task dashboard

3. **Create Tasks**
   - Fill in the task title (required)
   - Optionally add a description, due date, category, and status
   - Click "Add task" to save

4. **Manage Tasks**
   - View tasks in Cards, Table, or Calendar view
   - Filter by category or status using the filter bar
   - Update task status using the dropdown on each task card
   - Delete tasks using the delete button
   - For School tasks, update grading status (Not graded/Graded)

5. **Navigate Calendar**
   - Use the arrow buttons to navigate between months
   - Tasks with due dates appear on their respective days

## Project Structure

```
Taskly/
├── db.php              # Database connection configuration
├── login.html          # Login page
├── login.php           # Login processing
├── login.js            # Login/registration validation
├── login.css           # Authentication pages styling
├── register.html       # Registration page
├── register.php        # Registration processing
├── tasks.php           # Main task dashboard
├── tasks.js            # Task management JavaScript
├── tasks.css           # Dashboard styling
├── logout.php          # Session termination
├── Taskly.png          # Application logo
└── README.md           # This file
```
Flow Chart Design of the entire website:

<img width="683" height="679" alt="Image" src="https://github.com/user-attachments/assets/5e11bb47-c407-4666-9524-3759f7cad67c" />

UML Diagram design of the entire website:

<img width="1352" height="543" alt="Image" src="https://github.com/user-attachments/assets/9bceac29-bb41-4610-9e8a-343849b4f619" />

## Support

If you encounter any issues or have questions, please open an issue on the GitHub repository.

---

**Note**: Make sure to keep your database credentials secure and never commit sensitive information to version control.



