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

    // Make sure element references are declared early if needed widely
    const titleElement = document.getElementById('event-title');
    const dateTimeElement = document.getElementById('event-date-time-display'); 
    const categoryElement = document.getElementById('event-category');
    const locationElement = document.getElementById('event-location');
    const roomElement = document.getElementById('event-room');
    const contactElement = document.getElementById('event-contact');
    const descriptionElement = document.getElementById('event-description');
    const mapElement = document.getElementById('event-map');
    const commentsListElement = document.getElementById('comments-list'); // For comment loading check

    if (!eventId) {
        if (detailsSection) detailsSection.innerHTML = '<h2>Error: No Event ID specified in the URL (e.g., ?id=123).</h2>';
        console.error("No event ID found in URL query parameters.");
        if (commentsSection) commentsSection.style.display = 'none';
        return;
    }

    // Set currentEventId if it's declared globally and needed by other functions like addComment
    if (typeof currentEventId !== 'undefined') {
        currentEventId = eventId;
    } else {
        console.warn("global 'currentEventId' not found.");
        // Optionally store eventId on the comment button if needed
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
            console.log("Fetched event data:", data); // Debugging
            if (data.success && data.event) {
                const event = data.event;

                // --- Populate HTML elements ---
                if (titleElement) titleElement.textContent = event.name || 'Event Title Unavailable';
                if (categoryElement) categoryElement.textContent = `Category: ${event.category || 'N/A'}`;
                if (locationElement) locationElement.textContent = `Location: ${event.location_name || 'N/A'}`;

                if (roomElement) {
                    if (event.room_number && event.room_number.trim() !== '') {
                        roomElement.textContent = `Room: ${event.room_number}`;
                        roomElement.style.display = 'block';
                    } else {
                        roomElement.style.display = 'none';
                    }
                }

                if (contactElement) contactElement.textContent = `Contact: ${event.contact_email || 'N/A'} | ${event.contact_phone || 'N/A'}`;
                if (descriptionElement) descriptionElement.innerHTML = event.description ? event.description.replace(/\n/g, '<br>') : 'No description provided.';

                // --- ** Date/Time Formatting Copied from displayEvents ** ---
                let formattedDate = 'Date N/A';
                let formattedTime = '';

                if (event.date) {
                    try {
                        const eventDate = new Date(event.date + 'T00:00:00'); // Add time part
                        if (!isNaN(eventDate)) {
                            formattedDate = eventDate.toLocaleDateString('en-US', {
                                year: 'numeric', month: 'long', day: 'numeric'
                            });
                        } else {
                             console.warn("Could not parse event date:", event.date);
                             formattedDate = event.date; 
                        }
                    } catch (e) { console.error("Error parsing date:", e); formattedDate = event.date || 'Date Error'; }
                }

                if (event.time) {
                    try {
                        formattedTime = new Date(`2000-01-01T${event.time}`).toLocaleTimeString('en-US', {
                            hour: '2-digit', // Match displayEvents format
                            minute: '2-digit'
                        
                        });
                    } catch (timeError) {
                         console.warn("Could not format time:", event.time, timeError);
                         formattedTime = event.time;
                    }
                }

                // Update the element with the *NEW ID* using the generated strings
                if (dateTimeElement) { 
                     dateTimeElement.textContent = `Date & Time: ${formattedDate}${formattedTime ? ' • ' + formattedTime : ''}`;
                } else {
                    console.error("Element with ID 'event-date-time-display' not found!"); // Use the NEW ID here
                }


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

                // --- MAYBETODO: Handle Ratings ---
              


                // --- Fetch Comments (AFTER main details load) ---
                 if (typeof loadAndDisplayComments === 'function') {
                      loadAndDisplayComments(eventId);
                 } else if (typeof loadEventComments === 'function' && commentsListElement) { 
                      loadEventComments(eventId);
                 } else {
                      if(commentsListElement) commentsListElement.innerHTML = '<p>Error: Comment loading function not found.</p>';
                      console.error("Comment loading function not available.");
                 }


            } else {
                 // Failed to load event data (API returned success: false or no event object)
                 if (detailsSection) detailsSection.innerHTML = `<h2>Error loading event: ${data.error_message || 'Event data not found'}</h2>`;
                 if (commentsSection) commentsSection.style.display = 'none'; // Hide comments section too
            }
        })
        .catch(error => {
            // Failed to fetch or parse JSON
            console.error('Error fetching event details:', error);
            if (detailsSection) detailsSection.innerHTML = '<h2>Error loading event details. Please check the console.</h2>';
            if (commentsSection) commentsSection.style.display = 'none';
        });

  
     const addCommentButton = document.querySelector('#comments-section button');
     if (addCommentButton && typeof addComment === 'function') {
            addCommentButton.addEventListener('click', function() {
                const commentText = document.querySelector('#comment-text').value; // Assuming you have a textarea with this ID
                if (commentText) {
                    addComment(eventId, commentText); // Call your addComment function with the event ID and comment text
                } else {
                    alert("Please enter a comment before submitting.");
                }
            });
     }

} 
document.addEventListener('DOMContentLoaded', loadEventPageDetails);
