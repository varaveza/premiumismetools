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

  // Middleware untuk membatasi akses hanya dari localhost dan trusted sources
  app.use((req, res, next) => {
    const clientIP = req.ip || req.connection.remoteAddress || req.socket.remoteAddress;
    const forwardedFor = req.headers['x-forwarded-for'];
    const realIP = req.headers['x-real-ip'];
    
    // Get the actual client IP considering proxies
    let actualIP = clientIP;
    if (forwardedFor) {
      actualIP = forwardedFor.split(',')[0].trim();
    } else if (realIP) {
      actualIP = realIP;
    }
    
    const isLocalhost = actualIP === '127.0.0.1' || 
                       actualIP === '::1' || 
                       actualIP === '::ffff:127.0.0.1' ||
                       actualIP.startsWith('127.') ||
                       actualIP.startsWith('192.168.') ||
                       actualIP.startsWith('10.') ||
                       actualIP === 'localhost' ||
                       actualIP === '::ffff:192.168.1.1' ||
                       actualIP.includes('localhost');
    
    // Allow access from localhost and internal networks
    if (!isLocalhost && !actualIP.startsWith('192.168.') && !actualIP.startsWith('10.')) {
      console.log(`Access denied for IP: ${actualIP} (original: ${clientIP})`);
      return res.status(403).json({ 
        error: 'Access denied. This API is only accessible from localhost and internal networks.',
        clientIP: actualIP
      });
    }
    
    next();
  });

  // Konfigurasi CORS yang lebih spesifik
  const corsOptions = {
    origin: function (origin, callback) {
      // Allow requests with no origin (like mobile apps or curl requests)
      if (!origin) return callback(null, true);
      
      // Allow localhost and internal networks
      const allowedOrigins = [
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:80',
        'http://127.0.0.1:80',
        'http://localhost:8080',
        'http://127.0.0.1:8080',
        'http://localhost:3000',
        'http://127.0.0.1:3000'
      ];
      
      // Check if origin is allowed
      const isAllowed = allowedOrigins.some(allowed => origin.startsWith(allowed)) ||
                       origin.includes('localhost') ||
                       origin.includes('127.0.0.1') ||
                       origin.startsWith('http://192.168.') ||
                       origin.startsWith('http://10.');
      
      if (isAllowed) {
        callback(null, true);
      } else {
        console.log(`CORS blocked origin: ${origin}`);
        callback(new Error('Not allowed by CORS'));
      }
    },
    credentials: true,
    methods: ['GET', 'POST', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With']
  };
  
  app.use(cors(corsOptions));
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
    const clientIP = req.ip || req.connection.remoteAddress || req.socket.remoteAddress;
    
    console.log(`[${new Date().toISOString()}] Request from ${clientIP} for fileId: ${fileId}`);

    if (!fileId) {
      console.log('Error: fileId parameter missing');
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

