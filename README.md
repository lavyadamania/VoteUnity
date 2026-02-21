
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