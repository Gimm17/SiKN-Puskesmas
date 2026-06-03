# 📋 IMPLEMENTATION PLAN — SiKN Puskesmas Mapane
## Sistem Informasi Rekap Lahir Hidup & KN Lengkap

> **Versi:** 1.0.0  
> **Dibuat:** Juni 2026  
> **Untuk:** Puskesmas Mapane, Kabupaten Poso, Sulawesi Tengah  
> **Developer:** Gimora Digital

---

## 📌 DAFTAR ISI

1. [PRD — Product Requirements Document](#1-prd)
2. [Tech Stack](#2-tech-stack)
3. [Struktur Folder](#3-struktur-folder)
4. [ERD — Entity Relationship Diagram](#4-erd)
5. [Skema Database (Migration)](#5-skema-database)
6. [Logic & Alur Sistem](#6-logic--alur-sistem)
7. [Halaman & Fitur Detail](#7-halaman--fitur-detail)
8. [API Routes](#8-api-routes)
9. [Rencana Import Excel](#9-rencana-import-excel)
10. [Rencana Export (XLSX / CSV / PDF / Print)](#10-rencana-export)
11. [Roadmap & Estimasi Waktu](#11-roadmap--estimasi-waktu)

---

## 1. PRD

### 1.1 Latar Belakang

Ibu petugas Puskesmas Mapane saat ini melakukan rekap data bulanan secara manual — membuka file Excel `FORMAT INDIKATOR EDIT ANAK` satu per satu, menyalin data **Lahir Hidup (L/P)** dan **KN Lengkap (L/P)** dari 10 desa, lalu memasukkannya ke file `Format KN Lengkap` per-sheet bulan. Proses ini:
- Rawan salah ketik
- Memakan waktu saat file sudah menumpuk (12 file/tahun)
- Sulit menganalisis tren antar bulan/desa secara visual

### 1.2 Tujuan Produk

Membangun web app yang:
1. Mengotomasi proses rekap data dari file Excel sumber
2. Menyediakan dashboard analitik visual yang informatif
3. Memungkinkan input manual sebagai fallback
4. Menghasilkan output Excel yang formatnya identik dengan `Format KN Lengkap`
5. Mudah digunakan oleh petugas non-teknis

### 1.3 Pengguna

| Role | Akses |
|------|-------|
| **Admin** | Semua fitur + manajemen user |
| **Petugas** | Input, import, lihat dashboard, export |

### 1.4 Desa Tetap (Urutan Permanen)

```
No  Nama Desa
1   MAPANE
2   KASIGUNCU
3   TABALU
4   LANTOJAYA
5   TOINI
6   BETANIA
7   BEGA
8   MASAMBA
9   SAATU
10  PINEDAPA
```

### 1.5 Data yang Direkap

Dari file sumber (`FORMAT INDIKATOR EDIT ANAK`), diambil:

| Field | Keterangan |
|-------|-----------|
| `lahir_hidup_l` | Jumlah bayi lahir hidup Laki-laki |
| `lahir_hidup_p` | Jumlah bayi lahir hidup Perempuan |
| `kn_lengkap_l` | KN Lengkap (3x kunjungan) Laki-laki |
| `kn_lengkap_p` | KN Lengkap (3x kunjungan) Perempuan |

**Calculated (tidak disimpan, dihitung real-time):**
- `lahir_hidup_total` = L + P
- `kn_lengkap_total` = L + P
- `kn_pct_l` = KN L / LH L × 100%
- `kn_pct_p` = KN P / LH P × 100%
- `kn_pct_total` = KN Total / LH Total × 100%

### 1.6 Functional Requirements

| ID | Fitur | Prioritas |
|----|-------|-----------|
| F01 | Login / Logout dengan session auth | 🔴 Wajib |
| F02 | Dashboard analitik dengan grafik | 🔴 Wajib |
| F03 | Input manual per desa per bulan/tahun | 🔴 Wajib |
| F04 | Import dari file Excel (FORMAT INDIKATOR) | 🔴 Wajib |
| F05 | Pilih Nama File, Bulan, Tahun saat import | 🔴 Wajib |
| F06 | Edit data yang sudah tersimpan | 🔴 Wajib |
| F07 | Hapus data | 🟡 Penting |
| F08 | Export XLSX (format KN Lengkap) | 🔴 Wajib |
| F09 | Export CSV | 🟡 Penting |
| F10 | Export PDF | 🟡 Penting |
| F11 | Print langsung dari browser | 🟡 Penting |
| F12 | Grafik trend per desa | 🔴 Wajib |
| F13 | Grafik perbandingan antar desa | 🔴 Wajib |
| F14 | Filter data by bulan/tahun | 🔴 Wajib |
| F15 | Riwayat import (log) | 🟢 Nice to have |
| F16 | Manajemen user (Admin) | 🟡 Penting |

### 1.7 Non-Functional Requirements

- Responsive (desktop & tablet prioritas, mobile opsional)
- Load dashboard < 2 detik
- Support file Excel `.xlsx` ukuran hingga 5MB
- Password di-hash (bcrypt)
- Session timeout 8 jam

---

## 2. TECH STACK

### Backend
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| PHP | 8.3+ | Runtime |
| **Laravel** | **13** | Framework utama |
| Inertia.js | v2 | Server-side routing + client-side SPA |
| Laravel Sanctum | bawaan | Auth session-based |
| **PhpSpreadsheet** | ^2.2 | Read/write file XLSX (import & export) |
| DomPDF / Barryvdh | ^3.0 | Export PDF |
| MySQL | 8.0+ | Database utama |

### Frontend
| Teknologi | Versi | Kegunaan |
|-----------|-------|----------|
| **Vue 3** | **3.5+** | UI framework |
| Tailwind CSS | v4 | Styling |
| shadcn-vue | latest | Komponen UI |
| **ApexCharts** | ^3.x | Grafik dashboard |
| vue-apexcharts | ^1.x | Vue wrapper ApexCharts |
| Pinia | v2 | State management |
| Vite | 7 | Build tool |

### DevOps / Deploy
| Teknologi | Kegunaan |
|-----------|----------|
| cPanel + Shared Hosting | Deploy production |
| Composer | PHP dependency manager |
| npm | JS dependency manager |

### Library Tambahan
```json
{
  "require": {
    "phpoffice/phpspreadsheet": "^2.2",
    "barryvdh/laravel-dompdf": "^3.0",
    "laravel/tinker": "^2.9"
  },
  "require-dev": {
    "pestphp/pest": "^3.0"
  }
}
```

---

## 3. STRUKTUR FOLDER

```
sikn-puskesmas/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── RekapController.php        ← CRUD data rekap
│   │   │   ├── ImportController.php       ← Handle import Excel
│   │   │   ├── ExportController.php       ← Export XLSX/CSV/PDF
│   │   │   └── UserController.php         ← Manajemen user (admin)
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php
│   │   └── Requests/
│   │       ├── StoreRekapRequest.php
│   │       └── ImportRekapRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Desa.php
│   │   ├── RekapData.php
│   │   └── ImportLog.php
│   ├── Services/
│   │   ├── ExcelImportService.php         ← Logic parsing FORMAT INDIKATOR
│   │   ├── ExcelExportService.php         ← Logic generate FORMAT KN LENGKAP
│   │   └── DashboardService.php           ← Logic agregasi data grafik
│   └── Enums/
│       └── BulanEnum.php
├── database/
│   ├── migrations/
│   │   ├── 2026_01_01_create_desa_table.php
│   │   ├── 2026_01_02_create_rekap_data_table.php
│   │   └── 2026_01_03_create_import_logs_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── DesaSeeder.php                 ← Seed 10 desa permanen
│       └── UserSeeder.php                 ← Seed admin default
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   │   └── Login.vue
│   │   │   ├── Dashboard/
│   │   │   │   └── Index.vue              ← Dashboard utama
│   │   │   ├── Rekap/
│   │   │   │   ├── Index.vue              ← Tabel rekap + filter
│   │   │   │   ├── Create.vue             ← Form input manual
│   │   │   │   ├── Edit.vue               ← Form edit data
│   │   │   │   └── Import.vue             ← Form import Excel
│   │   │   └── User/
│   │   │       └── Index.vue              ← Manajemen user (admin)
│   │   ├── Components/
│   │   │   ├── Layout/
│   │   │   │   ├── AppLayout.vue          ← Layout utama + sidebar
│   │   │   │   ├── Sidebar.vue
│   │   │   │   └── Navbar.vue
│   │   │   ├── Dashboard/
│   │   │   │   ├── StatCard.vue           ← Kartu ringkasan angka
│   │   │   │   ├── ChartTrendBulanan.vue  ← Line chart trend
│   │   │   │   ├── ChartPerDesa.vue       ← Bar chart per desa
│   │   │   │   └── ChartCoverageKN.vue    ← Donut/gauge KN coverage %
│   │   │   ├── Rekap/
│   │   │   │   ├── RekapTable.vue
│   │   │   │   ├── RekapForm.vue
│   │   │   │   └── ImportForm.vue
│   │   │   └── UI/
│   │   │       ├── ConfirmModal.vue
│   │   │       ├── AlertBanner.vue
│   │   │       └── LoadingSpinner.vue
│   │   ├── Composables/
│   │   │   ├── useExport.js               ← Trigger export
│   │   │   └── useNotification.js
│   │   └── Stores/
│   │       └── useFilterStore.js          ← Pinia: state filter bulan/tahun
│   └── views/
│       ├── app.blade.php                  ← Root Inertia blade
│       └── pdf/
│           └── rekap-kn.blade.php         ← Template PDF export
├── routes/
│   └── web.php
├── storage/
│   └── app/
│       └── imports/                       ← Temp upload file Excel
├── templates/
│   └── format_kn_lengkap_template.xlsx    ← Template output Excel
└── tests/
    ├── Feature/
    │   ├── ImportTest.php
    │   └── RekapCRUDTest.php
    └── Unit/
        └── ExcelImportServiceTest.php
```

---

## 4. ERD

### Diagram (Text Representasi)

```
┌─────────────────┐        ┌──────────────────────────────────────────┐
│      users      │        │               rekap_data                 │
├─────────────────┤        ├──────────────────────────────────────────┤
│ id (PK)         │◄───┐   │ id (PK)                                  │
│ name            │    │   │ desa_id (FK → desa.id)                   │
│ email (unique)  │    │   │ bulan        INT (1–12)                  │
│ password        │    ├───│ tahun        INT (2020–2099)             │
│ role            │    │   │ lahir_hidup_l  INT DEFAULT 0             │
│ created_at      │    │   │ lahir_hidup_p  INT DEFAULT 0             │
│ updated_at      │    │   │ kn_lengkap_l   INT DEFAULT 0             │
└─────────────────┘    │   │ kn_lengkap_p   INT DEFAULT 0             │
                       │   │ created_by (FK → users.id)               │
                       │   │ updated_by (FK → users.id)               │
                       │   │ created_at                               │
                       │   │ updated_at                               │
                       │   └──────────────────────────────────────────┘
                       │             ▲
                       │             │ N:1
┌─────────────────┐    │   ┌─────────────────┐
│      desa       │    │   │                 │
├─────────────────┤    │   │  (desa memiliki │
│ id (PK)         │────┘   │  banyak rekap)  │
│ urutan  INT     │        │                 │
│ nama    VARCHAR │        └─────────────────┘
│ created_at      │
│ updated_at      │
└─────────────────┘

┌─────────────────────────────────────────┐
│              import_logs                │
├─────────────────────────────────────────┤
│ id (PK)                                 │
│ user_id   (FK → users.id)               │
│ filename  VARCHAR                       │
│ bulan     INT                           │
│ tahun     INT                           │
│ total_rows INT                          │
│ status    ENUM('success','partial','failed') │
│ catatan   TEXT (nullable)               │
│ created_at                              │
└─────────────────────────────────────────┘
     ▲ N:1
     │
  users.id
```

### Relasi

| Dari | Ke | Tipe | Keterangan |
|------|----|------|-----------|
| `rekap_data.desa_id` | `desa.id` | N:1 | Setiap rekap milik 1 desa |
| `rekap_data.created_by` | `users.id` | N:1 | Siapa yang menginput |
| `rekap_data.updated_by` | `users.id` | N:1 | Siapa yang terakhir edit |
| `import_logs.user_id` | `users.id` | N:1 | Siapa yang mengimport |

### Unique Constraint

```sql
UNIQUE (desa_id, bulan, tahun)
-- Satu desa hanya boleh punya 1 record per bulan per tahun
```

---

## 5. SKEMA DATABASE (MIGRATION)

### Tabel `desa`
```php
Schema::create('desa', function (Blueprint $table) {
    $table->id();
    $table->unsignedTinyInteger('urutan');    // 1-10, urutan tetap
    $table->string('nama', 100);             // MAPANE, KASIGUNCU, dst
    $table->timestamps();
});
```

**Seed Data:**
```php
$desa = [
    ['urutan' => 1,  'nama' => 'MAPANE'],
    ['urutan' => 2,  'nama' => 'KASIGUNCU'],
    ['urutan' => 3,  'nama' => 'TABALU'],
    ['urutan' => 4,  'nama' => 'LANTOJAYA'],
    ['urutan' => 5,  'nama' => 'TOINI'],
    ['urutan' => 6,  'nama' => 'BETANIA'],
    ['urutan' => 7,  'nama' => 'BEGA'],
    ['urutan' => 8,  'nama' => 'MASAMBA'],
    ['urutan' => 9,  'nama' => 'SAATU'],
    ['urutan' => 10, 'nama' => 'PINEDAPA'],
];
```

### Tabel `rekap_data`
```php
Schema::create('rekap_data', function (Blueprint $table) {
    $table->id();
    $table->foreignId('desa_id')->constrained('desa')->cascadeOnDelete();
    $table->unsignedTinyInteger('bulan');       // 1 = Januari, dst
    $table->unsignedSmallInteger('tahun');      // 2022, 2023, dst

    // Data inti
    $table->unsignedSmallInteger('lahir_hidup_l')->default(0);
    $table->unsignedSmallInteger('lahir_hidup_p')->default(0);
    $table->unsignedSmallInteger('kn_lengkap_l')->default(0);
    $table->unsignedSmallInteger('kn_lengkap_p')->default(0);

    // Audit
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->constrained('users');
    $table->timestamps();

    // Constraint: 1 desa 1 bulan 1 tahun
    $table->unique(['desa_id', 'bulan', 'tahun']);
    
    // Index untuk query
    $table->index(['bulan', 'tahun']);
    $table->index('desa_id');
});
```

### Tabel `import_logs`
```php
Schema::create('import_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->string('filename', 255);
    $table->unsignedTinyInteger('bulan');
    $table->unsignedSmallInteger('tahun');
    $table->unsignedTinyInteger('total_rows')->default(0);
    $table->enum('status', ['success', 'partial', 'failed'])->default('success');
    $table->text('catatan')->nullable();
    $table->timestamp('created_at')->useCurrent();
});
```

---

## 6. LOGIC & ALUR SISTEM

### 6.1 Alur Input Manual

```
User membuka halaman "Input Data"
    ↓
Pilih Bulan & Tahun
    ↓
Sistem load form 10 desa sesuai urutan permanen
    ↓
User isi kolom: LH-L, LH-P, KN-L, KN-P per desa
    ↓
Submit → RekapController@store
    ↓
Validasi: nilai >= 0, kn_l <= lh_l, kn_p <= lh_p
    ↓
Cek UNIQUE (desa_id, bulan, tahun):
    ├── Belum ada → INSERT
    └── Sudah ada → Tampilkan warning "Data sudah ada, gunakan Edit"
    ↓
Simpan ke rekap_data dengan created_by = auth()->id()
    ↓
Redirect ke halaman tabel dengan notifikasi sukses
```

### 6.2 Alur Import Excel

```
User membuka halaman "Import Excel"
    ↓
Form: [Pilih File .xlsx] [Dropdown Bulan] [Input Tahun]
    ↓
Submit → ImportController@store
    ↓
Validasi file: mime = xlsx, max 5MB
    ↓
ExcelImportService::parse($file, $bulan, $tahun)
    │
    ├── Load file dengan PhpSpreadsheet
    ├── Cari sheet "FORMAT INDIKATOR [TAHUN]" atau sheet pertama
    ├── Scan baris 1-10 (desa rows, skip header)
    ├── Untuk setiap baris:
    │   ├── Ambil nama desa (kolom B)
    │   ├── Match ke desa.id berdasarkan nama (case-insensitive)
    │   ├── Lahir Hidup L = kolom C (index 3)
    │   ├── Lahir Hidup P = kolom D (index 4)
    │   ├── KN Lengkap L  = kolom T (index 20) ← sesuai posisi di file
    │   └── KN Lengkap P  = kolom U (index 21)
    ↓
Bulk upsert ke rekap_data:
    └── RekapData::upsert([...], ['desa_id', 'bulan', 'tahun'], [...])
    ↓
Simpan ImportLog dengan status & catatan
    ↓
Return response: { berhasil: 10, gagal: 0, log_id: X }
```

### 6.3 Mapping Kolom FORMAT INDIKATOR → Kolom DB

Berdasarkan analisis file `FORMAT_INDIKATOR_EDIT__ANAK_2022_FEB.xlsx`:

| Data | Posisi di File | Kolom Excel | DB Field |
|------|---------------|-------------|----------|
| Nama Desa | Row 7-16, Kolom B | B | → match ke `desa.nama` |
| Lahir Hidup L | Kolom C (col index 3) | C | `lahir_hidup_l` |
| Lahir Hidup P | Kolom D (col index 4) | D | `lahir_hidup_p` |
| KN LENGKAP L | Kolom T (col index 20) | T | `kn_lengkap_l` |
| KN LENGKAP P | Kolom U (col index 21) | U | `kn_lengkap_p` |

> ⚠️ **Catatan Parsing:** Header baris di file FORMAT INDIKATOR berlapis (multi-row header). Parser akan mencari baris pertama yang kolom B-nya berisi nama desa yang dikenal (fuzzy match), lalu mulai ambil data dari sana.

### 6.4 Alur Export XLSX

```
User klik "Export XLSX" dengan filter bulan/tahun yang aktif
    ↓
ExportController@xlsx($bulan, $tahun)
    ↓
Ambil semua rekap_data WHERE bulan=$bulan AND tahun=$tahun
JOIN dengan desa ORDER BY desa.urutan ASC
    ↓
ExcelExportService::generateKNLengkap($data, $bulan, $tahun)
    │
    ├── Load template: storage/app/templates/format_kn_lengkap_template.xlsx
    ├── Isi header: BULAN, TAHUN
    ├── Untuk setiap desa (row 8-17):
    │   ├── NO, Puskesmas, Desa
    │   ├── LH L, LH P, LH Total
    │   ├── KN1 L, KN1 %, KN1 P, KN1 %, KN1 Total, KN1 %
    │   │    (KN1 diisi sama dengan KN Lengkap jika tidak ada data terpisah)
    │   ├── KN Lengkap L, %, P, %, Total, %
    │   └── Screening (kosong / 0 jika tidak ada data)
    ├── Isi baris JUMLAH (SUM formula)
    └── Set nama file: KN_Lengkap_{BULAN}_{TAHUN}.xlsx
    ↓
Return download response (stream)
```

### 6.5 Alur Dashboard Analitik

```
User membuka Dashboard
    ↓
DashboardController@index
    ↓
DashboardService::getSummary($tahun_aktif)
    │
    ├── Total LH tahun berjalan (sum all bulan, all desa)
    ├── Total KN Lengkap tahun berjalan
    ├── Coverage KN % = Total KN / Total LH × 100
    ├── Desa dengan coverage tertinggi
    └── Desa dengan coverage terendah
    ↓
DashboardService::getTrendBulanan($tahun)
    └── GROUP BY bulan → array 12 bulan [LH_total, KN_total, coverage%]
    ↓
DashboardService::getPerDesa($bulan, $tahun)
    └── GROUP BY desa → array per desa [LH, KN, coverage%]
    ↓
Pass ke Inertia → Dashboard/Index.vue
    ↓
ApexCharts render:
    ├── Line chart: trend LH & KN per bulan
    ├── Bar chart: perbandingan antar desa
    ├── Donut chart: coverage KN % keseluruhan
    └── Stat cards: angka ringkasan
```

---

## 7. HALAMAN & FITUR DETAIL

### 7.1 Halaman Login (`/login`)
- Form: Email + Password
- Tombol: "Masuk"
- Validasi sisi server + sisi client
- Auth: Laravel Session (Sanctum)

### 7.2 Dashboard (`/dashboard`)

**Stat Cards (row atas):**
| Card | Isi |
|------|-----|
| Total Lahir Hidup | Sum L+P semua desa bulan ini |
| Total KN Lengkap | Sum L+P semua desa bulan ini |
| Coverage KN % | KN / LH × 100% |
| Desa Tertinggi | Nama desa + % coverage |

**Grafik:**
1. **Line Chart — Trend Bulanan** (filter tahun): LH vs KN per bulan Jan–Des
2. **Bar Chart — Per Desa** (filter bulan+tahun): Perbandingan LH & KN per 10 desa
3. **Grouped Bar — L vs P** (filter bulan+tahun): Perbandingan gender
4. **Donut — Coverage %**: Visual cepat seberapa besar bayi yang ter-cover KN Lengkap

**Filter:** Dropdown Bulan + Tahun (default: bulan+tahun sekarang)

### 7.3 Tabel Rekap (`/rekap`)

- Filter: Bulan + Tahun
- Kolom: No, Desa, LH-L, LH-P, LH-Total, KN-L, KN-P, KN-Total, KN%, Aksi
- Aksi per baris: ✏️ Edit | 🗑️ Hapus
- Tombol atas: [+ Input Manual] [📥 Import Excel] [📤 Export ▼]
- Export dropdown: XLSX | CSV | PDF | 🖨️ Print

### 7.4 Form Input Manual (`/rekap/create`)

- Dropdown: Bulan + Tahun
- Table form: 10 baris (desa sudah otomatis sesuai urutan)
  - Kolom input: `LH L` | `LH P` | `KN L` | `KN P`
  - Kolom computed (readonly): `LH Total` | `KN Total` | `KN %`
- Tombol: [Simpan] [Batal]
- Validasi real-time: KN tidak boleh > LH

### 7.5 Form Import Excel (`/rekap/import`)

**Step 1 — Upload:**
```
┌────────────────────────────────────────┐
│  📂 Upload File Excel                  │
│  ┌──────────────────────────────────┐  │
│  │  Drop file .xlsx di sini atau    │  │
│  │  [Pilih File]                    │  │
│  └──────────────────────────────────┘  │
│                                        │
│  Bulan:  [▼ Pilih Bulan]              │
│  Tahun:  [2022        ]               │
│                                        │
│  [Preview Data]  [Import Sekarang]     │
└────────────────────────────────────────┘
```

**Step 2 — Preview (sebelum simpan):**
Tampilkan tabel preview 10 desa + data yang akan diimport. User konfirmasi.

**Step 3 — Hasil:**
Ringkasan: ✅ 10 baris berhasil, ⚠️ 0 baris gagal.

### 7.6 Form Edit (`/rekap/{id}/edit`)

- Tampilkan data existing
- Semua 4 field bisa diubah
- Timestamp `updated_by` & `updated_at` otomatis update

### 7.7 Manajemen User (`/users`) — Admin only

- Tabel: nama, email, role, created_at, Aksi
- Tambah user baru
- Reset password
- Nonaktifkan akun

---

## 8. API ROUTES

```php
// routes/web.php

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Rekap Data
    Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
    Route::get('/rekap/create', [RekapController::class, 'create'])->name('rekap.create');
    Route::post('/rekap', [RekapController::class, 'store'])->name('rekap.store');
    Route::get('/rekap/{rekap}/edit', [RekapController::class, 'edit'])->name('rekap.edit');
    Route::put('/rekap/{rekap}', [RekapController::class, 'update'])->name('rekap.update');
    Route::delete('/rekap/{rekap}', [RekapController::class, 'destroy'])->name('rekap.destroy');

    // Import Excel
    Route::get('/rekap/import', [ImportController::class, 'show'])->name('rekap.import');
    Route::post('/rekap/import/preview', [ImportController::class, 'preview'])->name('rekap.import.preview');
    Route::post('/rekap/import', [ImportController::class, 'store'])->name('rekap.import.store');

    // Export
    Route::get('/export/xlsx', [ExportController::class, 'xlsx'])->name('export.xlsx');
    Route::get('/export/csv', [ExportController::class, 'csv'])->name('export.csv');
    Route::get('/export/pdf', [ExportController::class, 'pdf'])->name('export.pdf');

    // Admin only
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('/users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
```

---

## 9. RENCANA IMPORT EXCEL

### ExcelImportService.php

```php
class ExcelImportService
{
    // Nama desa yang dikenali (lowercase untuk matching)
    private array $desaMap; // ['mapane' => 1, 'kasiguncu' => 2, ...]

    public function parse(UploadedFile $file, int $bulan, int $tahun): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $results = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = $this->getRowValues($row, $sheet);
            $desaNama = strtolower(trim($cells[1] ?? '')); // kolom B

            if (!isset($this->desaMap[$desaNama])) continue;

            $results[] = [
                'desa_id'        => $this->desaMap[$desaNama],
                'bulan'          => $bulan,
                'tahun'          => $tahun,
                'lahir_hidup_l'  => (int)($cells[2] ?? 0),   // kolom C
                'lahir_hidup_p'  => (int)($cells[3] ?? 0),   // kolom D
                'kn_lengkap_l'   => (int)($cells[19] ?? 0),  // kolom T
                'kn_lengkap_p'   => (int)($cells[20] ?? 0),  // kolom U
                'created_by'     => auth()->id(),
                'updated_by'     => auth()->id(),
            ];
        }

        return $results; // array siap upsert
    }
}
```

> **Strategi Kolom:** Karena header file FORMAT INDIKATOR berlapis (3–4 baris header), parser akan skip baris hingga menemukan kolom B yang berisi salah satu nama desa yang dikenal. Posisi kolom KN LENGKAP (L/P) dikonfirmasi pada **kolom T (index 19)** dan **kolom U (index 20)** berdasarkan analisis file aktual.

---

## 10. RENCANA EXPORT

### 10.1 Export XLSX

Template `format_kn_lengkap_template.xlsx` disimpan di `storage/app/templates/`.  
Format identik dengan file `Format__kn_lengkap_2022-_2023.xlsx` yang sudah ada.

Proses:
1. Load template
2. Isi data per sheet baru atau update sheet yang ada
3. Hitung otomatis: `LH Total = L + P`, `KN % = KN/LH`
4. Return sebagai download stream

### 10.2 Export CSV

Output flat format:
```
No,Desa,Bulan,Tahun,LH_L,LH_P,LH_Total,KN_L,KN_P,KN_Total,KN_Pct
1,MAPANE,1,2023,3,1,4,3,1,4,100%
...
```

### 10.3 Export PDF

Menggunakan **DomPDF** dengan Blade template `resources/views/pdf/rekap-kn.blade.php`.  
Layout: portrait A4, tabel 10 desa, header Puskesmas Mapane, tanda tangan.

### 10.4 Print Browser

Tambahkan tombol Print yang trigger `window.print()`.  
CSS `@media print` menyembunyikan sidebar, navbar, tombol — hanya tabel data yang tercetak.

---

## 11. ROADMAP & ESTIMASI WAKTU

### Sprint 1 — Fondasi (3–5 hari)
- [ ] Setup project Laravel 13 + Vue 3 + Inertia.js
- [ ] Konfigurasi Tailwind v4 + shadcn-vue
- [ ] Migrasi database + Seeder (desa, user admin)
- [ ] Auth (Login/Logout)
- [ ] Layout: AppLayout + Sidebar + Navbar

### Sprint 2 — Core Data (4–6 hari)
- [ ] Model + Controller RekapData (CRUD lengkap)
- [ ] Halaman tabel rekap dengan filter bulan/tahun
- [ ] Form input manual (create + edit)
- [ ] Validasi: KN ≤ LH, required fields

### Sprint 3 — Import Excel (3–4 hari)
- [ ] Integrasi PhpSpreadsheet
- [ ] ExcelImportService: parsing FORMAT INDIKATOR
- [ ] Halaman import dengan preview + konfirmasi
- [ ] ImportLog
- [ ] Testing edge case (file rusak, desa tidak match)

### Sprint 4 — Dashboard Analitik (4–5 hari)
- [ ] DashboardService: query agregasi
- [ ] Komponen StatCard
- [ ] Line chart trend bulanan (ApexCharts)
- [ ] Bar chart per desa
- [ ] Donut coverage KN %
- [ ] Filter interaktif

### Sprint 5 — Export (2–3 hari)
- [ ] ExcelExportService: generate FORMAT KN LENGKAP
- [ ] Export CSV
- [ ] Export PDF (DomPDF)
- [ ] Tombol Print (CSS print media)

### Sprint 6 — Polish & Deploy (2–3 hari)
- [ ] Manajemen user (Admin)
- [ ] Testing menyeluruh
- [ ] Optimasi query (N+1 prevention)
- [ ] Deploy ke cPanel

### Total Estimasi: **18–26 hari kerja** (~3–4 minggu)

---

## 🎨 DESAIN PALETTE

Terinspirasi dari konteks Puskesmas (kesehatan, kepercayaan, bersih):

| Nama | Hex | Kegunaan |
|------|-----|----------|
| Primary | `#0F6CBD` | Header, tombol utama |
| Primary Light | `#D3E5F5` | Background card, hover |
| Accent Green | `#107C10` | Status sukses, angka positif |
| Accent Orange | `#CA5010` | Peringatan, coverage rendah |
| Neutral 50 | `#F6F7F8` | Background halaman |
| Neutral 800 | `#201F1E` | Teks utama |

---

## 📝 CATATAN TEKNIS PENTING

1. **Unique Constraint**: Sistem akan **upsert** (update jika sudah ada) saat import, bukan error. User diberi tahu berapa baris yang di-update vs insert baru.

2. **Validasi Bisnis**: `kn_lengkap_l` tidak boleh melebihi `lahir_hidup_l`. Jika data Excel aneh (KN > LH), flag sebagai warning tapi tetap simpan apa adanya (karena bisa saja data sumber memang begitu).

3. **Kolom KN1 pada Export**: File `Format KN Lengkap` punya kolom KN1 (kunjungan 1x) terpisah dari KN Lengkap (3x). Karena file sumber tidak memisahkan KN1, kolom KN1 pada export akan diisi sama dengan KN Lengkap sebagai default, atau dikosongkan. **Bisa ditambah field KN1 nanti** sebagai enhancement.

4. **Multi-tahun**: Sistem mendukung data dari berbagai tahun. Filter tahun tersedia di semua halaman.

5. **Backup**: Rekomendasikan backup database MySQL mingguan via cPanel cron job.

---

*Dokumen ini adalah panduan implementasi lengkap untuk SiKN Puskesmas Mapane.*  
*Diperbarui sesuai kebutuhan selama development.*
