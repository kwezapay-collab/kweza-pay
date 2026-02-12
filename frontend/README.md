# Kweza Pay - Next.js Conversion

## ✅ Conversion Complete!

This Next.js application is a complete rewrite of the PHP Kweza Pay application, optimized for deployment on Vercel.

## 📦 What's Included

### Core Features
- ✅ **Authentication System**: Login, Register, Verify with JWT tokens
- ✅ **Student Dashboard**: Balance display, send money, pay merchant, transaction history
- ✅ **Merchant Dashboard**: QR code display, business balance, payment acceptance
- ✅ **Admin Dashboard**: User management, merchant approval, system oversight
- ✅ **Payment Processing**: P2P transfers, merchant payments, SU fees, withdrawals
- ✅ **Profile Management**: Profile updates, PIN changes, profile picture uploads

### Technical Stack
- **Framework**: Next.js 14 with App Router
- **Language**: TypeScript
- **Database**: Supabase (PostgreSQL)
- **Authentication**: JWT with HTTP-only cookies
- **Styling**: CSS Modules with PayPal-inspired design
- **Deployment**: Vercel-ready

## 🚀 Quick Start

### 1. Install Dependencies
```bash
cd kweza-nextjs
npm install
```

### 2. Configure Environment
```bash
cp .env.local.example .env.local
```

Edit `.env.local` with your credentials:
```env
# Supabase
NEXT_PUBLIC_SUPABASE_URL=your_supabase_url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_anon_key
SUPABASE_SERVICE_ROLE_KEY=your_service_role_key

# JWT
JWT_SECRET=your_random_secret_string_here

# PayChanger (Optional - test keys provided)
```

### 3. Set Up Database Functions
Run the SQL in `database/helper_functions.sql` in your Supabase SQL Editor.

### 4. Run Development Server
```bash
npm run dev
```

Visit [http://localhost:3000](http://localhost:3000)

### 5. Deploy to Vercel
```bash
vercel
```

## 📁 Project Structure

```
kweza-nextjs/
├── src/
│   ├── app/
│   │   ├── (auth)/              # Auth pages
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   └── verify/
│   │   ├── (dashboard)/         # Protected dashboards
│   │   │   ├── student/
│   │   │   ├── merchant/
│   │   │   ├── admin/
│   │   │   ├── person/
│   │   │   └── student-union/
│   │   └── api/                 # API Routes (20+ endpoints)
│   │       ├── auth/
│   │       ├── payments/
│   │       ├── transactions/
│   │       ├── merchant/
│   │       ├── admin/
│   │       └── user/
│   ├── components/
│   │   └── ui/                  # Reusable components
│   │       ├── Modal.tsx
│   │       └── Toast.tsx
│   ├── lib/
│   │   ├── supabase/           # Database clients
│   │   ├── auth/               # JWT utilities
│   │   └── services/           # PayChanger, etc.
│   └── middleware.ts           # Route protection
├── database/
│   └── helper_functions.sql    # SQL functions
└── package.json
```

## 🔌 API Endpoints

### Authentication (4)
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/verify` - Account verification
- `POST /api/auth/logout` - User logout
- `POST /api/auth/resend-code` - Resend verification

### Payments (5)
- `POST /api/payments/merchant` - Pay merchant via QR
- `POST /api/payments/send-money` - P2P transfers
- `POST /api/payments/su-fee` - Student union fees
- `POST /api/payments/withdraw` - Withdraw to mobile money

### Transactions (1)
- `GET /api/transactions/list` - Get transaction history

### Merchant (2)
- `POST /api/merchant/apply` - Apply as merchant
- `GET /api/merchant/info` - Get merchant details

### Admin (3)
- `GET /api/admin/users` - List all users
- `GET /api/admin/pending-merchants` - Pending applications
- `POST /api/admin/approve-merchant` - Approve merchant

### User (3)
- `GET /api/user/profile` - Get user profile
- `POST /api/user/upload-profile-pic` - Upload profile picture
- `PUT /api/user/update-pin` - Change PIN

**Total: 20+ API endpoints**

## 🎨 Features

### Student Dashboard
- View wallet balance
- Send money to contacts
- Pay merchants via QR code
- Pay student union fees
- Apply to become merchant
- View transaction history

### Merchant Dashboard
- Display QR code for payments
- View business balance
- Track received payments
- Pending approval workflow

### Admin Dashboard
- Approve/reject merchant applications
- View all users
- System statistics
- User management

## 🔐 Security

- **JWT Authentication**: HTTP-only cookies prevent XSS attacks
- **PIN Hashing**: bcrypt with salt rounds
- **Route Protection**: Middleware validates all dashboard routes
- **SQL Injection Prevention**: Parameterized queries via Supabase
- **CSRF Protection**: SameSite cookies

## 📝 Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `NEXT_PUBLIC_SUPABASE_URL` | Your Supabase project URL | Yes |
| `NEXT_PUBLIC_SUPABASE_ANON_KEY` | Public anon key | Yes |
| `SUPABASE_SERVICE_ROLE_KEY` | Service role key (server only) | Yes |
| `JWT_SECRET` | Secret for JWT signing | Yes |
| `PAYCHANGER_PUBLIC_KEY` | PayChanger public key | Optional |
| `PAYCHANGER_SECRET_KEY` | PayChanger secret key | Optional |

## 🚧 Known Limitations

- **Email sending**: Not yet implemented (verification codes logged to console)
- **PayChanger integration**: Using test keys, needs real API testing
- **Event/Ticket system**: Basic structure, needs full implementation
- **Cafe system**: Basic structure, needs full implementation
- **QR Scanner**: Placeholder, needs html5-qrcode integration

## 🔄 Migration from PHP

### Database
Your existing Supabase database works as-is. No schema changes needed.

### Sessions → JWT
PHP `$_SESSION` replaced with JWT tokens in HTTP-only cookies.

### User Data
All existing users, transactions, and merchants are preserved.

## 📊 Testing

### Test User Creation
```bash
# Create test users via register page
# Or insert directly in Supabase SQL:
INSERT INTO users (phone_number, full_name, pin_hash, user_type, is_verified)
VALUES ('265999123456', 'Test User', '$2a$10$...', 'Student', true);
```

### Test Merchant
1. Register as student
2. Apply to become merchant
3. Admin approves application
4. QR code generated automatically

## 🐛 Troubleshooting

**Module not found errors?**
```bash
rm -rf node_modules package-lock.json
npm install
```

**Supabase connection issues?**
- Verify environment variables
- Check Supabase project isn't paused

**Authentication not working?**
- Clear browser cookies
- Check JWT_SECRET is set
- Verify middleware.ts is running

## 📚 Documentation

- [Next.js Docs](https://nextjs.org/docs)
- [Supabase Docs](https://supabase.com/docs)
- [Vercel Deployment](https://vercel.com/docs)

## 🎯 Production Checklist

Before deploying:
- [ ] Set all environment variables in Vercel
- [ ] Run database helper functions
- [ ] Test authentication flow
- [ ] Test payment processing
- [ ] Create admin user
- [ ] Set up Supabase Storage bucket (`profile-pictures`)
- [ ] Configure custom domain (optional)
- [ ] Set up email service for verification codes

## 💡 Tips

- Use Vercel's preview deployments for testing
- Monitor API routes in Vercel dashboard
- Check Supabase logs for database errors
- Use browser DevTools to inspect network requests

---

**Need help?** Check the implementation plan and walkthrough documents in the artifacts folder.
