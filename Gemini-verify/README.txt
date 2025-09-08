How to run (CLI + PHP):

1) Install Python deps:
   - Ensure Python 3 is installed and available as "python" or "py"
   - In this folder, run:
       pip install -r requirements.txt

2) Run with PHP built-in server:
   - From this folder, run:
       php -S 0.0.0.0:6000
   - Open http://localhost:6000/index.php

3) Use the page:
   - Upload one or more photos (they go to fotosiswa/)
   - Click "Generate Cards from fotosiswa/" to run generate.py
   - See results in the gallery (output/)

Notes:
- On Windows, if "python" command fails, the page tries "py".
- You can also run the generator manually:
    python generate.py
- The script will delete photos in fotosiswa/ after processing.

PM2 (recommended for VPS):
1) Install Node.js + PM2
   curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
   sudo apt-get install -y nodejs
   sudo npm i -g pm2
2) From this folder:
   pm2 start ecosystem.config.js
   pm2 save
   pm2 startup
3) Access the app at http://your-domain:6000
