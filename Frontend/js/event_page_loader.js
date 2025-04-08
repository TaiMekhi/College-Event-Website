// Function to get query parameters from URL
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

// Function to load and display the event details on the standalone page
function loadEventPageDetails() {
    const eventId = getQueryParam('id'); // Get the 'id' from "?id=..."
    const detailsSection = document.getElementById('event-details');
    const commentsSection = document.getElementById('comments-section');

    // Get references to HTML elements (using the IDs from your new HTML structure)
    const titleElement = document.getElementById('event-title');
    const dateTimeElement = document.getElementById('event-date-time-display'); // Ensure this ID exists in HTML
    const categoryValueElement = document.getElementById('event-category'); // ID for the value span
    const locationValueElement = document.getElementById('event-location'); // ID for the value span
    const roomValueElement = document.getElementById('event-room');       // ID for the value span
    const roomItemElement = document.getElementById('event-room-item'); // ID for the whole room div item
    const contactValueElement = document.getElementById('event-contact');    // ID for the value span
    const descriptionValueElement = document.getElementById('event-description'); // ID for the value div/p
    const mapElement = document.getElementById('event-map');
    const commentsListElement = document.getElementById('comments-list');

    if (!eventId) {
        if (detailsSection) detailsSection.innerHTML = '<h2>Error: No Event ID specified in the URL (e.g., ?id=123).</h2>';
        console.error("No event ID found in URL query parameters.");
        if (commentsSection) commentsSection.style.display = 'none';
        return;
    }

    // Set currentEventId if needed by other functions like addComment
    if (typeof currentEventId !== 'undefined') {
        currentEventId = eventId;
    } else {
        console.warn("global 'currentEventId' not found.");
        const commentButton = document.querySelector('#comments-section button');
        if (commentButton) commentButton.dataset.eventId = eventId;
    }

    console.log("Loading details for event ID:", eventId);

    // --- Fetch Event Details ---
    fetch(`/Cop4710_Project/WAMPAPI/api/events/details.php?id=${eventId}`)
        .then(response => {
            if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
            return response.json();
         })
        .then(data => {
            console.log("Fetched event data:", data);
            if (data.success && data.event) {
                const event = data.event;

                // --- Populate HTML elements (setting only the value) ---
                if (titleElement) titleElement.textContent = event.name || 'Event Title Unavailable';

                // Category (set only value)
                if (categoryValueElement) categoryValueElement.textContent = event.category || 'N/A';

                // Location (set only value)
                if (locationValueElement) locationValueElement.textContent = event.location_name || 'N/A';

                // Room (set only value and handle visibility)
                if (roomValueElement && roomItemElement) {
                    if (event.room_number && event.room_number.trim() !== '') {
                        roomValueElement.textContent = event.room_number; // Set only value
                        roomItemElement.style.display = 'block'; // Show item
                    } else {
                        roomItemElement.style.display = 'none'; // Hide item
                    }
                }

                // Contact (set only value)
                if (contactValueElement) contactValueElement.textContent = `${event.contact_email || 'N/A'} | ${event.contact_phone || 'N/A'}`;

                // Description (set only value)
                if (descriptionValueElement) descriptionValueElement.innerHTML = event.description ? event.description.replace(/\n/g, '<br>') : 'No description provided.';

                // --- ** Date/Time Formatting Copied from displayEvents ** ---
                let formattedDate = 'Date N/A';
                let formattedTime = '';

                if (event.date) {
                    try {
                        const eventDate = new Date(event.date + 'T00:00:00'); // Add T00:00:00 for robustness
                        if (!isNaN(eventDate)) {
                            formattedDate = eventDate.toLocaleDateString('en-US', {
                                year: 'numeric', month: 'long', day: 'numeric'
                            });
                        } else {
                             console.warn("Could not parse event date:", event.date);
                             formattedDate = event.date; // Fallback
                        }
                    } catch (e) { console.error("Error parsing date:", e); formattedDate = event.date || 'Date Error'; }
                }

                if (event.time) {
                    try {
                        // Use 2000-01-01 base and 2-digit options like displayEvents
                        formattedTime = new Date(`2000-01-01T${event.time}`).toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit'
                            // No timeZone specified, uses browser default
                        });
                    } catch (timeError) {
                         console.warn("Could not format time:", event.time, timeError);
                         formattedTime = event.time; // Fallback
                    }
                }

                // Update the element (ensure ID is correct in HTML)
                if (dateTimeElement) {
                     // Set only the combined value string, no "Date & Time:" prefix
                     dateTimeElement.textContent = `${formattedDate}${formattedTime ? ' at ' + formattedTime : ''}`;
                } else {
                    // Ensure your HTML has the element with id="event-date-time-display" inside a value span
                    console.error("Element with ID 'event-date-time-display' not found!");
                }
                // --- ** End Date/Time Formatting ** ---


                // --- Initialize Map ---
                if (mapElement) {
                    if (event.latitude && event.longitude) {
                         if (typeof initMap === 'function' && typeof google !== 'undefined' && google.maps) {
                             initMap(event.latitude, event.longitude, event.location_name);
                         } else {
                             console.warn("initMap function or Google Maps API not ready.");
                             mapElement.innerHTML = '<p class="error-message">Map could not be loaded.</p>';
                         }
                    } else {
                         mapElement.innerHTML = '<p>Map location not available.</p>';
                    }
                }

                // --- Handle Ratings ---
                // (Add your rating HTML/JS logic here if needed)


                // --- Fetch Comments ---
                // Use the separate loading function if available, otherwise the original
                 if (typeof loadAndDisplayComments === 'function') {
                     loadAndDisplayComments(eventId);
                 } else if (typeof loadEventComments === 'function' && commentsListElement) {
                     loadEventComments(eventId);
                 } else {
                      if(commentsListElement) commentsListElement.innerHTML = '<p>Error: Comment loading function not found.</p>';
                 }


            } else {
                 // Handle API success:false or no event data
                 if (detailsSection) detailsSection.innerHTML = `<h2>Error loading event: ${data.error_message || 'Event data not found'}</h2>`;
                 if (commentsSection) commentsSection.style.display = 'none';
            }
        })
        .catch(error => {
            // Handle fetch network error or JSON parsing error
            console.error('Error fetching event details:', error);
            if (detailsSection) detailsSection.innerHTML = '<h2>Error loading event details. Please check the console.</h2>';
            if (commentsSection) commentsSection.style.display = 'none';
        });

     // --- Comment Button Listener ---
     // Keep your listener setup, but ensure addComment function is ready
     const addCommentButton = document.querySelector('#comments-section button');
     if (addCommentButton && typeof addComment === 'function') {
        // Check if listener already exists to prevent duplicates if this runs multiple times
        if (!addCommentButton.dataset.listenerAttached) {
             addCommentButton.addEventListener('click', function() {
                 const commentText = document.querySelector('#comment-text').value;
                 let eventIdForComment = currentEventId || this.dataset.eventId; // Get ID

                 if (!eventIdForComment) {
                      alert("Error: Could not determine Event ID for comment.");
                      return;
                 }

                 if (commentText) {
                      addComment(eventIdForComment, commentText); // Pass ID and Text
                 } else {
                      alert("Please enter a comment before submitting.");
                 }
             });
             addCommentButton.dataset.listenerAttached = 'true'; // Mark as attached
        }
     } else if (addCommentButton && !typeof addComment === 'function') {
         console.error("addComment function not found!");
     }

} // End of loadEventPageDetails function

// Ensure this runs once the DOM is ready
document.addEventListener('DOMContentLoaded', loadEventPageDetails);