/**
 * JavaScript functions for RSO management in the Student Dashboard
 */

/**
 * Load user's RSOs
 */
function loadUserRsos() {
    // Show loading state
    document.getElementById('userRsosList').innerHTML = '<p>Loading RSOs...</p>';
    
    const userID = sessionStorage.getItem('userID');
    fetch(`/Cop4710_Project/WAMPAPI/api/rsos/user.php?user_id=${userID}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.rsos && data.rsos.length > 0) {
                displayUserRsos(data.rsos);
            } else {
                document.getElementById('userRsosList').innerHTML = '<p>You are not a member of any RSOs.</p>';
            }
        } else {
            document.getElementById('userRsosList').innerHTML = `<p>Error: ${data.error_message || 'Failed to load RSOs'}</p>`;
        }
    })
    .catch(error => {
        console.error('Error loading user RSOs:', error);
        document.getElementById('userRsosList').innerHTML = '<p>Error loading RSOs. Please try again later.</p>';
    });
}

/**
 * Display user's RSOs
 * @param {Array} rsos - Array of RSO objects
 */
function displayUserRsos(rsos) {
    const container = document.getElementById('userRsosList');
    let html = '';
    
    rsos.forEach(rso => {
        const isAdmin = rso.role === 'admin';
        
        html += `<div class="rso-card">
                    <h3>${rso.name}</h3>
                    <div class="rso-status">${rso.status}</div>
                    <div class="rso-members">${rso.member_count} Members</div>
                    <div class="rso-description">
                        ${rso.description}
                    </div>
                    <div class="rso-actions">
                        <button class="rso-button" onclick="viewRsoEvents(${rso.rso_id})">View Events</button>
                        ${isAdmin ? 
                            `<button class="rso-button admin-button" onclick="manageRso(${rso.rso_id})">Manage RSO</button>` : 
                            `<button class="rso-button" onclick="leaveRso(${rso.rso_id})">Leave RSO</button>`
                        }
                    </div>
                </div>`;
    });
    
    container.innerHTML = html;
}

/**
 * Load available RSOs to join
 */
function loadAvailableRsos() {
    // Show loading state
    document.getElementById('availableRsosList').innerHTML = '<p>Loading available RSOs...</p>';
    
    const userID = sessionStorage.getItem('userID');
    fetch(`/Cop4710_Project/WAMPAPI/api/rsos/available.php?user_id=${userID}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.rsos && data.rsos.length > 0) {
                displayAvailableRsos(data.rsos);
            } else {
                document.getElementById('availableRsosList').innerHTML = '<p>No available RSOs found at your university.</p>';
            }
        } else {
            let errorMessage = data.error_message || 'Failed to load RSOs';
            
            // Special case for no university
            if (errorMessage.includes('not associated with any university')) {
                errorMessage = 'You need to join a university before you can see or join RSOs. Please update your profile.';
            }
            
            document.getElementById('availableRsosList').innerHTML = `<p>Error: ${errorMessage}</p>`;
        }
    })
    .catch(error => {
        console.error('Error loading available RSOs:', error);
        document.getElementById('availableRsosList').innerHTML = '<p>Error loading RSOs. Please try again later.</p>';
    });
}

/**
 * Display available RSOs
 * @param {Array} rsos - Array of RSO objects
 */
function displayAvailableRsos(rsos) {
    const container = document.getElementById('availableRsosList');
    let html = '';
    
    if (rsos.length === 0) {
        container.innerHTML = '<p>No available RSOs found at your university.</p>';
        return;
    }
    
    rsos.forEach(rso => {
        const statusClass = rso.status === 'Active' ? 'active-rso' : 'pending-rso';
        
        html += `<div class="rso-card ${statusClass}" data-status="${rso.status.toLowerCase()}">
                    <h3>${rso.name}</h3>
                    <div class="rso-status">${rso.status}</div>
                    <div class="rso-members">${rso.member_count} Members</div>
                    <div class="rso-description">
                        ${rso.description}
                    </div>
                    <div class="rso-actions">
                        <button class="rso-button" onclick="joinRso(${rso.rso_id})">Join RSO</button>
                    </div>
                </div>`;
    });
    
    container.innerHTML = html;
}

/**
 * Filter available RSOs by status
 */
function filterAvailableRsos() {
    const statusFilter = document.getElementById('rso-status-filter').value;
    const rsoCards = document.querySelectorAll('#availableRsosList .rso-card');
    
    rsoCards.forEach(function(card) {
        const status = card.dataset.status;
        
        if (statusFilter === 'all' || status === statusFilter) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Create a new RSO
 */
function createRso() {
    const rsoName = document.getElementById('rso_name').value.trim();
    const rsoDescription = document.getElementById('rso_description').value.trim();
    
    // Check if all fields are filled
    if (!rsoName || !rsoDescription) {
        document.getElementById('createRsoResult').innerHTML = '<p class="error">Please fill out all fields.</p>';
        return;
    }
    
    const userID = sessionStorage.getItem('userID');
    
    // Show loading message
    document.getElementById('createRsoResult').innerHTML = '<p>Creating RSO...</p>';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('user_id', userID);
    formData.append('name', rsoName);
    formData.append('description', rsoDescription);
    
    // Submit RSO creation request
    fetch('/Cop4710_Project/WAMPAPI/api/rsos/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset form
            document.getElementById('createRsoForm').reset();
            
            let message = '<p class="success">RSO created successfully.</p>';
            
            if (!data.is_active) {
                message += `<p>The RSO needs ${data.members_needed} more members to become active.</p>`;
            }
            
            document.getElementById('createRsoResult').innerHTML = message;
            
            // Reload RSO lists
            loadUserRsos();
            loadAvailableRsos();
        } else {
            document.getElementById('createRsoResult').innerHTML = 
                `<p class="error">Failed to create RSO: ${data.error_message || 'Unknown error'}</p>`;
        }
    })
    .catch(error => {
        console.error('Error creating RSO:', error);
        document.getElementById('createRsoResult').innerHTML = 
            '<p class="error">Error creating RSO. Please try again later.</p>';
    });
}

/**
 * Join an RSO directly (no request/approval needed)
 * @param {number} rsoId - The RSO ID
 */
function joinRso(rsoId) {
    const userID = sessionStorage.getItem('userID');
    
    // Prepare form data
    const formData = new FormData();
    formData.append('user_id', userID);
    formData.append('rso_id', rsoId);
    
    // Submit join request
    fetch('/Cop4710_Project/WAMPAPI/api/rsos/join.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('You have successfully joined the RSO!');
            
            // If RSO is now active, show a message
            if (data.is_active) {
                alert('This RSO is now active with ' + data.member_count + ' members!');
            }
            
            // Reload RSO lists
            loadUserRsos();
            loadAvailableRsos();
        } else {
            alert(`Failed to join RSO: ${data.error_message || 'Unknown error'}`);
        }
    })
    .catch(error => {
        console.error('Error joining RSO:', error);
        alert('Error joining RSO. Please try again later.');
    });
}

/**
 * Leave an RSO
 * @param {number} rsoId - The RSO ID
 */
function leaveRso(rsoId) {
    if (confirm('Are you sure you want to leave this RSO?')) {
        const userID = sessionStorage.getItem('userID');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('user_id', userID);
        formData.append('rso_id', rsoId);
        
        // Submit leave request
        fetch('/Cop4710_Project/WAMPAPI/api/rsos/leave.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('You have left the RSO.');
                
                // Reload RSO lists
                loadUserRsos();
                loadAvailableRsos();
            } else {
                alert(`Failed to leave RSO: ${data.error_message || 'Unknown error'}`);
            }
        })
        .catch(error => {
            console.error('Error leaving RSO:', error);
            alert('Error leaving RSO. Please try again later.');
        });
    }
}

/**
 * View events for a specific RSO
 * @param {number} rsoId - The RSO ID
 */
function viewRsoEvents(rsoId) {
    // Switch to RSO events tab and filter by this RSO
    document.querySelectorAll('.tab-link').forEach(function(tab) {
        if (tab.dataset.tab === 'rso-events') {
            tab.click();
            
            // Set the RSO filter to this RSO
            setTimeout(function() {
                const rsoFilter = document.getElementById('rso-event-filter');
                if (rsoFilter) {
                    rsoFilter.value = rsoId;
                    
                    // Trigger filter change event
                    const event = new Event('change');
                    rsoFilter.dispatchEvent(event);
                }
            }, 500); // Wait for tab content to load
        }
    });
}

/**
 * Manage an RSO (for admins)
 * @param {number} rsoId - The RSO ID
 */
function manageRso(rsoId) {
    alert('RSO management functionality will be implemented in the admin dashboard.');
}

// Add load functions to DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    // Add tab click handler for the join RSO tab
    const joinRsoTab = document.querySelector('.tab-link[data-tab="join-rso"]');
    if (joinRsoTab) {
        joinRsoTab.addEventListener('click', function() {
            loadAvailableRsos();
        });
    }
});