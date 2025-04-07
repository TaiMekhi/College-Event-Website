/**
 * Map functionality for event creation and viewing using Google Maps
 */

let createEventMap;
let currentMarker;
let defaultLocation = {lat: 28.6024, lng: -81.2001}; // Default location (UCF coordinates)

/**
 * Initialize map for event creation
 */
function initCreateEventMap() {
    // Check if map container exists
    const mapContainer = document.getElementById('create-event-map');
    if (!mapContainer) return;
    
    // Initialize the map centered on UCF
    createEventMap = new google.maps.Map(mapContainer, {
        center: defaultLocation,
        zoom: 14,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    });
    
    // Create a marker that can be dragged
    currentMarker = new google.maps.Marker({
        position: defaultLocation,
        map: createEventMap,
        draggable: true
    });
    
    // Update coordinates when marker is dragged
    google.maps.event.addListener(currentMarker, 'dragend', function() {
        const position = currentMarker.getPosition();
        document.getElementById('event_latitude').value = position.lat().toFixed(6);
        document.getElementById('event_longitude').value = position.lng().toFixed(6);
        
        // Get address from coordinates
        reverseGeocode(position.lat(), position.lng());
    });
    
    // Add click event to map
    google.maps.event.addListener(createEventMap, 'click', function(event) {
        setLocationMarker(event.latLng.lat(), event.latLng.lng());
    });
    
    // Add search functionality using the location input
    const locationInput = document.getElementById('event_location');
    if (locationInput) {
        locationInput.addEventListener('blur', function() {
            const query = this.value.trim();
            if (query) {
                // Show loading indicator
                this.classList.add('loading');
                
                // Search for the location
                searchLocation(query);
            }
        });
        
        // Optional: Create autocomplete for better user experience
        const autocomplete = new google.maps.places.Autocomplete(locationInput, {
            types: ['establishment', 'geocode'],
            componentRestrictions: {country: 'us'}
        });
        
        // Update map when place is selected from autocomplete
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            if (!place.geometry || !place.geometry.location) {
                // User entered the name of a place that was not suggested
                return;
            }
            
            // Set the map to the new location
            createEventMap.setCenter(place.geometry.location);
            createEventMap.setZoom(16);
            
            // Update marker and form fields
            setLocationMarker(
                place.geometry.location.lat(),
                place.geometry.location.lng()
            );
        });
    }
}

/**
 * Set location marker and update form fields
 */
function setLocationMarker(lat, lng) {
    // Update marker position
    if (currentMarker) {
        currentMarker.setPosition({lat: lat, lng: lng});
    } else {
        // Create new marker if it doesn't exist
        currentMarker = new google.maps.Marker({
            position: {lat: lat, lng: lng},
            map: createEventMap,
            draggable: true
        });
        
        // Add drag event handler
        google.maps.event.addListener(currentMarker, 'dragend', function() {
            const position = currentMarker.getPosition();
            document.getElementById('event_latitude').value = position.lat().toFixed(6);
            document.getElementById('event_longitude').value = position.lng().toFixed(6);
            
            // Get address from coordinates
            reverseGeocode(position.lat(), position.lng());
        });
    }
    
    // Update form fields
    document.getElementById('event_latitude').value = lat.toFixed(6);
    document.getElementById('event_longitude').value = lng.toFixed(6);
    
    // Reverse geocode to get address
    reverseGeocode(lat, lng);
}

/**
 * Search for location by name/address
 */
function searchLocation(query) {
    // Show loading indicator
    document.getElementById('event_location').classList.add('loading');
    
    const geocoder = new google.maps.Geocoder();
    
    // Add "UCF" to the search if it's not already included
    if (!query.toLowerCase().includes("ucf")) {
        query += " UCF";
    }
    
    geocoder.geocode({'address': query}, function(results, status) {
        // Remove loading indicator
        document.getElementById('event_location').classList.remove('loading');
        
        if (status === 'OK' && results[0]) {
            const location = results[0].geometry.location;
            
            // Update map view and set marker
            createEventMap.setCenter(location);
            createEventMap.setZoom(16);
            setLocationMarker(location.lat(), location.lng());
        } else {
            alert('Location not found. Please try a different search term or click directly on the map.');
        }
    });
}

/**
 * Get address from coordinates using reverse geocoding
 */
function reverseGeocode(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    const latlng = {lat: parseFloat(lat), lng: parseFloat(lng)};
    
    geocoder.geocode({'location': latlng}, function(results, status) {
        if (status === 'OK' && results[0]) {
            document.getElementById('event_location').value = results[0].formatted_address;
        }
    });
}

/**
 * Initialize map for event viewing
 */
function initEventViewMap(lat, lng, locationName) {
    const mapElement = document.getElementById('event-map');
    
    if (!mapElement) {
        console.error('Map element not found');
        return;
    }
    
    try {
        // Create map
        const viewMap = new google.maps.Map(mapElement, {
            center: {lat: parseFloat(lat), lng: parseFloat(lng)},
            zoom: 15,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });
        
        // Add marker
        const marker = new google.maps.Marker({
            position: {lat: parseFloat(lat), lng: parseFloat(lng)},
            map: viewMap,
            title: locationName || 'Event Location'
        });
        
        // Add info window
        const infoWindow = new google.maps.InfoWindow({
            content: locationName || 'Event Location'
        });
        
        marker.addListener('click', function() {
            infoWindow.open(viewMap, marker);
        });
        
        // Open info window by default
        infoWindow.open(viewMap, marker);
    } catch (error) {
        console.error('Error initializing view map:', error);
        
        // Fallback to basic display
        mapElement.innerHTML = `
            <div class="map-placeholder">
                <p><strong>Location:</strong> ${locationName}</p>
                <p><strong>Coordinates:</strong> ${lat}, ${lng}</p>
                <p class="map-note">Map display not available.</p>
            </div>
        `;
    }
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Initialize create event map if on the appropriate tab
    const createEventTab = document.getElementById('create-event');
    if (createEventTab) {
        // Check if user is on create event tab
        if (createEventTab.classList.contains('active')) {
            initCreateEventMap();
        } else {
            // Setup event listener for tab click
            document.querySelectorAll('.tab-link').forEach(function(tab) {
                if (tab.dataset.tab === 'create-event') {
                    tab.addEventListener('click', function() {
                        // Initialize map with slight delay to ensure container is visible
                        setTimeout(initCreateEventMap, 100);
                    });
                }
            });
        }
    }
});