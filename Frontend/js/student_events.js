/**
 * JavaScript functions for handling events in the Student Dashboard
 */

// Current event ID for event details view
let currentEventId = null;

/**
 * Load public events from the API
 */
function loadPublicEvents() {
    // Show loading state
    document.getElementById('publicEventsList').innerHTML = '<p>Loading events...</p>';
    
    // Fetch public events
    fetch('/Cop4710_Project/WAMPAPI/api/events/public.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.events && data.events.length > 0) {
            displayEvents('publicEventsList', data.events);
        } else {
            document.getElementById('publicEventsList').innerHTML = '<p>No public events found.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading public events:', error);
        document.getElementById('publicEventsList').innerHTML = '<p>Error loading events. Please try again later.</p>';
    });
}

/**
 * Load university events for the current user
 */
function loadUniversityEvents() {
    // Show loading state
    document.getElementById('universityEventsList').innerHTML = '<p>Loading events...</p>';
    
    const userID = sessionStorage.getItem('userID');
    fetch(`/Cop4710_Project/WAMPAPI/api/events/university.php?user_id=${userID}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.university_id) {
                if (data.events && data.events.length > 0) {
                    displayEvents('universityEventsList', data.events);
                } else {
                    document.getElementById('universityEventsList').innerHTML = '<p>No university events found.</p>';
                }
            } else {
                document.getElementById('no-university-message').style.display = 'block';
                document.getElementById('universityEventsList').innerHTML = '';
            }
        } else {
            document.getElementById('universityEventsList').innerHTML = `<p>Error: ${data.error_message || 'Failed to load events'}</p>`;
        }
    })
    .catch(error => {
        console.error('Error loading university events:', error);
        document.getElementById('universityEventsList').innerHTML = '<p>Error loading events. Please try again later.</p>';
    });
}

/**
 * Load RSO events for the current user
 */
function loadRsoEvents() {
    // Show loading state
    document.getElementById('rsoEventsList').innerHTML = '<p>Loading events...</p>';
    
    // Fix for the null element error - check if element exists before using it
    const noRsoMessage = document.getElementById('no-rso-message');
    if (noRsoMessage) {
        noRsoMessage.style.display = 'none';
    }
    
    const userID = sessionStorage.getItem('userID');
    fetch(`/Cop4710_Project/WAMPAPI/api/events/rso.php?user_id=${userID}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.rsos && data.rsos.length > 0) {
                // Populate RSO filter dropdown
                populateRsoFilter(data.rsos);
                
                if (data.events && data.events.length > 0) {
                    displayEvents('rsoEventsList', data.events, true);
                } else {
                    document.getElementById('rsoEventsList').innerHTML = '<p>No RSO events found.</p>';
                }
            } else {
                // Check if element exists before using it
                if (noRsoMessage) {
                    noRsoMessage.style.display = 'block';
                }
                document.getElementById('rsoEventsList').innerHTML = '';
            }
        } else {
            document.getElementById('rsoEventsList').innerHTML = `<p>Error: ${data.error_message || 'Failed to load events'}</p>`;
        }
    })
    .catch(error => {
        console.error('Error loading RSO events:', error);
        document.getElementById('rsoEventsList').innerHTML = '<p>Error loading events. Please try again later.</p>';
    });
}

// Rest of your functions remain the same until viewEventDetails

/**
 * View event details
 * @param {number} eventId - The event ID
 */
function viewEventDetails(eventId) {
    currentEventId = eventId;
    
    // Show loading state
    document.getElementById('event-title').textContent = 'Loading event details...';
    document.getElementById('event-datetime').textContent = '';
    document.getElementById('event-category').textContent = '';
    document.getElementById('event-location').textContent = '';
    document.getElementById('event-contact').textContent = '';
    document.getElementById('event-description').textContent = '';
    document.getElementById('comments-list').innerHTML = '<p>Loading comments...</p>';
    
    // Show event details container
    document.getElementById('event-details').style.display = 'block';
    
    // Scroll to details
    document.getElementById('event-details').scrollIntoView({
        behavior: 'smooth'
    });
    
    // Fetch event details - fixed URL formatting
    fetch(`/Cop4710_Project/WAMPAPI/api/events/details.php?id=${eventId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.event) {
            const event = data.event;
            
            // Update event details
            document.getElementById('event-title').textContent = event.name;
            
            // Format date and time
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
            
            document.getElementById('event-datetime').textContent = `${formattedDate}${formattedTime ? ' • ' + formattedTime : ''}`;
            document.getElementById('event-category').textContent = event.category;
            document.getElementById('event-location').textContent = event.location_name;
            document.getElementById('event-contact').textContent = `${event.contact_email} | ${event.contact_phone}`;
            document.getElementById('event-description').textContent = event.description;
            
            // Update map (if coordinates exist)
            if (event.latitude && event.longitude) {
                initMap(event.latitude, event.longitude, event.location_name);
            }
            
            // Update rating stars based on user's rating if exists
            resetRatingStars();
            if (data.user_rating) {
                selectRatingStars(data.user_rating);
            }
            
            // Load comments
            loadEventComments(eventId);
        } else {
            alert('Failed to load event details.');
            closeEventDetails();
        }
    })
    .catch(error => {
        console.error('Error loading event details:', error);
        alert('Error loading event details. Please try again later.');
        closeEventDetails();
    });
}

// More functions remain the same until loadEventComments

/**
 * Load comments for an event
 * @param {number} eventId - The event ID
 */
function loadEventComments(eventId) {
    fetch(`/Cop4710_Project/WAMPAPI/api/comments/event.php?id=${eventId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayComments(data.comments);
        } else {
            document.getElementById('comments-list').innerHTML = '<p>Failed to load comments.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading comments:', error);
        document.getElementById('comments-list').innerHTML = '<p>Error loading comments. Please try again later.</p>';
    });
}

// More functions remain the same until addComment

/**
 * Add a comment to the current event
 */
function addComment() {
    if (!currentEventId) {
        alert('No event selected.');
        return;
    }
    
    const commentText = document.getElementById('comment-text').value.trim();
    if (!commentText) {
        alert('Please enter a comment.');
        return;
    }
    
    const userID = sessionStorage.getItem('userID');
    
    // Prepare form data
    const formData = new FormData();
    formData.append('event_id', currentEventId);
    formData.append('user_id', userID);
    formData.append('comment', commentText);
    
    // Submit comment
    fetch('/Cop4710_Project/WAMPAPI/api/comments/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear comment input
            document.getElementById('comment-text').value = '';
            
            // Reload comments
            loadEventComments(currentEventId);
        } else {
            alert('Failed to add comment: ' + (data.error_message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error adding comment:', error);
        alert('Error adding comment. Please try again later.');
    });
}

// More functions remain the same until deleteComment

/**
 * Delete a comment
 * @param {number} commentId - The comment ID
 */
function deleteComment(commentId) {
    if (confirm('Are you sure you want to delete this comment?')) {
        fetch(`/Cop4710_Project/WAMPAPI/api/comments/delete.php?id=${commentId}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload comments
                loadEventComments(currentEventId);
            } else {
                alert('Failed to delete comment: ' + (data.error_message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error deleting comment:', error);
            alert('Error deleting comment. Please try again later.');
        });
    }
}

// submitEventRating function remains the same