function loadUserRsos() {
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
        document.getElementById('userRsosList').innerHTML = '<p>Error loading RSOs. Please try again later.</p>';
    });
}

function displayUserRsos(rsos) {
    const container = document.getElementById('userRsosList');
    let html = '';
    
    rsos.forEach(rso => {
        const isAdmin = rso.role === 'admin';
        
        html += `<div class="rso-card">
                    <h3>${rso.name}</h3>
                    <div class="rso-status">${rso.status}</div>
                    <div class="rso-members">${rso.member_count} Members</div>
                    <div class="rso-description">${rso.description}</div>
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

function loadAvailableRsos() {
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
            
            if (errorMessage.includes('not associated with any university')) {
                errorMessage = 'You need to join a university before you can see or join RSOs. Please update your profile.';
            }
            
            document.getElementById('availableRsosList').innerHTML = `<p>Error: ${errorMessage}</p>`;
        }
    })
    .catch(error => {
        document.getElementById('availableRsosList').innerHTML = '<p>Error loading RSOs. Please try again later.</p>';
    });
}

function displayAvailableRsos(rsos) {
    const container = document.getElementById('availableRsosList');
    
    if (!rsos || rsos.length === 0) {
        container.innerHTML = '<p>No available RSOs found at your university.</p>';
        return;
    }
    
    let html = '';
    rsos.forEach(rso => {
        const statusClass = rso.status === 'Active' ? 'active-rso' : 'pending-rso';
        
        html += `<div class="rso-card ${statusClass}" data-status="${rso.status.toLowerCase()}">
                    <h3>${rso.name}</h3>
                    <div class="rso-status">${rso.status}</div>
                    <div class="rso-members">${rso.member_count} Members</div>
                    <div class="rso-description">${rso.description}</div>
                    <div class="rso-actions">
                        <button class="rso-button" onclick="joinRso(${rso.rso_id})">Join RSO</button>
                    </div>
                </div>`;
    });
    
    container.innerHTML = html;
}

function filterAvailableRsos() {
    const statusFilter = document.getElementById('rso-status-filter').value;
    const rsoCards = document.querySelectorAll('#availableRsosList .rso-card');
    
    rsoCards.forEach(function(card) {
        const status = card.dataset.status;
        card.style.display = (statusFilter === 'all' || status === statusFilter) ? 'block' : 'none';
    });
}

function createRso() {
    const userID = sessionStorage.getItem('userID');
    const createRsoResult = document.getElementById('createRsoResult');

    const rsoName = document.getElementById('rso_name').value.trim();
    const rsoDescription = document.getElementById('rso_description').value.trim();

    if (!rsoName || !rsoDescription || !userID) {
        createRsoResult.innerHTML = '<div class="error-message">Please fill in all fields and ensure you are logged in.</div>';
        return;
    }

    createRsoResult.innerHTML = '<div class="loading-message">Creating RSO...</div>';

    const formData = new FormData();
    formData.append('name', rsoName);
    formData.append('description', rsoDescription);
    formData.append('user_id', userID);

    fetch("/Cop4710_Project/WAMPAPI/api/rsos/create.php", {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            if (data.message && data.message.includes('university')) {
                createRsoResult.innerHTML = '<div class="error-message">You must be part of a university to create an RSO. Please update your profile first.</div>';
            } else {
                createRsoResult.innerHTML = `<div class="error-message">${data.message || 'You must be part of a university to create an RSO'}</div>`;
            }
            return Promise.reject(); // Stop here if RSO creation failed
        }

        document.getElementById('rso_name').value = '';
        document.getElementById('rso_description').value = '';

        createRsoResult.innerHTML = '<div class="success-message">RSO created successfully! Updating your permissions...</div>';

        return fetch("/Cop4710_Project/WAMPAPI/api/users/check_user_role.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ update_role: true })
        });
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            sessionStorage.setItem('userRole', 'admin');
            createRsoResult.innerHTML = '<div class="success-message">Session updated! Refreshing page...</div>';
            setTimeout(() => { window.location.reload(); }, 2000);
        } else {
            createRsoResult.innerHTML = '<div class="warning-message">RSO created, but session update failed.</div>';
        }
    })
    .catch(error => {
        if (!createRsoResult.innerHTML.includes('error-message')) {
            createRsoResult.innerHTML = '<div class="error-message">Error: Unable to complete operation.</div>';
        }
    });
}
function joinRso(rsoId) {
    const userID = sessionStorage.getItem('userID');
    
    const formData = new FormData();
    formData.append('user_id', userID);
    formData.append('rso_id', rsoId);
    
    fetch('/Cop4710_Project/WAMPAPI/api/rsos/join.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('You have successfully joined the RSO!');
            
            if (data.is_active) {
                alert('This RSO is now active with ' + data.member_count + ' members!');
            }
            
            loadUserRsos();
            loadAvailableRsos();
        } else {
            alert(`Failed to join RSO: ${data.error_message || 'Unknown error'}`);
        }
    })
    .catch(error => {
        alert('Error joining RSO. Please try again later.');
    });
}

function leaveRso(rsoId) {
    if (confirm('Are you sure you want to leave this RSO?')) {
        const userID = sessionStorage.getItem('userID');
        
        const formData = new FormData();
        formData.append('user_id', userID);
        formData.append('rso_id', rsoId);
        
        fetch('/Cop4710_Project/WAMPAPI/api/rsos/leave.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('You have left the RSO.');
                loadUserRsos();
                loadAvailableRsos();
            } else {
                alert(`Failed to leave RSO: ${data.error_message || 'Unknown error'}`);
            }
        })
        .catch(error => {
            alert('Error leaving RSO. Please try again later.');
        });
    }
}

function viewRsoEvents(rsoId) {
    document.querySelectorAll('.tab-link').forEach(function(tab) {
        if (tab.dataset.tab === 'rso-events') {
            tab.click();
            
            setTimeout(function() {
                const rsoFilter = document.getElementById('rso-event-filter');
                if (rsoFilter) {
                    rsoFilter.value = rsoId;
                    const event = new Event('change');
                    rsoFilter.dispatchEvent(event);
                }
            }, 500);
        }
    });
}

function manageRso(rsoId) {
    alert('RSO management functionality will be implemented in the admin dashboard.');
}

document.addEventListener('DOMContentLoaded', function() {
    const joinRsoTab = document.querySelector('.tab-link[data-tab="join-rso"]');
    if (joinRsoTab) {
        joinRsoTab.addEventListener('click', function() {
            loadAvailableRsos();
        });
    }
});