const fs = require('fs');
const path = require('path');

function deleteOldCookies(directoryPath, maxAgeMs) {
    try {
        if (!fs.existsSync(directoryPath)) {
            console.log(`[cleanup] Directory not found: ${directoryPath}`);
            return;
        }

        const now = Date.now();
        const entries = fs.readdirSync(directoryPath, { withFileTypes: true });
        let deletedCount = 0;

        for (const entry of entries) {
            if (!entry.isFile()) continue;
            const filePath = path.join(directoryPath, entry.name);
            try {
                const stats = fs.statSync(filePath);
                const ageMs = now - stats.mtimeMs;
                if (ageMs > maxAgeMs) {
                    fs.unlinkSync(filePath);
                    deletedCount += 1;
                }
            } catch (e) {
                console.error(`[cleanup] Error processing ${filePath}: ${e.message}`);
            }
        }

        console.log(`[cleanup] Deleted ${deletedCount} old cookie file(s) from ${directoryPath}`);
    } catch (e) {
        console.error(`[cleanup] Fatal error: ${e.message}`);
        process.exitCode = 1;
    }
}

(function main(){
    // cookies directory is alongside this script
    const cookiesDir = path.join(__dirname, 'cookies');
    const maxAgeMs = 24 * 60 * 60 * 1000; // 24 hours
    deleteOldCookies(cookiesDir, maxAgeMs);
})();


