document.addEventListener('DOMContentLoaded', function() {
    const userRole = sessionStorage.getItem('userRole');
    const userID = sessionStorage.getItem('userID');
    
    if (!userID) {
        alert('No user ID found. Redirecting to login page.');
        window.location.href = 'index.html';
        return;
    }
    
    if (userRole !== 'student' && userRole !== 'admin' && userRole !== 'superadmin') {
        alert('Invalid user role. Redirecting to login page.');
        window.location.href = 'index.html';
        return;
    }
    
    document.getElementById('welcomeUser').textContent = 'Welcome, Student!';
    
    fetchUserInfo(userID);
    setupTabNavigation();
    
    loadPublicEvents();
    loadUniversityEvents();
    loadRsoEvents();
    loadUserRsos();
    loadAvailableRsos();
    
    setupRatingStars();
});

function setupTabNavigation() {
    const tabLinks = document.querySelectorAll('.tab-link');
    
    tabLinks.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.dataset.tab;
            
            document.querySelectorAll('.tab-link').forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.remove('active');
            });
            
            const tabContent = document.getElementById(targetTab);
            if (tabContent) {
                tabContent.classList.add('active');
            }
        });
    });
}

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
            
            submitEventRating(value);
        });
    });
}

function fetchUserInfo(userID) {
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
            
            sessionStorage.setItem('firstName', firstName);
            sessionStorage.setItem('lastName', lastName);
        }
    })
    .catch(error => {
        // Silent error
    });
}

function logout() {
    fetch('http://localhost/Cop4710_Project/WAMPAPI/api/auth/logout.php', {
        method: 'POST',
        credentials: 'include'
    })
    .catch(error => {
        // Silent error
    })
    .finally(() => {
        sessionStorage.clear();
        window.location.href = 'index.html';
    });
}