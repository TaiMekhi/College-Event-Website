/**
 * Main JavaScript file for Student Dashboard
 * Handles core functionality and initialization
 */

// Check authentication on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - student_main.js');
    
    const userRole = sessionStorage.getItem('userRole');
    const userID = sessionStorage.getItem('userID');
    
    // Debug session values
    console.log('Session values:', {
        userID: userID,
        userRole: userRole
    });
    
    if (!userID) {
        console.error('No userID found in sessionStorage');
        alert('No user ID found. Redirecting to login page.');
        window.location.href = 'index.html';
        return;
    }
    
    if (userRole !== 'student' && userRole !== 'admin' && userRole !== 'superadmin') {
        console.error('Invalid userRole:', userRole);
        alert('Invalid user role. Redirecting to login page.');
        window.location.href = 'index.html';
        return;
    }
    
    // Set welcome message
    document.getElementById('welcomeUser').textContent = 'Welcome, Student!';
    
    // Load user info
    try {
        fetchUserInfo(userID);
    } catch (error) {
        console.error('Error fetching user info:', error);
    }
    
    // Setup tab navigation - must come first
    setupTabNavigation();
    
    // Load initial data
    try {
        loadPublicEvents();
        loadUniversityEvents();
        loadRsoEvents();
        loadUserRsos();
        loadAvailableRsos();
    } catch (error) {
        console.error('Error loading initial data:', error);
    }
    
    // Setup event rating stars
    try {
        setupRatingStars();
    } catch (error) {
        console.error('Error setting up rating stars:', error);
    }
});

/**
 * Sets up tab navigation functionality
 */
function setupTabNavigation() {
    console.log('Setting up tab navigation');
    
    const tabLinks = document.querySelectorAll('.tab-link');
    console.log(`Found ${tabLinks.length} tab links`);
    
    tabLinks.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log(`Tab clicked: ${this.textContent.trim()}`);
            
            const targetTab = this.dataset.tab;
            console.log(`Target tab: ${targetTab}`);
            
            // Update active tab link
            document.querySelectorAll('.tab-link').forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
            
            // Show target tab content
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.remove('active');
            });
            
            const tabContent = document.getElementById(targetTab);
            if (tabContent) {
                tabContent.classList.add('active');
                console.log(`Activated tab content: ${targetTab}`);
            } else {
                console.error(`Tab content not found with ID: ${targetTab}`);
            }
        });
    });
}

/**
 * Sets up event rating star functionality
 */
function setupRatingStars() {
    document.querySelectorAll('.rating-stars .star').forEach(function(star) {
        star.addEventListener('click', function() {
            const value = this.dataset.value;
            const stars = document.querySelectorAll('.rating-stars .star');
            
            stars.forEach(function(s) {
                if (s.dataset.value <= value) {
                    s.classList.add('selected');
                } else {
                    s.classList.remove('selected');
                }
            });
            
            // Submit rating to server
            submitEventRating(value);
        });
    });
}

/**
 * Fetch user information
 * @param {string} userID - The user's ID
 */
function fetchUserInfo(userID) {
    // Use a more reliable endpoint that matches your API structure
    fetch(`http://localhost/Cop4710_Project/WAMPAPI/api/users/details.php?id=${userID}`)
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.user) {
            const firstName = data.user.first_name || '';
            const lastName = data.user.last_name || '';
            document.getElementById('welcomeUser').textContent = `Welcome, ${firstName} ${lastName}!`;
            
            // Store in sessionStorage for future use
            sessionStorage.setItem('firstName', firstName);
            sessionStorage.setItem('lastName', lastName);
        } else {
            console.error('User data not found in response');
        }
    })
    .catch(error => {
        console.error('Error fetching user info:', error);
    });
}

/**
 * Log out the user
 */
function logout() {
    console.log('Logging out');
    
    // Send logout request to server if needed
    fetch('http://localhost/Cop4710_Project/WAMPAPI/api/auth/logout.php', {
        method: 'POST',
        credentials: 'include' // Include cookies
    })
    .catch(error => {
        console.error('Error during logout:', error);
    })
    .finally(() => {
        // Always clear session storage and redirect
        sessionStorage.clear();
        window.location.href = 'index.html';
    });
}