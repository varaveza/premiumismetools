import os
import json
from datetime import datetime
from flask import Flask, request, jsonify
from dotenv import load_dotenv
import ipaddress

# Reuse logic from main.py
from main import Spotify, StudentVerifier

load_dotenv()
APP = Flask(__name__)

USAGE_DIR = os.path.join(os.path.dirname(__file__), "logs")
USAGE_FILE = os.path.join(USAGE_DIR, "usage.json")

def _today_str() -> str:
    return datetime.utcnow().strftime("%Y-%m-%d")

def _load_usage():
    try:
        if not os.path.exists(USAGE_FILE):
            return {"date": _today_str(), "global_count": 0, "per_ip": {}}
        with open(USAGE_FILE, "r", encoding="utf-8") as f:
            data = json.load(f)
        if data.get("date") != _today_str():
            return {"date": _today_str(), "global_count": 0, "per_ip": {}}
        if "per_ip" not in data:
            data["per_ip"] = {}
        if "global_count" not in data:
            data["global_count"] = 0
        return data
    except Exception:
        return {"date": _today_str(), "global_count": 0, "per_ip": {}}

def _save_usage(data):
    os.makedirs(USAGE_DIR, exist_ok=True)
    tmp_file = USAGE_FILE + ".tmp"
    with open(tmp_file, "w", encoding="utf-8") as f:
        json.dump(data, f)
    os.replace(tmp_file, USAGE_FILE)

MAX_PER_IP_PER_DAY = 10
MAX_GLOBAL_PER_DAY = 150

def get_client_ip():
    """Get client IP address from various headers"""
    # Check for forwarded headers first (for reverse proxy)
    forwarded_for = request.headers.get('X-Forwarded-For')
    if forwarded_for:
        return forwarded_for.split(',')[0].strip()
    
    real_ip = request.headers.get('X-Real-IP')
    if real_ip:
        return real_ip.strip()
    
    # Fallback to remote_addr
    return request.remote_addr or "127.0.0.1"

def is_ip_whitelisted(client_ip: str) -> bool:
    """Check if client IP is in whitelist"""
    whitelist_str = os.getenv("IP_WHITELIST", "")
    if not whitelist_str.strip():
        # If no whitelist configured, allow all IPs
        return True
    
    whitelist_ips = [ip.strip() for ip in whitelist_str.split(",") if ip.strip()]
    
    try:
        client_ip_obj = ipaddress.ip_address(client_ip)
        
        for allowed_ip in whitelist_ips:
            try:
                # Support both single IPs and CIDR ranges
                if '/' in allowed_ip:
                    # CIDR range
                    network = ipaddress.ip_network(allowed_ip, strict=False)
                    if client_ip_obj in network:
                        return True
                else:
                    # Single IP
                    if client_ip_obj == ipaddress.ip_address(allowed_ip):
                        return True
            except ValueError:
                # Invalid IP format in whitelist, skip
                continue
                
        return False
    except ValueError:
        # Invalid client IP format
        return False

def check_api_key():
    """Check API key if configured"""
    api_key = os.getenv("API_KEY")
    if not api_key:
        return True  # No API key required
    
    provided_key = request.headers.get("X-API-Key") or request.headers.get("Authorization", "").replace("Bearer ", "")
    return provided_key == api_key

@APP.before_request
def before_request():
    """Check IP whitelist before processing requests"""
    client_ip = get_client_ip()
    
    # Check IP whitelist
    if not is_ip_whitelisted(client_ip):
        return jsonify({
            "success": False,
            "error": "IP address not whitelisted",
            "ip": client_ip
        }), 403

    # Enforce daily rate limits only for account creation endpoint
    if request.path == "/api/create" and request.method == "POST":
        usage = _load_usage()
        # Global cap
        if usage.get("global_count", 0) >= MAX_GLOBAL_PER_DAY:
            return jsonify({
                "success": False,
                "error": "Daily global limit reached",
                "limit": MAX_GLOBAL_PER_DAY,
                "ip": client_ip
            }), 429
        # Per-IP cap
        ip_count = int(usage.get("per_ip", {}).get(client_ip, 0))
        if ip_count >= MAX_PER_IP_PER_DAY:
            return jsonify({
                "success": False,
                "error": "Daily per-user limit reached",
                "limit": MAX_PER_IP_PER_DAY,
                "ip": client_ip
            }), 429

@APP.route("/", methods=["GET"])
def health_check():
    """Health check endpoint"""
    return jsonify({
        "status": "healthy",
        "service": "SPO Creator API",
        "version": "1.0.0",
        "ip": get_client_ip()
    })

@APP.route("/api/create", methods=["POST"])
def api_create():
    """Create Spotify account with optional student verification"""
    client_ip = get_client_ip()
    
    try:
        data = request.get_json(silent=True) or request.form
        domain = (data.get("domain") or os.getenv("DOMAIN", "")).strip()
        password = (data.get("password") or os.getenv("PASSWORD", "")).strip()
        trial_link = (data.get("trial_link") or "").strip()

        if not domain or not password:
            return jsonify({
                "success": False,
                "error": "Missing domain or password"
            }), 400

        # Set environment variables for Spotify class
        os.environ["DOMAIN"] = domain
        os.environ["PASSWORD"] = password

        # Create Spotify account
        spotify = Spotify(process_id=0, use_proxy=(os.getenv("USE_PROXY", "False").lower() == "true"))
        account = spotify.create(persist_files=True)

        if not account:
            return jsonify({
                "success": False,
                "error": "Account creation failed"
            }), 500

        email = account.get("email")
        is_student = False

        # If trial link provided, attempt student verification
        if trial_link:
            try:
                # Overwrite student.txt with the provided single link
                with open("student.txt", "w", encoding="utf-8") as f:
                    f.write(trial_link.strip() + "\n")

                verifier = StudentVerifier(process_id=0, use_proxy=(os.getenv("USE_PROXY", "False").lower() == "true"))
                verification_success, is_student = verifier.verify({"email": email, "password": password})
                # verification_success indicates if the process completed successfully
                # is_student indicates the actual account type (True for student, False for basic)
            except Exception as e:
                is_student = False

        # Increment usage counters after successful creation
        usage = _load_usage()
        usage["global_count"] = int(usage.get("global_count", 0)) + 1
        usage.setdefault("per_ip", {})
        usage["per_ip"][client_ip] = int(usage["per_ip"].get(client_ip, 0)) + 1
        _save_usage(usage)

        return jsonify({
            "success": True,
            "email": email,
            "status": "STUDENT" if is_student else "REGULAR",
            "ip": client_ip
        }), 200

    except Exception as e:
        return jsonify({
            "success": False,
            "error": str(e)
        }), 500

@APP.route("/api/status", methods=["GET"])
def api_status():
    """Get service status"""
    usage = _load_usage()
    return jsonify({
        "success": True,
        "service": "SPO Creator API",
        "version": "1.0.0",
        "date": usage.get("date"),
        "global_count": usage.get("global_count", 0),
        "per_ip": usage.get("per_ip", {}),
        "limits": {"per_ip_per_day": MAX_PER_IP_PER_DAY, "global_per_day": MAX_GLOBAL_PER_DAY}
    }), 200

@APP.errorhandler(404)
def not_found(error):
    return jsonify({
        "success": False,
        "error": "Endpoint not found"
    }), 404

@APP.errorhandler(405)
def method_not_allowed(error):
    return jsonify({
        "success": False,
        "error": "Method not allowed"
    }), 405

@APP.errorhandler(500)
def internal_error(error):
    return jsonify({
        "success": False,
        "error": "Internal server error"
    }), 500

if __name__ == "__main__":
    # For development only
    APP.run(host="0.0.0.0", port=int(os.environ.get("PORT", 5111)), debug=False)
