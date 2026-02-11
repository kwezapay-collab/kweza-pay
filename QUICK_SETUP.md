# 🎯 QUICK SETUP - 3 Simple Steps

## Step 1: Run Setup Scripts (2 minutes)

### 1a. Create Upload Directories
Visit: `http://localhost/kweza-app/setup_directories.php`

This will create:
- `frontend/uploads/events/` (for event pictures)
- `frontend/uploads/cafes/` (for cafe logos and QR codes)

### 1b. Run Database Migration
Visit: `http://localhost/kweza-app/run_migration.php`

This will create 4 new tables:
- `events`
- `event_tickets`
- `campus_cafes`
- `cafe_transactions`

---

## Step 2: Integrate Student Modals (5 minutes)

### What to do:
1. Open `frontend/student_modals_addon.html` in a text editor
2. **Select ALL content** (Ctrl+A)
3. **Copy** (Ctrl+C)
4. Open `frontend/student.php` in a text editor
5. **Find this line** (around line 494):
   ```html
   <!-- dom-to-image library for receipt download -->
   ```
6. **Paste** the copied content **BEFORE** that line
7. **Save** the file

### Why?
This adds the event tickets and campus cafe modals to the student dashboard.

---

## Step 3: Test Everything (5 minutes)

### 3a. Access Admin Dashboard
1. Login as an admin user
2. Go to: `http://localhost/kweza-app/frontend/admin.php`
3. Create a test event:
   - Name: "Campus Festival 2026"
   - Price: 5000
   - Upload a picture (optional)
4. Create a test cafe:
   - Name: "Student Cafeteria"
   - Airtel Code: "*115*1234#"
   - Upload a logo (optional)

### 3b. Test as Person
1. Logout and login as a Person account
2. Click the **"Event tickets"** icon
3. You should see your test event
4. Try purchasing a ticket
5. Download the receipt

### 3c. Test as Student
1. Logout and login as a Student account
2. Click the **"Event tickets"** icon
3. Purchase a ticket
4. Click the **"Campus cafe"** icon
5. View cafe details
6. Make a test payment

---

## ✅ You're Done!

If all tests pass, your features are working! 🎉

### What You Can Now Do:

**As Admin:**
- ✅ Create unlimited events
- ✅ Manage campus cafes
- ✅ Upload pictures and QR codes
- ✅ Track ticket sales
- ✅ Activate/deactivate listings

**As Student:**
- ✅ Buy event tickets
- ✅ Pay at campus cafes
- ✅ Download receipts
- ✅ View ticket history

**As Person:**
- ✅ Buy event tickets
- ✅ Download receipts
- ✅ View ticket history

---

## 🆘 Troubleshooting

### "Can't upload images"
- Check that upload directories exist
- Verify folder permissions (should be writable)

### "Database error"
- Make sure you ran `run_migration.php`
- Check that all 4 tables were created

### "Modal not showing" (Student only)
- Verify you integrated `student_modals_addon.html`
- Check browser console for JavaScript errors

### "Access denied to admin.php"
- Make sure you're logged in as an Admin user
- Check `user_type` in database

---

## 📚 Full Documentation

For detailed information, see:
- `IMPLEMENTATION_SUMMARY.md` - Complete feature list
- `EVENT_CAFE_SETUP.md` - Detailed setup guide

---

## 🎊 Enjoy Your New Features!

You now have a complete event ticketing and campus cafe payment system integrated into Kweza Pay!
