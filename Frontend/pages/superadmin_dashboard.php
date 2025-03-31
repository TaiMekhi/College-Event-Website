<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Events - Super Admin Dashboard</title>
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .dashboard-nav {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .dashboard-nav ul {
            display: flex;
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .dashboard-nav li {
            margin-right: 20px;
        }
        
        .dashboard-nav a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            padding: 5px 10px;
        }
        
        .dashboard-nav a.active {
            background-color: #4CAF50;
            color: white;
            border-radius: 3px;
        }
        
        .dashboard-content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .dashboard-card {
            flex: 1 1 300px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .form-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .form-container h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .event-list {
            width: 100%;
        }
        
        .event-item {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .event-actions button {
            margin-left: 10px;
        }
        
        .university-details {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .university-details h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .university-details .edit-button {
            float: right;
            margin-top: -45px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #f5f5f5;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
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
        
        <!-- My University Tab -->
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
        
        <!-- University RSOs Tab -->
        <div id="my-rsos" class="tab-content">
            <h2>Registered Student Organizations (RSOs)</h2>
            <div id="rsoList">
                <p>Loading RSOs...</p>
            </div>
        </div>
        
        <!-- Pending Events Tab -->
        <div id="events" class="tab-content">
            <h2>Pending Public Events</h2>
            <div id="pendingEventsList" class="event-list">
                <p>Loading pending events...</p>
            </div>
        </div>
    </div>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // Check authentication on page load
        document.addEventListener('DOMContentLoaded', function() {
            const userRole = sessionStorage.getItem('userRole');
            const userID = sessionStorage.getItem('userID');
            const universityID = sessionStorage.getItem('universityID');
            
            if (!userRole || userRole !== 'superadmin' || !userID) {
                window.location.href = 'index.html';
                return;
            }
            
            if (!universityID) {
                window.location.href = 'create_university.html';
                return;
            }
            
            // Load user info
            fetchUserInfo(userID);
            
            // Load initial data
            loadMyUniversity(universityID);
            loadMyRSOs(universityID);
            loadPendingEvents(universityID);
            
            // Setup tab navigation
            document.querySelectorAll('.tab-link').forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetTab = this.dataset.tab;
                    
                    // Update active tab link
                    document.querySelectorAll('.tab-link').forEach(function(t) {
                        t.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Show target tab content
                    document.querySelectorAll('.tab-content').forEach(function(content) {
                        content.classList.remove('active');
                    });
                    document.getElementById(targetTab).classList.add('active');
                });
            });
        });
        
        function fetchUserInfo(userID) {
            fetch(`/Cop4710_Project/WAMPAPI/users.php?user_id=${userID}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('welcomeUser').textContent = `Welcome, ${data.user.first_name}!`;
                }
            })
            .catch(error => {
                console.error('Error fetching user info:', error);
            });
        }
        
        function loadMyUniversity(universityID) {
            fetch(`/Cop4710_Project/WAMPAPI/universities.php?university_id=${universityID}`)
            .then(response => response.json())
            .then(data => {
                const universityInfo = document.getElementById('universityInfo');
                
                if (data.success) {
                    const uni = data.university;
                    
                    let html = `
                        <button onclick="showEditForm()" class="edit-button">Edit University</button>
                        <h3>${uni.name}</h3>
                        <p><strong>Location:</strong> ${uni.location}</p>
                        <p><strong>Number of Students:</strong> ${uni.num_students}</p>
                        <p><strong>Email Domain:</strong> ${uni.email_domain}</p>
                        <p><strong>Description:</strong> ${uni.description}</p>
                    `;
                    
                    if (uni.pictures) {
                        html += `<p><strong>Picture URLs:</strong> ${uni.pictures}</p>`;
                    }
                    
                    universityInfo.innerHTML = html;
                    
                    // Also pre-populate the edit form
                    document.getElementById('edit_university_id').value = uni.university_id;
                    document.getElementById('edit_university_name').value = uni.name;
                    document.getElementById('edit_university_location').value = uni.location;
                    document.getElementById('edit_university_description').value = uni.description;
                    document.getElementById('edit_num_students').value = uni.num_students;
                    document.getElementById('edit_university_email_domain').value = uni.email_domain;
                    document.getElementById('edit_university_pictures').value = uni.pictures || '';
                } else {
                    universityInfo.innerHTML = `<p>Error loading university details: ${data.error_message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('universityInfo').innerHTML = '<p>Error loading university details. Please try again later.</p>';
            });
        }
        
        function loadMyRSOs(universityID) {
            fetch(`/Cop4710_Project/WAMPAPI/rsos.php?university_id=${universityID}`)
            .then(response => response.json())
            .then(data => {
                const rsoList = document.getElementById('rsoList');
                
                if (data.success && data.rsos && data.rsos.length > 0) {
                    let html = '<table class="data-table">';
                    html += '<thead><tr><th>Name</th><th>Description</th><th>Members</th><th>Status</th><th>Admin</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.rsos.forEach(rso => {
                        html += `<tr>
                            <td>${rso.name}</td>
                            <td>${rso.description.substring(0, 100)}${rso.description.length > 100 ? '...' : ''}</td>
                            <td>${rso.member_count || '0'}</td>
                            <td>${rso.status}</td>
                            <td>${rso.admin_name || 'N/A'}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    rsoList.innerHTML = html;
                } else {
                    rsoList.innerHTML = '<p>No RSOs found for your university yet.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('rsoList').innerHTML = '<p>Error loading RSOs. Please try again later.</p>';
            });
        }
        
        function loadPendingEvents(universityID) {
            fetch(`/Cop4710_Project/WAMPAPI/events.php?status=pending&type=public&university_id=${universityID}`)
            .then(response => response.json())
            .then(data => {
                const pendingEventsList = document.getElementById('pendingEventsList');
                
                if (data.success && data.events && data.events.length > 0) {
                    let html = '';
                    
                    data.events.forEach(event => {
                        html += `<div class="event-item">
                            <div class="event-info">
                                <h3>${event.name}</h3>
                                <p>${event.description.substring(0, 100)}${event.description.length > 100 ? '...' : ''}</p>
                                <p><strong>Date:</strong> ${event.event_date} at ${event.event_time}</p>
                                <p><strong>Category:</strong> ${event.category}</p>
                            </div>
                            <div class="event-actions">
                                <button onclick="viewEventDetails(${event.id})">View Details</button>
                                <button onclick="approveEvent(${event.id})">Approve</button>
                                <button onclick="rejectEvent(${event.id})">Reject</button>
                            </div>
                        </div>`;
                    });
                    
                    pendingEventsList.innerHTML = html;
                } else {
                    pendingEventsList.innerHTML = '<p>No pending events found for your university.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                pendingEventsList.innerHTML = '<p>Error loading pending events. Please try again later.</p>';
            });
        }
        
        function showEditForm() {
            document.getElementById('universityDetails').style.display = 'none';
            document.getElementById('editUniversityForm').style.display = 'block';
        }
        
        function cancelEdit() {
            document.getElementById('universityDetails').style.display = 'block';
            document.getElementById('editUniversityForm').style.display = 'none';
            document.getElementById('editUniversityResult').innerHTML = '';
        }
        
        function updateUniversity() {
            const universityID = document.getElementById('edit_university_id').value;
            const name = document.getElementById('edit_university_name').value.trim();
            const location = document.getElementById('edit_university_location').value.trim();
            const description = document.getElementById('edit_university_description').value.trim();
            const numStudents = document.getElementById('edit_num_students').value.trim();
            const emailDomain = document.getElementById('edit_university_email_domain').value.trim();
            const pictures = document.getElementById('edit_university_pictures').value.trim();
            const editUniversityResult = document.getElementById('editUniversityResult');
            
            editUniversityResult.innerHTML = '';
            
            if (!name || !location || !description || !numStudents || !emailDomain) {
                editUniversityResult.innerHTML = '<p>Please fill out all required fields.</p>';
                return;
            }
            
            fetch('/Cop4710_Project/WAMPAPI/universities.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `university_id=${universityID}&name=${encodeURIComponent(name)}&location=${encodeURIComponent(location)}&description=${encodeURIComponent(description)}&num_students=${encodeURIComponent(numStudents)}&email_domain=${encodeURIComponent(emailDomain)}&pictures=${encodeURIComponent(pictures)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    editUniversityResult.innerHTML = '<p>University updated successfully!</p>';
                    setTimeout(() => {
                        cancelEdit();
                        loadMyUniversity(universityID);
                    }, 1500);
                } else {
                    editUniversityResult.innerHTML = `<p>Error: ${data.error_message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                editUniversityResult.innerHTML = '<p>An error occurred. Please try again.</p>';
            });
        }
        
        function viewEventDetails(eventId) {
            // Implement view event details functionality
            alert('View event ' + eventId + ' details functionality to be implemented');
        }
        
        function approveEvent(eventId) {
            fetch(`/Cop4710_Project/WAMPAPI/events.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${eventId}&status=approved`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Event approved successfully');
                    const universityID = sessionStorage.getItem('universityID');
                    loadPendingEvents(universityID);
                } else {
                    alert('Error: ' + data.error_message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while approving the event');
            });
        }
        
        function rejectEvent(eventId) {
            fetch(`/Cop4710_Project/WAMPAPI/events.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${eventId}&status=rejected`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Event rejected');
                    const universityID = sessionStorage.getItem('universityID');
                    loadPendingEvents(universityID);
                } else {
                    alert('Error: ' + data.error_message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while rejecting the event');
            });
        }
        
        function logout() {
            // Clear session storage
            sessionStorage.removeItem('userRole');
            sessionStorage.removeItem('userID');
            sessionStorage.removeItem('universityID');
            
            // Redirect to login page
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>