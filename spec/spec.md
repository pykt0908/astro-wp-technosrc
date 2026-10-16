# SPEC — Astro + WordPress Headless CMS Monorepo

Version: 2.0  
Target: Shared Hosting without Node.js runtime (Production) & Docker Compose (Local Dev)  
Development: Monorepo (Astro Frontend + WordPress Backend)  
Language: Thai content, UTF-8  

---

## 1. Project Goal & Overview
สร้างระบบเว็บไซต์องค์กรประสิทธิภาพสูงและความปลอดภัยสูงสุด โดยใช้:
- **Frontend:** Astro (Static Site Generation - SSG) สไตล์ Tailwind CSS v4 และ TypeScript Strict
- **Backend / CMS:** WordPress เป็น Headless CMS สำหรับจัดการข่าวสาร แบนเนอร์ หมวดหมู่ และการตั้งค่าเว็บไซต์
- **Local Development:** WordPress และ MariaDB ทำงานผ่าน Docker Compose พร้อม phpMyAdmin
- **Custom Plugin (`project-core`):** ควบคุม Business Logic ทั้งหมด (Custom Post Types, Custom Fields, Custom REST API, Site Settings, Webhook Manager) โดยไม่แก้ไข WordPress Core
- **Production CI/CD:** GitHub Actions สั่ง Build Static HTML/CSS/JS แล้วส่งขึ้น Shared Hosting ผ่าน SFTP/FTPS โดยไม่มี Node.js รันบน Production Server

---

## 2. System Architecture

```text
[LOCAL DEVELOPMENT]
Developer Browser
  ├── Astro Dev Server  --> http://localhost:4321
  ├── WordPress Admin   --> http://localhost:8080/wp-admin
  └── phpMyAdmin        --> http://localhost:8081

Docker Network:
  ├── WordPress Container (PHP 8.2 + Apache) <--> Bind-mount: wordpress/wp-content/plugins/project-core
  └── MariaDB Container   (Persistent volume)

[PRODUCTION DEPLOYMENT]
Editor -> WordPress (/cms/wp-admin or https://cms.example.com)
                 │
                 │ Event: Publish / Update / Trash / Settings Change
                 ▼
          project-core Webhook (HMAC-SHA256 Signed + Debounced 30s)
                 │
                 │ HTTPS (Authenticated)
                 ▼
        Webhook Relay / GitHub App
                 │
                 │ POST /repos/{owner}/{repo}/dispatches (event_type: wordpress_content_changed)
                 ▼
        GitHub Actions Runner (Ubuntu Latest)
                 │
                 ├─ 1. Fetch REST API from WordPress Production
                 ├─ 2. Build Static HTML/CSS/JS (dist/)
                 ├─ 3. Upload dist/ via SFTP to public_html/
                 ▼
   Shared Hosting (Apache/Nginx Static Files - Fast & Secure, Zero Node.js)
```

---

## 3. Technology Stack

| Layer | Technology | Version / Details |
|---|---|---|
| **Frontend** | Astro | v5.x (Static output mode) |
| **Language** | TypeScript | Strict mode (`astro/tsconfigs/strict`) |
| **Styling** | Tailwind CSS | v4 via `@tailwindcss/vite` |
| **Sanitization** | sanitize-html | XSS prevention & safe HTML rendering |
| **Backend CMS** | WordPress | 6.7+ on PHP 8.2+ |
| **Database** | MariaDB / MySQL | 10.11 / 8.0 |
| **Local Dev** | Docker Compose | Containers for WP, MariaDB, phpMyAdmin |
| **CI/CD** | GitHub Actions | Ubuntu runner, Node 22, SFTP Deploy |
| **Hosting** | Shared Hosting | cPanel / DirectAdmin / Apache / Nginx |

---

## 4. Monorepo File Structure

```text
project-root/
├── frontend/                          # Astro Static Frontend
│   ├── public/
│   │   ├── favicon.svg
│   │   └── robots.txt
│   ├── src/
│   │   ├── components/
│   │   │   ├── Header.astro           # Responsive navigation
│   │   │   ├── Footer.astro           # Site footer & contact info
│   │   │   ├── NewsCard.astro         # News card with fallback image
│   │   └── SeoHead.astro              # SEO, OG, Twitter & JSON-LD
│   │   ├── layouts/
│   │   │   └── BaseLayout.astro
│   │   ├── lib/
│   │   │   ├── wordpress.ts           # Centralized API client
│   │   │   └── sanitize.ts            # XSS sanitizer
│   │   ├── types/
│   │   │   └── wordpress.ts           # Strict TypeScript models
│   │   ├── styles/
│   │   │   └── global.css             # Tailwind v4 import & custom prose
│   │   └── pages/
│   │       ├── index.astro            # Homepage (Hero, Banners, Stats, News)
│   │       ├── about.astro            # About us
│   │       ├── services.astro         # Services catalog
│   │       ├── contact.astro          # Contact form & details
│   │       ├── 404.astro              # Custom 404
│   │       └── news/
│   │           ├── index.astro        # News list (Page 1)
│   │           ├── page/[page].astro  # News pagination
│   │           └── [slug].astro       # News detail (getStaticPaths)
│   ├── astro.config.mjs
│   ├── package.json
│   ├── tsconfig.json
│   └── .env.example
├── wordpress/                         # WordPress Source & Custom Plugins
│   └── wp-content/
│       ├── plugins/
│       │   └── project-core/          # Custom Modular Plugin
│       │       ├── project-core.php
│       │       └── includes/
│       │           ├── class-project-core.php
│       │           ├── class-post-types.php
│       │           ├── class-custom-fields.php
│       │           ├── class-site-settings.php
│       │           ├── class-webhook-manager.php
│       │           ├── class-rest-api.php
│       │           └── class-logger.php
│       └── themes/
├── .github/
│   └── workflows/
│       └── deploy.yml                 # Build & Deploy workflow
├── docker-compose.yml                 # Local WordPress & MariaDB stack
├── .env.example                       # Root environment template
├── .gitignore                         # Excludes uploads, DB dumps, secrets
├── spec.md                            # Specifications (this file)
├── design.md                          # Architecture & API design
├── DEPLOYMENT.md                      # Shared hosting deployment guide
├── README.md                          # Getting started & setup guide
└── package.json                       # Monorepo convenience scripts
```

---

## 5. WordPress Core Plugin (`project-core`) Specification

### 5.1 Custom Post Type: `banner`
- **Fields:**
  - `title`: ชื่อแบนเนอร์
  - `featured_image`: รูปภาพ Desktop
  - `banner_mobile_image_url`: รูปภาพ Mobile
  - `banner_target_url`: URL ปลายทางเมื่อคลิก
  - `banner_is_active`: สถานะเปิด/ปิด (Boolean)
  - `menu_order`: ลำดับการแสดงผล

### 5.2 Posts (News & Articles) SEO Metadata
- Standard WP Post fields (`title`, `slug`, `content`, `excerpt`, `date`, `featured_media`, `categories`, `tags`)
- Custom Meta Fields:
  - `seo_meta_title`: กำหนดหัวข้อ SEO เฉพาะ
  - `seo_meta_description`: คำอธิบายย่อสำหรับ Search Engine
  - `seo_og_image_url`: รูปภาพ Open Graph เฉพาะ

### 5.3 Global Site Settings
- หน้าตั้งค่าใน WP Admin: `Project Settings > Site Settings`
- จัดเก็บในตาราง `wp_options`:
  - `site_name`, `site_tagline`, `logo_url`, `favicon_url`
  - ข้อมูลติดต่อ: `contact_email`, `contact_phone`, `contact_address`, `office_hours`
  - Social Links: `social_facebook`, `social_line`, `social_linkedin`, `social_github`
  - `footer_copyright`
  - Webhook: `webhook_url`, `webhook_secret`, `webhook_enabled`

### 5.4 Custom REST API Endpoints (`project-core/v1`)
- `GET /wp-json/project-core/v1/health`: ตรวจสอบสถานะ CMS
- `GET /wp-json/project-core/v1/site-settings`: ข้อมูลเว็บไซต์ (ปิดบัง Webhook secrets)
- `GET /wp-json/project-core/v1/banners`: รายการแบนเนอร์ที่เปิดใช้งาน เรียงตาม `menu_order`

### 5.5 Webhook Manager
- ดักจับ Action: `transition_post_status` (publish, update, trash, delete) สำหรับ `post` และ `banner`
- ดักจับการแก้ไข Taxonomies (categories, tags) และ Site Settings
- **Debounce:** กักเวลา 30 วินาทีด้วย Transient ป้องกันการสั่ง Build ซ้ำซ้อนขณะบันทึกถี่
- **Security:** คำนวณ HMAC-SHA256 signature ส่งผ่าน Header `X-Project-Signature: sha256=<hash>`

---

## 6. Acceptance Criteria
1. สามารถสั่ง `npm run docker:up` เพื่อเปิด WordPress (`:8080`) และ phpMyAdmin (`:8081`) บนเครื่อง Local
2. สามารถสั่ง `npm run dev` เพื่อเปิด Astro บนเครื่อง Local (`:4321`)
3. Astro ดึงข้อมูลจาก WordPress REST API ได้อย่างถูกต้อง และมี Fallback Mock Data เมื่อ CMS ปิดอยู่
4. `npm run check` และ `npm run build` ผ่าน 100% โดยไม่มี Type Errors
5. Static Build สร้างไฟล์ Static HTML/CSS/JS ครบถ้วนโดยไม่ต้องใช้ Node.js บน Server ปลายทาง
6. ไม่มีการ Commit Database Password, Secret Keys หรือ Uploaded Media ลง Git Repository
7. กระบวนการ Deploy ผ่าน GitHub Actions ไม่ลบหรือเขียนทับโฟลเดอร์ `/cms` หรือไฟล์ฐานข้อมูล
