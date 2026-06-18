<?php
// 1. START SESSION (MUST BE FIRST)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. SECURITY CHECK: If user is NOT logged in, redirect immediately.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit(); 
}

// NOTE: Make sure 'connect.php' path is correct
include 'connect.php'; 

// Set the current page variable for navigation highlighting
$currentPage = 'dashboard'; 
$pageTitle = "Administrator Dashboard";

// 3. Include header *after* setting the necessary variables
include 'dashboard_header.php'; // Assuming this file contains the correct sidebar layout

$doctorCount = 0;
$patientCount = 0;

// Query 1: Get Total Doctor Count (Ensure 'doctors' table exists!)
$sql_doctors = "SELECT COUNT(id) AS total_doctors FROM doctors";
$result_doctors = mysqli_query($conn, $sql_doctors);
if ($result_doctors) {
    $data_doctors = mysqli_fetch_assoc($result_doctors);
    $doctorCount = $data_doctors['total_doctors'];
}

// Query 2: Get Total Patient Count (Ensure 'patients' table exists!)
$sql_patients = "SELECT COUNT(id) AS total_patients FROM patients";
$result_patients = mysqli_query($conn, $sql_patients);
if ($result_patients) {
    $data_patients = mysqli_fetch_assoc($result_patients);
    $patientCount = $data_patients['total_patients'];
}

// Get today's date for display
$today = date('Y-m-d'); 
?>

<style>
    /* ----------------------------------------------------------------------
        DASHBOARD SPECIFIC STYLES (Simplified)
    ---------------------------------------------------------------------- */
    :root {
        --primary-color: #007bff;
        --white: #ffffff;
        --border-color: #dee2e6;
        --shadow-light: 0 2px 5px rgba(0, 0, 0, 0.05); 
        --card-radius: 8px;
    }
    
    /* --- Main Header Section (Title and Date) --- */
    .dashboard-header-container {
        display: flex;
        justify-content: space-between; 
        align-items: flex-start;
        border-bottom: 1px solid #e9ecef; 
        padding-bottom: 15px;
        margin-bottom: 25px;
    }
    .dashboard-header-container h2 {
        font-size: 24px;
        font-weight: 600; 
        color: #212529;
        margin: 0;
    }

    /* --- Attractive Date Display --- */
    .date-display-card {
        display: flex;
        align-items: center;
        background-color: var(--white);
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 5px 10px;
        box-shadow: var(--shadow-light);
        line-height: 1.1; 
        text-align: right;
    }
    .date-info {
        padding-right: 10px;
    }
    .date-info small {
        display: block;
        font-size: 10px;
        color: #6c757d;
        font-weight: 500;
        margin-top: 2px;
    }
    .date-info p {
        font-size: 16px;
        font-weight: 700;
        color: #343a40;
        margin: 0;
    }
    .date-display-card i {
        font-size: 24px;
        color: var(--primary-color); 
    }

    /* Status Header and Content Block Headers */
    .status-header, .content-block-header {
        font-size: 18px;
        font-weight: 600;
        color: #343a40;
        margin-bottom: 10px;
    }

    /* Status Cards */
    .status-cards-container {
        display: flex;
        gap: 30px; 
        margin-bottom: 40px;
    }
    .status-card {
        flex: 1;
        background-color: var(--primary-color);
        color: var(--white);
        padding: 30px; 
        border-radius: var(--card-radius);
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        text-align: center;
        transition: transform 0.2s;
    }
    .status-card:hover {
        transform: translateY(-2px);
    }
    .status-card h3 {
        font-size: 36px;
        margin: 0;
        font-weight: 700;
    }
    .status-card p {
        font-size: 16px;
        margin: 5px 0 0 0;
        opacity: 0.9;
    }

    /* Content Blocks (Latest Doctors/Patients) */
    .content-blocks-container {
        display: flex;
        gap: 40px;
        margin-top: 40px;
    }
    .content-block-wrapper {
        flex: 1;
    }
    .content-block {
        background-color: var(--white);
        padding: 40px 25px; 
        border-radius: var(--card-radius);
        box-shadow: var(--shadow-light); 
        border: 1px solid var(--border-color); 
        text-align: center;
    }
    .content-block p {
        color: #6c757d;
        margin-top: 20px;
        margin-bottom: 25px; 
    }
    .btn-primary-go { 
        padding: 10px 20px; 
        display: inline-block;
        text-decoration: none;
        background-color: var(--primary-color);
        color: var(--white);
        border-radius: 4px;
        transition: background-color 0.2s;
        font-size: 14px;
        border: none;
        cursor: pointer;
    }
    .btn-primary-go:hover {
        background-color: #0056b3;
    }
</style>


<div class="dashboard-header-container">
    <h2>Administrator Dashboard</h2>
    
    <div class="date-display-card">
        <div class="date-info">
            <small>Today's Date</small>
            <p><?php echo date('Y-m-d'); ?></p>
        </div>
        <i class="fas fa-calendar-alt"></i>
    </div>
</div>


<p class="status-header">Status</p>

<div class="status-cards-container">
    <div class="status-card">
        <h3><?php echo $doctorCount; ?></h3>
        <p>Total Doctors</p>
    </div>
    <div class="status-card">
        <h3><?php echo $patientCount; ?></h3>
        <p>Total Patients</p>
    </div>
</div>

<p class="status-header">Latest Registrations</p>
<div class="content-blocks-container">
    
    <div class="content-block-wrapper">
        <p class="content-block-header">Latest Registered Doctors</p>
        <div class="content-block">
            <p>Doctor List Content Coming Soon (Display list here)</p>
            <a href="../Doctors/doctors.php" class="btn-primary-go">Go to Doctors Page</a>
        </div>
    </div>

    <div class="content-block-wrapper">
        <p class="content-block-header">Latest Registered Patients</p>
        <div class="content-block">
            <p>Patient List Content Coming Soon (Display list here)</p>
            <a href="../Patients/patients.php" class="btn-primary-go">Go to Patients Page</a>
        </div>
    </div>
</div>


</div> 
    </div>
</body>
</html>