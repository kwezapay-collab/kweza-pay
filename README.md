# Kweza Pay - Reorganized Project

This project has been reorganized into clear **Frontend** and **Backend** directories to mirror your preferred workflow while maintaining the power of Next.js.

## 📁 Directory Structure

### 🌐 [frontend/](./frontend)
This folder contains the entire **Full-Stack Next.js Application**.
- **User Interface (Frontend)**: All pages and dashboards located in `src/app`.
- **API Endpoints (Backend)**: All server-side routes and PayChangu logic in `src/app/api`.
- **Execution**: To run the app, navigate to this folder:
  ```bash
  cd frontend
  npm run dev
  ```

### 🗄️ [backend/](./backend)
This folder contains **Database & Maintenance Resources**.
- **Database**: SQL scripts for Supabase and project setup.
- **SQL Script**: Use `backend/database/setup_supabase.sql` to initialize your database.

---
**Note**: The legacy PHP files have been fully removed. This Next.js version is now your single source of truth.
