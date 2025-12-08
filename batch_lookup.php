<?php
/**
 * Splynx Batch IP Lookup Client (PHP/HTML Wrapper)
 *
 * This file includes config.php to securely retrieve the Google API key on the server-side,
 * and then embeds it into the client-side JavaScript for map initialization.
 */
require_once 'config.php';

// The Google API key is now available in $googleApiKey
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splynx Batch IP Lookup & Map</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
        .card { box-shadow: 0 0 20px rgba(0, 0, 0, 0.05); }
        #map { height: 500px; width: 100%; border-radius: 0.5rem; }
        .table-row-selected {
            background-color: #e6f0ff !important; /* Lighter blue for selected row */
            border-left: 4px solid #3b82f6;
        }
        .table-row:hover {
            background-color: #f3f4f6;
            cursor: pointer;
        }
        /* Style for better responsiveness on small screens */
        .responsive-table th, .responsive-table td {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 768px) {
            .hide-on-mobile {
                display: none;
            }
        }
    </style>
</head>
<body class="flex flex-col items-center min-h-screen p-4 sm:p-8">

    <div class="w-full max-w-7xl mt-5">
        <!-- Main Application Card -->
        <div class="card bg-white p-6 sm:p-8 rounded-xl border border-gray-200">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-3">Splynx Batch IP Lookup & Map</h1>
            
            <!-- Message Area -->
            <div id="message-container" class="mb-4"></div>

            <!-- Input and Controls -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="md:col-span-2">
                    <label for="ip-list" class="block text-sm font-medium text-gray-700 mb-2">Paste IPv4 Addresses (One per line)</label>
                    <textarea id="ip-list" rows="6" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-gray-800" placeholder="e.g., 192.168.1.1&#10;10.0.0.5&#10;..."></textarea>
                </div>
                <div class="flex flex-col space-y-3">
                    <button id="lookup-button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-150 ease-in-out shadow-md">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" id="loading-spinner">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Batch Lookup
                    </button>
                    <button id="download-csv-button" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-lg transition duration-150 ease-in-out shadow-md" disabled>
                        Download CSV
                    </button>
                    <button id="download-kml-button" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 rounded-lg transition duration-150 ease-in-out shadow-md" disabled>
                        Download KML
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">Found <span id="ip-count" class="font-bold">0</span> IPs to process.</p>
                </div>
            </div>

            <!-- Results Section -->
            <div id="results-section" class="mt-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Lookup Results</h2>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 responsive-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-on-mobile">Status</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-on-mobile">Phone</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-on-mobile">Email</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-on-mobile">2nd Contact Name</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-on-mobile">2nd Contact Phone</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-on-mobile">Lat/Lng</th>
                            </tr>
                        </thead>
                        <tbody id="results-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Results will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Map Section -->
            <div id="map-section" class="mt-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Service Map View</h2>
                <div id="map"></div>
            </div>

        </div>
    </div>

    <script>
        /**
         * Client-side JavaScript logic for batch lookups and map integration.
         */
        
        // PHP securely injects the Google API Key and API URL
        const GOOGLE_API_KEY = "<?php echo htmlspecialchars($googleApiKey); ?>";
        const API_URL = 'api.php'; 
        
        // State management
        let lookupResults = []; // Stores the successful lookup data
        let map;
        let markers = [];
        let infoWindow;
        let selectedMarker = null;

        // DOM elements
        const ipListTextarea = document.getElementById('ip-list');
        const lookupButton = document.getElementById('lookup-button');
        const downloadCsvButton = document.getElementById('download-csv-button');
        const downloadKmlButton = document.getElementById('download-kml-button');
        const resultsTableBody = document.getElementById('results-table-body');
        const messageContainer = document.getElementById('message-container');
        const ipCountSpan = document.getElementById('ip-count');
        const loadingSpinner = document.getElementById('loading-spinner');

        // --- Utility Functions ---

        /**
         * Displays a temporary message in the message container.
         */
        const displayMessage = (message, type = 'info', isFatal = false) => {
            let color = { info: 'bg-blue-100 text-blue-800', error: 'bg-red-100 text-red-800', success: 'bg-green-100 text-green-800' }[type];
            messageContainer.innerHTML = `
                <div class="p-3 rounded-lg font-medium ${color} ${isFatal ? 'shadow-xl' : ''}">
                    ${message}
                </div>
            `;
            if (!isFatal && type !== 'info') {
                setTimeout(() => messageContainer.innerHTML = '', 7000);
            }
        };

        /**
         * Clears all markers from the map.
         */
        const clearMarkers = () => {
            markers.forEach(marker => marker.setMap(null));
            markers = [];
            selectedMarker = null;
        };
        
        // --- Map Initialization ---

        /**
         * Google Maps API callback. Initializes the map.
         */
        window.initMap = function() {
            if (!GOOGLE_API_KEY || GOOGLE_API_KEY === '') {
                 // Should have been caught by the FATAL ERROR check below, but for safety
                return;
            }

            // Default to central New Zealand if no results loaded yet
            const defaultLocation = { lat: -41.28889, lng: 174.77722 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 7,
                center: defaultLocation,
                mapTypeId: google.maps.MapTypeId.ROADMAP // Default map type
            });

            infoWindow = new google.maps.InfoWindow();
            displayMessage('Map Initialized. Ready for IP lookups.', 'success');
        };

        /**
         * Places markers on the map based on the lookup results.
         */
        const plotResultsOnMap = () => {
            if (!map) {
                displayMessage('Map is not initialized. Please check the Google API key in config.php.', 'error', true);
                return;
            }

            clearMarkers();
            
            const bounds = new google.maps.LatLngBounds();
            let plotCount = 0;

            lookupResults.forEach((result, index) => {
                const lat = parseFloat(result.service_latitude);
                const lng = parseFloat(result.service_longitude);

                if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                    plotCount++;
                    const location = { lat: lat, lng: lng };
                    bounds.extend(location);
                    
                    // Create content for InfoWindow
                    const contentString = `
                        <div class="text-sm">
                            <h4 class="font-bold text-base mb-1">${result.customer_name}</h4>
                            <p><strong>IP:</strong> ${result.service_ipv4}</p>
                            <p><strong>Address:</strong> ${result.service_address}</p>
                            <p><strong>Service ID:</strong> ${result.service_id}</p>
                            <p class="mt-1">Status: <span class="font-bold text-xs p-1 rounded-full text-white ${result.service_status === 'Active' ? 'bg-green-500' : 'bg-red-500'}">${result.service_status}</span></p>
                        </div>
                    `;

                    const marker = new google.maps.Marker({
                        position: location,
                        map: map,
                        title: result.customer_name,
                        // Custom icon based on status
                        icon: {
                            url: result.service_status === 'Active' ? 
                                'http://maps.google.com/mapfiles/ms/icons/green-dot.png' :
                                'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                        },
                        dataIndex: index // Store the index to link back to the table
                    });

                    marker.addListener('click', () => {
                        infoWindow.setContent(contentString);
                        infoWindow.open(map, marker);
                        highlightTableRow(index);
                        selectedMarker = marker;
                    });

                    markers.push(marker);
                }
            });

            if (plotCount > 0) {
                map.fitBounds(bounds);
                displayMessage(`Successfully plotted ${plotCount} services on the map.`, 'success');
            } else {
                 displayMessage('No geocoded data available to plot on the map.', 'info');
                 // Reset map center if no points were plotted
                 map.setCenter({ lat: -41.28889, lng: 174.77722 });
                 map.setZoom(7);
            }
        };

        /**
         * Highlights a row in the table and scrolls to it.
         */
        const highlightTableRow = (index) => {
            // Remove selection from all rows
            document.querySelectorAll('#results-table-body tr').forEach(row => {
                row.classList.remove('table-row-selected');
            });

            const selectedRow = document.getElementById(`row-${index}`);
            if (selectedRow) {
                // Add selection class to the selected row
                selectedRow.classList.add('table-row-selected');
                
                // Scroll into view
                selectedRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        };

        /**
         * Renders the results in the HTML table.
         */
        const renderTable = () => {
            resultsTableBody.innerHTML = ''; // Clear previous results
            
            lookupResults.forEach((result, index) => {
                const statusColor = result.service_status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                
                const row = document.createElement('tr');
                row.id = `row-${index}`;
                row.className = 'table-row';
                row.onclick = () => {
                    // Pan map to the marker when table row is clicked
                    const marker = markers.find(m => m.dataIndex === index);
                    if (marker && map) {
                        map.panTo(marker.getPosition());
                        // Trigger marker click to open info window
                        google.maps.event.trigger(marker, 'click');
                    }
                };

                // The inner HTML now includes the new columns (Email, 2nd Contact Name, 2nd Contact Phone)
                row.innerHTML = `
                    <td class="px-3 py-3 text-sm font-medium text-gray-900">${result.service_ipv4}</td>
                    <td class="px-3 py-3 text-sm text-gray-500">${result.customer_name} (${result.customer_status})</td>
                    <td class="px-3 py-3 text-sm font-medium hide-on-mobile">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColor}">
                            ${result.service_status}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-sm text-gray-500 hide-on-mobile">${result.customer_phone || 'N/A'}</td>
                    <td class="px-3 py-3 text-sm text-gray-500 hide-on-mobile">${result.customer_email || 'N/A'}</td>
                    <td class="px-3 py-3 text-sm text-gray-500 hide-on-mobile">${result.contact_2_name || 'N/A'}</td>
                    <td class="px-3 py-3 text-sm text-gray-500 hide-on-mobile">${result.contact_2_phone || 'N/A'}</td>
                    <td class="px-3 py-3 text-sm text-gray-500">${result.service_address || 'N/A'}</td>
                    <td class="px-3 py-3 text-sm text-gray-500 hide-on-mobile">${result.service_latitude ? `${result.service_latitude.toFixed(4)} / ${result.service_longitude.toFixed(4)}` : 'N/A'}</td>
                `;

                resultsTableBody.appendChild(row);
            });
        };


        // --- Core Lookup Logic ---

        /**
         * Sanitizes IP list and initiates the batch lookup process.
         */
        const processIps = async () => {
            const ipList = ipListTextarea.value.trim().split('\n')
                .map(ip => ip.trim())
                .filter(ip => ip.match(/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/)); // Basic IPv4 validation

            if (ipList.length === 0) {
                displayMessage('Please enter valid IPv4 addresses (one per line).', 'error');
                return;
            }
            
            // UI state updates
            lookupButton.disabled = true;
            loadingSpinner.classList.remove('hidden');
            lookupButton.textContent = 'Processing...';
            downloadCsvButton.disabled = true;
            downloadKmlButton.disabled = true;
            resultsTableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-gray-500">Starting batch lookup...</td></tr>';
            clearMarkers();
            lookupResults = [];

            // Execute lookups in parallel
            const lookups = ipList.map(ip => lookupService(ip));
            const allResults = await Promise.all(lookups);

            // Filter out failed lookups
            lookupResults = allResults.filter(result => result && result.service_ipv4);
            
            // Final UI updates
            lookupButton.disabled = false;
            loadingSpinner.classList.add('hidden');
            lookupButton.textContent = 'Batch Lookup';
            
            ipCountSpan.textContent = ipList.length;

            if (lookupResults.length > 0) {
                renderTable();
                plotResultsOnMap();
                downloadCsvButton.disabled = false;
                // Only enable KML if there's at least one geocoded point
                const hasGeocoded = lookupResults.some(r => r.service_latitude && r.service_latitude !== 0);
                downloadKmlButton.disabled = !hasGeocoded;
            } else {
                resultsTableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-red-500 font-semibold">No active services found for the IPs provided.</td></tr>';
                displayMessage('Batch lookup complete. Zero active services found.', 'error');
            }
        };


        /**
         * Fetches service details for a single IP from the internal API.
         */
        const lookupService = async (ipv4) => {
            try {
                // Use a simple non-blocking fetch to the local api.php
                const response = await fetch(`${API_URL}?ipv4=${ipv4}`);
                const data = await response.json();

                if (response.ok && data.service_ipv4) {
                    return data;
                } else {
                    console.warn(`[Lookup Failed] IP: ${ipv4}, Error: ${data.error || 'Unknown error'}`);
                    return { service_ipv4: ipv4, error: data.error || 'Not Found' };
                }
            } catch (error) {
                console.error(`[Fetch Error] IP: ${ipv4}, Error:`, error);
                return { service_ipv4: ipv4, error: 'API Unreachable' };
            }
        };

        // --- Export Functions ---
        
        /**
         * Generates and downloads the lookup results as a CSV file.
         */
        const downloadCsv = () => {
            if (lookupResults.length === 0) {
                displayMessage('No data to export.', 'error');
                return;
            }

            // Define CSV Headers - UPDATED to include new fields
            const headers = [
                'IP Address', 'Customer Name', 'Customer Status', 'Customer Phone', 'Customer Email', 
                '2nd Contact Name', '2nd Contact Phone',
                'Service Status', 'Service ID', 'Service Address', 'Latitude', 'Longitude'
            ];

            const csvRows = [headers.join(',')];

            lookupResults.forEach(result => {
                // Ensure all fields are present to maintain column count and escape commas
                const row = [
                    result.service_ipv4,
                    `"${(result.customer_name || '').replace(/"/g, '""')}"`,
                    result.customer_status || 'N/A',
                    result.customer_phone || 'N/A',
                    result.customer_email || 'N/A', // New: Email
                    result.contact_2_name || 'N/A', // New: 2nd Contact Name
                    result.contact_2_phone || 'N/A', // New: 2nd Contact Phone
                    result.service_status || 'N/A',
                    result.service_id || 'N/A',
                    `"${(result.service_address || '').replace(/"/g, '""')}"`,
                    result.service_latitude || '',
                    result.service_longitude || ''
                ];
                csvRows.push(row.join(','));
            });

            const csvContent = csvRows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'splynx_batch_lookup.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        /**
         * Generates and downloads the lookup results as a KML file.
         */
        const downloadKml = () => {
            if (lookupResults.length === 0) {
                displayMessage('No data to export.', 'error');
                return;
            }

            const placemarks = lookupResults.filter(result => 
                result.service_latitude && result.service_latitude !== 0 && result.service_longitude && result.service_longitude !== 0
            ).map(result => {
                const styleUrl = `#${result.service_status === 'Active' ? 'activeStyle' : 'inactiveStyle'}`;
                const description = `
                    <p><b>Service ID:</b> ${result.service_id}</p>
                    <p><b>IP:</b> ${result.service_ipv4}</p>
                    <p><b>Customer:</b> ${result.customer_name}</p>
                    <p><b>Status:</b> ${result.service_status}</p>
                    <p><b>Address:</b> ${result.service_address}</p>
                `;
                
                return `
<Placemark>
    <name>${result.customer_name} (${result.service_ipv4})</name>
    <description><![CDATA[${description}]]></description>
    <styleUrl>${styleUrl}</styleUrl>
    <Point>
        <coordinates>${result.service_longitude},${result.service_latitude},0</coordinates>
    </Point>
</Placemark>
                `.trim();
            }).join('\n');

            if (!placemarks) {
                displayMessage('No geocoded results were found to generate a KML file.', 'error');
                return;
            }

            const kmlContent = `
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>Splynx Service Locations Export</name>
    <Style id="activeStyle">
      <IconStyle>
        <scale>1.2</scale>
        <Icon><href>http://maps.google.com/mapfiles/kml/paddle/grn-blank.png</href></Icon>
      </IconStyle>
    </Style>
    <Style id="inactiveStyle">
      <IconStyle>
        <scale>1.2</scale>
        <Icon><href>http://maps.google.com/mapfiles/kml/paddle/red-blank.png</href></Icon>
      </IconStyle>
    </Style>
    ${placemarks}
  </Document>
</kml>
            `.trim();

            const blob = new Blob([kmlContent], { type: 'application/vnd.google-earth.kml+xml;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'splynx_map_export.kml');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        // --- Event Listeners and Initialization ---
        document.addEventListener('DOMContentLoaded', () => {
            lookupButton.addEventListener('click', processIps);
            downloadCsvButton.addEventListener('click', downloadCsv);
            downloadKmlButton.addEventListener('click', downloadKml);
            
            // Update IP count dynamically
            ipListTextarea.addEventListener('input', () => {
                const count = ipListTextarea.value.trim().split('\n').filter(ip => ip.trim() !== '').length;
                ipCountSpan.textContent = count;
            });

            // --- Load Google Maps API ---\
            if (GOOGLE_API_KEY && GOOGLE_API_KEY !== '') {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_API_KEY}&callback=initMap`;
                script.async = true;
                document.head.appendChild(script);
            } else {
                displayMessage('FATAL ERROR: Google API Key is missing. Check config.php.', 'error', true);
                document.getElementById('map').innerHTML = '<p class="text-center text-red-500 py-20">Map disabled: Google API Key missing or invalid.</p>';
            }
        });
    </script>
</body>
</html>
