# DEPLOYMENT — Production Deployment Guide for Shared Hosting

คู่มือการติดตั้งและ Deploy เว็บไซต์ Astro Static Frontend ร่วมกับ WordPress Headless CMS บน Shared Hosting (cPanel / DirectAdmin / Apache / Nginx) โดยไม่ต้องติดตั้ง Node.js บน Server ปลายทาง

---

## 1. Production Architecture Overview

- **Public Website:** `https://example.com/` (Static HTML/CSS/JS จาก `frontend/dist/`)
- **WordPress Admin:** `https://example.com/cms/` (หรือ `https://cms.example.com`)
- **REST API:** `https://example.com/cms/wp-json/wp/v2/`
- **Build Server:** GitHub Actions (Node.js 22 บน Ubuntu Runner)
- **Deployment Protocol:** SFTP (หรือ FTPS ที่เข้ารหัส TLS)

---

## 2. Shared Hosting Setup

### 2.1 ติดตั้ง WordPress บน Hosting
1. ติดตั้ง WordPress ผ่าน Softaculous / File Manager ที่ไดเรกทอรี `public_html/cms/` (หรือ Root Directory ของ Subdomain `cms.example.com`)
2. ตั้งค่า WordPress Site URL และ Home URL:
   - Site Address (URL): `https://example.com/cms`
   - WordPress Address (URL): `https://example.com/cms`
3. ตั้งค่า Permalinks ใน WordPress Admin (`Settings > Permalinks`): เลือก **Post name** (`/%postname%/`)
4. คัดลอกโฟลเดอร์ Plugin `wordpress/wp-content/plugins/project-core` ไปยัง Server และกด **Activate** ในเมนู Plugins

### 2.2 ป้องกันไดเรกทอรี `/cms` ใน `.htaccess`
ตรวจสอบให้แน่ใจว่าไฟล์ `.htaccess` ที่ Root ของ Hosting ไม่เขียนทับการทำงานของ WordPress:

```apache
# Root public_html/.htaccess
RewriteEngine On

# ยกเว้นการ Rewrite สำหรับไดเรกทอรี WordPress
RewriteRule ^cms/ - [L]

# Static HTML fallback และ Trailing slash handling
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME}/index.html -f
RewriteRule ^(.*)/$ $1/index.html [L]

# Custom 404
ErrorDocument 404 /404.html
```

---

## 3. GitHub Actions Configuration

### 3.1 Repository Variables
ไปที่ **GitHub Repository > Settings > Secrets and variables > Actions > Variables**:
- `PUBLIC_SITE_URL`: `https://example.com`
- `WP_API_BASE_URL`: `https://example.com/cms/wp-json/wp/v2`

### 3.2 Repository Secrets
ไปที่ **GitHub Repository > Settings > Secrets and variables > Actions > Secrets**:
- `SFTP_HOST`: IP Address หรือ Domain ของ Hosting (เช่น `sftp.example.com`)
- `SFTP_PORT`: พอร์ต SSH/SFTP (ปกติ `22` หรือพอร์ตที่ Hosting กำหนด)
- `SFTP_USERNAME`: ชื่อผู้ใช้ SFTP
- `SFTP_PRIVATE_KEY`: SSH Private Key (แนะนำแบบ ed25519) หรือ `SFTP_PASSWORD`
- `SFTP_TARGET_DIR`: ไดเรกทอรีปลายทาง (เช่น `/home/username/public_html`)

---

## 4. GitHub Actions Deployment Step

เพิ่มขั้นตอน SFTP Sync ใน `.github/workflows/deploy.yml` ต่อจาก `npm run build`:

```yaml
      - name: Deploy to Shared Hosting via SFTP
        uses: SamKirkland/FTP-Deploy-Action@v4.3.5
        with:
          server: ${{ secrets.SFTP_HOST }}
          port: ${{ secrets.SFTP_PORT || 22 }}
          username: ${{ secrets.SFTP_USERNAME }}
          password: ${{ secrets.SFTP_PASSWORD }}
          protocol: sftp
          local-dir: frontend/dist/
          server-dir: ${{ secrets.SFTP_TARGET_DIR }}/
          # สำคัญ: ไม่ลบโฟลเดอร์ /cms, uploads, หรือไฟล์ .htaccess
          exclude: |
            **/cms/**
            **/.htaccess
            **/wp-content/**
            **/wp-admin/**
            **/wp-includes/**
            **/.well-known/**
```

---

## 5. Webhook Relay Setup (Automatic Rebuild)

เพื่อความปลอดภัย ห้ามนำ GitHub Personal Access Token (PAT) ไปใส่ไว้ใน WordPress โดยตรง ให้ใช้ Webhook Relay คั่นกลาง:

### ตัวอย่าง Cloudflare Worker Relay:
```javascript
export default {
  async fetch(request, env) {
    if (request.method !== 'POST') return new Response('Method Not Allowed', { status: 405 });

    const signature = request.headers.get('X-Project-Signature');
    const bodyText = await request.text();

    // 1. ตรวจสอบ HMAC Signature
    const encoder = new TextEncoder();
    const key = await crypto.subtle.importKey(
      'raw',
      encoder.encode(env.WEBHOOK_SECRET),
      { name: 'HMAC', hash: 'SHA-256' },
      false,
      ['verify']
    );
    const expectedSig = 'sha256=' + await hmacHex(key, encoder.encode(bodyText));
    if (signature !== expectedSig) {
      return new Response('Unauthorized Signature', { status: 401 });
    }

    // 2. เรียก GitHub API
    const ghRes = await fetch(`https://api.github.com/repos/${env.GITHUB_REPO}/dispatches`, {
      method: 'POST',
      headers: {
        'Accept': 'application/vnd.github.v3+json',
        'Authorization': `Bearer ${env.GITHUB_PAT}`,
        'User-Agent': 'Technosrc-Relay',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ event_type: 'wordpress_content_changed' })
    });

    return new Response('Dispatched to GitHub', { status: ghRes.status });
  }
};
```

---

## 6. Checklist ก่อนเปิดใช้งานจริง (Launch Checklist)

- [ ] สำรองฐานข้อมูล WordPress และไฟล์ทั้งหมดบน Hosting ไว้ล่วงหน้า
- [ ] ทดสอบสร้าง/แก้ไขข่าวใน WordPress แล้วตรวจสอบว่า Webhook ยิงไปที่ Relay สำเร็จ
- [ ] ตรวจสอบว่า GitHub Actions Build สำเร็จ และไฟล์ Static ถูกอัปโหลดไปที่ `public_html/`
- [ ] ทดสอบเข้าหน้าแรก `https://example.com/` และหน้าบทความ `https://example.com/news/slug/`
- [ ] ทดสอบเข้าหน้า WordPress Admin `https://example.com/cms/wp-admin` ว่าไม่ได้รับผลกระทบ
- [ ] ทดสอบเปิดหน้า 404 เมื่อเรียก URL ที่ไม่มีอยู่จริง
- [ ] ตรวจสอบความถูกต้องของ Sitemap ที่ `https://example.com/sitemap-index.xml`
- [ ] ตรวจสอบ Google Search Console และการแสดงผล Open Graph Card
