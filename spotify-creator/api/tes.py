#!/usr/bin/env python3
"""
Simple API test script
"""

import requests
import json
import time

def test_api():
    """Test API endpoints"""
    base_url = "http://localhost:5112"
    
    print("🚀 Testing SPO Creator API")
    print("=" * 50)
    
    # Test 1: Health Check
    print("1. Testing Health Check...")
    try:
        response = requests.get(f"{base_url}/", timeout=5)
        if response.status_code == 200:
            print("✅ Health check passed")
            print(f"   Response: {response.text[:100]}...")
        else:
            print(f"❌ Health check failed: {response.status_code}")
    except Exception as e:
        print(f"❌ Health check error: {e}")
    
    print()
    
    # Test 2: API Status
    print("2. Testing API Status...")
    try:
        response = requests.get(f"{base_url}/api/status", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print("✅ API status passed")
            print(f"   Response: {json.dumps(data, indent=2)}")
        else:
            print(f"❌ API status failed: {response.status_code}")
            print(f"   Response: {response.text}")
    except Exception as e:
        print(f"❌ API status error: {e}")
    
    print()
    
    # Test 3: Create Account (without trial_link)
    print("3. Testing Create Account (Basic)...")
    try:
        headers = {
            "Content-Type": "application/json"
        }
        payload = {
            "domain": "test.com",
            "password": "TestPass123"
        }
        
        response = requests.post(f"{base_url}/api/create", 
                               headers=headers, 
                               json=payload,
                               timeout=30)
        
        if response.status_code == 200:
            data = response.json()
            print("✅ Create account passed")
            print(f"   Response: {json.dumps(data, indent=2)}")
        else:
            print(f"❌ Create account failed: {response.status_code}")
            print(f"   Response: {response.text}")
    except Exception as e:
        print(f"❌ Create account error: {e}")
    
    print()
    print("=" * 50)
    print("🎉 API testing completed!")

if __name__ == "__main__":
    test_api()
