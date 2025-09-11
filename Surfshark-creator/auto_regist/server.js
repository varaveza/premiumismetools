const express = require('express')
const fs = require('fs')
const axios = require('axios')
const path = require('path')

// Load config dari ../config.json (single source of truth)
function loadConfig() {
	try {
		const cfgPath = path.join(__dirname, '..', 'config.json')
		const raw = fs.readFileSync(cfgPath, 'utf-8')
		return JSON.parse(raw)
	} catch (_) {
		return {}
	}
}
const cfg = loadConfig()

const app = express()
app.use(express.json())

const PORT = process.env.PORT || cfg.port || 7070
const DEFAULT_DOMAIN = process.env.DEFAULT_DOMAIN || cfg.domain || '@yotomail.com'
const DEFAULT_PASSWORD = process.env.DEFAULT_PASSWORD || cfg.password || 'masuk@B1'
const ALLOW_EXTERNAL = (process.env.ALLOW_EXTERNAL !== undefined
	? String(process.env.ALLOW_EXTERNAL).toLowerCase() === 'true'
	: (cfg.allow_external === undefined ? true : !!cfg.allow_external))

function generateRandomEmail(domain) {
	const chars = 'abcdefghijklmnopqrstuvwxyz0123456789'
	let prefix = ''
	for (let i = 0; i < 7; i++) {
		prefix += chars.charAt(Math.floor(Math.random() * chars.length))
	}
	return prefix + domain
}

async function createAccount({ email, password }) {
	const shouldCallExternal = ALLOW_EXTERNAL
	if (!email) {
		throw new Error('email is required')
	}
	if (!password) {
		throw new Error('password is required')
	}

	if (shouldCallExternal) {
		const res = await axios.post('https://api.surfshark.com/v1/account/users', { email, password }, {
			headers: {
				'User-Agent': 'SurfsharkAndroid/3.6.0 com.surfshark.vpnclient.android/release/other/306009022 device/mobile'
			}
		})
		if (res.status !== 201) {
			throw new Error(`external API responded with status ${res.status}`)
		}
	}

	return { email, password }
}

app.get('/health', (req, res) => {
	res.json({ status: 'ok', port: PORT })
})

app.post('/register', async (req, res) => {
	try {
		let { email, password, domain } = req.body || {}
		domain = domain || DEFAULT_DOMAIN
		if (!email) {
			email = generateRandomEmail(domain)
		}
		password = password || DEFAULT_PASSWORD

		const result = await createAccount({ email, password })
		res.status(201).json({ success: true, ...result })
	} catch (err) {
		res.status(400).json({ success: false, error: err.message })
	}
})

app.listen(PORT, () => {
	console.log(`API listening on http://localhost:${PORT}`)
})
