(function() {
    let localLatestTimestamp = window.LAST_PUBLISHED_TIMESTAMP || '0';
    let isUpdating = false;

    // Check for updates every 30 seconds
    setInterval(checkForUpdates, 30000);

    // Initial check after 5 seconds just in case the cached page is very stale
    setTimeout(checkForUpdates, 5000);

    async function checkForUpdates() {
        if (isUpdating) return;

        try {
            const response = await fetch('/news/api/check_updates.php?_t=' + new Date().getTime());
            if (!response.ok) return;
            const data = await response.json();

            if (data.status === 'success' && data.latest_timestamp && data.latest_timestamp > localLatestTimestamp) {
                // There is new content! Fetch the fresh homepage bypassing Cloudflare edge cache.
                isUpdating = true;
                await fetchAndInjectFreshContent(data.latest_timestamp);
            }
        } catch (error) {
            console.error('Realtime updates check failed:', error);
        }
    }

    async function fetchAndInjectFreshContent(newTimestamp) {
        try {
            // Fetch the homepage with a cache-busting parameter
            const response = await fetch('/news/index.php?_nocache=' + new Date().getTime());
            if (!response.ok) throw new Error('Failed to fetch fresh HTML');
            
            const html = await response.text();
            
            // Parse the HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Find the main content area in the fresh document
            // It could be Theme 1 (.content-container without t2-theme-main) or Theme 2 (.t2-theme-main)
            const freshMain = doc.querySelector('main');
            const currentMain = document.querySelector('main');

            if (freshMain && currentMain) {
                // Update the DOM seamlessly
                currentMain.innerHTML = freshMain.innerHTML;
                
                // Update the local timestamp so we don't fetch again until the next new article
                localLatestTimestamp = newTimestamp;

                // Re-initialize Feather icons and scripts inside the main area
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }

                // If Theme 1 slider exists, re-initialize it
                if (typeof startInterval === 'function') {
                    startInterval();
                }

                // If Theme 2 slider exists, re-initialize it
                if (typeof t2StartInterval === 'function') {
                    t2StartInterval();
                }
            }
        } catch (error) {
            console.error('Failed to inject fresh content:', error);
        } finally {
            isUpdating = false;
        }
    }
})();
