<?php
// Start session for authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    // Use JavaScript for redirection to maintain sessionStorage
    echo '<script>window.location.href = "index.html";</script>';
    exit();
}

// Get user role
$userRole = $_SESSION['userRole'] ?? 'student';

// Redirect superadmins to their own dashboard
if ($userRole === 'superadmin') {
    echo '<script>window.location.href = "superadmin_dashboard.php";</script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Events - Dashboard</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/student.css" rel="stylesheet">
    <?php if ($userRole === 'admin'): ?>
    <link href="../css/admin.css" rel="stylesheet">
    <?php endif; ?>
    <!-- Add Leaflet CSS and JS for maps -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCte7_5_UgEqB-1_3j0ZTlQGI0aTHKkirc&libraries=places"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        /* Map-related styles */
        .loading {
            background-image: url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==');
            background-repeat: no-repeat;
            background-position: right center;
            background-size: 20px 20px;
            padding-right: 25px;
        }
        #create-event-map, #event-map {
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><?php echo ucfirst($userRole); ?> Dashboard</h1>
            <div>
                <span id="welcomeUser"></span>
                <button onclick="logout()" class="logout-btn">Logout</button>
            </div>
        </div>
        
        <div class="dashboard-nav">
            <ul>
                <li><a href="#" class="tab-link active" data-tab="public-events">Public Events</a></li>
                <li><a href="#" class="tab-link" data-tab="university-events">University Events</a></li>
                <li><a href="#" class="tab-link" data-tab="rso-events">RSO Events</a></li>
                <li><a href="#" class="tab-link" data-tab="my-rsos">My RSOs</a></li>
                <li><a href="#" class="tab-link" data-tab="join-rso">Join/Create RSO</a></li>
                <li><a href="#" class="tab-link" data-tab="user-profile">My Profile</a></li>
                <li><a href="#" class="tab-link" data-tab="create-event">Create Event</a></li>
                
                <!-- Admin-only tabs -->
                <?php if ($userRole === 'admin'): ?>
                <li><a href="#" class="tab-link" data-tab="manage-rso">Manage RSO</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Public Events Tab -->
        <div id="public-events" class="tab-content active">
            <h2>Public Events</h2>
            <div class="event-filter">
                <label for="public-event-filter">Filter by:</label>
                <select id="public-event-filter">
                    <option value="all">All Categories</option>
                    <option value="social">Social</option>
                    <option value="fundraising">Fundraising</option>
                    <option value="tech">Tech Talks</option>
                </select>
            </div>
            <div id="publicEventsList" class="event-list">
                <!-- Event cards will be loaded here -->
                <p>Loading public events...</p>
            </div>
        </div>
        
        <!-- University Events Tab -->
        <div id="university-events" class="tab-content">
            <h2>University Events</h2>
            <div class="event-filter">
                <label for="university-event-filter">Filter by:</label>
                <select id="university-event-filter">
                    <option value="all">All Categories</option>
                    <option value="social">Social</option>
                    <option value="fundraising">Fundraising</option>
                    <option value="tech">Tech Talks</option>
                </select>
            </div>

            <div id="no-university-message" style="display:none;">
                <p>You are not associated with any university. Please update your profile to join a university.</p>
            </div>

            <div id="universityEventsList" class="event-list">
                <!-- Event cards will be loaded here -->
            </div>
        </div>
        
        <!-- RSO Events Tab -->
        <div id="rso-events" class="tab-content">
            <h2>RSO Events</h2>
            <div class="event-filter">
                <label for="rso-event-filter">Filter by:</label>
                <select id="rso-event-filter">
                    <option value="all">All RSOs</option>
                    <!-- RSO options will be loaded here -->
                </select>
                <select id="rso-category-filter">
                    <option value="all">All Categories</option>
                    <option value="social">Social</option>
                    <option value="fundraising">Fundraising</option>
                    <option value="tech">Tech Talks</option>
                </select>
            </div>
            <div id="rsoEventsList" class="event-list">
                <!-- Event cards will be loaded here -->
                <div id="no-rso-message" style="display:none;">
                    <p>You are not a member of any RSOs. Join an RSO to see events.</p>
                </div>
            </div>
        </div>
        
        <!-- My RSOs Tab -->
        <div id="my-rsos" class="tab-content">
            <h2>My RSOs</h2>
            <div id="userRsosList" class="rso-list">
                <!-- RSO cards will be loaded here -->
                <p>Loading your RSOs...</p>
            </div>
        </div>
        
        <!-- Join/Create RSO Tab -->
        <div id="join-rso" class="tab-content">
            <div class="create-rso-container">
                <h2>Create New RSO</h2>
                <p>To create a new RSO, you need at least 4 other students from your university to join before it becomes active.</p>
                <form id="createRsoForm">
                    <div class="form-group">
                        <label for="rso_name">RSO Name:</label>
                        <input type="text" id="rso_name" name="rso_name" required>
                    </div>
                    <div class="form-group">
                        <label for="rso_description">Description:</label>
                        <textarea id="rso_description" name="rso_description" required></textarea>
                    </div>
                    <div id="createRsoResult"></div>
                    <div class="form-group">
                        <button type="button" onclick="createRso()">Create RSO</button>
                    </div>
                </form>
            </div>
            
            <div class="form-container">
                <h2>Available RSOs</h2>
                <div class="filter-container">
                    <div class="form-group">
                        <label for="rso-status-filter">Filter by status:</label>
                        <select id="rso-status-filter" onchange="filterAvailableRsos()">
                            <option value="all">All RSOs</option>
                            <option value="active">Active</option>
                            <option value="pending">Pending Activation</option>
                        </select>
                    </div>
                </div>
                <div id="availableRsosList" class="rso-list">
                    <!-- Available RSO cards will be loaded here -->
                    <p>Loading available RSOs...</p>
                </div>
            </div>
        </div>

        <!-- User Profile Section -->
        <div id="user-profile" class="tab-content">
            <div class="section-container">
                <h2>Your Profile</h2>
                
                <!-- Profile Display -->
                <div id="profile-display" class="profile-section">
                    <div class="profile-info">
                        <p><strong>Username:</strong> <span id="display-username"></span></p>
                        <p><strong>First Name:</strong> <span id="display-firstname"></span></p>
                        <p><strong>Last Name:</strong> <span id="display-lastname"></span></p>
                        <p><strong>Email:</strong> <span id="display-email"></span></p>
                        <p><strong>University:</strong> <span id="display-university"></span></p>
                    </div>
                </div>
                
                <!-- Profile Update Form -->
                <div class="profile-section">
                    <h3>Update Profile</h3>
                    <form id="profileUpdateForm" class="profile-form">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep current):</label>
                            <input type="password" id="password" name="password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" onclick="updateProfile()">Update Profile</button>
                        </div>
                        
                        <div id="profileUpdateResult"></div>
                    </form>
                </div>
                
                <!-- University Join Form -->
                <div class="profile-section">
                    <h3>Join University</h3>
                    <form id="universityJoinForm" class="profile-form">
                        <div class="form-group">
                            <label for="university_select">Select University:</label>
                            <select id="university_select" name="university_select" required>
                                <option value="">Select a university</option>
                            </select>
                        </div>
                        
                        <div id="university-domain-info" style="display: none;"></div>
                        
                        <div class="form-actions">
                            <button type="button" onclick="joinUniversity()">Join University</button>
                        </div>
                        
                        <div id="universityJoinResult"></div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Event Tab (Available to all users) -->
        <div id="create-event" class="tab-content">
            <h2>Create New Event</h2>
            
            <div class="notice-box">
                <?php if ($userRole === 'student'): ?>
                <p><strong>Note:</strong> Student-created events are public events that require approval by a Super Admin before they appear in the events listing.</p>
                <?php elseif ($userRole === 'admin'): ?>
                <p><strong>Note:</strong> RSO events can only be created for active RSOs (those with 5+ members). Your inactive RSOs will not appear in the RSO selection.</p>
                <?php endif; ?>
            </div>
            
            <form id="createEventForm" class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_name">Event Name:</label>
                        <input type="text" id="event_name" name="event_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_category">Category:</label>
                        <select id="event_category" name="event_category" required>
                            <option value="">Select Category</option>
                            <option value="social">Social</option>
                            <option value="fundraising">Fundraising</option>
                            <option value="tech">Tech Talks</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_description">Description:</label>
                    <textarea id="event_description" name="event_description" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Date:</label>
                        <input type="date" id="event_date" name="event_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_time">Time:</label>
                        <input type="time" id="event_time" name="event_time" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_location">Location/Building Name:</label>
                    <input type="text" id="event_location" name="event_location" required placeholder="e.g., Engineering Building 1, Student Union, Reflection Pond">
                </div>
                
                <div class="form-group">
                    <label for="event_room">Room Number (optional):</label>
                    <input type="text" id="event_room" name="event_room" placeholder="e.g., 201">
                </div>
                
                <div class="form-group">
                    <label>Select Location on Map:</label>
                    <div id="create-event-map" style="height: 300px; width: 100%; margin: 10px 0;"></div>
                    <!-- Hidden fields for coordinates -->
                    <input type="hidden" id="event_latitude" name="event_latitude">
                    <input type="hidden" id="event_longitude" name="event_longitude">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_contact_phone">Contact Phone:</label>
                        <input type="tel" id="event_contact_phone" name="event_contact_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_contact_email">Contact Email:</label>
                        <input type="email" id="event_contact_email" name="event_contact_email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_type">Event Type:</label>
                    <?php if ($userRole === 'student'): ?>
                    <input type="hidden" id="event_type" name="event_type" value="public">
                    <p>As a student, you can create public events which will require approval by a Super Admin.</p>
                    <?php else: ?>
                    <select id="event_type" name="event_type" required onchange="toggleRsoSelection()">
                        <option value="">Select Type</option>
                        <option value="public">Public Event</option>
                        <option value="private">University (Private) Event</option>
                        <option value="rso">RSO Event</option>
                    </select>
                    <?php endif; ?>
                </div>
                
                <?php if ($userRole !== 'student'): ?>
                <div class="form-group" id="rso-selection-container" style="display: none;">
                    <label for="event_rso">Select RSO:</label>
                    <select id="event_rso" name="event_rso">
                        <option value="">Select RSO</option>
                        <!-- Admin RSOs will be loaded here -->
                    </select>
                </div>
                <?php endif; ?>
                
                <div id="createEventResult"></div>
                
                <div class="form-group">
                    <button type="button" id="create-event-btn">
                        <?php echo ($userRole === 'student') ? 'Submit for Approval' : 'Create Event'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Admin-only tabs content -->
        <?php if ($userRole === 'admin'): ?>
        <!-- Manage RSO Tab -->
        <div id="manage-rso" class="tab-content">
            <h2>Manage Your RSOs</h2>
            
            <div class="filter-container">
                <div class="form-group">
                    <label for="manage-rso-select">Select RSO to Manage:</label>
                    <select id="manage-rso-select" onchange="loadRsoMembers()">
                        <option value="">Select an RSO</option>
                        <!-- Admin RSOs will be loaded here -->
                    </select>
                </div>
            </div>
            
            <div id="rso-management-container" style="display: none;">
                <div class="admin-section">
                    <h3>RSO Details</h3>
                    <div id="rso-details" class="details-container">
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span id="rso-name-display">Loading...</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Description:</span>
                            <span id="rso-description-display">Loading...</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span id="rso-status-display">Loading...</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">University:</span>
                            <span id="rso-university-display">Loading...</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Member Count:</span>
                            <span id="rso-member-count-display">Loading...</span>
                        </div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <h3>RSO Members</h3>
                    <div class="table-container">
                        <table id="rso-members-table" class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rso-members-list">
                                <!-- Members will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="no-admin-rsos-message" style="display:none;">
                <p>You are not an administrator of any RSOs. Create or join an RSO to become an administrator.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Event Details Container (Hidden by default) -->
        <div id="event-details" class="event-detail-container">
            <button onclick="closeEventDetails()" style="float: right;">×</button>
            <h2 id="event-title">Event Title</h2>
            
            <div class="event-info">
                <div class="event-info-left">
                    <p><strong>Date & Time:</strong> <span id="event-datetime">May 15, 2023 • 10:00 AM</span></p>
                    <p><strong>Category:</strong> <span id="event-category">Social</span></p>
                    <p><strong>Location:</strong> <span id="event-location">Main Campus Quad</span></p>
                    <p><strong>Room:</strong> <span id="event-room">Main Campus Quad</span></p>
                    <p><strong>Contact:</strong> <span id="event-contact">events@university.edu | (123) 456-7890</span></p>
                    <div id="event-description">
                        Event description will be displayed here.
                    </div>
                </div>
                <div class="event-info-right">
                    <div id="event-map" class="event-map">
                        Map will be displayed here
                    </div>
                    <div>
                        <p><strong>Rate this event:</strong></p>
                        <div class="rating-stars" id="event-rating">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="event-comments">
                <h3>Comments</h3>
                <div id="comments-list">
                    <!-- Comments will be loaded here -->
                    <p>Loading comments...</p>
                </div>
                
                <div class="comment-form">
                    <div class="form-group">
                        <label for="comment-text">Add a comment:</label>
                        <textarea id="comment-text" name="comment" required></textarea>
                    </div>
                    <div class="form-group">
                        <button type="button" onclick="addComment()">Post Comment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include JavaScript files -->
    <script src="../js/map.js"></script>
    <script src="../js/student_main.js"></script>
    <script src="../js/student_events.js"></script>
    <script src="../js/student_rsos.js"></script>
    <script src="../js/profile.js"></script>
    
    <!-- Include admin JavaScript only for admins -->
    <?php if ($userRole === 'admin'): ?>
    <script src="../js/admin.js"></script>
    <?php endif; ?>

    <script>
        // Attach event listener to the Create Event button
        document.addEventListener('DOMContentLoaded', function() {
            const createEventBtn = document.getElementById('create-event-btn');
            if (createEventBtn) {
                createEventBtn.addEventListener('click', submitEventForm);
            }
            
            // Initialize map for create event tab if it's the initial active tab
            if (document.querySelector('#create-event.active')) {
                setTimeout(initCreateEventMap, 100);
            }
        });
    </script>
</body>
</html>