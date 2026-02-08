---
description: Build and deploy the Secure Online Voting System mini project prototype
---

# Secure Online Voting System - Build Workflow

> **"c:\Users\Lavya\Desktop\Online Voting System" "C:\xampp\htdocs\voting"
   ```

// turbo
7. Create uploads directory with write permissions:
   ```powershell
   mkdir "C:\xampp\htdocs\voting\uploads\faces"
   ```

---

## Phase 3: Configuration

8. Edit `config/database.php`:
   - Update MySQL credentials if different from default
   - Default: host=localhost, user=root, pass=(empty), db=voting_system

---

## Phase 4: Python Setup (Face Verification)

// turbo
9. Install Python dependencies:
   ```powershell
   pip install opencv-python numpy
   ```

// turbo
10. Test webcam access:
    ```powershell
    python "C:\xampp\htdocs\voting\python\capture_face.py" --test
    ```

---

## Phase 5: Testing the Application

11. Open browser and navigate to: http://localhost/voting/

12. **Test Registration**:
    - Fill form with name, email, password
    - Enter mock Aadhaar (any 12 digits, e.g., 123456789012)
    - Allow webcam access → Capture face → Submit

13. **Test Login**:
    - Enter email and password
    - Complete face verification when prompted

14. **Test Voting**:
    - Select a candidate
    - Click "Cast Vote"
    - Verify: Should see "Vote cast successfully"

15. **Test One-Vote Logic**:
    - Try to vote again
    - Verify: Should show "You have already voted"

16. **Test Admin Panel**: http://localhost/voting/pages/admin/login.php
    - Login: username=admin, password=admin123
    - View dashboard and vote chain

---

## Phase 6: Verify Hash Chain

17. In Admin Panel → View Votes:
    - Check that each vote's `previous_hash` matches the previous vote's `vote_hash`
    - First vote should have `previous_hash` = "GENESIS"

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| MySQL connection failed | Check XAMPP MySQL is running; verify credentials in database.php |
| Webcam not working | Allow browser camera permissions; check if another app is using it |
| Python script error | Ensure OpenCV is installed: `pip install opencv-python` |
| Face capture fails | Try a well-lit environment; ensure face is visible |

---

## Demo Presentation Order

1. Show folder structure and explain each component
2. Show database schema in phpMyAdmin
3. Demo registration with Aadhaar and face capture
4. Demo login with face verification
5. Demo voting and show hash chain in admin panel
6. Explain blockchain concept and limitations
7. Discuss future scope
