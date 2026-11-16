<div align="center">

# 🏥 SiKN Puskesmas Mapane

### Sistem Informasi Kesehatan & Rekapitulasi Data Puskesmas Mapane

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue](https://img.shields.io/badge/Vue-3%20%2B%20Inertia.js-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org/)
[![MySQL](https://img.shields.io/badge/Database-MySQL%20%2F%20SQLite-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)

*Full-stack healthcare data management, analytics dashboard, multi-file/multi-sheet Excel import, and automated reporting platform built for Puskesmas Mapane.*

</div>

---

## ✨ Key Features

- **Analytics Dashboard & Yearly Recap** — Interactive visual summaries and yearly health rekapitulasi views (`/dashboard`, `/rekap/yearly`).
- **Smart Excel Import (Single, Batch & Multi-Sheet)** — Preview sheets, switch worksheets before saving, and batch-import multiple `.xlsx` workbooks safely via `ImportController`.
- **Full CRUD & Bulk Operations** — Manage health rekapitulasi records with validation, editing, and bulk deletion.
- **Multi-Format Export & Print Preview** — Export data directly to **XLSX**, **CSV**, **PDF**, and formatted print previews (`/print/preview`).
- **Admin User Management & Backup/Restore** — Role-protected user creation/password reset plus one-click database backup export & restore (`/backup`).

---

## 🚀 Local Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Migrate database & seed
php artisan migrate --seed

# 4. Run dev servers
php artisan serve
npm run dev
```
