<?php
session_start();
require_once 'db.php'; // Connects to database


if (!isset($_SESSION['user_id'])) { //Checks if user is logged in
    header("Location: login.html");
    exit;
}

//Declares user info
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Selected filters from query string (GET parameters)
$selectedCategory = $_GET['category'] ?? 'all';
$selectedStatus = $_GET['status_filter'] ?? 'all';

// Helper to redirect with current filters (AI SUGGESTED)
function redirectWithFilters(string $category, string $status): void {
    header(
        "Location: tasks.php?category=" . urlencode($category) .
        "&status_filter=" . urlencode($status)
    );
    exit;
}

//Beginning of actual code and checks if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    //sets a case for different actions
    switch ($action) {
        //Adding case
        case 'add':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $dueDate = $_POST['due_date'] ?? null;
            $category = trim($_POST['category'] ?? '');
            $customCategory = trim($_POST['custom_category'] ?? '');
            $statusTag = trim($_POST['status_tag'] ?? 'Not started');

            // Custom categories
            if ($customCategory !== '') {
                $category = $customCategory;
            }
            if ($category === '') {
                $category = 'General';
            }

            // Sets default status
            if ($statusTag === '') {
                $statusTag = 'Not started';
            }

            // For school tasks set default grading
            $grading = null;
            if (strcasecmp($category, 'School') === 0) {
                $grading = 'Not graded';
            }

            if ($title !== '') { //Takes values and inserts into database if title not empty
                $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, due_date, category, status, grading) VALUES (?, ?, ?, ?, ?, ?, ?)"); //Prepares SQL Statement for adding database
                if ($stmt) {
                    $stmt->bind_param("issssss",$userId,$title, $description, $dueDate, $category, $statusTag, $grading); //Inserts values into placeholders
                    $stmt->execute();
                    $stmt->close();
                }
            }

            redirectWithFilters($selectedCategory, 'all');
            break;
        
        //Delete case
        case 'delete': 
            $taskId = (int)($_POST['task_id'] ?? 0); //Uses database ID to delete tasks
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?"); //Prepares SQL statement to delete task
            if ($stmt) {
                $stmt->bind_param("ii", $taskId, $userId); //Inserts task ID and user ID into placeholders
                $stmt->execute();
                $stmt->close();
            }
            break;

        //Changing status tag case
        case 'change_status_tag':
            $taskId    = (int)($_POST['task_id'] ?? 0); //Uses database ID to change status
            $statusTag = trim($_POST['new_status_tag'] ?? '');

            if ($statusTag !== '') { //If user selected a status
                $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?"); //Prepares SQL statement to update status
                if ($stmt) {
                    $stmt->bind_param("sii", $statusTag, $taskId, $userId); //Inserts status, task ID, and user ID into placeholders
                    $stmt->execute();
                    $stmt->close();
                }
            }
            break;

        //Changing grading case
        case 'change_grading':
            $taskId  = (int)($_POST['task_id'] ?? 0); //Uses database to get task ID
            $grading = trim($_POST['new_grading'] ?? ''); //Uses database to get new grading value

            if ($grading !== '') { //If user selected a grading value
                $stmt = $conn->prepare("UPDATE tasks SET grading = ? WHERE id = ? AND user_id = ?"); //Prepares SQL statement to update grading
                if ($stmt) {
                    $stmt->bind_param("sii", $grading, $taskId, $userId);  //Inserts grading, task ID, and user ID into placeholders
                    $stmt->execute();
                    $stmt->close();
                }
            }
            break;
    }
}

$catStmt = $conn->prepare("SELECT DISTINCT category FROM tasks WHERE user_id = ? ORDER BY category"); //Fetches all categories for sidebar
$catStmt->bind_param("i", $userId);
$catStmt->execute();
$catResult = $catStmt->get_result();
$allCategories = [];
while ($row = $catResult->fetch_assoc()) { //Builds array of categories
    if (!empty($row['category'])) {
        $allCategories[] = $row['category'];
    }
}
$catStmt->close();

// Build query for tasks with optional filters (category + status)
$query = "SELECT id, title, description, status, due_date, created_at, category, grading FROM tasks WHERE user_id = ?"; 
$params = [$userId];
$types = "i";

if ($selectedCategory !== 'all') {
    $query .= " AND category = ?"; // Adds category filter
    $params[] = $selectedCategory;
    $types .= "s";
}
if ($selectedStatus !== 'all') {
    $query    .= " AND status = ?"; //Adds status filter
    $params[]  = $selectedStatus;
    $types    .= "s";
}

$query .= " ORDER BY status = 'Completed', due_date IS NULL, due_date, created_at DESC"; //Sets query to order by status, due date, and creation date

$stmt = $conn->prepare($query); //Prepares SQL statement
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$tasks  = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Active and Completed task counters
$activeCount    = 0;
$completedCount = 0;
foreach ($tasks as $t) {
    if ($t['status'] === 'Completed') { //Checks for status to increment counters
        $completedCount++;
    } else {
        $activeCount++;
    }
}

// Organize tasks by due date for calendar view using associative array
$tasksByDate = [];
foreach ($tasks as $t) {
    if (!empty($t['due_date'])) {
        $tasksByDate[$t['due_date']][] = $t; //Adds task to array under its due date
    }
}

//Calendar setup
$calendarYear = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y'); 
$calendarMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

if ($calendarMonth < 1)  { //Goes to previous year 
    $calendarMonth = 12; 
    $calendarYear--; 
}

if ($calendarMonth > 12) { //Goes to next year
    $calendarMonth = 1;  
    $calendarYear++; 
}

$firstDay = new DateTime(sprintf('%04d-%02d-01', $calendarYear, $calendarMonth));
$daysInMonth = (int)$firstDay->format('t'); // total days in a month
$startWeekday = (int)$firstDay->format('w'); // sun = 0, mon = 1 ... sat = 6

$calendarLabel = $firstDay->format('F Y'); 

//Going to previous year and month
$prevYear  = $calendarYear;
$prevMonth = $calendarMonth - 1;
if ($prevMonth < 1) { //Goes to previous year
    $prevMonth = 12; 
    $prevYear--; 
}

//Going to next year and month
$nextYear  = $calendarYear;
$nextMonth = $calendarMonth + 1; 
if ($nextMonth > 12) { //GOes to next year
    $nextMonth = 1; 
    $nextYear++; 
}

//Renders Calendar
function renderCalendar(int $calendarYear,int $calendarMonth,int $prevYear,int $prevMonth,int $nextYear,
    int $nextMonth,string $calendarLabel,int $startWeekday,int $daysInMonth,array $tasksByDate) {
    ?>
    <div class="calendar-header"> <!-- Uses HTML to render calendar -->
        <div class="calendar-header-left">
            <button type="button"
                    class="calendar-nav-btn"
                    data-year="<?php echo $prevYear; ?>"
                    data-month="<?php echo $prevMonth; ?>">
                <
            </button>
            <span><?php echo htmlspecialchars($calendarLabel); ?></span>
            <button type="button"
                    class="calendar-nav-btn"
                    data-year="<?php echo $nextYear; ?>"
                    data-month="<?php echo $nextMonth; ?>">
                
            </button>
        </div>
        <span class="calendar-header-sub">Showing tasks by due date</span>
    </div>
    <div class="calendar-grid">
        <?php
        $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        foreach ($dayNames as $dn) { //Makes a list of day names at the top of calendar
            echo '<div class="calendar-day-name">' . $dn . '</div>'; 
        }

        for ($i = 0; $i < $startWeekday; $i++) { //Makes empty cells for days
            echo '<div class="calendar-cell"></div>';
        }

        // Loops for each day in the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day);
            echo '<div class="calendar-cell">'; //Starts calendar cell
            echo '<div class="calendar-date">' . $day . '</div>'; //Displays day number

            if (isset($tasksByDate[$dateStr])) { //Checks for tasks in that day
                foreach ($tasksByDate[$dateStr] as $t) { 
                    echo '<div class="calendar-task">' . htmlspecialchars($t['title']). '</div>'; //Displays each task title for that day
                }
            }
            echo '</div>';
        }
        ?>
    </div>
    <?php
}


if (isset($_GET['ajax']) && $_GET['ajax'] === 'calendar') {
    ob_start();
    renderCalendar($calendarYear, $calendarMonth, $prevYear, $prevMonth,
        $nextYear, $nextMonth, $calendarLabel, $startWeekday, 
        $daysInMonth, $tasksByDate);
    $html = ob_get_clean();
    echo $html;
    exit;
}
?>
<!doctype html> <!-- tasks.php – main dashboard for managing tasks -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Taskly - Your Tasks</title>

    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="tasks.css">
</head>
<body>
    <div class="app-shell">
        <header class="top-nav">
            <div class="nav-left">
                <div class="logo-box">
                    <img src="Taskly.png" alt="Taskly logo">
                </div>
                <div class="welcome-text">
                    <span class="welcome-main"> <!-- Welcome message at the top -->
                        Hello, <?php echo htmlspecialchars($username); ?>!
                    </span>
                    <span class="welcome-sub">
                        Ready to manage and organize your tasks?
                    </span>
                </div>
            </div>
            <div class="nav-right">
                <a class="nav-btn logout" href="logout.php">Logout</a> <!-- Logout button calls logout.php -->
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <div class="sidebar-title">Navigation</div>
            </div>
            <div class="sidebar-section-label">Views</div>
            <div class="sidebar-nav"> <!--Links to different views-->
                <a href="#cards-view" class="active-link">Cards</a>
                <a href="#table-view" class="active-link">Table</a>
                <a href="#calendar-view" class="active-link">Calendar</a>
            </div>

            <div class="sidebar-section-label">Filters</div>
            <div class="sidebar-filters"> <!--Takes previous php calculations to show counters-->
                <div>Active: <strong><?php echo $activeCount; ?></strong></div>
                <div>Completed: <strong><?php echo $completedCount; ?></strong></div>
                <div>Total: <strong><?php echo $activeCount + $completedCount; ?></strong></div>
            </div>

            <div class="sidebar-section-label">Categories</div>
            <div class="sidebar-chips">
                <?php if (empty($allCategories)): ?>
                    <span class="sidebar-pill">General</span> <!-- Default category is general -->
                <?php else: ?>
                    <?php foreach ($allCategories as $cat): ?> <!-- Displays each category as a tag -->
                        <span class="sidebar-pill"><?php echo htmlspecialchars($cat); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="sidebar-section-label">Status tags</div> <!-- Shows the only 3 status tags -->
            <div class="sidebar-chips">
                <span class="sidebar-pill">Not started</span>
                <span class="sidebar-pill">In progress</span>
                <span class="sidebar-pill">Completed</span>
            </div>

            <div class="sidebar-section-label">Grading</div> <!-- Shows graded or not graded tags -->
            <div class="sidebar-chips">
                <span class="sidebar-pill">Not graded</span>
                <span class="sidebar-pill">Graded</span>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-panel" role="main">
            <div class="app-header">
                <h2 id="new-task">Taskly Dashboard</h2>
                <small>Manage everything, the Taskly way.</small>
            </div>

            <!-- ADD TASK FORM -->
            <section class="add-task-form">
                <div class="add-task-header">
                    <h3>Add a new task</h3>
                    <span>Assign your tasks a title, description, deadline, category, and status!</span>
                </div>

                <form method="post">
                    <input type="hidden" name="action" value="add">

                    <div class="add-task-row">
                        <input class="input" type="text" name="title" placeholder="Task title" required> <!-- Title Text -->
                        <input class="input" type="date" name="due_date" placeholder="yyyy-mm-dd"> <!-- Due Date -->
                    </div>

                    <textarea class="input" name="description" placeholder="Description and Additional Notes (Optional)"></textarea> <!-- Description -->

                    <div class="meta-row">
                        <div> <!-- Div and dropdownfor categories-->
                            <label class="label" for="category">Category</label> 
                            <select class="input" id="category" name="category">
                                <option value="General">General</option>
                                <option value="School">School</option>
                                <option value="Work">Work</option>
                                <option value="Personal">Personal</option>
                                <option value="Errands">Errands</option>
                                <option value="__custom">Create your own!</option> <!-- JS function below if custom category was chosen --> 
                            </select>

                            <input class="input custom-field" type="text" id="custom_category" name="custom_category" placeholder="Type a custom category"> <!-- Text for custom category-->
                        </div>

                        <div> <!--Div and dropdown for statuses -->
                            <label class="label" for="status_tag">Status</label>
                            <select class="input" id="status_tag" name="status_tag">
                                <option value="Not started">Not started</option>
                                <option value="In progress">In progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <button class="button add-btn" type="submit">Add task</button> <!--Submits form for new task-->
                </form>
            </section>

            <div class="filter-bar"> <!-- Filters what is seen on the 3 views-->
                <form method="get" class="filter-form">
                    <select name="category" onchange="this.form.submit()">
                        <option value="all" <?php if ($selectedCategory === 'all') echo 'selected'; ?>> <!-- Shows all categories -->
                            All categories
                        </option>
                        <?php foreach ($allCategories as $cat): ?> <!-- Checks if each task has a specific category -->
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                <?php if ($selectedCategory === $cat) echo 'selected'; ?>> <!-- Adds attribute selected if it has that category -->
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status_filter" onchange="this.form.submit()"> <!-- Simple conditional when selecting specific status tag -->
                        <option value="all" <?php if ($selectedStatus === 'all') echo 'selected'; ?>>
                            All status tags
                        </option>
                        <option value="Not started" <?php if ($selectedStatus === 'Not started') echo 'selected'; ?>>
                            Not started
                        </option>
                        <option value="In progress" <?php if ($selectedStatus === 'In progress') echo 'selected'; ?>>
                            In progress
                        </option>
                        <option value="Completed" <?php if ($selectedStatus === 'Completed') echo 'selected'; ?>>
                            Completed
                        </option>
                    </select>
                </form>

                <div class="stats-text"> <!-- Shows active completed and total here as well -->
                    Active: <?php echo $activeCount; ?> - Completed: <?php echo $completedCount; ?> - Total: <?php echo $activeCount + $completedCount; ?>
                </div>
            </div>

            <!-- CARDS VIEW -->
            <div class="section-label" id="cards-view">Task cards</div>
            <section class="tasks-layout">
                <?php if (empty($tasks)): ?>
                    <p class="empty-text">No tasks yet. Create one to get started!</p>
                <?php else: ?> <!-- Creates task cards for each task using arrays -->
                    <?php foreach ($tasks as $task): ?>
                        <?php
                            $isComplete = ($task['status'] === 'Completed');
                            $cardClass = $isComplete ? 'task-card task-complete' : 'task-card';
                            $statusTag = $task['status'] ?: 'Not started'; //Default status is not started

                            $statusClass = 'status-not-started';
                            if (strcasecmp($statusTag, 'In progress') === 0) { //Sets status to in-progress
                                $statusClass = 'status-in-progress';
                            } elseif (strcasecmp($statusTag, 'Completed') === 0) { //Sets status to completed
                                $statusClass = 'status-completed';
                            }

                            $isSchool = (strcasecmp($task['category'], 'School') === 0); //Sets grading option to task if its category is school
                            $gradingValue = $task['grading'] ?? null;
                        ?>
                        <article class="<?php echo $cardClass; ?>">
                            <div class="task-main">
                                <h4 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h4> <!-- Sets task title in card-->

                                <?php if (!empty($task['description'])): ?>
                                    <p class="task-description"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p> <!-- Sets description if there is one -->
                                <?php endif; ?>

                                <div class="task-meta">
                                    <?php if ($isSchool && $gradingValue !== null): ?> 
                                        Grading: <?php echo htmlspecialchars($gradingValue); ?> <!-- Prints grade status if school category-->
                                    <?php else: ?>
                                        Status: <?php echo htmlspecialchars($task['status']); ?> <!-- Prints status if not school category -->
                                    <?php endif; ?>

                                    <?php if (!empty($task['due_date'])): ?> - Due: <?php echo htmlspecialchars($task['due_date']); ?> <!-- Prints due date -->
                                    <?php endif; ?>
                                </div>

                                <div class="badge-row">
                                    <?php if (!empty($task['category'])): ?>
                                        <span class="badge category"><?php echo htmlspecialchars($task['category']); ?></span> <!-- Displays a category badge if category exists -->
                                    <?php endif; ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusTag); ?></span> <!-- Displays status badge -->
                                    <?php if ($isSchool && $gradingValue !== null): ?>
                                        <span class="badge grading"><?php echo htmlspecialchars($gradingValue); ?></span> <!-- Displays grade badge if school category -->
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="task-actions">
                                <!-- Change status -->
                                <form method="post">
                                    <input type="hidden" name="action" value="change_status_tag">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>"> <!-- Sends ID of task being updated -->
                                    <select name="new_status_tag" class="small-btn" onchange="this.form.submit()"> <!-- Creates dropdown option to change status-->
                                        <option value="Not started" <?php if ($statusTag === 'Not started') echo 'selected'; ?>>Not started</option>
                                        <option value="In progress" <?php if ($statusTag === 'In progress') echo 'selected'; ?>>In progress</option>
                                        <option value="Completed" <?php if ($statusTag === 'Completed') echo 'selected'; ?>>Completed</option>
                                    </select>
                                </form>

                                <?php if ($isSchool): ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="change_grading">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>"> <!-- Sends ID of task being updated -->
                                        <select name="new_grading" class="small-btn" onchange="this.form.submit()"> <!-- Creates dropdown option to change grade -->
                                            <option value="Not graded" <?php if ($gradingValue === 'Not graded') echo 'selected'; ?>>Not graded</option>
                                            <option value="Graded" <?php if ($gradingValue === 'Graded') echo 'selected'; ?>>Graded</option>
                                        </select>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete button to delete task -->
                                <form method="post">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button class="small-btn delete" type="submit">Delete</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- TABLE VIEW -->
            <div class="section-label" id="table-view">Table view</div>
            <div class="table-wrapper">
                <div class="table-header">
                    <span>All tasks (<?php echo $activeCount + $completedCount; ?>)</span> <!-- Prints total number of tasks as well -->
                </div>
                <table class="task-table">
                    <thead>
                        <tr> <!-- Table header -->
                            <th>Name</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Grading / Status</th>
                            <th>Due date</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?> <!-- Displays tasks if there are some -->
                            <tr>
                                <td colspan="6" class="empty-text">
                                    No tasks to display yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <?php
                                    $isSchool = (strcasecmp($task['category'], 'School') === 0); //Sets an exception for school tasks
                                    $gradingValue = $task['grading'] ?? null; //Sets grade status
                                    $gradeOrComplete = $isSchool && $gradingValue !== null ? $gradingValue: $task['status'];
                                ?>
                                <tr> <!-- Displays a row with the tasks values -->
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo htmlspecialchars($task['category']); ?></td>
                                    <td><?php echo htmlspecialchars($task['status']); ?></td>
                                    <td><?php echo htmlspecialchars($gradeOrComplete); ?></td>
                                    <td><?php echo htmlspecialchars($task['due_date']); ?></td>
                                    <td><?php echo htmlspecialchars($task['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- CALENDAR VIEW -->
            <div class="section-label" id="calendar-view">Calendar view</div>
            <div class="calendar-wrapper">
                <?php //Just displays calendar regularly
                renderCalendar($calendarYear,$calendarMonth,$prevYear,$prevMonth, 
                                $nextYear, $nextMonth, $calendarLabel,$startWeekday,
                                $daysInMonth,$tasksByDate);
                ?>
            </div>
        </main>
    </div>
    <script src="tasks.js"></script>
</body>
</html>