/**
 * Dealspace Tracking Pixel - Form Key Based Events (No Updates)
 */

(function() {
    'use strict';

    // Configuration injected by server
    var config = {{CONFIG_PLACEHOLDER}};

    // Default configuration if not provided
    config.trackAllForms = config.trackAllForms !== false; // Default to true
    config.autoIdentifyFromForms = config.autoIdentifyFromForms !== false; // Default to true

    // Track form keys - maps form selector to current form key (if focused)
    var activeFormKeys = {};

    // Track form states to prevent duplicate events
    var formEventHistory = {};

    // Function to normalize field mappings regardless of input format
    function normalizeFieldMappings(fieldMappings) {
        // Define default mappings
        var defaultMappings = {
            'name': ['name', 'full_name', 'fullname', 'person_name', 'customer_name'],
            'first_name': ['first_name', 'firstname', 'fname', 'given_name'],
            'last_name': ['last_name', 'lastname', 'lname', 'family_name', 'surname'],
            'email': ['email', 'email_address', 'e_mail', 'user_email', 'contact_email'],
            'phone': ['phone', 'telephone', 'mobile', 'phone_number', 'contact_phone'],
            'message': ['message', 'comment', 'inquiry', 'description', 'notes'],
            'company': ['company', 'organization', 'business', 'company_name'],
            'property_interest': ['property', 'property_id', 'listing', 'property_interest'],
            'budget': ['budget', 'price_range', 'max_price', 'price_limit']
        };

        // Already in correct object format - merge with defaults
        if (!Array.isArray(fieldMappings) && typeof fieldMappings === 'object' && fieldMappings !== null) {
            var mergedMappings = {};

            // Start with default mappings
            for (var field in defaultMappings) {
                mergedMappings[field] = defaultMappings[field].slice(); // Create a copy
            }

            // Add or extend with user mappings
            for (var userField in fieldMappings) {
                if (mergedMappings[userField]) {
                    // Merge arrays, avoiding duplicates
                    var combined = mergedMappings[userField].concat(fieldMappings[userField]);
                    mergedMappings[userField] = combined.filter(function(item, index) {
                        return combined.indexOf(item) === index;
                    });
                } else {
                    // New field not in defaults
                    mergedMappings[userField] = fieldMappings[userField];
                }
            }

            return mergedMappings;
        }

        // Array of arrays format
        if (Array.isArray(fieldMappings)) {
            var convertedMappings = {};
            var standardFields = ['name', 'first_name', 'last_name', 'email', 'phone', 'message', 'company', 'property_interest', 'budget'];

            fieldMappings.forEach(function(mappingArray, index) {
                if (Array.isArray(mappingArray) && mappingArray.length > 0) {
                    // Check if first element looks like a standard field name
                    var possibleFieldName = mappingArray[0];

                    if (standardFields.indexOf(possibleFieldName) !== -1 && mappingArray.length > 1) {
                        // First element is the field name, rest are mappings
                        convertedMappings[possibleFieldName] = mappingArray.slice(1);
                    } else {
                        // All elements are mappings, use default field order
                        var defaultFields = ['name', 'first_name', 'last_name', 'email', 'phone', 'message', 'company', 'property_interest', 'budget'];
                        var fieldName = defaultFields[index] || 'custom_field_' + index;
                        convertedMappings[fieldName] = mappingArray;
                    }
                }
            });

            // Merge converted mappings with defaults
            var finalMappings = {};

            // Start with default mappings
            for (var field in defaultMappings) {
                finalMappings[field] = defaultMappings[field].slice(); // Create a copy
            }

            // Add or extend with converted mappings
            for (var convertedField in convertedMappings) {
                if (finalMappings[convertedField]) {
                    // Merge arrays, avoiding duplicates
                    var combined = finalMappings[convertedField].concat(convertedMappings[convertedField]);
                    finalMappings[convertedField] = combined.filter(function(item, index) {
                        return combined.indexOf(item) === index;
                    });
                } else {
                    // New field not in defaults
                    finalMappings[convertedField] = convertedMappings[convertedField];
                }
            }

            return finalMappings;
        }

        // Return default mappings if structure is unrecognized or null
        return defaultMappings;
    }

    // Normalize field mappings to handle different input formats
    config.fieldMappings = normalizeFieldMappings(config.fieldMappings);

    // Ensure all field mappings are arrays
    Object.keys(config.fieldMappings).forEach(function(key) {
        if (!Array.isArray(config.fieldMappings[key])) {
            config.fieldMappings[key] = [];
        }
    });

    // Global error handler for tracking
    var trackingErrors = [];

    // Track identified users to prevent duplicates
    var identifiedUsers = new Set();

    // Debug logging
    function debugLog(message, data) {
        if (config.debug && window.console && window.console.log) {
            console.log('[Dealspace Pixel] ' + message, data || '');
        }
    }

    // Error logging
    function logError(error, context) {
        trackingErrors.push({ error: error.message, context: context, timestamp: new Date().toISOString() });
        debugLog('Error: ' + error.message, context);
    }

    // Add debug logging to see what was converted
    debugLog('Tracking script initialized', config);
    debugLog('Normalized field mappings:', config.fieldMappings);

    // Utility functions
    function generateId() {
        return 'ds_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }

    // Generate form key for tracking
    function generateFormKey() {
        return 'form_' + Math.random().toString(36).substr(2, 12) + '_' + Date.now();
    }

    function getUtmParams() {
        try {
            var params = {};
            var urlParams = new URLSearchParams(window.location.search);

            ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function(param) {
                if (urlParams.has(param)) {
                    params[param] = urlParams.get(param);
                }
            });

            return Object.keys(params).length > 0 ? params : null;
        } catch (error) {
            logError(error, 'getUtmParams');
            return null;
        }
    }

    // Get cached UTM parameters (from first visit or current page)
    function getCachedUtmParams() {
        try {
            // First check current page UTM
            var currentUtm = getUtmParams();
            if (currentUtm) {
                // Cache current UTM for future use
                setCookie('ds_utm_params', JSON.stringify(currentUtm), 30); // Cache for 30 days
                if (isLocalStorageAvailable()) {
                    localStorage.setItem('ds_utm_params', JSON.stringify(currentUtm));
                }
                return currentUtm;
            }

            // If no current UTM, try to get cached UTM
            var cachedUtm = getCookie('ds_utm_params');
            if (!cachedUtm && isLocalStorageAvailable()) {
                cachedUtm = localStorage.getItem('ds_utm_params');
            }

            return cachedUtm ? JSON.parse(cachedUtm) : null;
        } catch (error) {
            logError(error, 'getCachedUtmParams');
            return null;
        }
    }

    function getCookie(name) {
        try {
            var value = '; ' + document.cookie;
            var parts = value.split('; ' + name + '=');
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        } catch (error) {
            logError(error, 'getCookie');
            return null;
        }
    }

    function setCookie(name, value, days) {
        try {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
        } catch (error) {
            logError(error, 'setCookie');
        }
    }

    // Check if local storage is available
    function isLocalStorageAvailable() {
        try {
            var test = '__dealspace_test__';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch (error) {
            return false;
        }
    }

    // Function to detect if this is a page refresh
    function isPageRefresh() {
        try {
            // Method 1: Check if performance.navigation is available (older browsers)
            if (window.performance && window.performance.navigation) {
                return window.performance.navigation.type === 1; // TYPE_RELOAD
            }

            // Method 2: Check if performance.getEntriesByType is available (newer browsers)
            if (window.performance && window.performance.getEntriesByType) {
                var navigationEntries = window.performance.getEntriesByType('navigation');
                if (navigationEntries.length > 0) {
                    return navigationEntries[0].type === 'reload';
                }
            }

            // Method 3: Fallback - check if we've seen this exact URL recently
            var currentUrl = window.location.href;
            var lastPageUrl = getCookie('ds_last_page_url');
            var lastPageTime = getCookie('ds_last_page_time');

            if (lastPageUrl === currentUrl && lastPageTime) {
                var timeDiff = Date.now() - parseInt(lastPageTime);
                // If same URL and less than 2 seconds ago, likely a refresh
                if (timeDiff < 2000) {
                    return true;
                }
            }

            // Store current page info for future reference
            setCookie('ds_last_page_url', currentUrl, 1);
            setCookie('ds_last_page_time', Date.now().toString(), 1);

            return false;
        } catch (error) {
            logError(error, 'isPageRefresh');
            return false;
        }
    }

    // Check if page view was already tracked for current page
    function isPageViewTracked() {
        try {
            var currentUrl = window.location.href;
            var trackedPages = getCookie('ds_tracked_pages');
            if (!trackedPages && isLocalStorageAvailable()) {
                trackedPages = localStorage.getItem('ds_tracked_pages');
            }

            if (trackedPages) {
                var pages = JSON.parse(trackedPages);
                return pages.indexOf(currentUrl) !== -1;
            }
            return false;
        } catch (error) {
            logError(error, 'isPageViewTracked');
            return false;
        }
    }

    // Mark page view as tracked
    function markPageViewTracked() {
        try {
            var currentUrl = window.location.href;
            var trackedPages = getCookie('ds_tracked_pages');
            if (!trackedPages && isLocalStorageAvailable()) {
                trackedPages = localStorage.getItem('ds_tracked_pages');
            }

            var pages = trackedPages ? JSON.parse(trackedPages) : [];
            if (pages.indexOf(currentUrl) === -1) {
                pages.push(currentUrl);
                // Keep only last 50 pages to prevent cookie/storage overflow
                if (pages.length > 50) {
                    pages = pages.slice(-50);
                }

                var pagesJson = JSON.stringify(pages);
                setCookie('ds_tracked_pages', pagesJson, 1); // Expire after 1 day
                if (isLocalStorageAvailable()) {
                    localStorage.setItem('ds_tracked_pages', pagesJson);
                }
            }
        } catch (error) {
            logError(error, 'markPageViewTracked');
        }
    }

    // Check if scroll milestones were tracked for current page by current user
    function areScrollMilestonesTracked() {
        try {
            var currentUrl = window.location.href;
            var key = 'ds_scroll_tracked_' + visitorId;
            var trackedScrollPages = getCookie(key);
            if (!trackedScrollPages && isLocalStorageAvailable()) {
                trackedScrollPages = localStorage.getItem(key);
            }

            if (trackedScrollPages) {
                var pages = JSON.parse(trackedScrollPages);
                return pages.indexOf(currentUrl) !== -1;
            }
            return false;
        } catch (error) {
            logError(error, 'areScrollMilestonesTracked');
            return false;
        }
    }

    // Mark scroll milestones as tracked for current page by current user
    function markScrollMilestonesTracked() {
        try {
            var currentUrl = window.location.href;
            var key = 'ds_scroll_tracked_' + visitorId;
            var trackedScrollPages = getCookie(key);
            if (!trackedScrollPages && isLocalStorageAvailable()) {
                trackedScrollPages = localStorage.getItem(key);
            }

            var pages = trackedScrollPages ? JSON.parse(trackedScrollPages) : [];
            if (pages.indexOf(currentUrl) === -1) {
                pages.push(currentUrl);
                // Keep only last 50 pages to prevent cookie/storage overflow
                if (pages.length > 50) {
                    pages = pages.slice(-50);
                }

                var pagesJson = JSON.stringify(pages);
                setCookie(key, pagesJson, 30); // Expire after 30 days
                if (isLocalStorageAvailable()) {
                    localStorage.setItem(key, pagesJson);
                }
            }
        } catch (error) {
            logError(error, 'markScrollMilestonesTracked');
        }
    }

    // Get stored person data
    function getStoredPersonData() {
        try {
            var personData = getCookie('ds_person_data');
            if (!personData && isLocalStorageAvailable()) {
                personData = localStorage.getItem('ds_person_data');
            }
            return personData ? JSON.parse(personData) : null;
        } catch (error) {
            logError(error, 'getStoredPersonData');
            return null;
        }
    }

    // Create a unique identifier for person data to prevent duplicates
    function createPersonDataHash(personData) {
        if (!personData) return null;

        // Normalize email and phone to prevent minor variations from creating different hashes
        var email = (personData.email || '').toLowerCase().trim();
        var phone = (personData.phone || '').replace(/\D/g, ''); // Remove non-digits from phone
        var name = (personData.name || personData.first_name + ' ' + personData.last_name || '').toLowerCase().trim();

        // Only create hash if we have meaningful data
        if (!email && !phone) return null;
        if (!name || name === 'undefined undefined') return null;

        var key = email + '|' + phone + '|' + name;
        debugLog('Creating person hash:', { personData: personData, hash: key });

        return key;
    }

    // Enhanced visitor tracking with fallbacks
    var visitorId = getCookie('ds_visitor_id');
    var sessionId = getCookie('ds_session_id');

    // Fallback to localStorage if cookies fail
    if (!visitorId && isLocalStorageAvailable()) {
        try {
            visitorId = localStorage.getItem('ds_visitor_id');
        } catch (error) {
            logError(error, 'localStorage visitor fallback');
        }
    }

    if (!sessionId && isLocalStorageAvailable()) {
        try {
            sessionId = localStorage.getItem('ds_session_id');
        } catch (error) {
            logError(error, 'localStorage session fallback');
        }
    }

    // Generate new IDs if not found
    if (!visitorId) {
        visitorId = generateId();
        setCookie('ds_visitor_id', visitorId, 365);
        if (isLocalStorageAvailable()) {
            try {
                localStorage.setItem('ds_visitor_id', visitorId);
            } catch (error) {
                logError(error, 'localStorage visitor save');
            }
        }
    }

    if (!sessionId) {
        sessionId = generateId();
        setCookie('ds_session_id', sessionId, 1);
        if (isLocalStorageAvailable()) {
            try {
                localStorage.setItem('ds_session_id', sessionId);
            } catch (error) {
                logError(error, 'localStorage session save');
            }
        }
    }

    debugLog('Visitor ID: ' + visitorId + ', Session ID: ' + sessionId);

    // ================================
    // SEQUENTIAL REQUEST PROCESSING
    // ================================

    // Request queue for both offline support and sequential processing
    var requestQueue = [];
    var isProcessingQueue = false;
    var isOnline = navigator.onLine !== false;

    // Sequential request processor - ensures only one request at a time
    function processQueue() {
        // Prevent concurrent processing
        if (isProcessingQueue || requestQueue.length === 0) {
            return;
        }

        // Only process if online
        if (!isOnline) {
            debugLog('Offline - queue processing paused. Queue length: ' + requestQueue.length);
            return;
        }

        isProcessingQueue = true;
        var request = requestQueue.shift();

        debugLog('Processing request from queue. Remaining: ' + requestQueue.length, {
            endpoint: request.endpoint,
            queuePosition: 'current'
        });

        // Process the current request
        sendTrackingDataInternal(request.endpoint, request.data, function(error, response) {
            // Mark processing as complete
            isProcessingQueue = false;

            // Call the original callback
            if (request.callback) {
                request.callback(error, response);
            }

            // Process next item in queue after a small delay to prevent overwhelming the server
            if (requestQueue.length > 0) {
                setTimeout(function() {
                    processQueue();
                }, 50); // 50ms delay between requests
            } else {
                debugLog('Queue processing complete - all requests sent');
            }
        });
    }

    // Enhanced online/offline handlers
    window.addEventListener('online', function() {
        isOnline = true;
        debugLog('Connection restored. Queue length: ' + requestQueue.length);
        // Start processing queue when connection is restored
        setTimeout(processQueue, 100);
    });

    window.addEventListener('offline', function() {
        isOnline = false;
        debugLog('Connection lost. Requests will be queued. Current queue length: ' + requestQueue.length);
    });

    // Internal API call function - SIMPLIFIED (NO UPDATE LOGIC)
    function sendTrackingDataInternal(endpoint, data, callback) {
        try {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', config.apiUrl + '/' + endpoint, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Timeout handling
            xhr.timeout = 10000; // 10 seconds

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    debugLog('Successfully tracked: ' + endpoint, data);
                    if (callback) callback(null, JSON.parse(xhr.responseText || '{}'));
                } else {
                    var error = new Error('HTTP ' + xhr.status);
                    logError(error, 'sendTrackingData - ' + endpoint);
                    if (callback) callback(error);
                }
            };

            xhr.onerror = function() {
                var error = new Error('Network error');
                logError(error, 'sendTrackingData - ' + endpoint);
                if (callback) callback(error);
            };

            xhr.ontimeout = function() {
                var error = new Error('Request timeout');
                logError(error, 'sendTrackingData - ' + endpoint);
                if (callback) callback(error);
            };

            // Prepare data - always add required fields
            data.script_key = config.scriptKey;
            data.visitor_id = visitorId;
            data.session_id = sessionId;
            data.timestamp = new Date().toISOString();
            data.user_agent = navigator.userAgent;
            data.screen_resolution = screen.width + 'x' + screen.height;
            data.viewport_size = window.innerWidth + 'x' + window.innerHeight;

            // Only include person data if explicitly provided in the data or for specific events
            var storedPersonData = getStoredPersonData();
            if (data.person_data) {
                // Person data was explicitly provided - keep it
            } else if (data.include_person_data && storedPersonData) {
                // Event specifically requests person data to be included
                data.person_data = storedPersonData;
            } else if (endpoint === 'form-submission' && storedPersonData) {
                // Form submissions should include stored person data
                data.person_data = storedPersonData;
            }
            // For page views and other events, don't automatically include person data

            if (config.trackUtmParameters) {
                data.utm_params = getCachedUtmParams();
            }

            debugLog('Sending tracking data to: ' + endpoint, data);
            xhr.send(JSON.stringify(data));

        } catch (error) {
            logError(error, 'sendTrackingDataInternal');
            if (callback) callback(error);
        }
    }

    // Public API call function with sequential queue support
    function sendTrackingData(endpoint, data, callback) {
        // Add to queue regardless of online status
        var queueItem = {
            endpoint: endpoint,
            data: data,
            callback: callback,
            timestamp: Date.now()
        };

        requestQueue.push(queueItem);

        debugLog('Request queued: ' + endpoint + '. Queue length: ' + requestQueue.length, {
            endpoint: endpoint,
            queueLength: requestQueue.length,
            isProcessing: isProcessingQueue,
            isOnline: isOnline
        });

        // Start processing if not already processing and online
        if (!isProcessingQueue && isOnline) {
            setTimeout(processQueue, 10); // Small delay to allow batching of rapid requests
        }
    }

    // Enhanced person data validation - requires email/phone + name
    function isValidPersonData(personData) {
        if (!personData) return false;

        var hasEmail = personData.email && personData.email.trim().length > 3 && personData.email.includes('@');
        var hasPhone = personData.phone && personData.phone.trim().length >= 10;
        var hasEmailOrPhone = hasEmail || hasPhone;

        var hasValidName = false;
        if (personData.name && personData.name.trim().length > 1 && personData.name !== 'undefined undefined') {
            hasValidName = true;
        } else if (personData.first_name && personData.first_name.trim().length > 0 &&
                personData.last_name && personData.last_name.trim().length > 0) {
            hasValidName = true;
        }

        var isValid = hasEmailOrPhone && hasValidName;

        debugLog('Person data validation:', {
            personData: personData,
            hasEmail: hasEmail,
            hasPhone: hasPhone,
            hasValidName: hasValidName,
            isValid: isValid
        });

        return isValid;
    }

    // Extract person data from form with dynamic field mappings
    function extractPersonDataFromForm(formData) {
        var personData = {};
        var hasPersonData = false;

        debugLog('Extracting person data using field mappings', config.fieldMappings);
        debugLog('Form data available:', Object.keys(formData));

        // Function to find field value using mapping array
        function findFieldValue(fieldMappingsArray) {
            if (!Array.isArray(fieldMappingsArray) || fieldMappingsArray.length === 0) {
                return null;
            }

            for (var i = 0; i < fieldMappingsArray.length; i++) {
                var fieldName = fieldMappingsArray[i];
                if (formData.hasOwnProperty(fieldName) && formData[fieldName]) {
                    debugLog('Found field match: ' + fieldName + ' = ' + formData[fieldName]);
                    return formData[fieldName];
                }
            }
            return null;
        }

        // Function to find and combine multiple field values (for cases like multiple name inputs)
        function findAllFieldValues(fieldMappingsArray) {
            if (!Array.isArray(fieldMappingsArray) || fieldMappingsArray.length === 0) {
                return [];
            }

            var values = [];
            for (var i = 0; i < fieldMappingsArray.length; i++) {
                var fieldName = fieldMappingsArray[i];
                if (formData.hasOwnProperty(fieldName) && formData[fieldName] && formData[fieldName].trim()) {
                    values.push(formData[fieldName].trim());
                    debugLog('Found field match: ' + fieldName + ' = ' + formData[fieldName]);
                }
            }
            return values;
        }

        // Extract data using field mappings
        Object.keys(config.fieldMappings).forEach(function(standardField) {
            var mappingsArray = config.fieldMappings[standardField];

            if (standardField === 'name') {
                // For name fields, we might want to combine multiple values
                var nameValues = findAllFieldValues(mappingsArray);

                if (nameValues.length > 0) {
                    // Join multiple name values with space, or use the first one
                    personData.name = nameValues.join(' ').trim();
                    hasPersonData = true;
                    debugLog('Combined name field: ' + personData.name);
                }
            } else {
                // For other fields, use the first match found
                var value = findFieldValue(mappingsArray);
                if (value && value.trim()) {
                    personData[standardField] = value.trim();
                    hasPersonData = true;
                    debugLog('Set field: ' + standardField + ' = ' + value);
                }
            }
        });

        // Create full name if not provided but first/last names are available
        if (!personData.name && (personData.first_name || personData.last_name)) {
            personData.name = (personData.first_name || '') + ' ' + (personData.last_name || '');
            personData.name = personData.name.trim();
            if (personData.name) {
                hasPersonData = true;
                debugLog('Created full name from first/last: ' + personData.name);
            }
        }

        // Only return person data if it meets validation criteria
        if (hasPersonData && isValidPersonData(personData)) {
            debugLog('Successfully extracted valid person data', personData);
            return personData;
        }

        debugLog('No valid person data found or validation failed', personData);
        return null;
    }

    // Page view tracking - ONCE PER PAGE
    if (config.trackPageViews !== false) {
        var pageStartTime = Date.now();
        var pageViewTracked = false;
        var visibilityHidden = false;
        var focusTime = 0;
        var lastFocusTime = Date.now();

        // Track page visibility
        function handleVisibilityChange() {
            if (document.hidden || document.webkitHidden || document.msHidden) {
                if (!visibilityHidden) {
                    focusTime += Date.now() - lastFocusTime;
                    visibilityHidden = true;
                }
            } else {
                if (visibilityHidden) {
                    lastFocusTime = Date.now();
                    visibilityHidden = false;
                }
            }
        }

        // Add visibility change listeners
        document.addEventListener('visibilitychange', handleVisibilityChange);
        document.addEventListener('webkitvisibilitychange', handleVisibilityChange);
        document.addEventListener('msvisibilitychange', handleVisibilityChange);

        // Track window focus/blur
        window.addEventListener('focus', function() {
            if (visibilityHidden) {
                lastFocusTime = Date.now();
                visibilityHidden = false;
            }
        });

        window.addEventListener('blur', function() {
            if (!visibilityHidden) {
                focusTime += Date.now() - lastFocusTime;
                visibilityHidden = true;
            }
        });

        function trackPageView() {
            if (pageViewTracked || isPageViewTracked()) {
                debugLog('Page view already tracked for this page - skipping');
                return;
            }

            // Check if this is a page refresh
            var isRefresh = isPageRefresh();

            if (isRefresh) {
                debugLog('Page refresh detected - skipping page view tracking');
                return;
            }

            pageViewTracked = true;
            markPageViewTracked();

            var currentTime = Date.now();
            var totalTime = Math.round((currentTime - pageStartTime) / 1000);
            var activeFocusTime = visibilityHidden ? focusTime : focusTime + (currentTime - lastFocusTime);

            debugLog('Tracking new page view (not a refresh)');

            // Prepare page view data
            var pageViewData = {
                page_url: window.location.href,
                page_title: document.title,
                page_referrer: document.referrer || null,
                page_duration: totalTime,
                active_time: Math.round(activeFocusTime / 1000),
                scroll_depth: getScrollDepth(),
                is_refresh: false
            };

            // Include person data if available
            var storedPersonData = getStoredPersonData();
            if (storedPersonData) {
                pageViewData.person_data = storedPersonData;
                debugLog('Including stored person data in page view', storedPersonData);
            } else {
                debugLog('No stored person data found for page view');
            }

            sendTrackingData('page-view', pageViewData);
        }

        // Calculate scroll depth
        function getScrollDepth() {
            try {
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                var windowHeight = window.innerHeight;
                var documentHeight = Math.max(
                    document.body.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.clientHeight,
                    document.documentElement.scrollHeight,
                    document.documentElement.offsetHeight
                );

                var scrollDepth = Math.round(((scrollTop + windowHeight) / documentHeight) * 100);
                return Math.min(scrollDepth, 100);
            } catch (error) {
                logError(error, 'getScrollDepth');
                return 0;
            }
        }

        // Track initial page view
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(trackPageView, 100);
            });
        } else {
            setTimeout(trackPageView, 100);
        }

        // Track page unload with enhanced reliability
        var unloadTracked = false;
        function trackPageUnload() {
            if (unloadTracked) return;
            unloadTracked = true;

            var currentTime = Date.now();
            var totalTime = Math.round((currentTime - pageStartTime) / 1000);
            var activeFocusTime = visibilityHidden ? focusTime : focusTime + (currentTime - lastFocusTime);

            var data = {
                script_key: config.scriptKey,
                visitor_id: visitorId,
                session_id: sessionId,
                timestamp: new Date().toISOString(),
                page_url: window.location.href,
                page_title: document.title,
                page_referrer: document.referrer || null,
                page_duration: totalTime,
                active_time: Math.round(activeFocusTime / 1000),
                scroll_depth: getScrollDepth(),
                user_agent: navigator.userAgent,
                screen_resolution: screen.width + 'x' + screen.height,
                viewport_size: window.innerWidth + 'x' + window.innerHeight,
                is_unload: true
            };

            // Include person data if available
            var storedPersonData = getStoredPersonData();
            if (storedPersonData) {
                data.person_data = storedPersonData;
                debugLog('Including stored person data in page unload', storedPersonData);
            }

            if (config.trackUtmParameters) {
                data.utm_params = getCachedUtmParams();
            }

            // Use sendBeacon if available for more reliable unload tracking
            if (navigator.sendBeacon) {
                try {
                    navigator.sendBeacon(
                        config.apiUrl + '/page-view',
                        JSON.stringify(data)
                    );
                    debugLog('Page unload tracked via sendBeacon');
                } catch (error) {
                    logError(error, 'sendBeacon unload tracking');
                }
            } else {
                // Fallback to synchronous request
                try {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', config.apiUrl + '/page-view', false); // Synchronous
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.send(JSON.stringify(data));
                } catch (error) {
                    logError(error, 'synchronous unload tracking');
                }
            }
        }

        // Multiple unload event listeners for better coverage
        window.addEventListener('beforeunload', trackPageUnload);
        window.addEventListener('pagehide', trackPageUnload);
        window.addEventListener('unload', trackPageUnload);

        // Track scroll milestones - ONCE PER PAGE PER USER
        var scrollMilestones = [25, 50, 75, 90];
        var trackedMilestones = [];
        var scrollTrackingEnabled = !areScrollMilestonesTracked();

        function trackScrollMilestones() {
            if (!scrollTrackingEnabled) return;

            var scrollDepth = getScrollDepth();

            scrollMilestones.forEach(function(milestone) {
                if (scrollDepth >= milestone && trackedMilestones.indexOf(milestone) === -1) {
                    trackedMilestones.push(milestone);

                    sendTrackingData('custom-event', {
                        event_type: 'Scroll Milestone',
                        page_url: window.location.href,
                        page_title: document.title,
                        message: 'User scrolled to ' + milestone + '% of page',
                        description: 'Scroll depth milestone reached',
                        scroll_depth: scrollDepth
                    });

                    // If we've tracked all milestones, mark as complete and disable further tracking
                    if (trackedMilestones.length === scrollMilestones.length) {
                        markScrollMilestonesTracked();
                        scrollTrackingEnabled = false;
                        debugLog('All scroll milestones tracked for this page by this user');
                    }
                }
            });
        }

        // Throttled scroll tracking (only if enabled for this page/user)
        if (scrollTrackingEnabled) {
            var scrollTimer = null;
            window.addEventListener('scroll', function() {
                if (scrollTimer) clearTimeout(scrollTimer);
                scrollTimer = setTimeout(trackScrollMilestones, 250);
            });
        }
    }

    // ================================
    // NEW FORM TRACKING WITH FORM KEYS
    // ================================

    // Enhanced form tracking with FORM KEY SYSTEM (NO UPDATES)
    function setupFormTracking() {
        var forms = document.querySelectorAll('form');

        debugLog('Setting up form tracking for ' + forms.length + ' forms found');

        Array.prototype.forEach.call(forms, function(form, index) {
            // Skip if already has tracking listener
            if (form.dataset.dealspaceTracked) return;
            form.dataset.dealspaceTracked = 'true';

            var formId = getFormSelector(form, index);
            var formStartTime = Date.now();
            var formInteractions = 0;
            var formFields = {};
            var hasBeenFocused = false;

            // Initialize form event history if not exists with unique keys per form
            var formHistoryKey = formId + '_' + Date.now(); // Make each form instance unique
            if (!formEventHistory[formHistoryKey]) {
                formEventHistory[formHistoryKey] = {
                    formStarted: false,
                    formFilled: false,
                    formSubmitted: false
                };
            }

            // Track form interactions
            var inputs = form.querySelectorAll('input, textarea, select');
            const filledFormsLock = new Set();
            Array.prototype.forEach.call(inputs, function(input) {
                if (input.type !== 'hidden' && input.type !== 'submit' && input.type !== 'button') {

                    // FOCUS EVENT - Generate form key and track "Form Started"
                    input.addEventListener('focus', function() {
                        formFields[input.name || input.id || 'unnamed'] = {
                            type: input.type,
                            focused: true,
                            focusTime: Date.now()
                        };

                        // Generate form key on first focus (if not already generated)
                        if (!activeFormKeys[formId]) {
                            activeFormKeys[formId] = generateFormKey();
                            debugLog('Generated form key for ' + formId + ': ' + activeFormKeys[formId]);
                        }

                        // Track "Form Started" only once per form using unique history key
                        if (!hasBeenFocused && !formEventHistory[formHistoryKey].formStarted) {
                            hasBeenFocused = true;
                            formEventHistory[formHistoryKey].formStarted = true;

                            var formDuration = Math.round((Date.now() - formStartTime) / 1000);

                            sendTrackingData('custom-event', {
                                event_type: 'Form Started',
                                page_url: window.location.href,
                                page_title: document.title,
                                message: 'User started filling form',
                                description: 'Form interaction started',
                                form_selector: formId,
                                form_key: activeFormKeys[formId],
                                form_duration: formDuration,
                                form_interactions: formInteractions,
                                utm_params: getCachedUtmParams()
                            });

                            debugLog('Form Started event sent for: ' + formId + ' with key: ' + activeFormKeys[formId]);
                        }
                    });

                    // BLUR EVENT - Modified to NOT remove form key immediately
                    input.addEventListener('blur', function() {
                        if (formFields[input.name || input.id || 'unnamed']) {
                            formFields[input.name || input.id || 'unnamed'].blurTime = Date.now();
                        }

                        // Only remove form key if ALL inputs are blurred AND we're not about to submit
                        setTimeout(function() {
                            // Check if any input in this form is still focused
                            var anyFocused = false;
                            var isSubmitting = form.dataset.submitting === 'true';

                            Array.prototype.forEach.call(inputs, function(inp) {
                                if (inp === document.activeElement) {
                                    anyFocused = true;
                                }
                            });

                            // Also check if any submit button in this form is focused
                            var submitButtons = form.querySelectorAll('input[type="submit"], button[type="submit"], button:not([type])');
                            Array.prototype.forEach.call(submitButtons, function(btn) {
                                if (btn === document.activeElement) {
                                    anyFocused = true; // Treat submit button focus as form still active
                                }
                            });

                            // Only remove form key if truly no longer interacting with form AND not submitting
                            if (!anyFocused && !isSubmitting && activeFormKeys[formId]) {
                                debugLog('All inputs blurred and not submitting, removing form key for: ' + formId);
                                delete activeFormKeys[formId];
                            }
                        }, 150); // Slightly longer delay to handle submit button clicks
                    });

                    // INPUT EVENT - Track interactions and check for "Form Filled"
                    input.addEventListener('input', function () {
                        formInteractions++;

                        // Only process if form has an active key
                        if (!activeFormKeys[formId]) return;

                        // Don't allow duplicate firing for the same form
                        if (filledFormsLock.has(formId)) return;

                        var currentFormData = {};
                        var filledFieldsCount = 0;

                        Array.prototype.forEach.call(inputs, function (inp) {
                            if (inp.name && inp.value &&
                                inp.type !== 'password' &&
                                inp.type !== 'file' &&
                                inp.type !== 'hidden') {

                                if (inp.type === 'radio' || inp.type === 'checkbox') {
                                    if (inp.checked) {
                                        currentFormData[inp.name] = inp.value;
                                        filledFieldsCount++;
                                    }
                                } else if (inp.value.trim().length > 0) {
                                    currentFormData[inp.name] = inp.value.length > 1000
                                        ? inp.value.substring(0, 1000) + '...'
                                        : inp.value;
                                    filledFieldsCount++;
                                }
                            }
                        });

                        var extractedPersonData = null;
                        if (config.autoIdentifyFromForms !== false &&
                            filledFieldsCount >= 3 &&
                            !formEventHistory[formHistoryKey].formFilled) {

                            extractedPersonData = extractPersonDataFromForm(currentFormData);

                            if (extractedPersonData && isValidPersonData(extractedPersonData)) {
                                var personHash = createPersonDataHash(extractedPersonData);

                                if (personHash && !identifiedUsers.has(personHash)) {
                                    // Set both locks
                                    formEventHistory[formHistoryKey].formFilled = true;
                                    filledFormsLock.add(formId);

                                    var formDuration = Math.round((Date.now() - formStartTime) / 1000);

                                    debugLog('Auto-identifying user from form fill data', extractedPersonData);
                                    console.log('called filled from here', extractedPersonData);

                                    window.dealspace.identify(extractedPersonData, 'Form Filled');
                                    identifiedUsers.add(personHash);

                                    sendTrackingData('custom-event', {
                                        event_type: 'Form Filled',
                                        page_url: window.location.href,
                                        page_title: document.title,
                                        message: 'User filled form with valid contact data',
                                        description: 'Form filled with validated name and email/phone',
                                        form_selector: formId,
                                        form_key: activeFormKeys[formId],
                                        form_data: currentFormData,
                                        form_duration: formDuration,
                                        form_interactions: formInteractions,
                                        person_data: extractedPersonData,
                                        utm_params: getCachedUtmParams()
                                    });

                                    debugLog('Form Filled event sent for: ' + formId + ' with key: ' + activeFormKeys[formId]);
                                }
                            }
                        }
                    });
                }
            });

            // SUBMIT EVENT - Track "Form Submitted" with better form key handling
            form.addEventListener('submit', function(e) {
                // Prevent default submission initially to ensure tracking completes
                e.preventDefault();

                var formSelector = getFormSelector(form, index);
                var originalForm = form;

                // Mark form as submitting to prevent form key removal
                originalForm.dataset.submitting = 'true';

                debugLog('Form submission detected', formSelector);

                // Check using unique history key to prevent duplicates
                if (formEventHistory[formHistoryKey].formSubmitted) {
                    debugLog('Form submission already tracked, proceeding with original submission');
                    proceedWithSubmission();
                    return;
                }

                // Extract final form data
                var finalFormData = {};
                Array.prototype.forEach.call(inputs, function(input) {
                    if (input.name && input.value &&
                        input.type !== 'password' &&
                        input.type !== 'file' &&
                        input.type !== 'hidden') {

                        if (input.type === 'radio' || input.type === 'checkbox') {
                            if (input.checked) {
                                finalFormData[input.name] = input.value;
                            }
                        } else {
                            var value = input.value.length > 1000 ?
                                    input.value.substring(0, 1000) + '...' :
                                    input.value;
                            finalFormData[input.name] = value;
                        }
                    }
                });

                var formDuration = Math.round((Date.now() - formStartTime) / 1000);

                // Extract person data from final form
                var extractedPersonData = null;
                if (config.autoIdentifyFromForms !== false) {
                    extractedPersonData = extractPersonDataFromForm(finalFormData);
                    if (extractedPersonData) {
                        var personHash = createPersonDataHash(extractedPersonData);

                        // Only identify if we haven't identified this person data before
                        if (personHash && !identifiedUsers.has(personHash)) {
                            debugLog('Auto-identifying user from form submission', extractedPersonData);
                            window.dealspace.identify(extractedPersonData, 'Form Submitted');
                            console.log('call form submitted from here', extractedPersonData);

                            identifiedUsers.add(personHash);
                        }
                    }
                }

                // Function to proceed with form submission
                function proceedWithSubmission(event) {
                    debugLog('Proceeding with original form submission');

                    // Clean up submission flag
                    originalForm.dataset.submitting = 'false';

                    // Remove the event listener using the function name
                    originalForm.removeEventListener('submit', proceedWithSubmission);

                    // Submit the form normally
                    if (typeof originalForm.submit === 'function') {
                        originalForm.submit();
                    } else {
                        // Fallback: create and dispatch a new submit event
                        var submitEvent = document.createEvent('Event');
                        submitEvent.initEvent('submit', true, true);
                        originalForm.dispatchEvent(submitEvent);
                    }
                }

                // Mark as submitted using unique history key
                formEventHistory[formHistoryKey].formSubmitted = true;

                // Use EXISTING form key if available, otherwise generate one for submission
                var formKey = activeFormKeys[formSelector];
                if (!formKey) {
                    formKey = generateFormKey();
                    activeFormKeys[formSelector] = formKey; // Store it temporarily
                    debugLog('Generated form key for submission: ' + formKey);
                } else {
                    debugLog('Using existing form key for submission: ' + formKey);
                }

                // Prepare submission data
                var submissionData = {
                    event_type: 'Form Submitted',
                    page_url: window.location.href,
                    page_title: document.title,
                    page_referrer: document.referrer || null,
                    message: 'User submitted form',
                    description: 'Form submission completed',
                    form_selector: formSelector,
                    form_key: formKey,
                    form_data: finalFormData,
                    form_duration: formDuration,
                    form_interactions: formInteractions,
                    form_fields_data: formFields,
                    utm_params: getCachedUtmParams()
                };

                // Include person data if available
                if (extractedPersonData) {
                    submissionData.person_data = extractedPersonData;
                }

                // Send submission event
                debugLog('Sending Form Submitted event with key: ' + formKey);
                sendTrackingData('custom-event', submissionData, function(error, response) {
                    // Proceed with form submission after tracking
                    setTimeout(proceedWithSubmission, 100);
                });

                // Remove form key after successful tracking (with delay)
                setTimeout(function() {
                    if (activeFormKeys[formSelector]) {
                        delete activeFormKeys[formSelector];
                        debugLog('Removed form key after submission: ' + formSelector);
                    }
                }, 500);

                // Fallback: proceed with submission after 2 seconds regardless of tracking status
                setTimeout(function() {
                    debugLog('Tracking timeout - proceeding with form submission');
                    proceedWithSubmission();
                }, 2000);
            });
        });

        debugLog('Form tracking setup complete. Tracking ' + forms.length + ' forms.');
    }

    // Get form selector helper
    function getFormSelector(form, index) {
        if (form.id) return '#' + form.id;
        if (form.name) return '[name="' + form.name + '"]';
        if (form.className) return '.' + form.className.split(' ')[0];
        return 'form:nth-of-type(' + (index + 1) + ')';
    }

    // Initialize form tracking when DOM is ready
    var formsSetup = false;
    function initializeFormTracking() {
        if (formsSetup) return;
        formsSetup = true;
        setupFormTracking();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFormTracking);
    } else {
        initializeFormTracking();
    }

    // Monitor for dynamically added forms
    var observer;
    if (window.MutationObserver) {
        observer = new MutationObserver(function(mutations) {
            var shouldResetup = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        var node = mutation.addedNodes[i];
                        if (node.nodeType === 1) { // Element node
                            if (node.tagName === 'FORM' || node.querySelector('form')) {
                                shouldResetup = true;
                                break;
                            }
                        }
                    }
                }
            });

            if (shouldResetup) {
                debugLog('New forms detected, re-setting up tracking');
                setTimeout(function() {
                    formsSetup = false;
                    initializeFormTracking();
                }, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Public API
    window.dealspace = window.dealspace || {};

    // Track custom events (but NOT "General Inquiry" - this is removed)
    window.dealspace.track = function(eventType, data, callback) {
        // Block "General Inquiry" events
        if (eventType === 'General Inquiry') {
            debugLog('Blocking "General Inquiry" event - this event type is disabled');
            if (callback) callback(new Error('General Inquiry events are disabled'));
            return;
        }

        data = data || {};
        debugLog('Custom event: ' + eventType, data);

        var eventData = {
            event_type: eventType,
            page_url: window.location.href,
            page_title: document.title,
            page_referrer: document.referrer || null,
            message: data.message || null,
            description: data.description || null,
            property_data: data.property || null,
            custom_data: data.custom || null
        };

        // Only include person data if explicitly provided
        if (data.person) {
            eventData.person_data = data.person;
        }

        sendTrackingData('custom-event', eventData, callback);
    };

    // Property tracking helper
    window.dealspace.trackProperty = function(propertyData, eventType, callback) {
        eventType = eventType || 'Viewed Property';
        debugLog('Property event: ' + eventType, propertyData);

        sendTrackingData('custom-event', {
            event_type: eventType,
            page_url: window.location.href,
            page_title: document.title,
            page_referrer: document.referrer || null,
            property_data: propertyData,
            message: eventType + ': ' + (propertyData.address || propertyData.title || 'Property')
        }, callback);
    };

    // Enhanced identify visitor with validation and duplicate prevention
    window.dealspace.identify = function(personData, eventType = "Visitor Identified") {
        debugLog('Attempting to identify visitor', personData);

        // Validate person data before storing
        if (!isValidPersonData(personData)) {
            debugLog('Person data validation failed - requires email/phone + name', personData);
            return false;
        }

        // Check if we've already identified this person in this session
        var personHash = createPersonDataHash(personData);
        if (personHash && identifiedUsers.has(personHash)) {
            debugLog('Person already identified in this session, skipping duplicate', personData);
            return true; // Return true since the person is already identified
        }

        try {
            setCookie('ds_person_data', JSON.stringify(personData), 365);
            if (isLocalStorageAvailable()) {
                localStorage.setItem('ds_person_data', JSON.stringify(personData));
            }

            // Add to identified users set
            if (personHash) {
                identifiedUsers.add(personHash);
            }

            debugLog('Visitor identified successfully', personData);

            // Send identification event (only once per person per session)
            sendTrackingData('custom-event', {
                event_type: eventType,
                page_url: window.location.href,
                page_title: document.title,
                person_data: personData,
                message: 'Visitor identified: ' + (personData.email || personData.name || 'Unknown')
            });

            return true;
        } catch (error) {
            logError(error, 'identify');
            return false;
        }
    };

    // Get visitor information
    window.dealspace.getVisitor = function() {
        try {
            var personData = getStoredPersonData();

            return {
                visitorId: visitorId,
                sessionId: sessionId,
                personData: personData,
                errors: trackingErrors,
                utmParams: getCachedUtmParams(),
                identifiedUsersCount: identifiedUsers.size,
                fieldMappings: config.fieldMappings,
                queueLength: requestQueue.length,
                isProcessingQueue: isProcessingQueue,
                isOnline: isOnline,
                activeFormKeys: activeFormKeys,
                formEventHistory: formEventHistory
            };
        } catch (error) {
            logError(error, 'getVisitor');
            return {
                visitorId: visitorId,
                sessionId: sessionId,
                personData: null,
                errors: trackingErrors,
                utmParams: null,
                identifiedUsersCount: 0,
                fieldMappings: config.fieldMappings,
                queueLength: requestQueue.length,
                isProcessingQueue: isProcessingQueue,
                isOnline: isOnline,
                activeFormKeys: activeFormKeys,
                formEventHistory: formEventHistory
            };
        }
    };

    // Manual page view tracking (for SPAs)
    window.dealspace.trackPageView = function(pageData, callback) {
        pageData = pageData || {};

        var trackingData = {
            page_url: pageData.url || window.location.href,
            page_title: pageData.title || document.title,
            page_referrer: pageData.referrer || document.referrer || null,
            page_duration: 0,
            is_manual: true,
            is_refresh: false
        };

        // Include person data if available
        var storedPersonData = getStoredPersonData();
        if (storedPersonData) {
            trackingData.person_data = storedPersonData;
            debugLog('Including stored person data in manual page view', storedPersonData);
        }

        sendTrackingData('page-view', trackingData, callback);
    };

    // Get configuration (for debugging)
    window.dealspace.getConfig = function() {
        return config;
    };

    // Enable/disable debug mode
    window.dealspace.setDebug = function(enabled) {
        config.debug = enabled;
        debugLog('Debug mode ' + (enabled ? 'enabled' : 'disabled'));
    };

    // Clear tracking cache (for testing)
    window.dealspace.clearCache = function() {
        try {
            setCookie('ds_tracked_pages', '', -1);
            setCookie('ds_scroll_tracked_' + visitorId, '', -1);
            setCookie('ds_utm_params', '', -1);

            if (isLocalStorageAvailable()) {
                localStorage.removeItem('ds_tracked_pages');
                localStorage.removeItem('ds_scroll_tracked_' + visitorId);
                localStorage.removeItem('ds_utm_params');
            }

            // Clear identified users set
            identifiedUsers.clear();

            // Clear form tracking data
            activeFormKeys = {};
            formEventHistory = {};

            debugLog('Tracking cache cleared');
        } catch (error) {
            logError(error, 'clearCache');
        }
    };

    // Validate person data (public method for external use)
    window.dealspace.isValidPersonData = function(personData) {
        return isValidPersonData(personData);
    };

    // Clear identified users (for testing)
    window.dealspace.clearIdentifiedUsers = function() {
        identifiedUsers.clear();
        debugLog('Identified users cache cleared');
    };

    // Test field mapping extraction (for debugging)
    window.dealspace.testFieldMapping = function(formData) {
        debugLog('Testing field mapping with data:', formData);
        var result = extractPersonDataFromForm(formData);
        debugLog('Field mapping result:', result);
        return result;
    };

    // Get current field mappings (for debugging)
    window.dealspace.getFieldMappings = function() {
        return config.fieldMappings;
    };

    // Update field mappings dynamically (for advanced usage)
    window.dealspace.updateFieldMappings = function(newMappings) {
        if (newMappings && typeof newMappings === 'object') {
            config.fieldMappings = Object.assign({}, config.fieldMappings, newMappings);
            debugLog('Field mappings updated:', config.fieldMappings);
            return true;
        }
        return false;
    };

    // ================================
    // FORM KEY MANAGEMENT APIs
    // ================================

    // Get active form keys (for debugging)
    window.dealspace.getActiveFormKeys = function() {
        return activeFormKeys;
    };

    // Get form event history (for debugging)
    window.dealspace.getFormEventHistory = function() {
        return formEventHistory;
    };

    // Clear form keys and history (for testing)
    window.dealspace.clearFormTracking = function() {
        activeFormKeys = {};
        formEventHistory = {};
        debugLog('Form tracking data cleared');
    };

    // Force remove form key (for debugging)
    window.dealspace.removeFormKey = function(formSelector) {
        if (activeFormKeys[formSelector]) {
            delete activeFormKeys[formSelector];
            debugLog('Manually removed form key for: ' + formSelector);
            return true;
        }
        return false;
    };

    // ================================
    // SEQUENTIAL REQUEST QUEUE MANAGEMENT APIs
    // ================================

    // Get queue status
    window.dealspace.getQueueStatus = function() {
        return {
            queueLength: requestQueue.length,
            isProcessing: isProcessingQueue,
            isOnline: isOnline,
            nextRequest: requestQueue.length > 0 ? {
                endpoint: requestQueue[0].endpoint,
                timestamp: requestQueue[0].timestamp,
                age: Date.now() - requestQueue[0].timestamp
            } : null
        };
    };

    // Force process queue (for debugging/testing)
    window.dealspace.processQueue = function() {
        debugLog('Manual queue processing triggered');
        processQueue();
        return window.dealspace.getQueueStatus();
    };

    // Clear queue (for emergency situations)
    window.dealspace.clearQueue = function() {
        var clearedCount = requestQueue.length;
        requestQueue = [];
        isProcessingQueue = false;
        debugLog('Queue cleared. Removed ' + clearedCount + ' requests');
        return clearedCount;
    };

    // Pause/resume queue processing
    window.dealspace.pauseQueue = function() {
        isOnline = false;
        debugLog('Queue processing paused manually');
    };

    window.dealspace.resumeQueue = function() {
        isOnline = true;
        debugLog('Queue processing resumed manually');
        setTimeout(processQueue, 100);
    };

    debugLog('Dealspace tracking initialized successfully with form key system (no updates)');

    // Heartbeat to keep session alive (every 5 minutes) - now uses sequential queue
    setInterval(function() {
        if (document.hidden || document.webkitHidden || document.msHidden) {
            return; // Don't send heartbeat when page is hidden
        }

        sendTrackingData('custom-event', {
            event_type: 'Session Heartbeat',
            page_url: window.location.href,
            page_title: document.title,
            message: 'Session heartbeat',
            is_heartbeat: true
        });
    }, 300000); // 5 minutes

})();
