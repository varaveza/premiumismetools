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

        use_proxy = (os.getenv("USE_PROXY", "False").lower() == "true")

        spotify = Spotify(process_id=0, use_proxy=use_proxy)
        account = spotify.create()
        if not account:
            print(json.dumps({"success": False, "error": "Account creation failed"}))
            return 2

        email = account.get("email")
        is_student = False
        if trial_link:
            try:
                with open(os.path.join(BASE_DIR, "student.txt"), "w", encoding="utf-8") as f:
                    f.write(trial_link.strip() + "\n")
                verifier = StudentVerifier(process_id=0, use_proxy=use_proxy)
                is_student = bool(verifier.verify({"email": email, "password": password}))
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


