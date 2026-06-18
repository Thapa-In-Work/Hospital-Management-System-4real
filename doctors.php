<?php
// 0. OUTPUT BUFFERING FIX
// Start output buffering to prevent "Headers already sent" errors.
ob_start();

// 1. Core Setup
require_once 'connect.php'; 

$pageTitle = "Doctors Management";
require_once 'doctors_header.php'; 

$error = '';
$success = '';
$edit_mode = false;
$doctor_data = ['id' => '', 'name' => '', 'department' => '', 'shift' => ''];
$search_term = ''; // Variable for the search term

// --- HANDLE POST REQUESTS (DELETE, UPDATE, CREATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Handle DELETE OPERATION
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $doctor_id = filter_var($_POST['doctor_id'], FILTER_SANITIZE_NUMBER_INT);
        $doctor_id = (int)$doctor_id;

        if ($doctor_id <= 0) {
            $error = "Invalid doctor ID provided for deletion.";
        } else {
            $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $doctor_id);
                if ($stmt->execute()) {
                    // Redirect on success to prevent refresh issues
                    header('Location: doctors.php?success=' . urlencode("Doctor (ID: {$doctor_id}) successfully deleted."));
                    exit();
                } else {
                    $error = "Failed to delete doctor. Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Error (Delete): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
            }
        }
    } 
    
    // 3. Handle UPDATE OPERATION
    elseif (isset($_POST['update_doctor'])) {
        $id = filter_var($_POST['doctor_id'], FILTER_SANITIZE_NUMBER_INT);
        $name = trim($_POST['name']);
        $department = trim($_POST['department']);
        $shift = trim($_POST['shift']);

        if (empty($name) || empty($department) || empty($shift) || (int)$id <= 0) {
            $error = "All fields and a valid Doctor ID are required to update a doctor.";
        } else {
            $stmt = $conn->prepare("UPDATE doctors SET name = ?, department = ?, shift = ? WHERE id = ?");

            if ($stmt) {
                $stmt->bind_param("sssi", $name, $department, $shift, $id);
                if ($stmt->execute()) {
                    // Redirect on success to prevent refresh issues
                    header('Location: doctors.php?success=' . urlencode("Doctor **" . htmlspecialchars($name) . "** updated successfully!"));
                    exit();
                } else {
                    $error = "Failed to update doctor. Error: " . $stmt->error;
                    // Re-set data to keep form populated if update failed
                    $doctor_data = ['id' => $id, 'name' => $name, 'department' => $department, 'shift' => $shift];
                    $edit_mode = true; 
                }
                $stmt->close();
            } else {
                $error = "Database Error (Update): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
            }
        }
    }
    
    // 4. Handle CREATE OPERATION
    elseif (isset($_POST['add_doctor'])) {
        $name = trim($_POST['name']);
        $department = trim($_POST['department']);
        $shift = trim($_POST['shift']);

        if (empty($name) || empty($department) || empty($shift)) {
            $error = "All fields are required to add a new doctor.";
        } else {
            $stmt = $conn->prepare("INSERT INTO doctors (name, department, shift) VALUES (?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("sss", $name, $department, $shift);
                if ($stmt->execute()) {
                    // Redirect on success to prevent refresh issues
                    header('Location: doctors.php?success=' . urlencode("Doctor **" . htmlspecialchars($name) . "** added successfully!"));
                    exit();
                } else {
                    $error = "Failed to add doctor. Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Error (Create): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
            }
        }
    }
}

// 6. Handle GET requests (EDIT SETUP, SUCCESS MESSAGES, SEARCH)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    
    // Check for success messages from a redirect
    if (isset($_GET['success'])) {
        $success = htmlspecialchars(urldecode($_GET['success']));
    }
    
    // Check for edit action
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $id = (int)$id;
        
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT id, name, department, shift FROM doctors WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $doctor_data = $result->fetch_assoc();
                $edit_mode = true; // Flag to show the Edit form
            } else {
                $error = "Doctor not found.";
            }
            $stmt->close();
        } else {
             $error = "Invalid Doctor ID specified for editing.";
        }
    }
    
    // Check for search term
    if (isset($_GET['search_name']) && !empty(trim($_GET['search_name']))) {
        $search_term = trim($_GET['search_name']);
    }
}


// 7. Handle READ OPERATION (FETCH ALL DOCTORS with Search)
$doctors = [];
$sql = "SELECT id, name, department, shift FROM doctors";
$params = [];
$types = '';
// Flag to track if prepared statement was used
$stmt_used = false; 

if (!empty($search_term)) {
    $sql .= " WHERE name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= 's';
}

$sql .= " ORDER BY id ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        // Mark statement as used
        $stmt_used = true;
    } else {
        $error .= " Could not prepare search statement: " . mysqli_error($conn);
        $result = false;
    }
} else {
    $result = mysqli_query($conn, $sql);
}

if ($result) {
    // Check if $result is a mysqli_result object before fetching
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
    
    // FIX: Only close $stmt if it was actually prepared and executed in the search block.
    if ($stmt_used && isset($stmt) && is_object($stmt)) {
        $stmt->close();
    }
    // mysqli_query results do not have a separate close method.
    // If mysqli_query was used, $result is a standard mysqli_result and does not need $stmt->close().
} else {
    $error .= " Could not fetch doctor list: " . mysqli_error($conn);
}
?>

<style>
    /* ... (CSS styles for form and table containers are here) ... */
    :root {
        --primary-color: #007bff;
        --white: #ffffff;
    }
    .add-form-container {
        background-color: var(--white);
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); 
        margin-bottom: 40px;
        border: 1px solid #dee2e6; 
    }
    .add-form-container h4 {
        color: #212529;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    .form-row .form-group {
        flex: 1;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #495057;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        box-sizing: border-box; 
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        outline: none;
    }
    .data-table {
        width: 100%;
        border-collapse: separate; 
        border-spacing: 0;
        margin-top: 20px;
        background-color: var(--white);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        overflow: hidden; 
    }
    .data-table th, .data-table td {
        padding: 15px 15px; 
        text-align: left;
    }
    .data-table th {
        background-color: var(--primary-color);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        border-bottom: 1px solid #0056b3;
    }
    .data-table tbody tr {
        border-bottom: 1px solid #e9ecef;
    }
    .data-table tbody tr:last-child {
        border-bottom: none;
    }
    .data-table tbody tr:hover {
        background-color: #f1f1f1;
    }
    .action-btn {
        padding: 6px 10px;
        margin-right: 5px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        transition: opacity 0.2s;
        border: none;
    }
    .action-btn:hover { opacity: 0.8; }
    .edit-btn { background-color: #ffc107; color: #343a40; }
    .delete-btn { background-color: #dc3545; color: white; }
    .cancel-btn { 
        background-color: #6c757d; 
        color: white; 
        padding: 10px 15px; 
        border-radius: 4px;
        display: inline-block;
        text-align: center;
        margin-left: 10px;
    }
    .message-success {
        color: #155724; 
        margin-bottom: 20px; 
        padding: 15px; 
        border: 1px solid #c3e6cb; 
        background-color: #d4edda; 
        border-radius: 4px;
        font-weight: 500;
    }
    .message-error {
        color: #721c24; 
        padding: 15px; 
        border: 1px solid #f5c6cb; 
        background-color: #f8d7da; 
        border-radius: 4px; 
        margin-bottom: 20px;
        font-weight: 500;
    }
    .search-bar-container {
        margin-bottom: 25px;
        padding: 15px;
        background-color: var(--white);
        border-radius: 8px;
        border: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .search-bar-container input[type="text"] {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    .search-bar-container button {
        padding: 10px 20px;
        border-radius: 4px;
    }
</style>


<div class="section-header">
    <h2>Doctor Management</h2>
    <p>View, Add, and Manage Doctor records.</p>
</div>

<?php if (!empty($error)): ?>
    <p class="message-error"><?php echo $error; ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <p class="message-success"><?php echo $success; ?></p>
<?php endif; ?>


<div class="add-form-container">
    <h4 style="margin-top: 0;"><?php echo $edit_mode ? 'Edit Doctor Record (ID: ' . htmlspecialchars($doctor_data['id']) . ')' : 'Add New Doctor'; ?></h4>
    
    <form action="doctors.php" method="POST">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="update_doctor" value="1">
            <input type="hidden" name="doctor_id" value="<?php echo htmlspecialchars($doctor_data['id']); ?>">
        <?php else: ?>
            <input type="hidden" name="add_doctor" value="1">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label for="name">Doctor Name:</label>
                <input type="text" id="name" name="name" placeholder="E.g., Dr. John Smith" 
                         value="<?php echo htmlspecialchars($doctor_data['name'] ?? $_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="department">Department:</label>
                <select id="department" name="department" required>
                    <option value="">Select Department</option>
                    <?php $selected_dept = $doctor_data['department'] ?? $_POST['department'] ?? ''; ?>
                    <option value="Cardiology" <?php echo ($selected_dept == 'Cardiology') ? 'selected' : ''; ?>>Cardiology</option>
                    <option value="Neurology" <?php echo ($selected_dept == 'Neurology') ? 'selected' : ''; ?>>Neurology</option>
                    <option value="Pediatrics" <?php echo ($selected_dept == 'Pediatrics') ? 'selected' : ''; ?>>Pediatrics</option>
                    <option value="Oncology" <?php echo ($selected_dept == 'Oncology') ? 'selected' : ''; ?>>Oncology</option>
                    <option value="Orthopedics" <?php echo ($selected_dept == 'Orthopedics') ? 'selected' : ''; ?>>Orthopedics</option>
                    <option value="General" <?php echo ($selected_dept == 'General') ? 'selected' : ''; ?>>General Medicine</option>
                </select>
            </div>
            <div class="form-group">
                <label for="shift">Shift:</label>
                <select id="shift" name="shift" required>
                    <option value="">Select Shift</option>
                    <?php $selected_shift = $doctor_data['shift'] ?? $_POST['shift'] ?? ''; ?>
                    <option value="Morning" <?php echo ($selected_shift == 'Morning') ? 'selected' : ''; ?>>Morning</option>
                    <option value="Afternoon" <?php echo ($selected_shift == 'Afternoon') ? 'selected' : ''; ?>>Afternoon</option>
                    <option value="Night" <?php echo ($selected_shift == 'Night') ? 'selected' : ''; ?>>Night</option>
                </select>
            </div>
        </div>
        
        <?php if ($edit_mode): ?>
            <button type="submit" class="btn-primary" style="width: 250px;"><i class="fas fa-save"></i> Save Changes</button>
            <a href="doctors.php" class="cancel-btn" style="width: 100px;"><i class="fas fa-times"></i> Cancel</a>
        <?php else: ?>
            <button type="submit" class="btn-primary" style="width: 250px;"><i class="fas fa-plus"></i> Add Doctor</button>
        <?php endif; ?>

    </form>
</div>


<div class="search-bar-container">
    <form action="doctors.php" method="GET" style="display: flex; width: 100%; gap: 10px;">
        <input type="text" name="search_name" placeholder="Search Doctor by Name..." 
                 value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search_term)): ?>
            <a href="doctors.php" class="cancel-btn" style="padding: 10px 15px; margin-left: 0;"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="section-header">
    <h4>All Doctors (Total: <?php echo count($doctors); ?>)</h4>
</div>

<?php if (empty($doctors)): ?>
    <div style="text-align: center; padding: 40px; background-color: white; border-radius: 8px; border: 1px solid #dee2e6; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);">
        <p style="color: #6c757d;">
            <?php echo !empty($search_term) ? "No doctors found matching '{$search_term}'." : "No doctors found in the database. Please add a new doctor above."; ?>
        </p>
    </div>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Department</th>
                <th>Shift</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1; 
            ?>
            <?php foreach ($doctors as $doctor): ?>
            <tr>
                <td><?php echo $counter; ?></td>
                <td><?php echo htmlspecialchars($doctor['name']); ?></td>
                <td><?php echo htmlspecialchars($doctor['department']); ?></td>
                <td><?php echo htmlspecialchars($doctor['shift']); ?></td>
                <td>
                    <a href="doctors.php?action=edit&id=<?php echo $doctor['id']; ?>" class="action-btn edit-btn">Edit</a>
                    
                    <form action="doctors.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete <?php echo addslashes($doctor['name']); ?>?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                        <button type="submit" class="action-btn delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php 
            $counter++; 
            ?>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
require_once 'doctors_footer.php';
mysqli_close($conn);

// 0. OUTPUT BUFFERING FIX
ob_end_flush();
?>