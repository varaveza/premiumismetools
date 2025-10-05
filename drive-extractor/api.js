const express = require('express');
const axios = require('axios');
const cors = require('cors'); // Import CORS
const { Worker, isMainThread, parentPort, workerData } = require('worker_threads');

// Pastikan Anda sudah menginstall semua dependensi:
// npm install express axios cors

// --- Worker Thread Logic ---
// Logika ini akan dijalankan HANYA jika script ini dijalankan sebagai worker thread.
if (!isMainThread) {
  const { fileId } = workerData;
  const downloadUrl = `https://drive.google.com/uc?export=download&id=${fileId}`;
  
  const axiosInstance = axios.create({
    timeout: 15000,
    maxRedirects: 5,
    responseType: 'text',
    httpAgent: new (require('http').Agent)({ keepAlive: true }),
    httpsAgent: new (require('https').Agent)({ keepAlive: true }),
  });

  axiosInstance.get(downloadUrl)
    .then(response => {
      parentPort.postMessage({ success: true, data: response.data });
    })
    .catch(error => {
      parentPort.postMessage({ 
        success: false, 
        error: error.message,
        status: error.response?.status 
      });
    });
}

if (isMainThread) {
  const app = express();
  const PORT = process.env.PORT || 1203;

  // Middleware untuk membatasi akses hanya dari localhost
  app.use((req, res, next) => {
    const clientIP = req.ip || req.connection.remoteAddress || req.socket.remoteAddress;
    const isLocalhost = clientIP === '127.0.0.1' || 
                       clientIP === '::1' || 
                       clientIP === '::ffff:127.0.0.1' ||
                       clientIP.startsWith('127.') ||
                       clientIP.startsWith('192.168.') ||
                       clientIP.startsWith('10.') ||
                       clientIP === 'localhost';
    
    if (!isLocalhost) {
      return res.status(403).json({ 
        error: 'Access denied. This API is only accessible from localhost.' 
      });
    }
    
    next();
  });

  // Gunakan middleware CORS untuk mengizinkan permintaan dari domain lain
  app.use(cors());
  app.use(express.json());

  // Fungsi untuk menjalankan fetcher di dalam worker
  function fetchFileWithWorker(fileId) {
    return new Promise((resolve, reject) => {
      const worker = new Worker(__filename, {
        workerData: { fileId }
      });

      const timeout = setTimeout(() => {
        worker.terminate();
        reject(new Error('Request timeout after 15 seconds'));
      }, 15000);

      worker.on('message', (result) => {
        clearTimeout(timeout);
        worker.terminate();
        if (result.success) {
          resolve(result.data);
        } else {
          const err = new Error(result.error);
          err.status = result.status;
          reject(err);
        }
      });

      worker.on('error', (error) => {
        clearTimeout(timeout);
        worker.terminate();
        reject(error);
      });

      worker.on('exit', (code) => {
        if (code !== 0) {
          // reject(new Error(`Worker stopped with exit code ${code}`));
        }
      });
    });
  }

  // --- API Endpoint ---
  app.get('/api/get-drive-content', async (req, res) => {
    const { fileId } = req.query;

    if (!fileId) {
      return res.status(400).json({ 
        error: 'Parameter "fileId" is required.' 
      });
    }

    try {
      const fileContent = await fetchFileWithWorker(fileId);
      res.setHeader('Content-Type', 'text/plain; charset=utf-8');
      res.send(fileContent);
    } catch (error) {
      console.error(`Error fetching fileId ${fileId}:`, error.message);

      if (error.status === 404 || error.message.includes('404')) {
        return res.status(404).json({
          error: 'File not found or not accessible.',
          details: 'Please ensure the File ID is correct and the sharing setting is "Anyone with the link".'
        });
      }
      
      if (error.message.includes('timeout')) {
          return res.status(408).json({
              error: 'Request Timeout',
              details: 'The server took too long to retrieve the file from Google Drive.'
          });
      }

      res.status(500).json({ 
        error: 'Failed to retrieve content from Google Drive.',
        details: error.message
      });
    }
  });

  const server = app.listen(PORT, () => {
    console.log(`Backend server is running on http://localhost:${PORT}`);
    console.log(`Use the endpoint: http://localhost:${PORT}/api/get-drive-content?fileId=YOUR_FILE_ID`);
  });

  // Kode ini memastikan server berhenti dengan benar saat Anda menekan Ctrl+C di terminal.
  process.on('SIGINT', () => {
    console.log('\nGracefully shutting down from SIGINT (Ctrl-C)');
    server.close(() => {
      console.log('Server closed');
      process.exit(0);
    });
  });
}

