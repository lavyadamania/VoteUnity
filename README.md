
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
| Database | MySQL 8.0+ |
| Frontend | HTML5, CSS3, JavaScript |
| Face Detection | Python + OpenCV (optional) |
| Maps | Leaflet.js + OpenStreetMap |
| Server | Apache (XAMPP recommended) |

## 📦 Installation

### Prerequisites
- XAMPP (or similar PHP/MySQL environment)
- Python 3.x with OpenCV (optional, for advanced face detection)

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/VoteUnity.git
   ```

2. **Copy to XAMPP**
   ```bash
   # Windows
   xcopy VoteUnity C:\xampp\htdocs\voting /E /I
   
   # Linux/Mac
   cp -r VoteUnity /opt/lampp/htdocs/voting
   ```

3. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create database: `voting_system`
   - Import: `sql/voting_system.sql`

4. **Configure Database**
   - Edit `config/database.php`:
   ```php
   $host = 'localhost';
   $dbname = 'voting_system';
   $username = 'root';
   $password = '';  // Your MySQL password
   ```

5. **Start Apache & MySQL** in XAMPP Control Panel

6. **Access the Application**
   - Homepage: http://localhost/voting/
   - Admin Panel: http://localhost/voting/pages/admin/login.php

## 👤 Default Admin Account

| Field | Value |
|-------|-------|
| Username | `admin` *(set during first setup)* |
| Password | *(set during first setup)* |

> **Note:** The first admin to register becomes the Super Admin.

## 📱 Usage Guide

### Voter Registration
1. Go to **Register** page
2. Enter name, email, password
3. Enter 12-digit Aadhaar number
4. Capture face photo using webcam
5. Submit registration

### Casting a Vote
1. Login with email and password
2. Verify face using webcam
3. Select your preferred candidate
4. Confirm your vote
5. Save your vote hash for verification

### Admin Panel
1. Login at `/pages/admin/login.php`
2. Complete face verification
3. Non-Super Admins must share location
4. Access dashboard features:
   - View real-time statistics
   - Monitor hash chain integrity
   - Track admin locations
   - Manage admin permissions

## 🔒 Security Features

| Feature | Description |
|---------|-------------|
| **Hash Chain** | Each vote links to previous vote's hash (blockchain-style) |
| **Face Verification** | Biometric authentication for voters and admins |
| **Aadhaar Validation** | 12-digit ID format verification |
| **Session Security** | PHP session management with timeout |
| **Location Tracking** | GPS tracking for admin accountability |
| **Role-Based Access** | Granular permission control |

## 📂 Project Structure

```
VoteUnity/
├── config/
│   └── database.php        # Database configuration
├── css/
│   └── style.css           # Main stylesheet
├── includes/
│   ├── functions.php       # Core helper functions
│   ├── header.php          # Common header
│   └── footer.php          # Common footer
├── js/
│   └── main.js             # Frontend JavaScript
├── pages/
│   ├── admin/              # Admin panel pages
│   │   ├── login.php
│   │   ├── dashboard.php
│   │   ├── view_votes.php
│   │   ├── location_tracker.php
│   │   └── manage_admins.php
│   ├── login.php           # Voter login
│   ├── register.php        # Voter registration
│   └── vote.php            # Voting page
├── python/
│   ├── verify_face.py      # Face comparison script
│   └── capture_face.py     # Face capture script
├── sql/
│   └── voting_system.sql   # Database schema
├── uploads/                # User face images
└── index.php               # Homepage
```

## 🔧 Configuration

### Face Verification
The system uses PHP GD library for basic face comparison. For advanced detection:

1. Install Python dependencies:
   ```bash
   pip install opencv-python numpy
   ```

2. Ensure Python is in PATH

### Location Tracking
- Uses browser Geolocation API
- Requires HTTPS in production
- Stores: latitude, longitude, accuracy, IP

## 📊 Database Schema

| Table | Purpose |
|-------|---------|
| `users` | Voter accounts and profiles |
| `admins` | Admin accounts with permissions |
| `candidates` | Election candidates |
| `votes` | Vote records with hash chain |
| `admin_locations` | Admin GPS tracking |

## 🌐 Deployment

For production deployment:

1. Use HTTPS (required for geolocation)
2. Set strong MySQL password
3. Disable PHP error display
4. Configure proper file permissions
5. Set up regular database backups

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 👨‍💻 Author

**Lavya**

---

*VoteUnity - Secure Online Voting System*