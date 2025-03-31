<?php
// Start session for authentication
session_start();

// Check if user is logged in and has student role
if (!isset($_SESSION['userID']) || $_SESSION['userRole'] !== 'student') {
    // Use JavaScript for redirection to maintain sessionStorage
    echo '<script>window.location.href = "index.html";</script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Events - Student Dashboard</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/student.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Student Dashboard</h1>
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
                <div class="event-card">
                    <h3>Spring Campus Festival</h3>
                    <div class="event-date">May 15, 2023 • 10:00 AM</div>
                    <div class="event-category">Social</div>
                    <div class="event-description">
                        Join us for a day of music, food, and fun at the annual Spring Campus Festival!
                    </div>
                    <div class="event-actions">
                        <div class="event-rating">
                            <div class="stars">★★★★☆</div>
                            <span>4.0</span>
                        </div>
                        <button class="event-button" onclick="viewEventDetails(1)">View Details</button>
                    </div>
                </div>
                
                <div class="event-card">
                    <h3>Coding Competition</h3>
                    <div class="event-date">June 5, 2023 • 9:00 AM</div>
                    <div class="event-category">Tech</div>
                    <div class="event-description">
                        Test your programming skills in our annual coding competition with prizes for top performers!
                    </div>
                    <div class="event-actions">
                        <div class="event-rating">
                            <div class="stars">★★★★★</div>
                            <span>4.8</span>
                        </div>
                        <button class="event-button" onclick="viewEventDetails(2)">View Details</button>
                    </div>
                </div>
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
                <div class="rso-card">
                    <h3>Computer Science Club</h3>
                    <div class="rso-status">Active</div>
                    <div class="rso-members">25 Members</div>
                    <div class="rso-description">
                        A community for students interested in computer science, programming, and technology.
                    </div>
                    <div class="rso-actions">
                        <button class="rso-button" onclick="viewRsoEvents(1)">View Events</button>
                        <button class="rso-button" onclick="leaveRso(1)">Leave RSO</button>
                    </div>
                </div>
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




<div id="user-profile" class="tab-content">
    <h2>My Profile</h2>
    
    <div class="profile-container">
        <!-- User Information Display Section -->
        <div class="profile-section">
            <h3>User Information</h3>
            <div class="user-info-display">
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span id="display-username">Loading...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">First Name:</span>
                    <span id="display-firstname">Loading...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Name:</span>
                    <span id="display-lastname">Loading...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current University:</span>
                    <span id="display-university">None</span>
                </div>
            </div>
        </div>

        <!-- Edit Profile Section -->
        <div class="profile-section">
            <h3>Edit Profile</h3>
            <form id="profileForm">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name">
                </div>
                
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                
                <div id="profileUpdateResult"></div>
                
                <div class="form-group">
                    <button type="button" onclick="updateProfile()">Update Profile</button>
                </div>
            </form>
        </div>
        
        <!-- University Selection Section -->
        <div class="profile-section full-width">
            <h3>Join a University</h3>
            <div class="form-group">
                <label for="university_select">Select University:</label>
                <select id="university_select">
                    <option value="">Select a University</option>
                    <!-- Universities will be loaded here -->
                </select>
            </div>
            
            <div id="universityJoinResult"></div>
            
            <div class="form-group">
                <button type="button" onclick="joinUniversity()">Join University</button>
            </div>
        </div>
    </div>
</div>



        
        <!-- Event Details Container (Hidden by default) -->
        <div id="event-details" class="event-detail-container">
            <button onclick="closeEventDetails()" style="float: right;">×</button>
            <h2 id="event-title">Event Title</h2>
            
            <div class="event-info">
                <div class="event-info-left">
                    <p><strong>Date & Time:</strong> <span id="event-datetime">May 15, 2023 • 10:00 AM</span></p>
                    <p><strong>Category:</strong> <span id="event-category">Social</span></p>
                    <p><strong>Location:</strong> <span id="event-location">Main Campus Quad</span></p>
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
                    <div class="comment-item">
                        <div class="comment-header">
                            <div class="comment-author">John Doe</div>
                            <div class="comment-date">May 10, 2023</div>
                        </div>
                        <div class="comment-content">
                            Looking forward to this event! It's going to be great.
                        </div>
                        <div class="comment-actions">
                            <button class="comment-button">Edit</button>
                            <button class="comment-button">Delete</button>
                        </div>
                    </div>
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
    <script src="../js/student_main.js"></script>
    <script src="../js/student_events.js"></script>
    <script src="../js/student_rsos.js"></script>
    <script src="../js/profile.js"></script>
</body>
</html>