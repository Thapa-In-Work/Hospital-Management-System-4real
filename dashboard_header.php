<?php
// Start session and security check (MUST be run before any HTML)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Security Check: If user is NOT logged in, redirect immediately.
if (!isset($_SESSION['user_id'])) {
    // NOTE: Adjust the path if necessary.
    header('Location: login.php'); 
    exit();
}

// Function to check if the current page is active
function isActive($pageUrl) {
    $currentFile = basename($_SERVER['PHP_SELF']);
    
    // Check for exact file match
    if ($currentFile === $pageUrl) {
        return ' active';
    }
    
    // Check for folder/file match 
    $uriPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $linkDir = strtolower(str_replace('.php', '', $pageUrl));
    if (strpos($uriPath, $linkDir . '/' . $pageUrl) !== false) {
         return ' active';
    }

    return '';
}

// Set default user email (using the email from the images for the default fallback)
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? 'sabeentapa9861@gmail.com');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Administrator Panel'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --sidebar-width: 260px;
        }

        /* Base Reset */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--light-bg); margin: 0; padding: 0; min-height: 100vh; color: #343a40; }
        
        /* Layout */
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { 
            width: var(--sidebar-width); 
            background-color: var(--white); 
            padding: 20px 0; 
            border-right: 1px solid #e9ecef; 
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05); 
            flex-shrink: 0; 
            box-sizing: border-box; 
            display: flex; 
            flex-direction: column;
        }
        .main-content { 
            flex-grow: 1; 
            padding: 30px 40px; 
            overflow-y: auto; 
            transition: margin-left 0.3s;
            margin-left: var(--sidebar-width);
        }

        /* Profile & Logout */
        .profile-card { 
            text-align: center; 
            margin: 0 20px 30px; 
            padding-bottom: 20px; 
            border-bottom: 1px solid #eee;
        }
        .profile-card i { 
            font-size: 40px; 
            color: var(--secondary-color); 
            margin-bottom: 10px;
        }
        .profile-card h4 { margin: 5px 0 0; font-weight: 600; }
        .profile-card small { color: var(--secondary-color); }
        .logout-btn { 
            padding: 10px 15px; 
            background-color: #dc3545; /* Red for Logout */
            color: var(--white); 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 14px; 
            text-decoration: none; 
            text-align: center; 
            display: block; 
            margin: 0 20px 30px;
            transition: background-color 0.3s;
        }
        .logout-btn:hover { background-color: #c82333; }

        /* Navigation */
        nav { padding: 0 20px; }
        .nav-link { 
            display: flex; 
            align-items: center; 
            padding: 12px 15px; 
            margin-bottom: 5px; 
            color: #495057; 
            text-decoration: none; 
            border-radius: 4px; 
            transition: background-color 0.2s, color 0.2s; 
            font-weight: 500; 
        }
        .nav-link:hover { background-color: #f1f1f1; color: var(--primary-color); }
        .nav-link.active { 
            background-color: #e6f2ff; /* Light blue background for active */
            color: var(--primary-color); 
            font-weight: 700;
        }
        .nav-link i { margin-right: 15px; width: 20px; text-align: center; }

        /* Main Content Structure */
        .section-header { border-bottom: 2px solid #e9ecef; padding-bottom: 10px; margin-bottom: 25px; }
        .section-header h2 { margin: 0; font-size: 24px; color: #212529; }
        .section-header p { margin-top: 5px; color: var(--secondary-color); font-size: 14px; }

        /* Button styles */
        .btn-primary { 
            padding: 10px 15px; 
            background-color: var(--primary-color); 
            color: var(--white); 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 14px; 
            text-decoration: none; 
            text-align: center; 
            transition: background-color 0.3s;
        }
        .btn-primary:hover { background-color: #0056b3; }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" style="position: fixed; height: 100vh;">
            <div class="profile-card">
                <i class="fas fa-user-circle"></i> 
                <h4>Administrator</h4>
                <small><?php echo $userEmail; ?></small>
            </div>

            <a href="logout.php" class="logout-btn">Log Out</a> 
            
            <nav>
                <a href="../LoginForum/dashboard.php" class="nav-link<?php echo isActive('dashboard.php'); ?>"><i class="fas fa-th-large"></i> Dashboard</a>

                <a href="../doctors/doctors.php" class="nav-link<?php echo isActive('doctors.php'); ?>"><i class="fas fa-user-md"></i> Doctors</a>
                
                <a href="../patients/patients.php" class="nav-link<?php echo isActive('patients.php'); ?>"><i class="fas fa-wheelchair"></i> Patients</a>
                <a href="settings.php" class="nav-link<?php echo isActive('settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </div>
        
        <div class="main-content">