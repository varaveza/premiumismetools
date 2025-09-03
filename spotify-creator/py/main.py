import json
import re
import tls_client
import faker
import random
import string
import os
import requests
import time
import uuid
import sys
import concurrent.futures
from dotenv import load_dotenv
from datetime import datetime
from urllib.parse import quote, urlparse
import threading

GREEN = "\033[92m"
RED = "\033[91m"
RESET = "\033[0m"

class Capsolver:
    def __init__(self, api_key):
        self.api_key = api_key
        self.base_url = "https://api.capsolver.com"
        # Optional tuning via env
        self.wait_attempts = int(os.getenv("CAPS_WAIT_ATTEMPTS", "30"))
        self.wait_interval = int(os.getenv("CAPS_WAIT_INTERVAL", "3"))
        
    def create_task(self, task_type, task_data):
        url = f"{self.base_url}/createTask"
        payload = {"clientKey": self.api_key, "task": {"type": task_type, **task_data}}
        try:
            resp = requests.post(url, json=payload, timeout=20)
            data = resp.json()
            if data.get("errorId"):
                print(f"[CAPSOLVER] createTask error: {data.get('errorCode')} - {data.get('errorDescription')}")
            return data
        except Exception as e:
            print(f"[CAPSOLVER] createTask exception: {str(e)}")
            return {"errorId": 1, "errorCode": "ClientException", "errorDescription": str(e)}
        
    def get_result(self, task_id):
        url = f"{self.base_url}/getTaskResult"
        payload = {"clientKey": self.api_key, "taskId": task_id}
        try:
            resp = requests.post(url, json=payload, timeout=20)
            data = resp.json()
            if data.get("errorId"):
                print(f"[CAPSOLVER] getTaskResult error: {data.get('errorCode')} - {data.get('errorDescription')}")
            return data
        except Exception as e:
            print(f"[CAPSOLVER] getTaskResult exception: {str(e)}")
            return {"errorId": 1, "errorCode": "ClientException", "errorDescription": str(e)}
        
    def wait_result(self, task_id, max_attempts=None, interval=None):
        max_attempts = max_attempts or self.wait_attempts
        interval = interval or self.wait_interval
        for attempt in range(1, max_attempts + 1):
            result = self.get_result(task_id)
            if result.get("status") == "ready":
                return result.get("solution", {}).get("gRecaptchaResponse")
            if result.get("status") == "failed":
                print(f"[CAPSOLVER] task failed: {result}")
                return None
            time.sleep(interval)
        return None
        
    def solve_v3(self, website_url, website_key, page_action, is_enterprise=True):
        task_data = {
            "websiteURL": website_url,
            "websiteKey": website_key,
            "pageAction": page_action,
            "isEnterprise": is_enterprise
        }
        response = self.create_task("RecaptchaV3TaskProxyless", task_data)
        if response.get("errorId") != 0:
            return None
        return self.wait_result(response.get("taskId"))
        
    def solve_v2(self, website_url, website_key, is_invisible=False):
        task_data = {
            "websiteURL": website_url,
            "websiteKey": website_key,
            "isInvisible": is_invisible
        }
        response = self.create_task("RecaptchaV2TaskProxyless", task_data)
        if response.get("errorId") != 0:
            return None
        return self.wait_result(response.get("taskId"))


class Spotify:
    def __init__(self, process_id=0, use_proxy=False):
        # Load .env from parent folder (spotify-creator/.env) and current folder
        base_dir = os.path.dirname(__file__)
        load_dotenv(os.path.join(base_dir, '..', '.env'))
        load_dotenv()
        self.process_id = process_id
        self.use_proxy = use_proxy
        self.proxy = os.getenv("PROXY")
        self.user_agent = self._random_ua()
        self.session = self._init_session()
        self.capsolver = Capsolver(os.getenv("CAPSOLVER_API_KEY"))
        self.fake = faker.Faker()
        self.domain = os.getenv("DOMAIN", "rajaiblis.com")
        self.password = os.getenv("PASSWORD", "Waroengku123")
        self.default_name = os.getenv("NAME", "")
        self.api_key = None
        self.csrf_token = None
        self.flow_id = None
        self.install_id = self._random_id()
        self.submit_id = self._random_id()
        self.login_token = None
        
    def _init_session(self):
        session = tls_client.Session(client_identifier="chrome_113", random_tls_extension_order=True)
        session.headers = {
            "User-Agent": self.user_agent,
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.5",
            "Accept-Encoding": "gzip, deflate, br",
            "Connection": "keep-alive",
        }
        
        if self.use_proxy and self.proxy:
            if '@' in self.proxy:
                auth, proxy = self.proxy.split('@')
                user, pwd = auth.split(':')
                host, port = proxy.split(':')
                session.proxies = {
                    "http": f"http://{user}:{pwd}@{host}:{port}",
                    "https": f"http://{user}:{pwd}@{host}:{port}"
                }
            else:
                host, port = self.proxy.split(':')
                session.proxies = {
                    "http": f"http://{host}:{port}",
                    "https": f"http://{host}:{port}"
                }
            self.log(f"Using proxy: {host}:****")
        return session
        
    def _random_id(self):
        return str(uuid.uuid4())
    
    def _random_ua(self):
        chrome = ["113.0.0.0", "114.0.0.0", "115.0.0.0", "116.0.0.0", "117.0.0.0"]
        os_ver = ["10.0", "11.0"]
        return f"Mozilla/5.0 (Windows NT {random.choice(os_ver)}; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{random.choice(chrome)} Safari/537.36"
    
    def log(self, message, success=False):
        process_tag = f"#{self.process_id}" if self.process_id > 0 else ""
        if success:
            print(f"{GREEN}{process_tag} {message}{RESET}")
        else:
            print(f"{process_tag} {message}")
    
    def get_data(self, max_retry=5):
        for try_num in range(1, max_retry + 1):
            try:
                url = "https://www.spotify.com/uk/signup"
                response = self.session.get(url)
                
                if response.status_code != 200:
                    self.log(f"Signup page error: {response.status_code} ({try_num}/{max_retry})")
                    time.sleep(2)
                    continue
                
                pattern = r'<script id="__NEXT_DATA__" type="application/json"[^>]*>(.*?)</script>'
                match = re.search(pattern, response.text, re.DOTALL)
                
                if not match:
                    self.log(f"Failed to find JSON data ({try_num}/{max_retry})")
                    time.sleep(2)
                    continue
                
                data = json.loads(match.group(1))
                props = data.get("props", {}).get("pageProps", {})
                keys = props.get("keys", {})
                
                self.api_key = keys.get("signupServiceAppKey")
                self.flow_id = props.get("flowId")
                self.install_id = props.get("spT") or ''.join(random.choices('0123456789abcdef', k=32))
                self.csrf_token = props.get("headers", {}).get("csrfToken")
                    
                if not self.api_key or not self.flow_id or not self.csrf_token:
                    self.log(f"Missing signup data ({try_num}/{max_retry})")
                    time.sleep(2)
                    continue
                    
                return True
                
            except Exception as e:
                self.log(f"Error: {str(e)} ({try_num}/{max_retry})")
                time.sleep(2)
                
        self.log("Failed to get signup data")
        return False
    
    def gen_account(self):
        base_username = self.fake.user_name().lower().replace('.', '_')
        username_prefix = base_username[:8] 
        random_numbers = str(random.randint(100, 999))
        
        username = (username_prefix + random_numbers)[:12]

        display_name = "Premiumisme"
            
        return {
            "email": f"{username}@{self.domain}",
            "password": self.password,
            "name": display_name,
            "birth": {
                "day": random.randint(1, 28),
                "month": random.randint(1, 12),
                "year": random.randint(1980, 2000)
            },
            "gender": 1
        }
    
    def check_email(self, email, max_retry=3):
        for try_num in range(1, max_retry + 1):
            try:
                url = f"https://spclient.wg.spotify.com/signup/public/v1/account?validate=1&email={quote(email)}"
                response = self.session.get(url)
                result = response.json()
                if result and result.get("status") == 1:
                    return True
                self.log(f"Email validation retry ({try_num}/{max_retry})")
                time.sleep(2)
            except Exception as e:
                self.log(f"Email error: {str(e)} ({try_num}/{max_retry})")
                time.sleep(2)
        return False
    
    def gen_dossier(self, email, max_retry=3):
        for try_num in range(1, max_retry + 1):
            try:
                url = "https://spclient.wg.spotify.com/email-dossier/generate-dossier"
                payload = {"email": email}
                response = self.session.post(url, json=payload)
                result = response.json()
                if result and result.get("ok") is not None:
                    return True
                self.log(f"Dossier retry ({try_num}/{max_retry})")
                time.sleep(2)
            except Exception as e:
                self.log(f"Dossier error: {str(e)} ({try_num}/{max_retry})")
                time.sleep(2)
        return False
    
    def check_pwd(self, password, max_retry=3):
        for try_num in range(1, max_retry + 1):
            try:
                url = f"https://spclient.wg.spotify.com/signup/public/v1/account?validate=1&password={quote(password)}"
                response = self.session.get(url)
                result = response.json()
                if result and result.get("status") == 1:
                    return True
                self.log(f"Password validation retry ({try_num}/{max_retry})")
                time.sleep(2)
            except Exception as e:
                self.log(f"Password error: {str(e)} ({try_num}/{max_retry})")
                time.sleep(2)
        return False
    
    def register(self, account, captcha, max_retry=3):
        for try_num in range(1, max_retry + 1):
            try:
                url = "https://spclient.wg.spotify.com/signup/public/v2/account/create"
                
                birth = account['birth']
                payload = {
                    "account_details": {
                        "birthdate": f"{birth['year']}-{birth['month']:02d}-{birth['day']:02d}",
                        "consent_flags": {
                            "eula_agreed": True,
                            "send_email": True,
                            "third_party_email": False
                        },
                        "display_name": account['name'],
                        "email_and_password_identifier": {
                            "email": account['email'],
                            "password": account['password']
                        },
                        "gender": account['gender']
                    },
                    "callback_uri": f"https://www.spotify.com/signup/challenge?flow_ctx={self.flow_id}%{int(time.time())}&locale=en-GB",
                    "client_info": {
                        "api_key": self.api_key,
                        "app_version": "v2",
                        "capabilities": [1],
                        "installation_id": self.install_id,
                        "platform": "www"
                    },
                    "tracking": {
                        "creation_flow": "",
                        "creation_point": "spotify.com",
                        "referrer": ""
                    },
                    "recaptcha_token": captcha,
                    "submission_id": self.submit_id,
                    "flow_id": self.flow_id
                }
                
                self.session.headers.update({
                    "Content-Type": "application/json",
                    "X-CSRF-Token": self.csrf_token,
                })
                
                response = self.session.post(url, json=payload)
                
                try:
                    return response.json()
                except:
                    if try_num < max_retry:
                        self.log(f"Registration response error ({try_num}/{max_retry})")
                        time.sleep(2)
                        continue
                    return response.text
                    
            except Exception as e:
                if try_num < max_retry:
                    self.log(f"Registration error: {str(e)} ({try_num}/{max_retry})")
                    time.sleep(2)
                else:
                    self.log(f"Registration failed: {str(e)}")
                    return None
        return None
    
    def solve_challenge(self, response, max_retry=3):
        for try_num in range(1, max_retry + 1):
            try:
                if isinstance(response, dict):
                    session_id = response.get("challenge", {}).get("session_id")
                else:
                    match = re.search(r'"session_id":"([^"]+)"', response)
                    session_id = match.group(1) if match else None
                
                if not session_id:
                    self.log(f"No session_id found ({try_num}/{max_retry})")
                    time.sleep(2)
                    continue
                
                self.log(f"Session: {session_id[:8]}")
                
                url = "https://challenge.spotify.com/api/v1/get-session"
                payload = {"session_id": session_id}
                
                self.session.headers.update({"Content-Type": "application/json"})
                resp = self.session.post(url, json=payload)
                
                if resp.status_code != 200:
                    self.log(f"Challenge session error: {resp.status_code} ({try_num}/{max_retry})")
                    time.sleep(2)
                    continue
                
                challenge_data = resp.json()
                
                challenge_url = None
                if "in_progress" in challenge_data:
                    details = challenge_data.get("in_progress", {}).get("challenge_details", {})
                    web_launcher = details.get("web_challenge_launcher", {})
                    challenge_url = web_launcher.get("url")
                
                if not challenge_url:
                    self.log(f"No challenge URL ({try_num}/{max_retry})")
                    time.sleep(2)
                    continue
                
                self.log("Solving challenge captcha")
                captcha_token = self.capsolver.solve_v2(
                    challenge_url,
                    "6LeO36obAAAAALSBZrY6RYM1hcAY7RLvpDDcJLy3"
                )
                
                if not captcha_token:
                    self.log(f"Challenge captcha failed ({try_num}/{max_retry})")
                    time.sleep(2)
                    continue
                
                parts = challenge_url.split('/')
                if len(parts) >= 7:
                    c_session_id = parts[4]
                    challenge_id = parts[5]
                    
                    url = "https://challenge.spotify.com/api/v1/invoke-challenge-command"
                    payload = {
                        "session_id": c_session_id,
                        "challenge_id": challenge_id,
                        "recaptcha_challenge_v1": {"solve": {"recaptcha_token": captcha_token}}
                    }
                    
                    resp = self.session.post(url, json=payload)
                    
                    if resp.status_code != 200:
                        self.log(f"\033[93mChallenge submission error: {resp.status_code} ({try_num}/{max_retry})\033[0m")
                        continue
                    
                    self.log("Challenge submitted")
                    
                    url = "https://spclient.wg.spotify.com/signup/public/v2/account/complete-creation"
                    payload = {"session_id": session_id}
                    resp = self.session.post(url, json=payload)
                    
                    if resp.status_code != 200:
                        self.log(f"Challenge completion error: {resp.status_code} ({try_num}/{max_retry})")
                        time.sleep(2)
                        continue
                        
                    try:
                        result = resp.json()
                        if "success" in result:
                            username = result.get("success", {}).get("username")
                            self.login_token = result.get("success", {}).get("login_token")
                            self.log(f"Account created: {username}!", success=True)
                            return True
                    except:
                        pass
                    
                    return True
                else:
                    self.log(f"Invalid challenge URL ({try_num}/{max_retry})")
                    time.sleep(2)
                    
            except Exception as e:
                self.log(f"Challenge error: {str(e)} ({try_num}/{max_retry})")
                time.sleep(2)
        return False
    
    def get_config(self):
        try:
            url = "https://gew1-spclient.spotify.com/remote-config-resolver/v3/unauth/configuration"
            payload = {
                "propertySetId": "312e827d7d7800aa",
                "context": {"context": [{"policyInputName": "/remote-config/installation-id", "value": self.install_id}]},
                "fetchType": {"type": 0}
            }
            
            self.session.headers.update({"Content-Type": "application/json"})
            response = self.session.post(url, json=payload)
            
            self.log(f"Config: {response.status_code}")
            return True
            
        except Exception as e:
            self.log(f"Config error: {str(e)}")
            return False
    
    def auth(self, max_retry=3):
        if not self.login_token:
            self.log("No login token available")
            return False
            
        for try_num in range(1, max_retry + 1):
            try:
                url = "https://www.spotify.com/api/signup/authenticate"
                payload = {"splot": self.login_token}
                
                self.session.headers.update({"Content-Type": "application/x-www-form-urlencoded"})
                response = self.session.post(url, data=payload)
                
                self.log(f"Auth: {response.status_code}")
                
                if response.status_code == 200:
                    return True
                
                self.log(f"Auth error: {response.status_code} ({try_num}/{max_retry})")
                time.sleep(2)
                
            except Exception as e:
                self.log(f"Auth exception: {str(e)} ({try_num}/{max_retry})")
                time.sleep(2)
        return False
    
    def save(self, account, is_student=False):
        os.makedirs("cookies", exist_ok=True)
        
        # Optionally write account files based on env flag
        if os.getenv("WRITE_ACCOUNT_FILES", "true").lower() == "true":
            target_file = "akunstudent.txt" if is_student else "akunbiasa.txt"
            
            for file_name in ["akunbiasa.txt", "akunstudent.txt"]:
                if not os.path.exists(file_name):
                    with open(file_name, "w") as f:
                        pass
            
            with open(target_file, "a") as f:
                f.write(f"{account['email']}|{account['password']}\n")
            
            status = "student" if is_student else "basic"
            with open("akun.txt", "a") as f:
                f.write(f"{account['email']}|{account['password']}|{status}\n")
        
        cookie_header = ""
        for cookie in self.session.cookies:
            cookie_header += f"{cookie.name}={cookie.value}; "
        
        with open(f"cookies/{account['email']}.txt", "w") as f:
            f.write(cookie_header)
        
        self.log(f"Saved: {account['email']}")
    
    def create(self):        
        if not self.get_data():
            return False
            
        account = self.gen_account()
        self.log(f"{account['email']} | {account['name']}")
        
        self.log("Validating account")
        if not self.check_email(account['email']) or not self.gen_dossier(account['email']) or not self.check_pwd(account['password']):
            self.log("Validation failed")
            return False
        
        self.log("Solving captcha")
        max_captcha_attempts = int(os.getenv("CAPTCHA_MAX_ATTEMPTS", "10"))
        captcha = None
        for attempt in range(1, max_captcha_attempts + 1):
            captcha = self.capsolver.solve_v3(
                "https://www.spotify.com",
                "6LfCVLAUAAAAALFwwRnnCJ12DalriUGbj8FW_J39",
                "website/signup/submit_email"
            )
            if captcha:
                break
            self.log(f"Captcha failed, retrying ({attempt}/{max_captcha_attempts})")
            time.sleep(2)
        
        if not captcha:
            self.log("Captcha failed - giving up")
            return False
        
        self.log("Registering account")
        result = self.register(account, captcha)
        
        if not result:
            self.log("Registration failed")
            return False
            
        if isinstance(result, dict) and result.get("challenge"):
            self.log("Solving challenge")
            if not self.solve_challenge(result):
                self.log("Challenge failed")
                return False
        
        self.log("Getting configuration")
        self.get_config()
        
        self.log("Authenticating")
        self.auth()
        
        self.save(account)
        self.log(f"Account created: {account['email']}", success=True)
        return account

class StudentVerifier:
    _lock = threading.Lock()
    
    def __init__(self, process_id=0, use_proxy=False):
        self.process_id = process_id
        self.use_proxy = use_proxy
        self.proxy = os.getenv("PROXY") if use_proxy else None
        self.current_link = None
        
    def log(self, message, success=False):
        process_tag = f"#{self.process_id}" if self.process_id > 0 else ""
        if success:
            print(f"\033[92m{process_tag} {message}\033[0m")
        else:
            print(f"{process_tag} {message}")
    
    def get_link(self):
        with StudentVerifier._lock:
            try:
                if not os.path.exists("student.txt"):
                    self.log("No student.txt file found")
                    return None
                    
                with open("student.txt", "r") as f:
                    links = f.readlines()
                
                if not links:
                    self.log("No verification links available")
                    return None
                
                self.current_link = links[0].strip()
                
                with open("student.txt", "w") as f:
                    f.writelines(links[1:])
                    
                self.log(f"Got verification link: {self.current_link[:40]}")
                return self.current_link
                
            except Exception as e:
                self.log(f"Error getting verification link: {str(e)}")
                return None
    
    def return_link(self):
        if not self.current_link:
            return False
            
        with StudentVerifier._lock:
            try:
                with open("student.txt", "r") as f:
                    existing_links = f.readlines()
                
                with open("student.txt", "w") as f:
                    f.write(f"{self.current_link}\n")
                    f.writelines(existing_links)
                    
                self.log(f"Returned link to student.txt: {self.current_link[:40]}")
                return True
            except Exception as e:
                self.log(f"Error returning link: {str(e)}")
                return False
    
    def mark_as_used(self):
        if not self.current_link:
            return False
            
        with StudentVerifier._lock:
            try:
                os.makedirs(os.path.dirname("student-used.txt") or ".", exist_ok=True)
                with open("student-used.txt", "a") as f:
                    f.write(f"{self.current_link}\n")
                    
                self.log(f"Marked link as used: {self.current_link[:40]}")
                self.current_link = None
                return True
            except Exception as e:
                self.log(f"Error marking link as used: {str(e)}")
                return False
    
    def setup_session(self, cookie_string):
        session = tls_client.Session(client_identifier="chrome_113", random_tls_extension_order=True)
        
        session.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.5",
            "Accept-Encoding": "gzip, deflate, br",
            "Referer": "https://www.spotify.com/",
            "Cookie": cookie_string
        }
        
        if self.use_proxy and self.proxy:
            if '@' in self.proxy:
                auth, proxy = self.proxy.split('@')
                user, pwd = auth.split(':')
                host, port = proxy.split(':')
                session.proxies = {
                    "http": f"http://{user}:{pwd}@{host}:{port}",
                    "https": f"http://{user}:{pwd}@{host}:{port}"
                }
            else:
                host, port = self.proxy.split(':')
                session.proxies = {
                    "http": f"http://{host}:{port}",
                    "https": f"http://{host}:{port}"
                }
        return session
    
    def verify(self, account, max_retry=3):
        max_link_attempts = 5
        
        for link_attempt in range(1, max_link_attempts + 1):
            verification_link = self.get_link()
            if not verification_link:
                return False
                
            cookie_file = f"cookies/{account['email']}.txt"
            if not os.path.exists(cookie_file):
                self.log(f"No cookie file for {account['email']}")
                self.return_link()
                return False
                
            with open(cookie_file, "r") as f:
                cookie_string = f.read().strip()
                
            parsed_url = urlparse(verification_link)
            query = parsed_url.query
            verification_id = None
            
            if "verificationId=" in query:
                verification_id = query.split("verificationId=")[1].split("&")[0]
            
            if not verification_id:
                self.log("Could not extract verification ID")
                self.return_link()
                return False
            
            verification_success = False
            discount_already_used = False
            
            for try_num in range(1, max_retry + 1):
                try:
                    session = self.setup_session(cookie_string)
                    
                    self.log(f"Step 1: Applying with ID: {verification_id[:8]}")
                    apply_url = f"https://www.spotify.com/student/apply/sheerid-program?verificationId={verification_id}"
                    response = session.get(apply_url, allow_redirects=True)
                    
                    self.log(f"Step 1: {response.status_code}")
                    if response.status_code not in [200, 301, 302, 307]:
                        self.log(f"Step 1 failed ({try_num}/{max_retry})")
                        time.sleep(2)
                        continue
                    
                    self.log("Step 2: Confirming verification")
                    confirm_url = f"https://www.spotify.com/uk/student/confirmed/sheerid-program/?verificationId={verification_id}"
                    response = session.get(confirm_url, allow_redirects=True)
                    
                    self.log(f"Step 2: {response.status_code}")
                    if response.status_code not in [200, 301, 302, 307]:
                        self.log(f"Step 2 failed ({try_num}/{max_retry})")
                        time.sleep(2)
                        continue
                    
                    self.log("Step 3: Checking verification status")
                    verify_url = "https://www.spotify.com/uk/student/verification/"
                    response = session.get(verify_url, allow_redirects=True)
                    
                    self.log(f"Step 3: {response.status_code}")
                    if response.status_code != 200:
                        self.log(f"Step 3 failed ({try_num}/{max_retry})")
                        time.sleep(2)
                        continue
                    
                    # Disabled saving verification HTML to disk
                    html_content = response.text
                    
                    main_match = re.search(r'<main[^>]*>(.*?)</main>', html_content, re.DOTALL)
                    if main_match:
                        main_content = main_match.group(1)
                        if "Discount already used" in main_content or "student ID has already been used" in main_content:
                            self.log("This verification link has already been used")
                            discount_already_used = True
                            break
                    
                    is_verification_successful = False
                    success_patterns = [
                        r"You['']re verified as a student until:",
                        r"You are verified as a student until:",
                        r"verified as a student until",
                        r"<h1[^>]*>You['']re verified as a student",
                        r"student status is verified",
                        r"encore-premium-student-set"
                    ]
                    
                    for pattern in success_patterns:
                        if re.search(pattern, html_content, re.IGNORECASE):
                            is_verification_successful = True
                            break
                    
                    if is_verification_successful:
                        date_matches = re.search(r"until:?\s*(\d{2}/\d{2}/\d{4})", html_content)
                        if date_matches:
                            verified_until = date_matches.group(1)
                            self.log(f"Verified until: {verified_until}", success=True)
                        else:
                            self.log(f"Student verification successful!", success=True)
                            
                        self.update_status(account['email'], account['password'], is_student=True)
                        verification_success = True
                        break
                    else:
                        self.log(f"Verification pending ({try_num}/{max_retry})")
                        time.sleep(5)
                        
                except Exception as e:
                    self.log(f"Verification error: {str(e)} ({try_num}/{max_retry})")
                    time.sleep(2)
            
            if discount_already_used:
                self.mark_as_used()
                self.log(f"Trying with a new link (attempt {link_attempt}/{max_link_attempts})")
                continue
                
            if not verification_success:
                self.log(f"Failed to verify after {max_retry} attempts")
                self.return_link()
                
            return verification_success
            
        self.log(f"Exhausted {max_link_attempts} verification links, all used or failed")
        return False
    
    def update_status(self, email, password, is_student=True):
        # Optionally skip writing account status files
        if os.getenv("WRITE_ACCOUNT_FILES", "true").lower() != "true":
            return True
        with StudentVerifier._lock:
            try:
                target_file = "akunstudent.txt" if is_student else "akunbiasa.txt"
                
                for file_name in ["akunbiasa.txt", "akunstudent.txt"]:
                    if not os.path.exists(file_name):
                        with open(file_name, "w") as f:
                            pass
                
                account_exists = False
                for file_name in ["akunbiasa.txt", "akunstudent.txt"]:
                    if os.path.exists(file_name):
                        with open(file_name, "r") as f:
                            accounts = f.readlines()
                        
                        if any(line.strip().split('|')[0] == email for line in accounts if line.strip() and '|' in line):
                            account_exists = True
                            
                            if (is_student and file_name == "akunbiasa.txt") or (not is_student and file_name == "akunstudent.txt"):
                                with open(file_name, "w") as f:
                                    for acc in accounts:
                                        if acc.strip() and '|' in acc and acc.strip().split('|')[0] != email:
                                            f.write(acc)
                
                with open(target_file, "r") as f:
                    target_accounts = f.readlines()
                    
                account_in_target = False
                with open(target_file, "w") as f:
                    for acc in target_accounts:
                        if acc.strip() and '|' in acc and acc.strip().split('|')[0] == email:
                            f.write(f"{email}|{password}\n")
                            account_in_target = True
                        else:
                            f.write(acc)
                        
                    if not account_in_target:
                        f.write(f"{email}|{password}\n")
                
                if not os.path.exists("akun.txt"):
                    with open("akun.txt", "w") as f:
                        pass
                    
                with open("akun.txt", "r") as f:
                    accounts = f.readlines()
                
                with open("akun.txt", "w") as f:
                    updated = False
                    for account in accounts:
                        account_parts = account.strip().split("|")
                        if len(account_parts) >= 2 and account_parts[0] == email:
                            status = "student" if is_student else "basic"
                            f.write(f"{email}|{password}|{status}\n")
                            updated = True
                        else:
                            f.write(account)
                        
                    if not updated:
                        status = "student" if is_student else "basic"
                        f.write(f"{email}|{password}|{status}\n")
                
                return True
            except Exception as e:
                self.log(f"Error updating account status: {str(e)}")
                return False

class SpotifyLogin:
    def __init__(self, email, password, process_id=0, use_proxy=False):
        self.email = email
        self.password = password
        self.process_id = process_id
        self.use_proxy = use_proxy
        self.proxy = os.getenv("PROXY") if use_proxy else None
        self.session = self._init_session()
        self.capsolver = Capsolver(os.getenv("CAPSOLVER_API_KEY"))
        self.csrf_token = None
        self.flow_id = None
        
    def log(self, message, success=False):
        process_tag = f"#{self.process_id}" if self.process_id > 0 else ""
        if success:
            print(f"\033[92m{process_tag} {message}\033[0m")
        else:
            print(f"{process_tag} {message}")
    
    def _init_session(self):
        session = tls_client.Session(client_identifier="chrome_113", random_tls_extension_order=True)
        session.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.5",
            "Accept-Encoding": "gzip, deflate, br",
            "Connection": "keep-alive",
        }
        
        if self.use_proxy and self.proxy:
            if '@' in self.proxy:
                auth, proxy = self.proxy.split('@')
                user, pwd = auth.split(':')
                host, port = proxy.split(':')
                session.proxies = {
                    "http": f"http://{user}:{pwd}@{host}:{port}",
                    "https": f"http://{user}:{pwd}@{host}:{port}"
                }
            else:
                host, port = self.proxy.split(':')
                session.proxies = {
                    "http": f"http://{host}:{port}",
                    "https": f"http://{host}:{port}"
                }
            self.log(f"Using proxy: {host}:****")
        return session
    
    def _get_session(self):
        url = "https://accounts.spotify.com/en/login"
        resp = self.session.get(url)
        
        if resp.status_code != 200:
            self.log(f"Failed to get session: {resp.status_code}")
            return False
        
        try:
            self.csrf_token = resp.cookies.get("sp_sso_csrf_token")
            
            pattern = r'flowCtx":"([^"]+)"'
            match = re.search(pattern, resp.text)
            if match:
                self.flow_id = match.group(1)
            else:
                self.log("Failed to find flow_id")
                return False
                
            self.session.cookies.set("remember", quote(self.email))
            
            self.log(f"CSRF={self.csrf_token[:8]} FLOW={self.flow_id[:8]}")
            return True
        except Exception as e:
            self.log(f"Error parsing session: {str(e)}")
            return False
    
    def _get_additional_cookies(self):
        urls = [
            "https://open.spotify.com/",
            "https://pixel.spotify.com/v2/sync?ce=1&pp="
        ]
        
        for url in urls:
            try:
                self.session.get(url)
            except Exception as e:
                self.log(f"Error getting additional cookies: {str(e)}")
    
    def _submit_password(self, captcha_token):
        url = "https://accounts.spotify.com/login/password"
        
        payload = {
            "username": self.email,
            "password": self.password,
            "remember": "true",
            "recaptchaToken": captcha_token,
            "continue": "https://accounts.spotify.com/en/status",
            "flowCtx": self.flow_id,
        }
        
        headers = {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Csrf-Token": self.csrf_token,
        }
        
        response = self.session.post(url, data=payload, headers=headers)
        
        if response.status_code != 200:
            self.log(f"Failed to submit password: {response.status_code}")
            return None
            
        try:
            result = response.json()
            
            if result.get("result") == "ok":
                self.log("Login successful", success=True)
                self._get_additional_cookies()
                return True
            elif result.get("result") == "redirect_required":
                self.log("Challenge detected, handling challenge")
                return self._handle_challenge(result)
            else:
                error_type = result.get("error", "unknown_error")
                self.log(f"Login failed: {error_type}")
                return False
        except Exception as e:
            self.log(f"Error processing login response: {str(e)}")
            return False
    
    def _handle_challenge(self, data):
        try:
            challenge_url = data["data"]["redirect_url"]
            self.log(f"Challenge URL: {challenge_url}")
            
            self.session.get(challenge_url)
            
            captcha_token = self.capsolver.solve_v3(
                challenge_url,
                "6LfCVLAUAAAAALFwwRnnCJ12DalriUGbj8FW_J39",
                "accounts/login"
            )
            
            if not captcha_token:
                self.log("Failed to solve challenge captcha")
                return False
                
            session_id = challenge_url.split("c/")[1].split("/")[0]
            challenge_id = challenge_url.split(session_id + "/")[1].split("/")[0]
            
            url = "https://challenge.spotify.com/api/v1/invoke-challenge-command"
            payload = {
                "session_id": session_id,
                "challenge_id": challenge_id,
                "recaptcha_challenge_v1": {"solve": {"recaptcha_token": captcha_token}}
            }
            
            headers = {
                "Content-Type": "application/json"
            }
            
            response = self.session.post(url, json=payload, headers=headers)
            
            if response.status_code != 200:
                self.log(f"Failed to submit challenge: {response.status_code}")
                return False
                
            result = response.json()
            
            if "Completed" in result:
                interaction_hash = result["Completed"]["Hash"]
                interaction_reference = result["Completed"]["InteractionReference"]
                
                complete_url = f"https://accounts.spotify.com/login/challenge-completed?sessionId={session_id}&interactRef={interaction_reference}&hash={interaction_hash}"
                
                self.session.get(complete_url)
                self._get_additional_cookies()
                self.log("Challenge solved", success=True)
                return True
            else:
                self.log("Challenge response unexpected format")
                return False
                
        except Exception as e:
            self.log(f"Error handling challenge: {str(e)}")
            return False
    
    def login(self):
        self.log(f"Logging in: {self.email}")
        
        if not self._get_session():
            return False
            
        captcha_token = self.capsolver.solve_v3(
            "https://accounts.spotify.com/en/login",
            "6LfCVLAUAAAAALFwwRnnCJ12DalriUGbj8FW_J39",
            "accounts/login"
        )
        
        if not captcha_token:
            self.log("Failed to solve captcha")
            return False
            
        result = self._submit_password(captcha_token)
        
        if result:
            self.log(f"Login successful for {self.email}", success=True)
            return True
        else:
            self.log(f"Login failed for {self.email}")
            return False
    
    def save_cookies(self):
        os.makedirs("cookies", exist_ok=True)
        
        cookie_header = ""
        for cookie in self.session.cookies:
            cookie_header += f"{cookie.name}={cookie.value}; "
            
        with open(f"cookies/{self.email}.txt", "w") as f:
            f.write(cookie_header)
            
        self.log(f"Cookies saved for {self.email}")
        return cookie_header


def login_and_verify_task(process_id):
    use_proxy = os.getenv("USE_PROXY", "False").lower() == "true"
    
    if not os.path.exists("akunbiasa.txt"):
        print(f"#{process_id} No akunbiasa.txt file found")
        return False
        
    with open("akunbiasa.txt", "r") as f:
        accounts = f.readlines()
    
    if not accounts:
        print(f"#{process_id} No accounts in akunbiasa.txt")
        return False
    
    # Check if student links exist
    if not os.path.exists("student.txt"):
        print(f"#{process_id} No student.txt file found")
        return False
        
    with open("student.txt", "r") as f:
        student_links = f.readlines()
    
    if not student_links:
        print(f"#{process_id} No verification links available in student.txt")
        return False
    
    # Get an account using process_id for distribution
    account_index = (process_id - 1) % len(accounts)
    account_line = accounts[account_index].strip()
    
    try:
        email, password = account_line.split("|")
    except:
        print(f"#{process_id} Invalid account format: {account_line}")
        return False
    
    # Login
    login = SpotifyLogin(email, password, process_id, use_proxy)
    login_success = login.login()
    
    if not login_success:
        return False
        
    login.save_cookies()
    
    # Verify as student
    verifier = StudentVerifier(process_id=process_id, use_proxy=use_proxy)
    account = {"email": email, "password": password}
    success = verifier.verify(account)
    
    if success:
        print(f"{GREEN}#{process_id} Account verified as student: {email}{RESET}")
        
        # Remove this account from akunbiasa.txt
        with StudentVerifier._lock:
            with open("akunbiasa.txt", "r") as f:
                remaining_accounts = f.readlines()
            
            with open("akunbiasa.txt", "w") as f:
                for acc in remaining_accounts:
                    if acc.strip() and not acc.strip().startswith(email):
                        f.write(acc)
    else:
        print(f"#{process_id} Failed to verify: {email}")
    
    return success


def get_public_ip():
    try:
        response = requests.get("https://api.ipify.org?format=json", timeout=5)
        return response.json().get("ip", "Unknown")
    except:
        try:
            response = requests.get("https://ipinfo.io/json", timeout=5)
            return response.json().get("ip", "Unknown")
        except:
            return "Unknown"

def get_geolocation(ip):
    try:
        response = requests.get(f"https://ipinfo.io/{ip}/json", timeout=5)
        data = response.json()
        return {
            "city": data.get("city", "Unknown"),
            "region": data.get("region", "Unknown"),
            "country": data.get("country", "Unknown"),
            "org": data.get("org", "Unknown")
        }
    except:
        return {"city": "Unknown", "region": "Unknown", "country": "Unknown", "org": "Unknown"}

def create_account_task(process_id):
    use_proxy = os.getenv("USE_PROXY", "False").lower() == "true"
    spotify = Spotify(process_id=process_id, use_proxy=use_proxy)
    return spotify.create()

def create_and_verify_task(process_id):
    use_proxy = os.getenv("USE_PROXY", "False").lower() == "true"
    spotify = Spotify(process_id=process_id, use_proxy=use_proxy)
    account = spotify.create()
    
    if not account:
        print(f"#{process_id} Account creation failed")
        return False
    
    time.sleep(3)
    
    verifier = StudentVerifier(process_id=process_id, use_proxy=use_proxy)
    success = verifier.verify(account)
    
    if success:
        print(f"{GREEN}#{process_id} Account verified as student: {account['email']}{RESET}")
    else:
        print(f"#{process_id} Failed to verify: {account['email']}")
    
    return success


def run_concurrent(task_func, num_processes=1):
    with concurrent.futures.ThreadPoolExecutor(max_workers=num_processes) as executor:
        futures = [executor.submit(task_func, i+1) for i in range(num_processes)]
        results = [future.result() for future in concurrent.futures.as_completed(futures)]
    
    successful = sum(1 for result in results if result)
    print(f"\nSummary: {successful}/{num_processes} processes completed successfully")
    return results

def run_continuous(task_func, num_processes=1):
    print(f"\nStarting continuous operation with {num_processes} parallel processes")
    print("Press Ctrl+C to stop")
    
    completed = 0
    successful = 0
    
    try:
        while True:
            # Check if we've run out of resources
            if task_func == login_and_verify_task:
                # Check for accounts
                if not os.path.exists("akunbiasa.txt") or not os.path.getsize("akunbiasa.txt"):
                    print("\nNo more accounts in akunbiasa.txt. Stopping.")
                    break
                    
                # Check for student links
                if not os.path.exists("student.txt") or not os.path.getsize("student.txt"):
                    print("\nNo more verification links in student.txt. Stopping.")
                    break
            
            with concurrent.futures.ThreadPoolExecutor(max_workers=num_processes) as executor:
                futures = [executor.submit(task_func, i+1) for i in range(num_processes)]
                
                for future in concurrent.futures.as_completed(futures):
                    try:
                        result = future.result()
                        completed += 1
                        if result:
                            successful += 1
                    except Exception as e:
                        print(f"Task error: {str(e)}")
            
            print(f"\n--- Batch complete: {successful}/{completed} successful ---")
            
            # After each batch, check resources again
            if task_func == login_and_verify_task:
                # Check for accounts
                if not os.path.exists("akunbiasa.txt") or not os.path.getsize("akunbiasa.txt"):
                    print("No more accounts in akunbiasa.txt. Stopping.")
                    break
                    
                # Check for student links
                if not os.path.exists("student.txt") or not os.path.getsize("student.txt"):
                    print("No more verification links in student.txt. Stopping.")
                    break
            
    except KeyboardInterrupt:
        print("\nContinuous operation stopped by user")
        
    print(f"Final summary: {successful}/{completed} accounts processed successfully")

def display_dashboard():
    load_dotenv()
    public_ip = get_public_ip()
    geo = get_geolocation(public_ip)
    use_proxy = os.getenv("USE_PROXY", "False").lower() == "true"
    proxy = os.getenv("PROXY", "None") if use_proxy else "Disabled"
    process_count = int(os.getenv("PROCESS", "1"))
    domain = os.getenv("DOMAIN", "rajaiblis.com")
    password = os.getenv("PASSWORD", "Waroengku123")
    
    print(r"""
 __             _             _            _   
/ _\_ __   ___ | |_ _   _  __| | ___ _ __ | |_ 
\ \| '_ \ / _ \| __| | | |/ _` |/ _ \ '_ \| __|
_\ \ |_) | (_) | |_| |_| | (_| |  __/ | | | |_ 
\__/ .__/ \___/ \__|\__,_|\__,_|\___|_| |_|\__|
   |_|        
               Dev. Waroengku
           https://t.me/muhrifqie  


    """)
    print(f"🌐 IP      : {public_ip} | 📍 {geo['city']}, {geo['country']}")
    print(f"🚀 Proxy   : {'✅ Enabled' if use_proxy else '❌ Disabled'}")
    print(f"▶️ Procces : {process_count}")
    print(f"📧 Domain  : {domain}")
    
    regular_count = 0
    student_count = 0
    
    try:
        if os.path.exists("akunbiasa.txt"):
            with open("akunbiasa.txt", "r") as f:
                regular_count = len(f.readlines())
    except:
        pass
        
    try:
        if os.path.exists("akunstudent.txt"):
            with open("akunstudent.txt", "r") as f:
                student_count = len(f.readlines())
    except:
        pass
    
    print(f"\n👤 Account : {regular_count + student_count} ({regular_count} regular, {student_count} student)")
    
    try:
        with open("student.txt", "r") as f:
            student_links = f.readlines()
        print(f"🔗 Links   : {len(student_links)}")
    except:
        print("No student links available")

def main():
    load_dotenv()
    display_dashboard()
    
    print("\n1. Create Account")
    print("2. Create & Verify")
    print("3. Login & Verify")
    
    try:
        choice = int(input("\nChoice: "))
        process_count = int(os.getenv("PROCESS", "1"))
        custom_count = input(f"Number of parallel processes (default: {process_count}): ")
        if custom_count.strip():
            process_count = int(custom_count)
        
        continuous = input("Run continuously? (y/n): ").lower() == 'y'
        
        if choice == 1:
            if continuous:
                run_continuous(create_account_task, process_count)
            else:
                run_concurrent(create_account_task, process_count)
                
        elif choice == 2:
            if continuous:
                run_continuous(create_and_verify_task, process_count)
            else:
                run_concurrent(create_and_verify_task, process_count)
                
        elif choice == 3:
            if continuous:
                run_continuous(login_and_verify_task, process_count)
            else:
                run_concurrent(login_and_verify_task, process_count)
                
        else:
            print("Invalid choice")
            
    except ValueError:
        print("Please enter a valid number")
    except KeyboardInterrupt:
        print("\nOperation cancelled")
    except Exception as e:
        print(f"Error: {str(e)}")


if __name__ == "__main__":
    main()

