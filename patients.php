<?php
// 1. Core Setup - MUST BE AT THE TOP
require_once 'connect.php'; 

$error = '';
$success = '';
$edit_mode = false;

// Initialize patient_data array with empty strings for all fields
$patient_data = [
    'id' => '', 
    'name' => '', 
    'age' => '', 
    'cause' => '', 
    'contact_num' => '', 
    'dob' => ''
];
$search_term = ''; 

// --- HANDLE POST REQUESTS (DELETE, UPDATE, CREATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Handle DELETE OPERATION
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $patient_id = filter_var($_POST['patient_id'], FILTER_SANITIZE_NUMBER_INT);
        $patient_id = (int)$patient_id;

        if ($patient_id <= 0) {
            $error = "Invalid patient ID provided for deletion.";
        } else {
            $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $patient_id);
                if ($stmt->execute()) {
                    // Redirect MUST happen before any output
                    header('Location: patients.php?success=' . urlencode("Patient (ID: {$patient_id}) successfully deleted."));
                    exit();
                } else {
                    $error = "Failed to delete patient. Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Error (Delete): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
            }
        }
    } 
    
    // 3. Handle UPDATE OPERATION
    elseif (isset($_POST['update_patient'])) {
        $id = filter_var($_POST['patient_id'], FILTER_SANITIZE_NUMBER_INT);
        $name = trim($_POST['name']);
        $age = filter_var($_POST['age'], FILTER_SANITIZE_NUMBER_INT);
        $cause = trim($_POST['cause']);
        $contact_num = trim($_POST['contact_num']);
        $dob = trim($_POST['dob']);

        // Populate patient_data immediately in case validation or DB fails
        $patient_data = ['id' => $id, 'name' => $name, 'age' => $age, 'cause' => $cause, 'contact_num' => $contact_num, 'dob' => $dob];
        $edit_mode = true; // Stay in edit mode if an error occurs

        if (empty($name) || empty($age) || empty($cause) || empty($contact_num) || empty($dob) || (int)$id <= 0) {
            $error = "All fields and a valid Patient ID are required to update a patient.";
        } else {
            $stmt = $conn->prepare("UPDATE patients SET name = ?, age = ?, cause = ?, contact_num = ?, dob = ? WHERE id = ?");

            if ($stmt) {
                $stmt->bind_param("sisssi", $name, $age, $cause, $contact_num, $dob, $id);
                if ($stmt->execute()) {
                    // Redirect MUST happen before any output
                    header('Location: patients.php?success=' . urlencode("Patient **" . htmlspecialchars($name) . "** updated successfully!"));
                    exit();
                } else {
                    $error = "Failed to update patient. Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Error (Update): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
            }
        }
    }
    
    // 4. Handle CREATE OPERATION
    elseif (isset($_POST['add_patient'])) {
        $name = trim($_POST['name']);
        $age = filter_var($_POST['age'], FILTER_SANITIZE_NUMBER_INT);
        $cause = trim($_POST['cause']);
        $contact_num = trim($_POST['contact_num']);
        $dob = trim($_POST['dob']);

        // Populate patient_data for form re-filling in case of error
        $patient_data = ['id' => '', 'name' => $name, 'age' => $age, 'cause' => $cause, 'contact_num' => $contact_num, 'dob' => $dob];
        
        if (empty($name) || empty($age) || empty($cause) || empty($contact_num) || empty($dob)) {
            $error = "All fields are required to add a new patient.";
        } else {
            $stmt = $conn->prepare("INSERT INTO patients (name, age, cause, contact_num, dob) VALUES (?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("sisss", $name, $age, $cause, $contact_num, $dob);
                if ($stmt->execute()) {
                    // Redirect MUST happen before any output
                    header('Location: patients.php?success=' . urlencode("Patient **" . htmlspecialchars($name) . "** added successfully!"));
                    exit();
                } else {
                    $error = "Failed to add patient. Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Error (Create): Could not prepare statement. MySQL Error: " . mysqli_error($conn);
            }
        }
    }
}

// 5. Handle GET requests (EDIT SETUP, SUCCESS MESSAGES, SEARCH)
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
            $stmt = $conn->prepare("SELECT id, name, age, cause, contact_num, dob FROM patients WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $patient_data = $result->fetch_assoc();
                $edit_mode = true; // Flag to show the Edit form
            } else {
                $error = "Patient not found.";
            }
            $stmt->close();
        } else {
             $error = "Invalid Patient ID specified for editing.";
        }
    }

    // Check for search term
    if (isset($_GET['search_name']) && !empty(trim($_GET['search_name']))) {
        $search_term = trim($_GET['search_name']);
    }
}


// 6. Handle READ OPERATION (FETCH ALL PATIENTS with Search)
$patients = [];
$sql = "SELECT id, name, age, cause, contact_num, dob FROM patients";
$params = [];
$types = '';

if (!empty($search_term)) {
    $sql .= " WHERE name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= 's';
}

$sql .= " ORDER BY id DESC";

$stmt_fetch = $conn->prepare($sql);

if (!empty($params)) {
    $stmt_fetch->bind_param($types, ...$params); 
}
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();


if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    $stmt_fetch->close();
} else {
    $error .= " Could not fetch patient list: " . $conn->error;
}


// ----------------------------------------------------------------------
// 7. INCLUDE HEADER HERE - MOVED TO FIX "HEADERS ALREADY SENT" WARNING
// ----------------------------------------------------------------------
$pageTitle = "Patients Management";
require_once 'patients_header.php'; 
?>

<style>
    /* ... (Your existing CSS styles remain here) ... */
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
        flex-wrap: wrap; 
    }
    .form-row .form-group {
        flex: 1;
        min-width: 250px; 
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
    <h2>Patient Management</h2>
    <p>View, Add, Edit, and Manage Patient records.</p>
</div>

<?php if (!empty($error)): ?>
    <p class="message-error"><?php echo $error; ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <p class="message-success"><?php echo $success; ?></p>
<?php endif; ?>


<div class="add-form-container">
    <h4 style="margin-top: 0;"><?php echo $edit_mode ? 'Edit Patient Record (ID: ' . htmlspecialchars($patient_data['id']) . ')' : 'Add New Patient'; ?></h4>
    
    <form action="patients.php" method="POST">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="update_patient" value="1">
            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_data['id']); ?>">
        <?php else: ?>
            <input type="hidden" name="add_patient" value="1">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label for="name">Patient Name:</label>
                <input type="text" id="name" name="name" placeholder="E.g., Jane Doe" 
                        value="<?php echo htmlspecialchars($patient_data['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" placeholder="e.g., 35" min="1" max="120"
                        value="<?php echo htmlspecialchars($patient_data['age']); ?>" required>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth (DOB):</label>
                <input type="date" id="dob" name="dob" 
                        value="<?php echo htmlspecialchars($patient_data['dob']); ?>" required>
            </div>
            <div class="form-group">
                <label for="contact_num">Contact Number:</label>
                <input type="text" id="contact_num" name="contact_num" placeholder="e.g., 98xxxxxxxx" 
                        value="<?php echo htmlspecialchars($patient_data['contact_num']); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="cause">Cause/Reason for Visit:</label>
                <input type="text" id="cause" name="cause" placeholder="e.g., Severe Fever, Broken Arm" 
                        value="<?php echo htmlspecialchars($patient_data['cause']); ?>" required>
            </div>
            <div class="form-group" style="flex: 0;">
            </div>
        </div>

        <?php if ($edit_mode): ?>
            <button type="submit" class="btn-primary" style="width: 250px;"><i class="fas fa-save"></i> Save Changes</button>
            <a href="patients.php" class="cancel-btn" style="width: 100px;"><i class="fas fa-times"></i> Cancel</a>
        <?php else: ?>
            <button type="submit" class="btn-primary" style="width: 250px;"><i class="fas fa-plus"></i> Add Patient</button>
        <?php endif; ?>

    </form>
</div>


<div class="search-bar-container">
    <form action="patients.php" method="GET" style="display: flex; width: 100%; gap: 10px;">
        <input type="text" name="search_name" placeholder="Search Patient by Name..." 
                value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search_term)): ?>
            <a href="patients.php" class="cancel-btn" style="padding: 10px 15px; margin-left: 0;"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="section-header">
    <h4>All Patients (Total: <?php echo count($patients); ?>)</h4>
</div>

<?php if (empty($patients)): ?>
    <div style="text-align: center; padding: 40px; background-color: white; border-radius: 8px; border: 1px solid #dee2e6; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);">
        <p style="color: #6c757d;">
            <?php echo !empty($search_term) ? "No patients found matching '{$search_term}'." : "No patients found in the database. Please add a new patient above."; ?>
        </p>
    </div>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>No.</th> 
                <th>Name</th>
                <th>Age</th>
                <th>Contact</th>
                <th>DOB</th>
                <th>Cause</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Initialize the UI counter
            $row_number = 1; 
            ?>
            <?php foreach ($patients as $patient): ?>
            <tr>
                <td><?php echo $row_number; ?></td> 
                
                <td><?php echo htmlspecialchars($patient['name']); ?></td>
                <td><?php echo htmlspecialchars($patient['age']); ?></td>
                <td><?php echo htmlspecialchars($patient['contact_num']); ?></td>
                <td><?php echo htmlspecialchars($patient['dob']); ?></td>
                <td><?php echo htmlspecialchars($patient['cause']); ?></td>
                <td>
                    <a href="patients.php?action=edit&id=<?php echo $patient['id']; ?>" class="action-btn edit-btn">Edit</a>
                    
                    <form action="patients.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete <?php echo addslashes($patient['name']); ?>?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                        <button type="submit" class="action-btn delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php 
            // Increment the counter for the next row
            $row_number++; 
            ?>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
require_once 'patients_footer.php';
mysqli_close($conn);
?>