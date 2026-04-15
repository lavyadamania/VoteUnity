
# VoteUnity - Secure Online Voting System

A secure online voting platform with face verification, Aadhaar-based identity validation, blockchain-style vote auditing, and admin location tracking.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript&logoColor=black)

## 🌟 Features

### For Voters
- **Aadhaar Verification** - 12-digit Aadhaar-based identity validation
- **Face Recognition** - Webcam-based biometric authentication
- **Secure Voting** - One person, one vote enforcement
- **Vote Confirmation** - Hash receipt for vote verification

### For Admins
- **Dashboard** - Real-time voting statistics and analytics
- **Face Verification** - Biometric login for all admins
- **Location Tracking** - GPS-based admin location monitoring
- **Role-Based Access** - Super Admin controls permissions
- **Tamper Detection** - Blockchain-style hash chain verification
- **Admin Management** - Approve/reject new admin registrations

## 🛠️ Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.0+ |
| Database | MySQL / PostgreSQL |
| Frontend | HTML5, CSS3, JavaScript |
| Face Detection | PHP GD / OpenCV |
| Maps | Leaflet.js |

## 📦 Installation

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone [Your-Repository-URL]
   ```

2. **Configure Database**
   - Local: Import `sql/voting_system.sql` to MySQL.
   - Cloud: Run `sql/voting_system_pg.sql` in PostgreSQL (Neon/Supabase).
   - Edit `config/database.php` with your credentials.

3. **Access the Application**
   - Homepage: `http://localhost/`
   - Admin Panel: `/pages/admin/login.php`

## ☁️ Run Locally With Cloud DB (Same As Vercel)

1. Pull Vercel environment values (recommended):
   ```bash
   npx vercel login
   npx vercel env pull .env.local
   ```

2. If Vercel CLI is not available, copy `.env.local.example` to `.env.local` and fill your Neon values.

3. Start local server:
   ```bash
   php -S localhost:8000
   ```

4. Open:
   - `http://localhost:8000/`

## Docker (Containerized Run)

Use this when you want the whole stack in containers (PHP app + MySQL DB) with a reproducible environment.

1. Build and start containers:
   ```bash
   docker compose up -d --build
   ```

2. Open the app:
   - `http://localhost:8000/`

3. Useful commands:
   ```bash
   docker compose logs -f web
   docker compose logs -f db
   docker compose down
   ```

Notes:
- MySQL is exposed on host port `3307`.
- Schema auto-loads from `sql/voting_system.sql` on first DB boot.
- DB data is persisted in Docker volume `db_data`.

## Cloud Deployment (Docker Hub + EC2)

This project includes ready deployment files in [deploy/docker-compose.ec2.yml](deploy/docker-compose.ec2.yml).

### 1. Push app image to Docker Hub

```bash
# PowerShell (Windows)
./deploy/push-to-dockerhub.ps1 -DockerHubUser <your-dockerhub-username> -Tag latest
```

### 2. Provision EC2 (Ubuntu)

- Create EC2 instance (Ubuntu 22.04 or later).
- Open Security Group inbound rules:
   - `22` (SSH)
   - `80` (HTTP)

### 3. Install Docker on EC2

```bash
chmod +x deploy/ec2-setup.sh
./deploy/ec2-setup.sh
```

### 4. Configure production env on EC2

```bash
cd deploy
cp .env.ec2.example .env.ec2
nano .env.ec2
```

Set values for:
- `DOCKERHUB_USER`
- `MYSQL_ROOT_PASSWORD`
- `MYSQL_PASSWORD`

### 5. Start app on EC2

```bash
docker login
docker compose --env-file .env.ec2 -f docker-compose.ec2.yml up -d
```

### 6. Verify

- Open `http://<your-ec2-public-ip>/`

### Optional updates (new app version)

```bash
# locally
./deploy/push-to-dockerhub.ps1 -DockerHubUser <your-dockerhub-username> -Tag v2

# on EC2 (edit APP_TAG=v2 in .env.ec2)
docker compose --env-file .env.ec2 -f deploy/docker-compose.ec2.yml pull web
docker compose --env-file .env.ec2 -f deploy/docker-compose.ec2.yml up -d
```

## 👤 Admin Credentials

| Account | Username | Password |
|---------|----------|----------|
| Super Admin | `admin` | `admin123` |

## 🔒 Security Features

| Feature | Description |
|---------|-------------|
| **Hash Chain** | Each vote links to previous vote's hash for integrity |
| **Biometric Auth** | 60% similarity match required for all secure actions |
| **Location Audit** | Mandatory GPS coordinates for admin accountability |

## 👨‍💻 Author

**System Admin**

---

*VoteUnity - Secure Digital Democracy*