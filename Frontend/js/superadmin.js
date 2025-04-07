let currentEventId = null;

document.addEventListener('DOMContentLoaded', () => {
    const userRole = sessionStorage.getItem('userRole');
    const userID = sessionStorage.getItem('userID');

    if (userRole !== 'superadmin' || !userID) {
        window.location.href = '/Cop4710_Project/Frontend/pages/index.html';
        return;
    }

    const universityID = sessionStorage.getItem('universityID');
    if (!universityID) {
        checkUserUniversity(userID);
    } else {
        initializeDashboard(universityID);
    }

    setupTabs();
    
    
    document.getElementById('modal-approve-btn').addEventListener('click', () => {
        if (currentEventId) approveEvent(currentEventId);
    });
    
    document.getElementById('modal-reject-btn').addEventListener('click', () => {
        if (currentEventId) rejectEvent(currentEventId);
    });
});

function checkUserUniversity(userID) {
    fetch(`/Cop4710_Project/WAMPAPI/api/users/details.php?id=${userID}`)
        .then(response => response.ok ? response : fetch(`/Cop4710_Project/WAMPAPI/users.php?user_id=${userID}`))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.user?.university_id) {
                sessionStorage.setItem('universityID', data.user.university_id);
                initializeDashboard(data.user.university_id);
            }
        })
        .catch(() => initializeDashboard(null));
}

function initializeDashboard(universityID) {
    fetchUserInfo(sessionStorage.getItem('userID'));
    if (universityID) {
        loadMyUniversity(universityID);
        loadMyRSOs(universityID);
        loadPendingEvents(universityID);
    }
}

function setupTabs() {
    document.querySelectorAll('.tab-link').forEach(tab => {
        tab.addEventListener('click', e => {
            e.preventDefault();
            document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });
}

function fetchUserInfo(userID) {
    fetch(`/Cop4710_Project/WAMPAPI/api/users/details.php?id=${userID}`)
        .then(response => response.ok ? response : fetch(`/Cop4710_Project/WAMPAPI/users.php?user_id=${userID}`))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.user) {
                document.getElementById('welcomeUser').textContent = `Welcome, ${data.user.first_name || ''} ${data.user.last_name || ''}!`;
            }
        });
}

function loadMyUniversity(universityID) {
    fetch(`/Cop4710_Project/WAMPAPI/api/universities/universities.php?university_id=${universityID}`)
        .then(response => response.ok ? response : fetch(`/Cop4710_Project/WAMPAPI/university.php?university_id=${universityID}`))
        .then(res => res.json())
        .then(data => {
            const uni = data.university;
            if (data.success && uni) {
                document.getElementById('universityInfo').innerHTML = `
                    <button onclick="showEditForm()" class="edit-button">Edit University</button>
                    <h3>${uni.name}</h3>
                    <p><strong>Location:</strong> ${uni.location}</p>
                    <p><strong>Number of Students:</strong> ${uni.num_students}</p>
                    <p><strong>Email Domain:</strong> ${uni.email_domain}</p>
                    <p><strong>Description:</strong> ${uni.description}</p>
                    ${uni.pictures ? `<p><strong>Picture URLs:</strong> ${uni.pictures}</p>` : ''}
                `;

                document.getElementById('edit_university_id').value = uni.university_id;
                document.getElementById('edit_university_name').value = uni.name;
                document.getElementById('edit_university_location').value = uni.location;
                document.getElementById('edit_university_description').value = uni.description;
                document.getElementById('edit_num_students').value = uni.num_students;
                document.getElementById('edit_university_email_domain').value = uni.email_domain;
                document.getElementById('edit_university_pictures').value = uni.pictures || '';
            }
        });
}

function loadMyRSOs(universityID) {
    fetch(`/Cop4710_Project/WAMPAPI/api/universities/rsos.php?university_id=${universityID}`)
        .then(response => response.ok ? response : fetch(`/Cop4710_Project/WAMPAPI/rsos.php?university_id=${universityID}`))
        .then(res => res.json())
        .then(data => {
            const rsoList = document.getElementById('rsoList');
            if (data.success && data.rsos?.length) {
                rsoList.innerHTML = `
                    <table class="data-table">
                        <thead><tr><th>Name</th><th>Description</th><th>Members</th><th>Status</th><th>Admin</th></tr></thead>
                        <tbody>
                            ${data.rsos.map(rso => `
                                <tr>
                                    <td>${rso.name}</td>
                                    <td>${(rso.description || 'No description').slice(0, 100)}${(rso.description?.length > 100 ? '...' : '')}</td>
                                    <td>${rso.member_count || '0'}</td>
                                    <td>${rso.status || (rso.is_active ? 'Active' : 'Pending')}</td>
                                    <td>${rso.admin_name || 'N/A'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>`;
            } else {
                rsoList.innerHTML = '<p>No RSOs found for your university yet.</p>';
            }
        });
}

function loadPendingEvents(universityID) {
    fetch(`/Cop4710_Project/WAMPAPI/api/universities/events.php?status=pending&type=public&university_id=${universityID}`)
        .then(res => res.json())
        .then(data => {
            const pendingEventsList = document.getElementById('pendingEventsList');
            if (data.success && data.events?.length) {
                pendingEventsList.innerHTML = data.events.map(event => `
                    <div class="event-item" id="event-${event.event_id}">
                        <div class="event-info">
                            <h3>${event.name}</h3>
                            <p>${(event.description || 'No description').slice(0, 100)}${(event.description?.length > 100 ? '...' : '')}</p>
                            <p><strong>Date:</strong> ${event.date} at ${event.time || 'TBD'}</p>
                            <p><strong>Category:</strong> ${event.category || 'N/A'}</p>
                            <p><strong>University:</strong> ${event.university_name || 'N/A'}</p>
                        </div>
                        <div class="event-actions">
                            <button onclick="viewEventDetails(${event.event_id})">View Details</button>
                            <button onclick="approveEvent(${event.event_id})">Approve</button>
                            <button onclick="rejectEvent(${event.event_id})">Reject</button>
                        </div>
                    </div>
                `).join('');
            } else {
                pendingEventsList.innerHTML = '<p>No pending events found for your university.</p>';
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('pendingEventsList').innerHTML = '<p class="error-message">Error loading events.</p>';
        });
}

function approveEvent(eventId) {
    fetch('/Cop4710_Project/WAMPAPI/api/universities/events.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${eventId}&status=approved`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Event approved successfully.');
            closeEventModal();
            loadPendingEvents(sessionStorage.getItem('universityID'));
        } else {
            alert('Approval failed: ' + (data.error_message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while approving the event.');
    });
}

function rejectEvent(eventId) {
    if (!confirm("Are you sure you want to reject this event?")) return;

    fetch('/Cop4710_Project/WAMPAPI/api/universities/events.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${eventId}&status=rejected`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Event rejected.');
            closeEventModal();
            loadPendingEvents(sessionStorage.getItem('universityID'));
        } else {
            alert('Rejection failed: ' + (data.error_message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while rejecting the event.');
    });
}

function viewEventDetails(eventId) {
    currentEventId = eventId;
    
    fetch(`/Cop4710_Project/WAMPAPI/api/events/details.php?id=${eventId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.event) {
                const event = data.event;
                
                const eventDate = new Date(event.date);
                const formattedDate = eventDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                const formattedTime = event.time ? new Date(`2000-01-01T${event.time}`).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                }) : '';
                
                document.getElementById('modal-event-title').textContent = event.name;
                document.getElementById('modal-event-datetime').textContent = `${formattedDate}${formattedTime ? ' • ' + formattedTime : ''}`;
                document.getElementById('modal-event-category').textContent = event.category || 'N/A';
                document.getElementById('modal-event-location').textContent = event.location_name || 'N/A';
                document.getElementById('modal-event-contact').textContent = `${event.contact_email} | ${event.contact_phone}`;
                document.getElementById('modal-event-description').textContent = event.description || 'No description available';
                
                const roomContainer = document.getElementById('modal-room-container');
                if (event.room_number && event.room_number.trim() !== '') {
                    document.getElementById('modal-event-room').textContent = event.room_number;
                    roomContainer.style.display = '';
                } else {
                    roomContainer.style.display = 'none';
                }
                
                document.getElementById('eventDetailsModal').style.display = 'flex';
            } else {
                alert("Failed to load event details.");
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error fetching event details.");
        });
}

function closeEventModal() {
    document.getElementById('eventDetailsModal').style.display = 'none';
    currentEventId = null;
}


function logout() {
    fetch('/Cop4710_Project/WAMPAPI/api/auth/logout.php', { method: 'POST', credentials: 'include' })
        .finally(() => {
            sessionStorage.clear();
            window.location.href = '/Cop4710_Project/Frontend/pages/index.html';
        });
}