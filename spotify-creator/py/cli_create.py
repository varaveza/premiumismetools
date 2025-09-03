import os
import sys
import json

# Ensure we can import from current directory
BASE_DIR = os.path.dirname(__file__)
sys.path.insert(0, BASE_DIR)

from main import Spotify, StudentVerifier  # type: ignore


def main():
    try:
        if len(sys.argv) < 3:
            print(json.dumps({"success": False, "error": "Usage: cli_create.py <domain> <password> [trial_link]"}))
            return 1

        domain = sys.argv[1].strip()
        password = sys.argv[2].strip()
        trial_link = sys.argv[3].strip() if len(sys.argv) >= 4 else ""

        if not domain or not password:
            print(json.dumps({"success": False, "error": "Missing domain or password"}))
            return 1

        # Pass credentials via env for compatibility with existing code
        os.environ["DOMAIN"] = domain
        os.environ["PASSWORD"] = password
        
        # DISABLE account file writing - prevent permission errors
        os.environ["WRITE_ACCOUNT_FILES"] = "false"
        
        # DISABLE cookie saving - prevent permission errors  
        os.environ["SAVE_COOKIES"] = "false"

        use_proxy = (os.getenv("USE_PROXY", "False").lower() == "true")

        # Silence noisy prints during creation so only final JSON is emitted
        import contextlib
        devnull = open(os.devnull, 'w')
        try:
            with contextlib.redirect_stdout(devnull), contextlib.redirect_stderr(devnull):
                spotify = Spotify(process_id=0, use_proxy=use_proxy)
                account = spotify.create()
                # Get cookies from memory (no file I/O)
                cookies_data = getattr(spotify, 'cookies_json', {})
                cookie_header = ""
                if cookies_data and 'cookies' in cookies_data:
                    for name, value in cookies_data['cookies'].items():
                        cookie_header += f"{name}={value}; "
        finally:
            devnull.close()
        if not account:
            print(json.dumps({"success": False, "error": "Account creation failed"}))
            return 2

        email = account.get("email")
        is_student = False
        if trial_link:
            try:
                # Silence verification logs too
                devnull2 = open(os.devnull, 'w')
                try:
                    with contextlib.redirect_stdout(devnull2), contextlib.redirect_stderr(devnull2):
                        verifier = StudentVerifier(process_id=0, use_proxy=use_proxy)
                        is_student = bool(verifier.verify({"email": email, "password": password}, verification_link=trial_link, cookie_string=cookie_header))
                finally:
                    devnull2.close()
            except Exception:
                is_student = False

        print(json.dumps({
            "success": True,
            "email": email,
            "status": "student" if is_student else "basic"
        }))
        return 0
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        return 3


if __name__ == "__main__":
    sys.exit(main())


