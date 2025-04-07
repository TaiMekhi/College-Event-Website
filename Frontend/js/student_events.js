let currentEventId = null;

// Function to escape JavaScript strings in HTML
function escapeJsString(str) {
    if (!str) return '';
    // Escapes single quotes, double quotes, and backticks
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/`/g, '\\`');
  }

  // Function to share events on Twitter (X)
function shareEventOnTwitter(eventId, eventName, eventUrl) {
    console.log(`Sharing Event: ID=${eventId}, Name=${eventName}, URL=${eventUrl}`); // For debugging

    // Easy text for the tweet
    const text = `Check out this event: ${eventName}`;

    // Base Twitter Intent URL
    let shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`;

    // Window Popup options
    shareUrl += `&url=${encodeURIComponent(eventUrl)}`; // Add URL to the tweet
    const windowOptions = 'left=0,top=0,width=550,height=450,personalbar=0,toolbar=0,scrollbars=0,resizable=0';

    // Twitter share window popup
    window.open(shareUrl, 'ShareOnTwitter', windowOptions);
}

function loadPublicEvents() {
    document.getElementById('publicEventsList').innerHTML = '<p>Loading events...</p>';
    
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
        document.getElementById('publicEventsList').innerHTML = '<p>Error loading events. Please try again later.</p>';
    });
}

function loadUniversityEvents() {
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
        document.getElementById('universityEventsList').innerHTML = '<p>Error loading events. Please try again later.</p>';
    });
}

function loadRsoEvents() {
    document.getElementById('rsoEventsList').innerHTML = '<p>Loading events...</p>';
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
                populateRsoFilter(data.rsos);
                
                if (data.events && data.events.length > 0) {
                    displayEvents('rsoEventsList', data.events, true);
                } else {
                    document.getElementById('rsoEventsList').innerHTML = '<p>No RSO events found.</p>';
                }
            } else {
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
        document.getElementById('rsoEventsList').innerHTML = '<p>Error loading events. Please try again later.</p>';
    });
}

function viewEventDetails(eventId) {
    currentEventId = eventId;
    
    
    document.getElementById('event-title').textContent = 'Loading event details...';
    document.getElementById('event-datetime').textContent = '';
    document.getElementById('event-category').textContent = '';
    document.getElementById('event-location').textContent = '';
    document.getElementById('event-room').textContent = '';
    document.getElementById('event-contact').textContent = '';
    document.getElementById('event-description').textContent = '';
    document.getElementById('comments-list').innerHTML = '<p>Loading comments...</p>';
    
    document.getElementById('event-details').style.display = 'block';
    document.getElementById('event-details').scrollIntoView({behavior: 'smooth'});
    
    fetch(`/Cop4710_Project/WAMPAPI/api/events/details.php?id=${eventId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.event) {
            const event = data.event;
            
            document.getElementById('event-title').textContent = event.name;
            
            const eventDate = new Date(event.date);
            const formattedDate = eventDate.toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
            
            const formattedTime = event.time ? new Date(`2000-01-01T${event.time}`).toLocaleTimeString('en-US', {
                hour: '2-digit', minute: '2-digit'
            }) : '';
            
            document.getElementById('event-datetime').textContent = `${formattedDate}${formattedTime ? ' • ' + formattedTime : ''}`;
            document.getElementById('event-category').textContent = event.category;
            document.getElementById('event-location').textContent = event.location_name;
            
            const roomElement = document.getElementById('event-room');
            if (event.room_number && event.room_number.trim() !== '') {
                roomElement.textContent = event.room_number;
                roomElement.parentElement.style.display = '';
            } else {
                roomElement.textContent = 'N/A';
                roomElement.parentElement.style.display = 'none';
            }
            
            document.getElementById('event-contact').textContent = `${event.contact_email} | ${event.contact_phone}`;
            document.getElementById('event-description').textContent = event.description;
            
            if (event.latitude && event.longitude) {
                initMap(event.latitude, event.longitude, event.location_name);
            }
            
            resetRatingStars();
            if (data.user_rating) {
                selectRatingStars(data.user_rating);
            }
            
            loadEventComments(eventId);
        } else {
            alert('Failed to load event details.');
            closeEventDetails();
        }
    })
    .catch(error => {
        alert('Error loading event details. Please try again later.');
        closeEventDetails();
    });
}

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
        document.getElementById('comments-list').innerHTML = '<p>Error loading comments. Please try again later.</p>';
    });
}

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
    const formData = new FormData();
    formData.append('event_id', currentEventId);
    formData.append('user_id', userID);
    formData.append('comment', commentText);
    
    fetch('/Cop4710_Project/WAMPAPI/api/comments/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('comment-text').value = '';
            loadEventComments(currentEventId);
        } else {
            alert('Failed to add comment: ' + (data.error_message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error adding comment. Please try again later.');
    });
}

function deleteComment(commentId) {
    if (confirm('Are you sure you want to delete this comment?')) {
        fetch(`/Cop4710_Project/WAMPAPI/api/comments/delete.php?id=${commentId}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadEventComments(currentEventId);
            } else {
                alert('Failed to delete comment: ' + (data.error_message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error deleting comment. Please try again later.');
        });
    }
}

function displayEvents(containerId, events, includeRsoFilter = false) {
    const container = document.getElementById(containerId);
    
    if (!container) return;
    
    if (!events || events.length === 0) {
        container.innerHTML = '<p>No events found.</p>';
        return;
    }
    
    let html = '';
    
    events.forEach(event => {
        try {
            const eventDate = new Date(event.date);
            const formattedDate = eventDate.toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
            
            let formattedTime = '';
            if (event.time) {
                try {
                    formattedTime = new Date(`2000-01-01T${event.time}`).toLocaleTimeString('en-US', {
                        hour: '2-digit', minute: '2-digit'
                    });
                } catch (timeError) {}
            }
            
            const ratingValue = event.average_rating ? parseFloat(event.average_rating).toFixed(1) : 'N/A';
            const stars = getStarsHTML(ratingValue);

            const eventUrlForSharing = '';
            const safeEventName = escapeJsString(event.name || 'Unnamed Event');
            
            html += `
            <div class="event-card">
                <h3>${event.name || 'Unnamed Event'}</h3>
                <div class="event-date">${formattedDate}${formattedTime ? ' • ' + formattedTime : ''}</div>
                <div class="event-category">${event.category || 'Uncategorized'}</div>
                <div class="event-description">
                    ${event.description ? event.description.substring(0, 150) + (event.description.length > 150 ? '...' : '') : 'No description available'}
                </div>
                <div class="event-actions">
                    <button class="event-button" onclick="viewEventDetails(${event.event_id})">View Details</button>
                    <button
                        class="event-button twitter-share-button"
                        onclick="shareEventOnTwitter(${event.event_id}, '${escapeJsString(event.name)}', '${window.location.origin}/Cop4710_Project/Frontend/pages/event_details.html?id=${event.event_id}', '${formattedDate}')"
                        title="Share this event on X">
                        Share on X
                    </button>
                </div>
            </div>
        `;
        } catch (error) {}
    });
    
    container.innerHTML = html;
}

function getStarsHTML(rating) {
    if (rating === 'N/A') return '☆☆☆☆☆';
    
    const ratingNum = parseFloat(rating);
    let stars = '';
    
    for (let i = 1; i <= 5; i++) {
        stars += (i <= ratingNum) ? '★' : '☆';
    }
    
    return stars;
}

function closeEventDetails() {
    document.getElementById('event-details').style.display = 'none';
    currentEventId = null;
}

function initMap(lat, lng, locationName) {
    if (typeof initEventViewMap === 'function') {
        initEventViewMap(lat, lng, locationName);
    } else {
        const mapElement = document.getElementById('event-map');
        if (mapElement) {
            mapElement.innerHTML = `
                <div class="map-placeholder">
                    <p><strong>Location:</strong> ${locationName}</p>
                    <p><strong>Coordinates:</strong> ${lat}, ${lng}</p>
                    <p class="map-note">Map display not available.</p>
                </div>
            `;
        }
    }
}

function resetRatingStars() {
    document.querySelectorAll('.rating-stars .star').forEach(star => {
        star.classList.remove('selected');
    });
}

function selectRatingStars(rating) {
    document.querySelectorAll('.rating-stars .star').forEach(star => {
        if (parseInt(star.dataset.value) <= rating) {
            star.classList.add('selected');
        } else {
            star.classList.remove('selected');
        }
    });
}

function submitEventRating(rating) {
    if (!currentEventId) {
        alert('No event selected.');
        return;
    }
    
    const userID = sessionStorage.getItem('userID');
    const formData = new FormData();
    formData.append('event_id', currentEventId);
    formData.append('user_id', userID);
    formData.append('rating', rating);
    
    fetch('/Cop4710_Project/WAMPAPI/api/ratings/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Rating submitted successfully!');
        } else {
            alert('Failed to submit rating: ' + (data.error_message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error submitting rating. Please try again later.');
    });
}

function populateRsoFilter(rsos) {
    const filterSelect = document.getElementById('rso-event-filter');
    if (!filterSelect) return;
    
    while (filterSelect.options.length > 1) {
        filterSelect.remove(1);
    }
    
    rsos.forEach(rso => {
        const option = document.createElement('option');
        option.value = rso.id;
        option.textContent = rso.name;
        filterSelect.appendChild(option);
    });
}

function editComment(commentId, currentText) {
    const commentItem = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
    if (!commentItem) return;
    
    const commentContent = commentItem.querySelector('.comment-content');
    commentItem.setAttribute('data-original-content', commentContent.innerHTML);
    
    commentContent.innerHTML = `
        <textarea style="width: 100%; min-height: 60px; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;">${currentText}</textarea>
        <div>
            <button class="comment-button" onclick="saveComment(${commentId})">Save</button>
            <button class="comment-button" onclick="cancelEdit(${commentId})">Cancel</button>
        </div>
    `;
    
    const commentActions = commentItem.querySelector('.comment-actions');
    if (commentActions) {
        commentActions.style.display = 'none';
    }
    
    commentContent.querySelector('textarea').focus();
}

function saveComment(commentId) {
    const commentItem = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
    if (!commentItem) return;
    
    const textarea = commentItem.querySelector('textarea');
    const newText = textarea.value.trim();
    
    if (!newText) {
        alert('Comment cannot be empty.');
        return;
    }
    
    const formData = new FormData();
    formData.append('comment_id', commentId);
    formData.append('comment', newText);
    
    fetch('/Cop4710_Project/WAMPAPI/api/comments/edit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadEventComments(currentEventId);
        } else {
            alert('Failed to update comment: ' + (data.error_message || 'Unknown error'));
            cancelEdit(commentId);
        }
    })
    .catch(error => {
        alert('Error updating comment. Please try again later.');
        cancelEdit(commentId);
    });
}

function cancelEdit(commentId) {
    const commentItem = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
    if (!commentItem) return;
    
    const originalContent = commentItem.getAttribute('data-original-content');
    
    const commentContent = commentItem.querySelector('.comment-content');
    if (originalContent) {
        commentContent.innerHTML = originalContent;
    } else {
        loadEventComments(currentEventId);
    }
    
    const commentActions = commentItem.querySelector('.comment-actions');
    if (commentActions) {
        commentActions.style.display = 'block';
    }
}

function displayComments(comments) {
    const commentsList = document.getElementById('comments-list');
    if (!commentsList) return;
    
    if (!comments || comments.length === 0) {
        commentsList.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
        return;
    }
    
    let html = '';
    const currentUserID = sessionStorage.getItem('userID');
    
    comments.forEach(comment => {
        const commentDate = new Date(comment.timestamp);
        const formattedDate = commentDate.toLocaleDateString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
        
        html += `
            <div class="comment-item" data-comment-id="${comment.comment_id}">
                <div class="comment-header">
                    <div class="comment-author">${comment.user_name || 'Anonymous'}</div>
                    <div class="comment-date">${formattedDate}</div>
                </div>
                <div class="comment-content">
                    ${comment.comment}
                </div>
                ${comment.user_id == currentUserID ? `
                <div class="comment-actions">
                    <button class="comment-button" onclick="editComment(${comment.comment_id}, '${comment.comment.replace(/'/g, "\\'")}')">Edit</button>
                    <button class="comment-button" onclick="deleteComment(${comment.comment_id})">Delete</button>
                </div>
                ` : ''}
            </div>
        `;
    });
    
    commentsList.innerHTML = html;
}

function submitEventForm() {
    const eventName = document.getElementById('event_name').value.trim();
    const eventCategory = document.getElementById('event_category').value;
    const eventDescription = document.getElementById('event_description').value.trim();
    const eventDate = document.getElementById('event_date').value;
    const eventTime = document.getElementById('event_time').value;
    const eventLocation = document.getElementById('event_location').value.trim();
    const eventRoom = document.getElementById('event_room').value.trim();
    const eventLatitude = document.getElementById('event_latitude').value;
    const eventLongitude = document.getElementById('event_longitude').value;
    const eventContactPhone = document.getElementById('event_contact_phone').value.trim();
    const eventContactEmail = document.getElementById('event_contact_email').value.trim();
    const eventType = document.getElementById('event_type').value || 'public';
    const createEventResult = document.getElementById('createEventResult');
    
    if (!eventName || !eventCategory || !eventDescription || !eventDate || !eventTime || 
        !eventLocation || !eventLatitude || !eventLongitude || !eventContactPhone || 
        !eventContactEmail) {
        createEventResult.innerHTML = '<div class="error-message">Please fill in all required fields</div>';
        return;
    }
    
    const locationDisplay = eventRoom ? `${eventLocation}, Room ${eventRoom}` : eventLocation;
    
    const formData = new FormData();
    formData.append('name', eventName);
    formData.append('category', eventCategory);
    formData.append('description', eventDescription);
    formData.append('date', eventDate);
    formData.append('time', eventTime);
    formData.append('location', eventLocation);
    formData.append('room_number', eventRoom);
    formData.append('location_display', locationDisplay);
    formData.append('latitude', eventLatitude);
    formData.append('longitude', eventLongitude);
    formData.append('contact_phone', eventContactPhone);
    formData.append('contact_email', eventContactEmail);
    formData.append('type', eventType);
    
    if (eventType === 'rso') {
        const eventRso = document.getElementById('event_rso').value;
        if (!eventRso) {
            createEventResult.innerHTML = '<div class="error-message">Please select an RSO for RSO event</div>';
            return;
        }
        formData.append('rso_id', eventRso);
    }
    
    fetch("/Cop4710_Project/WAMPAPI/api/rsos/create_event.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            createEventResult.innerHTML = '<div class="success-message">' + data.message + '</div>';
            document.getElementById('createEventForm').reset();
            setTimeout(() => {
                createEventResult.innerHTML = '';
            }, 3000);
        } else {
            createEventResult.innerHTML = `<div class="error-message">Failed to create event: ${data.message || 'Unknown error'}</div>`;
        }
    })
    .catch(error => {
        createEventResult.innerHTML = `<div class="error-message">Error creating event: ${error}</div>`;
    });

  
}