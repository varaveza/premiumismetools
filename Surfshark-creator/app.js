import 'dotenv/config';
import axios from 'axios';
import chalk from 'chalk';
import { faker } from '@faker-js/faker';
import { promises as fs, readFileSync } from 'fs';
import { HttpsProxyAgent } from 'https-proxy-agent';
import pLimit from 'p-limit';
import http from 'http';
import path from 'path';
import { fileURLToPath } from 'url';

// --- Konfigurasi Proxy (bisa diatur lewat config.json) ---
const config = JSON.parse(readFileSync('./config.json', 'utf-8'));
const PROXY_LIST = (config && config.proxies) ? config.proxies : {
  SG: 'username__cr.sg:password@gw.dataimpulse.com:823'
};
const DEFAULT_COUNTRY = (config && config.default_country) ? config.default_country : 'SG';
const DEFAULT_PASSWORD = (config && config.password) ? config.password : 'masuk@B1';
const DEFAULT_DOMAIN = (config && config.domain) ? config.domain : '@yotomail.com';

// --- Fungsi Utilitas ---
function createProxyAgent(proxyUrl) {
  return new HttpsProxyAgent(`http://${proxyUrl}`);
}

function getCurrentTime() {
  const now = new Date();
  return `[${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}]`;
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// --- Fungsi Pengambilan IP ---
async function getRealIP(proxyAgent) {
  try {
    const response = await axios.get('https://api.ipify.org?format=json', {
      httpsAgent: proxyAgent,
      timeout: 10000
    });
    return response.data.ip;
  } catch {
    try {
      const response = await axios.get('https://httpbin.org/ip', {
        httpsAgent: proxyAgent,
        timeout: 10000
      });
      return response.data.origin.split(',')[0].trim();
    } catch {
      return 'Unknown';
    }
  }
}

// --- Fungsi Rotasi Proxy ---
function getProxyForCountry(selectedCountry, accountIndex) {
  if (selectedCountry === 'ALL') {
    const countries = Object.keys(PROXY_LIST);
    const rotatedCountry = countries[accountIndex % countries.length];
    return {
      country: rotatedCountry,
      proxy: PROXY_LIST[rotatedCountry]
    };
  }
  return {
    country: selectedCountry,
    proxy: PROXY_LIST[selectedCountry]
  };
}

// --- Fungsi Generate Email ---
function generateEmail() {
  const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
  let prefix = '';
  for (let i = 0; i < 7; i++) {
    prefix += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return prefix + DEFAULT_DOMAIN;
}

// --- Fungsi Registrasi Surfshark ---
async function regist(email, password, proxyAgent) {
  try {
    const res = await axios.post("https://api.surfshark.com/v1/account/users", { email, password }, {
      headers: {
        "User-Agent": "SurfsharkAndroid/3.6.0 com.surfshark.vpnclient.android/release/other/306009022 device/mobile"
      },
      httpsAgent: proxyAgent,
      timeout: 20000
    });
    
    if (res.status === 201) {
      return { success: true, message: 'Account created successfully' };
    } else {
      return { success: false, message: res.data?.message || 'Registration failed' };
    }
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// --- Fungsi Pembuatan Akun ---
async function createSingleAccount(selectedCountry, accountIndex, totalAccounts, passwordOverride) {
  let currentEmail = 'Unknown';
  let currentIP = 'Unknown';
  let currentCountry = 'Unknown';

  try {
    const proxyInfo = getProxyForCountry(selectedCountry, accountIndex);
    const proxyAgent = createProxyAgent(proxyInfo.proxy);
    currentCountry = proxyInfo.country;

    console.log(chalk.yellow(`${getCurrentTime()} [${accountIndex + 1}/${totalAccounts}] Memeriksa alamat IP`));
    currentIP = await getRealIP(proxyAgent);
    console.log(chalk.blue(`${getCurrentTime()} Menggunakan IP: ${currentIP} (${currentCountry})`));

    currentEmail = generateEmail();
    console.log(chalk.green(`${getCurrentTime()} Email Dibuat: ${currentEmail}`));

    const password = passwordOverride || DEFAULT_PASSWORD;

    console.log(chalk.yellow(`${getCurrentTime()} Mencoba registrasi akun Surfshark`));
    const regResult = await regist(currentEmail, password, proxyAgent);

    if (regResult.success) {
      console.log(chalk.green(`${getCurrentTime()} ✅ Akun Berhasil Dibuat!`));
      return { success: true, ip: currentIP, country: currentCountry, email: currentEmail, password };
    } else {
      console.log(chalk.red(`${getCurrentTime()} ❌ Registrasi gagal: ${regResult.message}`));
      return { success: false, ip: currentIP, country: currentCountry, email: currentEmail };
    }
  } catch (error) {
    console.error(chalk.red(`${getCurrentTime()} ❌ Error saat membuat akun: ${error.message}`));
    return { success: false, ip: currentIP, country: currentCountry, email: currentEmail };
  }
}

// --- Fungsi util untuk menjalankan pembuatan akun batch ---
async function runCreateBatch({ country, total, threads, password }) {
  const selectedCountry = country || DEFAULT_COUNTRY || 'SG';
  const totalAccounts = (!total || total < 1) ? 1 : total;
  const threadCount = (!threads || threads < 1) ? 1 : (threads > 20 ? 20 : threads);

  let successCount = 0;
  let failCount = 0;
  const successes = [];
  const allIPs = new Set();
  const countryCounts = {};
  const results = [];

  const limit = pLimit(threadCount);

  // Retry loop: terus mencoba sampai jumlah sukses terpenuhi atau melewati batas upaya
  let remaining = totalAccounts;
  let attempts = 0;
  const maxAttempts = Math.max(totalAccounts * 10, totalAccounts);
  const startTime = Date.now();

  while (successCount < totalAccounts && attempts < maxAttempts) {
    const batchSize = remaining;
    const batchTasks = Array.from({ length: batchSize }, (_, i) => {
      const idx = results.length + i;
      return limit(async () => {
        const result = await createSingleAccount(selectedCountry, idx, totalAccounts, password);

        if (result.success) {
          successCount++;
          successes.push({ email: result.email, password: password || DEFAULT_PASSWORD, country: result.country });
        } else {
          failCount++;
        }

        if (result.ip !== 'Unknown') {
          allIPs.add(`${result.ip}:${result.country}`);
        }

        if (result.country !== 'Unknown') {
          countryCounts[result.country] = (countryCounts[result.country] || 0) + 1;
        }

        return result;
      });
    });

    const batchResults = await Promise.all(batchTasks);
    results.push(...batchResults);
    attempts += batchSize;
    remaining = Math.max(0, totalAccounts - successCount);
  }

  const endTime = Date.now();
  const totalTime = Math.round((endTime - startTime) / 1000);

  return {
    selectedCountry,
    totalAccounts,
    threadCount,
    successCount,
    failCount,
    totalTimeSeconds: totalTime,
    successes,
    exhaustedRetries: successCount < totalAccounts,
    ipSet: Array.from(allIPs),
    countryCounts,
    results
  };
}

// --- HTTP Server ---
const PORT = process.env.PORT ? parseInt(process.env.PORT, 10) : 8080;

// --- Rate limit storage (file-based) ---
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const USAGE_DIR = path.join(__dirname, 'logs');
const USAGE_FILE = path.join(USAGE_DIR, 'usage.json');
const MAX_GLOBAL_PER_DAY = process.env.MAX_GLOBAL_PER_DAY ? parseInt(process.env.MAX_GLOBAL_PER_DAY, 10) : 300;
const MAX_PER_IP_PER_DAY = process.env.MAX_PER_IP_PER_DAY ? parseInt(process.env.MAX_PER_IP_PER_DAY, 10) : 25;
const LOCAL_ONLY = (process.env.LOCAL_ONLY || 'true').toLowerCase() === 'true';

function todayStr() {
  const now = new Date();
  const yyyy = now.getUTCFullYear();
  const mm = String(now.getUTCMonth() + 1).padStart(2, '0');
  const dd = String(now.getUTCDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

function loadUsage() {
  try {
    const raw = readFileSync(USAGE_FILE, 'utf-8');
    const data = JSON.parse(raw);
    if (data.date !== todayStr()) {
      return { date: todayStr(), global_count: 0, per_ip: {} };
    }
    if (typeof data.global_count !== 'number') data.global_count = 0;
    if (!data.per_ip || typeof data.per_ip !== 'object') data.per_ip = {};
    return data;
  } catch {
    return { date: todayStr(), global_count: 0, per_ip: {} };
  }
}

async function saveUsage(data) {
  await fs.mkdir(USAGE_DIR, { recursive: true });
  const tmp = USAGE_FILE + '.tmp';
  await fs.writeFile(tmp, JSON.stringify(data), 'utf-8');
  await fs.rename(tmp, USAGE_FILE);
}

function getClientIp(req) {
  const fwd = req.headers['x-forwarded-for'];
  if (typeof fwd === 'string' && fwd.length > 0) {
    return fwd.split(',')[0].trim();
  }
  const real = req.headers['x-real-ip'];
  if (typeof real === 'string' && real.length > 0) {
    return real.trim();
  }
  const ra = req.socket && req.socket.remoteAddress ? req.socket.remoteAddress : '127.0.0.1';
  return ra === '::1' ? '127.0.0.1' : ra;
}

function isLocalIp(ip) {
  if (!ip) return false;
  if (ip === '127.0.0.1' || ip === '::1' || ip === '::ffff:127.0.0.1') return true;
  return false;
}

function sendJson(res, statusCode, payload) {
  const data = JSON.stringify(payload);
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(data)
  });
  res.end(data);
}

function parseBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';
    req.on('data', chunk => { body += chunk.toString(); });
    req.on('end', () => {
      if (!body) return resolve({});
      try {
        resolve(JSON.parse(body));
      } catch (e) {
        reject(new Error('Invalid JSON body'));
      }
    });
    req.on('error', reject);
  });
}

const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, `http://${req.headers.host}`);
    const clientIp = getClientIp(req);

    // Restrict access to localhost only (configurable via LOCAL_ONLY)
    if (LOCAL_ONLY && !isLocalIp(clientIp)) {
      return sendJson(res, 403, { error: 'Forbidden: local access only', ip: clientIp });
    }

    if (req.method === 'GET' && url.pathname === '/health') {
      return sendJson(res, 200, { status: 'ok', time: new Date().toISOString() });
    }

    if (req.method === 'GET' && url.pathname === '/countries') {
      return sendJson(res, 200, {
        availableCountries: Object.keys(PROXY_LIST),
        defaultCountry: DEFAULT_COUNTRY,
        info: 'Tambah/ubah kode negara di config.json -> proxies',
        reference: 'https://www.ssl.com/id/kode-negara-a/'
      });
    }

    if (req.method === 'POST' && url.pathname === '/create') {
      const body = await parseBody(req);
      const country = body.country === 'ALL' ? 'ALL' : (body.country || DEFAULT_COUNTRY);

      if (country !== 'ALL' && !PROXY_LIST[country]) {
        return sendJson(res, 400, { error: `Proxy untuk negara ${country} tidak ditemukan. Atur di config.json -> proxies.`, availableCountries: Object.keys(PROXY_LIST) });
      }

      const total = parseInt(body.total, 10) || 1;
      const threads = Math.min(2, (parseInt(body.threads, 10) || 1));
      const password = typeof body.password === 'string' && body.password.length > 0 ? body.password : undefined;

      // --- Enforce simple daily limits (global and per-IP) ---
      const usage = loadUsage();
      const ipCount = usage.per_ip[clientIp] ? parseInt(usage.per_ip[clientIp], 10) || 0 : 0;
      if (usage.global_count >= MAX_GLOBAL_PER_DAY) {
        return sendJson(res, 429, { error: 'Daily global limit reached', limit: MAX_GLOBAL_PER_DAY });
      }
      if (ipCount >= MAX_PER_IP_PER_DAY) {
        return sendJson(res, 429, { error: 'Daily per-IP limit reached', limit: MAX_PER_IP_PER_DAY, ip: clientIp });
      }

      console.log(chalk.magenta.bold(`\n🚀 Memulai batch create via API | country=${country} total=${total} threads=${threads}`));
      const summary = await runCreateBatch({ country, total, threads, password });

      // Increment usage counters based on successes
      try {
        const toAdd = parseInt(summary.successCount, 10) || 0;
        if (toAdd > 0) {
          const updated = loadUsage();
          updated.global_count = (parseInt(updated.global_count, 10) || 0) + toAdd;
          if (!updated.per_ip[clientIp]) updated.per_ip[clientIp] = 0;
          updated.per_ip[clientIp] = (parseInt(updated.per_ip[clientIp], 10) || 0) + toAdd;
          await saveUsage(updated);
        }
      } catch (e) {
        console.error(chalk.red(`Gagal menyimpan usage: ${e.message}`));
      }
      return sendJson(res, 200, summary);
    }

    // Not found
    sendJson(res, 404, { error: 'Not Found' });
  } catch (error) {
    console.error(chalk.red(`API Error: ${error.message}`));
    sendJson(res, 500, { error: error.message });
  }
});

server.listen(PORT, () => {
  console.log(chalk.magenta.bold(`🚀 Surfshark Backend berjalan di http://localhost:${PORT}`));
});
