<?php

session_start();

if (!isset($_SESSION['userID'])) {
    
    echo '<script>window.location.href = "/Cop4710_Project/Frontend/pages/index.html";</script>';
    exit();
}

$userRole = $_SESSION['userRole'] ?? '';

if ($userRole !== 'superadmin') {
    echo '<script>window.location.href = "/Cop4710_Project/Frontend/pages/student_dashboard.php";</script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Events - Super Admin Dashboard</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/superadmin.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Super Admin Dashboard</h1>
            <div>
                <span id="welcomeUser"></span>
                <button onclick="logout()" class="logout-btn">Logout</button>
            </div>
        </div>
        
        <div class="dashboard-nav">
            <ul>
                <li><a href="#" class="tab-link active" data-tab="my-university">My University</a></li>
                <li><a href="#" class="tab-link" data-tab="my-rsos">University RSOs</a></li>
                <li><a href="#" class="tab-link" data-tab="events">Pending Events</a></li>
            </ul>
        </div>
        
        <div id="my-university" class="tab-content active">
            <div id="universityDetails" class="university-details">
                <h2>University Details</h2>
                <div id="universityInfo">
                    <p>Loading university information...</p>
                </div>
            </div>
            
            <div id="editUniversityForm" class="form-container" style="display: none;">
                <h2>Edit University</h2>
                <form id="universityEditForm">
                    <input type="hidden" id="edit_university_id">
                    <div class="form-group">
                        <label for="edit_university_name">University Name:</label>
                        <input type="text" id="edit_university_name" name="edit_university_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_university_location">Location:</label>
                        <input type="text" id="edit_university_location" name="edit_university_location" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_university_description">Description:</label>
                        <textarea id="edit_university_description" name="edit_university_description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_num_students">Number of Students:</label>
                        <input type="number" id="edit_num_students" name="edit_num_students" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_university_email_domain">Email Domain:</label>
                        <input type="text" id="edit_university_email_domain" name="edit_university_email_domain" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_university_pictures">Pictures (URLs, comma separated):</label>
                        <input type="text" id="edit_university_pictures" name="edit_university_pictures">
                    </div>
                    <span id="editUniversityResult"></span>
                    <div class="form-group">
                        <button type="button" onclick="updateUniversity()">Save Changes</button>
                        <button type="button" onclick="cancelEdit()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="my-rsos" class="tab-content">
            <h2>Registered Student Organizations (RSOs)</h2>
            <div id="rsoList">
                <p>Loading RSOs...</p>
            </div>
        </div>
        
        <div id="events" class="tab-content">
            <h2>Pending Public Events</h2>
            <div id="pendingEventsList" class="event-list">
                <p>Loading pending events...</p>
            </div>
        </div>
    </div>
    
    <div id="eventDetailsModal" class="modal-overlay">
        <div class="modal-container">
            <button class="modal-close" onclick="closeEventModal()">&times;</button>
            <div class="modal-header">
                <h2 id="modal-event-title">Event Details</h2>
            </div>
            <div class="modal-content">
                <p><strong>Date & Time:</strong> <span id="modal-event-datetime"></span></p>
                <p><strong>Category:</strong> <span id="modal-event-category"></span></p>
                <p><strong>Location:</strong> <span id="modal-event-location"></span></p>
                <p class="room-info" id="modal-room-container"><strong>Room:</strong> <span id="modal-event-room"></span></p>
                <p><strong>Contact:</strong> <span id="modal-event-contact"></span></p>
                <div>
                    <strong>Description:</strong>
                    <div id="modal-event-description"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="modal-approve-btn" class="approve-btn">Approve Event</button>
                <button id="modal-reject-btn" class="reject-btn">Reject Event</button>
                <button onclick="closeEventModal()">Close</button>
            </div>
        </div>
    </div>
    
    <script src="../js/superadmin.js"></script>
</body>
</html>