# Technosrc Web — Astro + WordPress Headless Monorepo

ระบบเว็บไซต์องค์กรประสิทธิภาพสูงและความปลอดภัยสูงสุด ผสานพลังระหว่าง **Astro (Static Output)**, **TypeScript Strict**, **Tailwind CSS v4** และ **WordPress Headless CMS** ผ่านสถาปัตยกรรม Monorepo

---

## 🌟 จุดเด่นของระบบ

- **100% Static HTML on Production:** หน้าบ้านเป็นไฟล์ Static ล้วน รองรับการวางบน Shared Hosting ทั่วไปโดยไม่ต้องติดตั้ง Node.js runtime
- **WordPress Local via Docker:** มี Environment จำลองสำหรับ WordPress, MariaDB และ phpMyAdmin พร้อมใช้งานได้ทันที
- **Custom Modular Plugin (`project-core`):** รวมการจัดการ Custom Post Types (`banner`), Custom Fields (SEO, แบนเนอร์), Custom REST API, Site Settings และ Webhook อัตโนมัติ โดยไม่แตะต้อง WordPress Core
- **Automated Rebuild & Deploy:** เมื่อผู้ดูแลเผยแพร่/แก้ไขข่าวหรือแบนเนอร์ WordPress จะส่ง Webhook เข้ารหัส HMAC-SHA256 ไปสั่ง GitHub Actions เพื่อ Build และ Deploy ขึ้นโฮสติ้งอัตโนมัติ
- **Local Dev Mock Fallback:** หากยังไม่ได้เปิด Docker หรือ WordPress ระบบ Frontend จะสลับไปใช้ Mock Data ให้โดยอัตโนมัติ ช่วยให้พัฒนา UI ได้อย่างต่อเนื่องโดยไม่สะดุด

---

## 📁 โครงสร้างโปรเจกต์ (Monorepo)

```text
new-technosrc-web/
├── frontend/                          # Astro Static Frontend
│   ├── public/                        # Static assets (favicon, robots)
│   ├── src/
│   │   ├── components/                # Header, Footer, NewsCard, SeoHead
│   │   ├── layouts/                   # BaseLayout
│   │   ├── lib/                       # wordpress.ts API Client, sanitize.ts
│   │   ├── types/                     # TypeScript Interfaces
│   │   ├── styles/                    # Tailwind v4 global.css
│   │   └── pages/                     # Static routes & dynamic news [slug]
│   ├── astro.config.mjs               # Static build, Sitemap, Tailwind Vite
│   ├── package.json
│   └── tsconfig.json                  # Strict TypeScript
├── wordpress/                         # WordPress Source & Custom Plugins
│   └── wp-content/
│       ├── plugins/
│       │   └── project-core/          # Custom Core Plugin
│       │       ├── project-core.php
│       │       └── includes/
│       │           ├── class-post-types.php
│       │           ├── class-custom-fields.php
│       │           ├── class-site-settings.php
│       │           ├── class-webhook-manager.php
│       │           ├── class-rest-api.php
│       │           └── class-logger.php
│       └── themes/
├── .github/
│   └── workflows/
│       └── deploy.yml                 # GitHub Actions Build & Deploy
├── docker-compose.yml                 # WordPress + MariaDB + phpMyAdmin
├── .env.example                       # Root environment variables
├── .gitignore                         # Exclude uploads, node_modules, secrets
├── spec.md                            # Functional & Technical Specs
├── design.md                          # Architecture & Database Design
├── DEPLOYMENT.md                      # Production Shared Hosting Guide
├── README.md                          # Documentation (this file)
└── package.json                       # Monorepo Scripts
```

---

## 🚀 เริ่มต้นใช้งานในเครื่อง Local

### 1. ความต้องการของระบบ
- Node.js `20+` หรือ `22+`
- npm `10+`
- Docker Desktop (สำหรับรัน WordPress และ MariaDB Local)

### 2. ตั้งค่า Environment Variables
คัดลอกไฟล์ `.env.example` ไปเป็น `.env`:
```bash
cp .env.example .env
```

ค่าเริ่มต้นใน `.env`:
```dotenv
PUBLIC_SITE_URL=http://localhost:4321
WP_API_BASE_URL=http://localhost:8080/wp-json/wp/v2
WP_LOCAL_PORT=8080
PMA_LOCAL_PORT=8081
DB_PORT=3306
DB_NAME=wordpress
DB_USER=wp_user
DB_PASSWORD=wp_password_secure
DB_ROOT_PASSWORD=root_password_secure
```

### 3. รัน WordPress Local Stack (Docker Compose)
```bash
# เริ่มต้น Containers ในพื้นหลัง
npm run docker:up

# ดูสถานะ Logs ของ WordPress และ MariaDB
npm run docker:logs

# หยุดการทำงาน
npm run docker:down

# รีเซ็ตฐานข้อมูลและเริ่มต้นใหม่
npm run docker:reset
```

เมื่อสั่ง `npm run docker:up` แล้ว จะสามารถเข้าใช้งานได้ที่:
- **WordPress Admin:** [http://localhost:8080/wp-admin](http://localhost:8080/wp-admin)
- **phpMyAdmin:** [http://localhost:8081](http://localhost:8081)
- **REST API Health:** [http://localhost:8080/wp-json/project-core/v1/health](http://localhost:8080/wp-json/project-core/v1/health)

*(หมายเหตุ: เมื่อเข้า WordPress ครั้งแรก ให้ทำการ Install WordPress ตามขั้นตอน แล้วไปที่เมนู **Plugins** เพื่อกด **Activate** ปลั๊กอิน `Project Core`)*

### 4. รัน Astro Frontend
```bash
# รัน Astro Dev Server
npm run dev
```
เปิดบราวเซอร์ที่ [http://localhost:4321](http://localhost:4321)

---

## 🛠️ คำสั่งที่ใช้บ่อย (Commands Cheat Sheet)

| คำสั่ง | รายละเอียด |
|---|---|
| `npm run dev` | รัน Astro Frontend สำหรับพัฒนา (Local Dev) |
| `npm run build` | ทำการ Build Static HTML/CSS/JS ไปยัง `frontend/dist/` |
| `npm run check` | ตรวจสอบ TypeScript Types และ Astro Diagnostics |
| `npm run preview` | เปิดดูผลลัพธ์ Static Build ผ่าน Local Web Server |
| `npm run docker:up` | สั่งเปิดระบบ WordPress, MariaDB และ phpMyAdmin |
| `npm run docker:down` | สั่งหยุดระบบ Docker Containers |
| `npm run docker:logs` | เปิดดู Live Logs ของระบบ Docker |
| `npm run docker:reset`| ล้าง Volume และเริ่มต้นฐานข้อมูล WordPress ใหม่ |

---

## 🧩 การทำงานของปลั๊กอิน `project-core`

ปลั๊กอินถูกออกแบบเป็น Modular Architecture อยู่ใน `wordpress/wp-content/plugins/project-core/`:
- **`class-post-types.php`**: ลงทะเบียน Custom Post Type `banner` และ Taxonomy `banner_position`
- **`class-custom-fields.php`**: เพิ่มช่องใส่ Target URL, รูปภาพ Mobile และสถานะเปิด/ปิด สำหรับแบนเนอร์ รวมถึงช่องใส่ SEO Title, Description, OG Image สำหรับข่าวสาร
- **`class-site-settings.php`**: หน้าตั้งค่าใน Admin สำหรับจัดการข้อมูลติดต่อ, โลโก้, Social Links และการเชื่อมต่อ Webhook
- **`class-rest-api.php`**: เสริม Endpoint `/wp-json/project-core/v1/banners` และ `/wp-json/project-core/v1/site-settings`
- **`class-webhook-manager.php`**: ดักจับ Event เมื่อมีการ Publish/Update/Delete ข่าวและแบนเนอร์ พร้อมระบบ Debounce 30 วินาที และเข้ารหัส HMAC-SHA256
- **`class-logger.php`**: บันทึก Log การทำงานและการยิง Webhook สำหรับ Debugging

---

## 📖 เอกสารอ้างอิงเพิ่มเติม

- [spec.md](spec.md) — ข้อกำหนดความต้องการเชิงลึกและเกณฑ์การทดสอบระบบ
- [design.md](design.md) — สถาปัตยกรรมระบบ, โครงสร้างฐานข้อมูล, Sequence Diagram และ REST API Specs
- [DEPLOYMENT.md](DEPLOYMENT.md) — ขั้นตอนการ Deploy เว็บไซต์และ WordPress ไปยัง Shared Hosting
