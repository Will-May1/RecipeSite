// main.js - Asynchronous AJAX Controller for ChefIt

async function triggerLinkExtraction() {
    const urlInput = document.getElementById('extractionUrl');
    const extractBtn = document.getElementById('extractBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    const targetUrl = urlInput.value.trim();
    
    // Simple front-end link validation sanity check
    if (!targetUrl) {
        alert("Please paste a valid Instagram Reel or website URL first.");
        return;
    }
    
    // Toggle Section 25: Loading spinner mechanics on
    extractBtn.disabled = true;
    btnText.textContent = " Processing Link...";
    btnSpinner.classList.remove('d-none');
    
    try {
        // Send AJAX hit to Phase 5 endpoint router via POST
        const response = await fetch('import_recipe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ url: targetUrl })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Redirect smoothly to homepage with success toast trigger flag
            window.location.href = 'index?success=1';
        } else {
            alert("ChefIt Extraction Error: " + (result.error || "Unable to extract recipe profile data. Please try manual entry."));
        }
        
    } catch (error) {
        console.error("Scraping execution break:", error);
        alert("A connection dropout occurred while communicating with the InfinityFree processing pipeline.");
    } finally {
        // Restore interactive element properties
        extractBtn.disabled = false;
        btnText.innerHTML = '<i class="bi bi-cpu me-1"></i> Extract Recipe';
        btnSpinner.classList.add('d-none');
    }
}
