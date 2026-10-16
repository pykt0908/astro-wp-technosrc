# Technosrc Web — Astro + WordPress Headless CMS Starter

เว็บไซต์บริษัทที่สร้างด้วย **Astro (Static Output)**, **TypeScript (Strict)**, **Tailwind CSS v4** และเชื่อมต่อกับ **WordPress Headless CMS** ผ่าน REST API ในขั้นตอนการ Build (SSG) หน้าเว็บที่เผยแพร่เป็น Static HTML/CSS/JS ทั้งหมด สามารถนำไปวางบน Shared Hosting (cPanel / DirectAdmin / Apache / Nginx) ได้โดยไม่ต้องติดตั้ง Node.js runtime

---

## 🏗️ Architecture

```text
Editor -> WordPress (/cms/wp-admin)
                 |
                 | REST API (GET /wp-json/wp/v2/posts?status=publish&_embed=1)
                 v
GitHub Actions -> Astro SSG build -> dist/ -> SFTP -> Shared Hosting public_html/
       ^
       | repository_dispatch (authenticated)
       |
WordPress publish/update/delete hook -> secure webhook relay -> GitHub API
```

---

## 📁 File Structure

```text
new-technosrc-web/
├── .github/workflows/deploy.yml   # GitHub Actions Build & Deploy pipeline
├── public/
│   ├── favicon.svg               # SVG brand icon
│   └── robots.txt                # Search engine crawler directives
├── src/
│   ├── components/
│   │   ├── Header.astro          # Responsive navigation & mobile toggle
│   │   ├── Footer.astro          # Brand footer with links
│   │   ├── NewsCard.astro        # News thumbnail, meta, and fallback image
│   │   └── SeoHead.astro         # OG tags, Twitter cards, JSON-LD Schema
│   ├── layouts/
│   │   └── BaseLayout.astro      # Master layout
│   ├── lib/
│   │   ├── wordpress.ts          # Typed REST API client (pagination, timeout)
│   │   └── sanitize.ts           # HTML sanitization against XSS
│   ├── types/
│   │   └── wordpress.ts          # Strict TypeScript interfaces
│   ├── styles/
│   │   └── global.css            # Tailwind v4 import & custom styling
│   └── pages/
│       ├── index.astro           # Homepage (Hero, Stats, Featured News, CTA)
│       ├── about.astro           # About Us
│       ├── services.astro        # Services
│       ├── contact.astro         # Contact Us & Form
│       ├── 404.astro             # Custom 404 page
│       └── news/
│           ├── index.astro       # News listing (page 1)
│           ├── page/[page].astro # Paginated news listing (page 2+)
│           └── [slug].astro      # News detail with getStaticPaths
├── .env.example                  # Environment variables template
├── .gitignore
├── astro.config.mjs              # Astro static output, Vite Tailwind, Sitemap
├── package.json
├── tsconfig.json                 # TypeScript strict mode
└── README.md
```

---

## 🚀 Getting Started

### 1. Prerequisites
- Node.js `20+` or `22+`
- npm `10+`

### 2. Environment Setup
คัดลอกไฟล์ `.env.example` ไปเป็น `.env`:
```bash
cp .env.example .env
```

กำหนดค่า:
```dotenv
PUBLIC_SITE_URL=https://example.com
WP_API_BASE_URL=https://example.com/cms/wp-json/wp/v2
```

*(หมายเหตุ: ในระหว่างการพัฒนาบนเครื่อง Local หากยังไม่ได้เชื่อมต่อ WordPress จริง ระบบจะมี Mock Data เพื่อให้สามารถ Preview หน้าเว็บและทดสอบ UI ได้ทันที)*

### 3. Development Server
```bash
npm run dev
```
เปิดบราวเซอร์ที่ [http://localhost:4321](http://localhost:4321)

### 4. Build Production (Static SSG)
```bash
npm run build
```
ผลลัพธ์ไฟล์ Static HTML/CSS/JS ทั้งหมดจะอยู่ในโฟลเดอร์ `dist/`

### 5. Type Check & Validation
```bash
npm run check
```

---

## 🔒 Security & Quality Highlights

1. **Static HTML 100% On Production:** ไม่มี Node.js หรือ PHP รันในส่วนของ Frontend จึงป้องกัน Web Application Vulnerabilities ได้อย่างสมบูรณ์
2. **HTML Sanitization:** ข้อมูล HTML จาก WordPress ถูกกรองผ่าน `sanitize-html` ป้องกัน XSS ก่อนนำไป Render
3. **No Secrets in Frontend:** ไม่มี Token หรือ Credential ฝังอยู่ใน Source Code หรือ Browser Bundle
4. **WCAG & SEO:** มี Canonical URL, Semantic Tags, Open Graph, Twitter Cards, Organization JSON-LD และ NewsArticle JSON-LD

---

## ⚙️ CI/CD & Deployment (GitHub Actions)

ดูรายละเอียดไฟล์ `.github/workflows/deploy.yml`:
- Trigger เมื่อมี Commit เข้าสู่ `main`, กดสั่งมือถือ (`workflow_dispatch`), หรือเมื่อมี Event `wordpress_content_changed` จาก Webhook Relay
- Build static output และเก็บเป็น Artifact
- สำหรับการส่งไฟล์ขึ้น SFTP ให้เพิ่ม step SFTP upload หลังจากตั้งค่า Secret ใน GitHub Repository
