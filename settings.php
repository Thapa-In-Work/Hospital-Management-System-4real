<?php
// Include necessary files
require_once 'connect.php';

// Set page title for the header file
$pageTitle = "Settings";

// Include the dashboard-specific header (which contains the security check and layout start)
require_once 'dashboard_header.php'; 

// --- PHP LOGIC PLACEHOLDER ---
// In a real application, this is where you would handle form submissions for password changes, etc.

?>

<style>
    /* Specific styles for the settings page cards */
    .settings-grid {
        display: grid;
        gap: 30px;
        margin-top: 30px;
    }
    .settings-card {
        background-color: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        border-left: 5px solid transparent; /* Default state */
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s, border-left-color 0.2s;
    }
    .settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        border-left-color: #007bff; /* Highlight on hover */
    }
    .settings-card i {
        font-size: 28px;
        color: #007bff;
        margin-right: 20px;
        width: 40px;
        text-align: center;
    }
    .settings-card h4 {
        margin: 0;
        font-weight: 600;
        color: #333;
    }
    .settings-card p {
        margin: 5px 0 0 0;
        color: #6c757d;
        font-size: 14px;
    }
    .back-link {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: #495057;
        margin-bottom: 25px;
        font-weight: bold;
    }
    .back-link i {
        margin-right: 8px;
    }
</style>


<div class="section-header">
    <h2>Settings</h2>
    <p>Manage your account preferences and security.</p>
</div>

<div class="settings-grid">
    
    <div class="settings-card" onclick="alert('Redirecting to Account Edit Form...');">
        <i class="fas fa-user-cog"></i>
        <div>
            <h4>Account Settings</h4>
            <p>Edit your Account Details & Change Password</p>
        </div>
    </div>

    <div class="settings-card" onclick="alert('Redirecting to View Details Page...');">
        <i class="fas fa-eye"></i>
        <div>
            <h4>View Account Details</h4>
            <p>View personal information about your account</p>
        </div>
    </div>

    <div class="settings-card" onclick="alert('Redirecting to Account Deletion Confirmation...');" style="border-left-color: #dc3545;">
        <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
        <div>
            <h4>Delete Account</h4>
            <p>Will permanently remove your account</p>
        </div>
    </div>

</div>

<?php
require_once 'dashboard_footer.php';
mysqli_close($conn);
?>