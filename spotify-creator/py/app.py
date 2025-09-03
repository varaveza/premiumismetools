import os
from flask import Flask, request, render_template_string, redirect, url_for
from dotenv import load_dotenv

# Reuse logic from main.py
from main import Spotify, StudentVerifier


load_dotenv()
APP = Flask(__name__)

DB_PATH = None  # Flask no longer persists or rate-limits; handled by PHP layer


FORM_HTML = """
<!doctype html>
<html lang=\"id\">
  <head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <title>SPO Creator Web</title>
    <style>
      body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', Arial, sans-serif; background:#121327; color:#E8E8F5; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
      .card { background: rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.15); border-radius:16px; padding:24px; width: 100%; max-width: 520px; box-shadow: 0 15px 35px rgba(0,0,0,.4); }
      h1 { margin:0 0 8px; font-size:22px; }
      p { margin:0 0 18px; opacity:.85; }
      .field { margin:12px 0; }
      label { display:block; font-weight:600; margin:0 0 6px; }
      input[type=text], input[type=password] { width:100%; padding:12px 14px; border-radius:10px; border:1px solid rgba(255,255,255,.15); background:rgba(255,255,255,.06); color:#fff; }
      .hint { font-size:12px; opacity:.75; margin-top:4px; }
      button { margin-top:14px; width:100%; padding:12px 16px; border:0; border-radius:10px; background:#6E67C7; color:#fff; font-weight:700; cursor:pointer; }
      .result { margin-top:18px; padding:12px; border-radius:10px; background:rgba(0,0,0,.25); border:1px solid rgba(255,255,255,.12); }
      .error { color:#ffb4b4; }
      .success { color:#b2ffb2; }
      .row { display:flex; gap:10px; }
      .row .field { flex:1; }
    </style>
  </head>
  <body>
    <div class=\"card\">
      <h1>SPO Creator</h1>
      <p>Masukkan domain, password, dan opsional link trial (SheerID). Jika ada link trial, sistem akan mencoba verifikasi student.</p>
      {% if message %}
        <div class=\"result\">{{ message|safe }}</div>
      {% endif %}
      <form method=\"post\" action=\"{{ url_for('index') }}\">
        <div class=\"row\">
          <div class=\"field\">
            <label>Domain email</label>
            <input type=\"text\" name=\"domain\" placeholder=\"contoh: geli.com\" value=\"{{ domain or '' }}\" required>
            <div class=\"hint\">Email akan dibuat acak: random@domain</div>
          </div>
          <div class=\"field\">
            <label>Password</label>
            <input type=\"password\" name=\"password\" placeholder=\"password akun\" value=\"{{ password or '' }}\" required>
          </div>
        </div>
        <div class=\"field\">
          <label>Link trial (opsional)</label>
          <input type=\"text\" name=\"trial_link\" placeholder=\"https://www.spotify.com/student/...verificationId=...\" value=\"{{ trial_link or '' }}\">
          <div class=\"hint\">Jika diisi, akan coba verifikasi Student.</div>
        </div>
        <button type=\"submit\">Buat Akun</button>
      </form>
    </div>
  </body>
</html>
"""


@APP.route("/", methods=["GET", "POST"])
def index():
    client_ip = request.headers.get("X-Forwarded-For", request.remote_addr or "-").split(",")[0].strip()

    message = None
    domain = ""
    password = ""
    trial_link = ""

    if request.method == "POST":
        domain = (request.form.get("domain") or "").strip()
        password = (request.form.get("password") or "").strip()
        trial_link = (request.form.get("trial_link") or "").strip()

        if not domain or not password:
            message = "<div class='error'>Domain dan Password wajib diisi.</div>"
            return render_template_string(FORM_HTML, message=message, domain=domain, password=password, trial_link=trial_link)

        # Set per-request environment for Spotify
        os.environ["DOMAIN"] = domain
        os.environ["PASSWORD"] = password

        # Create account (honor USE_PROXY from environment)
        spotify = Spotify(process_id=0, use_proxy=(os.getenv("USE_PROXY", "False").lower() == "true"))
        account = spotify.create()

        if not account:
            message = "<div class='error'><strong>Gagal:</strong> Tidak bisa membuat akun. Coba lagi.</div>"
            return render_template_string(FORM_HTML, message=message, domain=domain, password=password, trial_link=trial_link)

        email = account.get("email")

        # If trial link provided, attempt student verification
        is_student = False
        if trial_link:
            try:
                # Overwrite student.txt with the provided single link
                with open("student.txt", "w", encoding="utf-8") as f:
                    f.write(trial_link.strip() + "\n")

                verifier = StudentVerifier(process_id=0, use_proxy=(os.getenv("USE_PROXY", "False").lower() == "true"))
                is_student = bool(verifier.verify({"email": email, "password": password}))
            except Exception:
                is_student = False

        status_text = "STUDENT" if is_student else "REGULAR"
        message = f"<div class='success'><strong>Sukses:</strong> {email} dibuat sebagai <strong>{status_text}</strong>.</div>"
        return render_template_string(FORM_HTML, message=message, domain=domain, password=password, trial_link=trial_link)

    return render_template_string(FORM_HTML, message=message, domain=domain, password=password, trial_link=trial_link)


@APP.route("/api/create", methods=["POST"])
def api_create():
    try:
        backend_key = os.getenv("BACKEND_API_KEY")
        if backend_key:
            req_key = request.headers.get("X-API-Key")
            if req_key != backend_key:
                return {"success": False, "error": "Unauthorized"}, 401

        data = request.get_json(silent=True) or request.form
        domain = (data.get("domain") or os.getenv("DOMAIN", "")).strip()
        password = (data.get("password") or os.getenv("PASSWORD", "")).strip()
        trial_link = (data.get("trial_link") or "").strip()

        if not domain or not password:
            return {"success": False, "error": "Missing domain or password"}, 400

        os.environ["DOMAIN"] = domain
        os.environ["PASSWORD"] = password

        spotify = Spotify(process_id=0, use_proxy=(os.getenv("USE_PROXY", "False").lower() == "true"))
        account = spotify.create()

        if not account:
            return {"success": False, "error": "Account creation failed"}, 500

        email = account.get("email")
        is_student = False

        if trial_link:
            try:
                with open("student.txt", "w", encoding="utf-8") as f:
                    f.write(trial_link.strip() + "\n")

                verifier = StudentVerifier(process_id=0, use_proxy=(os.getenv("USE_PROXY", "False").lower() == "true"))
                is_student = bool(verifier.verify({"email": email, "password": password}))
            except Exception:
                is_student = False

        return {
            "success": True,
            "email": email,
            "status": "STUDENT" if is_student else "REGULAR"
        }, 200
    except Exception as e:
        return {"success": False, "error": str(e)}, 500


if __name__ == "__main__":
    # For local dev. Use: python app.py
    APP.run(host="0.0.0.0", port=int(os.environ.get("PORT", 5111)))


