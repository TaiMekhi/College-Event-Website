/**
 * JavaScript functions for User Profile management with email support
 */

/**
 * Load user profile data
 */
function loadUserProfile() {
    const userID = sessionStorage.getItem('userID');
    
    if (!userID) {
        console.error('No user ID found in session storage');
        return;
    }
    
    fetch(`/Cop4710_Project/WAMPAPI/api/users/details.php?id=${userID}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.user) {
            const user = data.user;
            
            // Update display section
            document.getElementById('display-username').textContent = user.user_name || '';
            document.getElementById('display-firstname').textContent = user.first_name || '';
            document.getElementById('display-lastname').textContent = user.last_name || '';
            document.getElementById('display-email').textContent = user.email || 'Not set';
            document.getElementById('display-university').textContent = user.university_name || 'None';
            
            // Populate form fields
            document.getElementById('first_name').value = user.first_name || '';
            document.getElementById('last_name').value = user.last_name || '';
            document.getElementById('email').value = user.email || '';
            
            // Store university ID for reference
            if (user.university_id) {
                document.getElementById('display-university').dataset.universityId = user.university_id;
            }
        } else {
            console.error('Failed to load user profile data');
            document.getElementById('profileUpdateResult').innerHTML = '<p class="error">Failed to load profile data</p>';
        }
    })
    .catch(error => {
        console.error('Error loading user profile:', error);
        document.getElementById('profileUpdateResult').innerHTML = '<p class="error">Error loading profile data</p>';
    });
}

/**
 * Load universities
 */
function loadUniversities() {
    fetch('/Cop4710_Project/WAMPAPI/api/universities/list.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.universities && data.universities.length > 0) {
            const select = document.getElementById('university_select');
            
            // Clear existing options except the first one
            while (select.options.length > 1) {
                select.remove(1);
            }
            
            // Add university options
            data.universities.forEach(university => {
                const option = document.createElement('option');
                option.value = university.university_id;
                option.textContent = university.name;
                option.dataset.domain = university.email_domain; // Store domain for reference
                select.appendChild(option);
            });
        } else {
            console.error('Failed to load universities');
        }
    })
    .catch(error => {
        console.error('Error loading universities:', error);
    });
}

/**
 * Show domain requirement for selected university
 */
function showDomainRequirement() {
    const universitySelect = document.getElementById('university_select');
    const selectedOption = universitySelect.options[universitySelect.selectedIndex];
    const emailDomain = selectedOption.dataset.domain;
    const domainInfo = document.getElementById('university-domain-info');
    
    if (emailDomain && universitySelect.value !== '') {
        domainInfo.innerHTML = `<p class="info-message">This university requires an email with domain: <strong>@${emailDomain}</strong></p>`;
        domainInfo.style.display = 'block';
    } else {
        domainInfo.style.display = 'none';
    }
}

/**
 * Update user profile
 */
function updateProfile() {
    const userID = sessionStorage.getItem('userID');
    
    if (!userID) {
        console.error('No user ID found in session storage');
        return;
    }
    
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Validate form
    if (!firstName) {
        document.getElementById('profileUpdateResult').innerHTML = '<p class="error">First name is required</p>';
        return;
    }
    
    if (!email || !validateEmail(email)) {
        document.getElementById('profileUpdateResult').innerHTML = '<p class="error">Please enter a valid email address</p>';
        return;
    }
    
    // Check if passwords match (if provided)
    if (password && password !== confirmPassword) {
        document.getElementById('profileUpdateResult').innerHTML = '<p class="error">Passwords do not match</p>';
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('user_id', userID);
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('email', email);
    
    // Only include password if provided
    if (password) {
        formData.append('password', password);
    }
    
    // Show loading message
    document.getElementById('profileUpdateResult').innerHTML = '<p>Updating profile...</p>';
    
    // Submit update
    fetch('/Cop4710_Project/WAMPAPI/api/users/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('profileUpdateResult').innerHTML = '<p class="success">Profile updated successfully</p>';
            
            // Update display fields
            document.getElementById('display-firstname').textContent = firstName;
            document.getElementById('display-lastname').textContent = lastName;
            document.getElementById('display-email').textContent = email;
            
            // Update session storage with new name
            sessionStorage.setItem('firstName', firstName);
            sessionStorage.setItem('lastName', lastName);
            
            // Update welcome message
            const welcomeElement = document.getElementById('welcomeUser');
            if (welcomeElement) {
                welcomeElement.textContent = `Welcome, ${firstName} ${lastName}!`;
            }
        } else {
            document.getElementById('profileUpdateResult').innerHTML = `<p class="error">Failed to update profile: ${data.error_message || 'Unknown error'}</p>`;
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        document.getElementById('profileUpdateResult').innerHTML = '<p class="error">Error updating profile</p>';
    });
}

/**
 * Join a university
 */
function joinUniversity() {
    const userID = sessionStorage.getItem('userID');
    
    if (!userID) {
        console.error('No user ID found in session storage');
        return;
    }
    
    const universityID = document.getElementById('university_select').value;
    
    if (!universityID) {
        document.getElementById('universityJoinResult').innerHTML = '<p class="error">Please select a university</p>';
        return;
    }
    
    // Check if email is set
    const userEmail = document.getElementById('display-email').textContent;
    if (userEmail === 'Not set') {
        document.getElementById('universityJoinResult').innerHTML = '<p class="error">Please set your email address in the profile section before joining a university</p>';
        return;
    }
    
    // Show loading message
    document.getElementById('universityJoinResult').innerHTML = '<p>Joining university...</p>';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('user_id', userID);
    formData.append('university_id', universityID);
    
    // Submit request
    fetch('/Cop4710_Project/WAMPAPI/api/users/join_university.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('universityJoinResult').innerHTML = '<p class="success">Successfully joined university</p>';
            
            // Update university display
            document.getElementById('display-university').textContent = data.university_name || 'Unknown';
            document.getElementById('display-university').dataset.universityId = universityID;
            
            // Reload university events
            if (typeof loadUniversityEvents === 'function') {
                loadUniversityEvents();
            }
        } else {
            document.getElementById('universityJoinResult').innerHTML = `<p class="error">Failed to join university: ${data.error_message || 'Unknown error'}</p>`;
        }
    })
    .catch(error => {
        console.error('Error joining university:', error);
        document.getElementById('universityJoinResult').innerHTML = '<p class="error">Error joining university</p>';
    });
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} True if valid email format
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Add load functions to DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    // Add tab click handler for the profile tab
    const profileTab = document.querySelector('.tab-link[data-tab="user-profile"]');
    if (profileTab) {
        profileTab.addEventListener('click', function() {
            loadUserProfile();
            loadUniversities();
        });
    }
    
    // Add university select change handler to show domain requirement
    const universitySelect = document.getElementById('university_select');
    if (universitySelect) {
        universitySelect.addEventListener('change', showDomainRequirement);
    }
});