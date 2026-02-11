# Event Tickets & Campus Cafe Features - Setup Guide

## Overview
This implementation adds two major features to your Kweza Pay application:
1. **Event Tickets** - Available for both Students and Person accounts
2. **Campus Cafe** - Available only for Student accounts

## Files Created

### Database
- `backend/db_events_cafes_schema.sql` - Database schema for events and cafes

### Frontend
- `frontend/admin.php` - Admin dashboard for managing events and cafes
- `frontend/student_modals_addon.html` - Modals and JavaScript for student.php (needs to be integrated)

### Backend APIs
- `backend/api/admin_create_event.php` - Create events
- `backend/api/admin_create_cafe.php` - Create campus cafes
- `backend/api/get_events.php` - Fetch all events
- `backend/api/get_cafes.php` - Fetch all cafes
- `backend/api/purchase_ticket.php` - Purchase event tickets
- `backend/api/pay_cafe.php` - Pay at campus cafes
- `backend/api/get_my_tickets.php` - Get user's tickets
- `backend/api/admin_toggle_event.php` - Toggle event status
- `backend/api/admin_delete_event.php` - Delete event
- `backend/api/admin_toggle_cafe.php` - Toggle cafe status
- `backend/api/admin_delete_cafe.php` - Delete cafe

## Installation Steps

### Step 1: Run Database Migration
Execute the SQL schema to create the necessary tables:

```bash
# Navigate to your MySQL
mysql -u your_username -p kweza_pay < backend/db_events_cafes_schema.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select `kweza_pay` database
3. Go to SQL tab
4. Copy and paste contents of `backend/db_events_cafes_schema.sql`
5. Click "Go"

### Step 2: Create Upload Directories
Create directories for file uploads:

```bash
mkdir -p frontend/uploads/events
mkdir -p frontend/uploads/cafes
chmod 777 frontend/uploads/events
chmod 777 frontend/uploads/cafes
```

On Windows (via PowerShell):
```powershell
New-Item -ItemType Directory -Force -Path "frontend\uploads\events"
New-Item -ItemType Directory -Force -Path "frontend\uploads\cafes"
```

### Step 3: Integrate Student Modals
The content from `frontend/student_modals_addon.html` needs to be added to `frontend/student.php`:

1. Open `frontend/student.php`
2. Find the line with `<!-- dom-to-image library for receipt download -->`
3. Copy the entire content from `student_modals_addon.html`
4. Paste it BEFORE the dom-to-image script tag

### Step 4: Access Admin Dashboard
1. Login as an admin user
2. Navigate to: `http://localhost/kweza-app/frontend/admin.php`
3. Create your first event or campus cafe

## Features

### For Admin Users

#### Event Management
- Create events with:
  - Event name and description
  - Ticket price
  - Event date and location
  - Maximum tickets (optional)
  - Event picture
  - Custom ticket template
- View all events
- Activate/Deactivate events
- Delete events
- Track tickets sold

#### Campus Cafe Management
- Add campus cafes with:
  - Cafe name and description
  - Airtel Money code
  - Cafe logo
  - QR code image
- View all cafes
- Activate/Deactivate cafes
- Delete cafes

### For Students

#### Event Tickets
- Browse available events
- View event details
- Purchase tickets with PIN verification
- View purchased tickets
- Download ticket receipts
- Ticket includes:
  - Unique ticket code
  - Event details
  - QR code data for verification

#### Campus Cafe
- Browse available campus cafes
- View cafe details and Airtel Money codes
- View QR codes for payment
- Make payments with amount and description
- Download payment receipts

### For Person Accounts

#### Event Tickets
- Same functionality as students
- Browse and purchase event tickets
- View purchased tickets
- Download receipts

## Usage

### Admin: Creating an Event
1. Go to admin dashboard
2. Click "Events Management" tab
3. Fill in event details:
   - Event Name (required)
   - Ticket Price (required)
   - Description, date, location (optional)
   - Upload event picture
   - Set maximum tickets if needed
4. Click "Create Event"

### Admin: Adding a Campus Cafe
1. Go to admin dashboard
2. Click "Campus Cafes" tab
3. Fill in cafe details:
   - Cafe Name (required)
   - Airtel Money Code (required)
   - Description (optional)
   - Upload cafe logo
   - Upload QR code image
4. Click "Add Cafe"

### Student/Person: Buying Event Tickets
1. Click "Event tickets" icon on dashboard
2. Browse available events
3. Click on an event to view details
4. Enter your PIN
5. Click "Confirm Purchase"
6. Download your ticket receipt

### Student: Paying at Campus Cafe
1. Click "Campus cafe" icon on dashboard
2. Browse available cafes
3. Click on a cafe
4. Enter payment amount
5. Add description (optional)
6. Enter your PIN
7. Click "Confirm Payment"
8. Download your receipt

## Security Features

- PIN verification for all purchases
- Transaction tracking
- Unique ticket codes
- Reference codes for all transactions
- Admin-only access to management features

## Database Tables

### events
- Stores event information
- Tracks tickets sold
- Supports custom ticket templates

### event_tickets
- Stores purchased tickets
- Tracks ticket status (valid, used, cancelled)
- Contains QR code data

### campus_cafes
- Stores cafe information
- Airtel Money codes
- QR code images

### cafe_transactions
- Tracks all cafe payments
- Links to users and cafes
- Payment method tracking

## File Upload Paths

Event pictures: `frontend/uploads/events/`
Cafe logos: `frontend/uploads/cafes/`
Cafe QR codes: `frontend/uploads/cafes/`

## Troubleshooting

### Images not uploading
- Check folder permissions (should be writable)
- Verify upload_max_filesize in php.ini
- Check post_max_size in php.ini

### Database errors
- Ensure all tables are created
- Check foreign key constraints
- Verify user has proper permissions

### Modal not showing
- Check browser console for JavaScript errors
- Ensure all modal HTML is properly integrated
- Verify modal IDs match function calls

## Future Enhancements

Potential additions:
- Email ticket delivery
- QR code scanning for ticket validation
- Event capacity warnings
- Cafe order history
- Payment analytics
- Bulk ticket purchases
- Event categories
- Cafe menu integration

## Support

For issues or questions:
1. Check browser console for errors
2. Verify database tables exist
3. Check file permissions
4. Review API responses in Network tab

## Notes

- All transactions are tracked in the main transactions table
- Tickets can be downloaded as images
- Admin can customize ticket templates
- QR codes can be uploaded or generated
- All monetary values use MWK (Malawi Kwacha)
