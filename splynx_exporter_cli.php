<?php

/**
 * Splynx Background Data Exporter (CLI Script)
 *
 * This script runs via cron/timer, fetches all active services from Splynx
 * using a two-step process, and compiles them into a single JSON file
 * indexed by IPv4 address for fast lookup.
 */

require_once 'config.php';
// Load the Splynx API Client class from its dedicated file
require_once 'SplynxApiClient.php'; 

// Silence all output during background operation
$isSilent = true; 

// 🚨 DEBUG CODE: Set a limit for testing. Set to 0 for no limit.
$SERVICE_EXPORT_LIMIT = 100; 
$serviceCount = 0;
// --- End Debug Code ---

// --- Main Execution ---

// 1. Initialize API Client
global $splynxBaseUrl, $apiKey, $apiSecret;
$apiClient = new SplynxApiClient($splynxBaseUrl, $apiKey, $apiSecret);

// 2. Define search parameters for retrieving ONLY active customers
$customerSearchParams = [
    // Request only the customer-level fields we need
    'fields' => 'id,name,status,email,phone,address,lat,lng,additional_attributes', 
    'with_additional_attributes' => 1,
    'limit' => 50000,
    
    // 🚀 STEP 1: Filter to fetch ONLY active customers (Customer Status filter)
    'search' => json_encode(['main_attributes' => ['status' => 'active']]), 
];

// --- STEP 1: Fetch all active customers ---
$customers = $apiClient->get('admin/customers/customer', $customerSearchParams);
$activeServicesData = [];

// 3. Process data
if ($customers) {
    if (empty($customers)) {
         error_log("CRON WARNING: Splynx returned zero 'active' customers based on the API filter. Data file not updated.");
    } else {
        foreach ($customers as $customer) {
            
            // Extract customer-level data
            $customerName = $customer['name'] ?? 'N/A';
            $customerPhone = $customer['phone'] ?? '';
            $customerEmail = $customer['email'] ?? '';
            $customerStatus = $customer['status'] ?? 'N/A';

            // --- Extract Custom Attributes ---
            $customAttrs = [];
            if (defined('CUSTOM_ATTRIBUTES')) { 
                foreach (CUSTOM_ATTRIBUTES as $splynxKey => $outputKey) { 
                    $attr = array_filter($customer['additional_attributes'] ?? [], fn($a) => ($a['key'] ?? '') === $splynxKey); 
                    $attrValue = !empty($attr) ? array_values($attr)[0]['value'] ?? '' : ''; 

                    if ($attrValue) { 
                        $customAttrs[$outputKey] = $attrValue; 
                    }
                }
            }
            
            // --- STEP 2: Retrieve services for the specific customer ---
            if (isset($customer['id'])) {
                // Targeted API call for this customer's internet services
                $services = $apiClient->get('admin/customers/customer/' . $customer['id'] . '/internet-services');
                
                // Loop through services
                foreach ($services ?? [] as $service) {
                    $ipv4 = trim($service['ipv4'] ?? ''); 
                    
                    // Filter: Check for 'active' status string and valid IPv4.
                    if (($service['status'] ?? '') === 'active' && !empty($ipv4)) { 

                        // Correctly parse the comma-separated Lat/Lng string from ['geo']['marker']
                        $latLngString = $service['geo']['marker'] ?? null;
                        $coordinates = [];

                        if (!empty($latLngString)) {
                            $coordinates = array_map('trim', explode(',', $latLngString));
                        }
                        
                        // Assign location values
                        $serviceAddress   = $service['geo']['address'] ?? ($customer['address'] ?? '');
                        $serviceLatitude  = (float)($coordinates[0] ?? ($customer['lat'] ?? 0.0)); 
                        $serviceLongitude = (float)($coordinates[1] ?? ($customer['lng'] ?? 0.0));
                        
                        // Basic sanity check to ensure we have a valid IP 
                        if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            
                            // 🚨 DEBUG CODE: Echo the processed IP address to standard output
                            echo "Processing IP: {$ipv4} for customer ID {$customer['id']}\n";
                            // --- End Debug Code ---

                            // DEBUG LIMIT CHECK: Stop processing if the limit is reached
                            if ($SERVICE_EXPORT_LIMIT > 0 && $serviceCount >= $SERVICE_EXPORT_LIMIT) {
                                error_log("DEBUG: Reached service limit of {$SERVICE_EXPORT_LIMIT}. Stopping service export.");
                                break 2; // Break out of both the service loop and the customer loop
                            }
                            $serviceCount++;
                            // --- End Debug Limit Check ---

                            $serviceData = [
                                'customer_id'      => $customer['id'] ?? '',
                                'customer_name'    => $customerName,
                                'customer_status'  => $customerStatus,
                                'customer_phone'   => $customerPhone, 
                                'customer_email'   => $customerEmail, 
                                'service_status'   => $service['status'] ?? 'N/A',
                                'service_id'       => $service['id'] ?? '',
                                'service_ipv4'     => $ipv4,
                                'service_address'  => $serviceAddress, 
                                'service_latitude' => $serviceLatitude,  
                                'service_longitude'=> $serviceLongitude, 
                            ];
                            
                            // Merge custom attributes into the service data
                            $activeServicesData[$ipv4] = array_merge($serviceData, $customAttrs);
                        }
                    }
                }
            }
        } 
    }
} else {
    error_log("CRON ERROR: Failed to retrieve customer data from Splynx. Check API key/secret and URL in config.php.");
}

// 4. Save the compiled IP data as JSON to shared memory
if (!empty($activeServicesData)) {
    // If the loop was stopped by the debug limit, the count will be SERVICE_EXPORT_LIMIT.
    $finalCount = count($activeServicesData);
    $logMessage = "Splynx data updated at " . DATA_STORE_PATH . " with " . $finalCount . " services." 
                  . ($SERVICE_EXPORT_LIMIT > 0 && $finalCount === $SERVICE_EXPORT_LIMIT ? " (DEBUG LIMIT ACTIVE)" : "");
    
    $json = json_encode($activeServicesData, JSON_PRETTY_PRINT);

    if (file_put_contents(DATA_STORE_PATH, $json) === false) {
        error_log("CRON ERROR: Could not write data to " . DATA_STORE_PATH);
    } else {
        if (!$isSilent) {
             echo "SUCCESS: $logMessage\n";
        }
        error_log("CRON SUCCESS: $logMessage");
    }
} else {
    error_log("CRON WARNING: Zero active services with IPv4 found. Data file not updated."); 
}

?>