# SPEC — Astro + WordPress Headless CMS Starter

Version: 1.0  
Target: Shared hosting without Node.js runtime  
Development: Local machine + Cursor  
Language: Thai content, UTF-8

## 1. Project goal
สร้างเว็บไซต์บริษัทที่ใช้ Astro เป็น Frontend และ WordPress เป็น Headless CMS สำหรับข่าว/บทความ โดยหน้าเว็บที่เผยแพร่เป็น Static HTML/CSS/JS ทั้งหมด ผู้ดูแลเขียนข่าวใน WordPress แล้วระบบสั่ง GitHub Actions Build และ Deploy ใหม่อัตโนมัติ ไม่ต้องติดตั้ง Node.js บน Shared Hosting

## 2. Architecture

```text
Editor -> WordPress (/cms/wp-admin)
                 |
                 | REST API (public published content)
                 v
GitHub Actions -> Astro SSG build -> dist/ -> SFTP -> Shared Hosting public_html/
      ^
      | repository_dispatch (authenticated)
      |
WordPress publish/update/delete hook -> secure webhook relay -> GitHub API
```

**Security boundary:** ห้ามส่ง GitHub token จาก WordPress ไปยัง Browser หรือฝังไว้ใน Frontend. WordPress ใช้ HTTPS ติดต่อ webhook relay ที่เก็บ GitHub credential ฝั่ง Server หรือใช้ GitHub App ที่มีสิทธิ์จำกัด; relay ตรวจ HMAC signature, rate limit และตรวจชนิด event ก่อนเรียก GitHub repository_dispatch. ห้ามเปิด unauthenticated endpoint ที่สั่ง Build ได้โดยตรง

## 3. Technology stack

| Layer | Technology |
|---|---|
| Frontend | Astro (current stable), TypeScript strict |
| Styling | Tailwind CSS via official Astro-compatible integration |
| CMS | WordPress + native REST API |
| Rendering | Astro `output: 'static'` |
| CI/CD | GitHub Actions |
| Deployment | SFTP preferred; FTPS only when SFTP unavailable |
| Hosting | Apache/Nginx Shared Hosting with PHP/MySQL for WordPress |
| SEO | Static HTML, sitemap, canonical, Open Graph, JSON-LD |

Pin exact versions and commit lockfile at project creation; consult current Astro/Tailwind documentation for compatible installation commands. No Node runtime on production hosting.

## 4. Domain layout

Recommended:
- `https://example.com/` = Astro static frontend
- `https://example.com/cms/` = WordPress backend
- `https://example.com/cms/wp-json/wp/v2/` = REST API

Alternative: `https://cms.example.com` for WordPress. Configure correct WordPress Site URL/Home URL, HTTPS, REST API, CORS only if browser fetch is later enabled, and cache rules. Do not overwrite an existing WordPress root installation; create a backup and test on staging.

## 5. MVP scope

1. Home page: hero, company intro, featured news, contact CTA.
2. News listing `/news/`: newest first, pagination, cards, thumbnail, excerpt, date.
3. News detail `/news/[slug]/`: title, publish date, cover image, sanitized WordPress HTML, breadcrumbs, related posts.
4. Static pages: `/about/`, `/services/`, `/contact/`, `/404.html`.
5. Responsive navigation and footer.
6. SEO: title, meta description, canonical, Open Graph, sitemap, robots.txt, article structured data.
7. Build-time WordPress data fetching, including pagination through all published posts.
8. Automatic build and deployment on publish/update/trash/delete events affecting public content.
9. Manual GitHub Actions deployment fallback.

Out of scope for MVP: member login on Astro, ecommerce, live comments, search requiring a server, realtime dashboard, on-demand SSR.

## 6. WordPress configuration

- Install WordPress at `/cms` or CMS subdomain.
- Enable HTTPS and readable permalink structure.
- Use standard `post`, `category`, `tag`, `featured_media` and `page` content types initially.
- Published content accessible via `GET /wp-json/wp/v2/posts?status=publish&_embed=1`.
- Only expose public published data to Astro; drafts/private content excluded.
- Configure media image sizes and alt text.
- Ensure WP REST API not blocked by security plugins or WAF for GitHub runners.
- If editorial workflow requires preview of drafts, design separately with authenticated preview; not part of MVP.
- Set WordPress timezone to `Asia/Bangkok` if site operates in Thailand.

### Content mapping

| WordPress field | Astro model |
|---|---|
| `id` | `id: number` |
| `slug` | `slug: string` |
| `title.rendered` | `titleHtml: string` |
| `excerpt.rendered` | `excerptHtml: string` |
| `content.rendered` | `contentHtml: string` |
| `date` | `publishedAt: string` |
| `modified` | `modifiedAt: string` |
| `_embedded['wp:featuredmedia'][0].source_url` | `coverUrl?: string` |
| `categories` | `categoryIds: number[]` |

Render trusted WordPress HTML only after sanitization. Use plain text for HTML title in metadata and accessible headings. Never trust arbitrary HTML as safe by default.

## 7. Astro project initialization

```bash
npm create astro@latest company-web
cd company-web
npx astro add tailwind
npm run dev
```

Choose TypeScript strict and Git during setup. The official Tailwind integration/install procedure can vary by major version; follow the CLI prompts and current official documentation.

`astro.config.mjs` example:

```js
import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  site: process.env.PUBLIC_SITE_URL ?? 'https://example.com',
  output: 'static',
  integrations: [],
  vite: { plugins: [tailwindcss()] },
});
```

Use the config above for Tailwind CSS v4 with the Vite plugin; if the CLI installs another supported integration, retain the generated config instead of registering Tailwind twice. Include `@import 'tailwindcss';` in the global CSS imported by the shared layout.

`.env.example`:

```dotenv
PUBLIC_SITE_URL=https://example.com
WP_API_BASE_URL=https://example.com/cms/wp-json/wp/v2
```

`WP_API_BASE_URL` is a build-time server environment variable. Never put secrets in `PUBLIC_` variables. In CI set both variables as repository variables, and use secrets for deployment credentials.

## 8. Recommended file structure

```text
company-web/
├── .github/workflows/deploy.yml
├── public/
│   ├── favicon.svg
│   └── robots.txt
├── src/
│   ├── components/
│   │   ├── Header.astro
│   │   ├── Footer.astro
│   │   ├── NewsCard.astro
│   │   └── SeoHead.astro
│   ├── layouts/BaseLayout.astro
│   ├── lib/wordpress.ts
│   ├── lib/sanitize.ts
│   ├── types/wordpress.ts
│   ├── styles/global.css
│   └── pages/
│       ├── index.astro
│       ├── about.astro
│       ├── services.astro
│       ├── contact.astro
│       ├── news/
│       │   ├── index.astro
│       │   ├── page/[page].astro
│       │   └── [slug].astro
│       └── 404.astro
├── .env.example
├── .gitignore
├── astro.config.mjs
├── package.json
├── package-lock.json
└── README.md
```

Note: `news/page/[page].astro` and `news/[slug].astro` must implement `getStaticPaths()`; route collision should be tested. Prefer `/news/page/2/` for pagination.

## 9. Data-fetching contract

Implement `src/lib/wordpress.ts` with:

```ts
getPublishedPosts(page?: number, perPage?: number)
getAllPublishedPosts()
getPostBySlug(slug: string)
getCategories()
```

Requirements:
- Use `fetch` only during build for MVP.
- Set request timeout with `AbortSignal.timeout` where supported.
- Handle HTTP errors, WordPress API error JSON, invalid responses, empty lists and missing featured images.
- Read `X-WP-TotalPages` to fetch all pages; WordPress `per_page` maximum commonly 100.
- Keep deterministic ordering by publish date descending.
- Handle large collections by pagination and avoid excessive parallel requests.
- Fail CI build on CMS outage rather than deploying incomplete content; keep the existing live site intact.
- Use absolute WordPress media URLs, `loading="lazy"`, responsive dimensions and alt text.
- Generate one detail page per published slug via `getStaticPaths()`.

## 10. SEO requirements

- Unique title and description for every page.
- Canonical absolute URL based on `PUBLIC_SITE_URL`.
- Open Graph title, description, URL, and image.
- `Article` JSON-LD for news details; `Organization` JSON-LD on home page.
- Generate sitemap with official `@astrojs/sitemap` integration.
- Robots rules allow public pages, exclude internal CMS paths as appropriate.
- Use semantic HTML and accessible image alt text.
- Return real 404 page for missing static slugs (depends on hosting configuration).
- If slug changes, consider WordPress-managed redirect map exported at build time or hosting redirects.

## 11. Automatic rebuild workflow

### Trigger flow

1. Editor publishes, updates, trashes, deletes a post, or changes content relevant to published pages.
2. WordPress hook sends a signed event to a server-side webhook relay. Debounce/coalesce rapid edits (e.g. 30–60 seconds) to avoid repeated builds.
3. Relay validates signature and event, then calls GitHub REST API `POST /repos/{owner}/{repo}/dispatches` with `event_type: wordpress_content_changed`.
4. GitHub Actions runs `npm ci`, `npm run build`, validates `dist/`, and deploys over SFTP.
5. On failure, notify maintainers through GitHub Actions; do not delete the previous live site.

**Important:** WordPress webhooks and the secure relay are not built into Astro. Implement and test a small WordPress plugin + relay or select a maintained WordPress webhook integration. A direct GitHub PAT stored in a generic WordPress webhook plugin is discouraged; prefer a relay/GitHub App with minimal permissions.

### GitHub Actions starter (`.github/workflows/deploy.yml`)

```yaml
name: Build and Deploy Astro
on:
  push:
    branches: [main]
  workflow_dispatch:
  repository_dispatch:
    types: [wordpress_content_changed]

concurrency:
  group: production-deploy
  cancel-in-progress: false

jobs:
  build:
    runs-on: ubuntu-latest
    permissions:
      contents: read
    env:
      PUBLIC_SITE_URL: ${{ vars.PUBLIC_SITE_URL }}
      WP_API_BASE_URL: ${{ vars.WP_API_BASE_URL }}
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: npm
      - run: npm ci
      - run: npm run build
      - name: Validate output
        run: test -f dist/index.html
      - uses: actions/upload-artifact@v4
        with:
          name: astro-dist
          path: dist/
```

**This workflow intentionally builds but does not yet deploy.** Add an SFTP deployment step after confirming the provider's SSH/SFTP access, target directory, and safe deployment mechanism. Do not assume SFTP exists on every Shared Hosting plan. Prefer upload to a release directory and atomic symlink/swap if supported; otherwise synchronize carefully without deleting `/cms`, `.htaccess`, uploads or unrelated server files. Pin GitHub Actions to reviewed commit SHAs for production hardening.

### GitHub configuration

Repository variables:
- `PUBLIC_SITE_URL`
- `WP_API_BASE_URL`

Repository secrets (once deployment is configured):
- `SFTP_HOST`
- `SFTP_PORT`
- `SFTP_USERNAME`
- `SFTP_PRIVATE_KEY` or another provider-supported secure credential
- `SFTP_TARGET_DIR`

Store relay credentials in the relay's secret store, not in GitHub Actions public variables or Astro source. Ensure the webhook does not expose secrets in logs.

## 12. Hosting deployment constraints

- Production web root serves `dist/` static assets.
- WordPress resides in `/cms` or separate subdomain.
- Do not upload `node_modules`, `.env`, source code, Git metadata, or GitHub secrets.
- Check Apache rewrite rules and trailing-slash behavior; test direct reload of `/news/example-slug/`.
- Keep `/cms` excluded from any deployment deletion/sync operation.
- Configure TLS, compression, caching for hashed `_astro/` assets, and appropriate HTML cache lifetime.
- Confirm file permissions, PHP WordPress requirements and REST API availability.
- If hosting has FTP only, prefer upgrading to SFTP/FTPS; never deploy credentials over unencrypted FTP.

## 13. Quality and security

- TypeScript strict mode; no `any` for WordPress response models.
- Sanitize HTML content and avoid XSS; WordPress sanitization alone is not a blanket guarantee.
- Escape metadata and JSON-LD safely.
- Validate all external URLs used in links/images.
- Ensure links, menus, keyboard navigation, color contrast and mobile layout meet WCAG 2.2 AA goals.
- Never expose WordPress admin credentials or GitHub tokens in browser bundles.
- Use least-privilege tokens, webhook signature validation, replay protection and rate limiting.
- Protect staging from indexing.
- Back up WordPress database/media and existing web root before first deploy.

## 14. Development phases

### Phase 1 — Foundation
- Create Astro project, TypeScript, Tailwind, layouts, responsive header/footer.
- Set up environment configuration and Git repository.

### Phase 2 — CMS integration
- Install WordPress in `/cms` or subdomain.
- Implement typed API client and published news listing/detail pages.
- Implement pagination, missing image fallback and HTML sanitization.

### Phase 3 — SEO and quality
- Sitemap, robots, canonical, OG, JSON-LD, accessibility and responsive tests.

### Phase 4 — CI/CD
- GitHub Actions build pipeline, artifact verification, safe SFTP deploy.
- Implement secure WordPress event hook and relay; verify publish/update/delete rebuild.

### Phase 5 — Launch
- Backup, staging test, DNS/TLS verification, production deployment and rollback drill.

## 15. Acceptance criteria

- [ ] `npm run dev` works locally.
- [ ] `npm run build` produces `dist/index.html`, static news pages and CSS/JS assets.
- [ ] Production website runs without Node.js.
- [ ] WordPress admin can create and publish news.
- [ ] Published news appears in listing and detail after automated deployment.
- [ ] Updated and deleted news are reflected after automated deployment.
- [ ] Missing featured images do not break pages.
- [ ] Build failure does not overwrite the working live website.
- [ ] Deployment does not overwrite/delete WordPress `/cms`.
- [ ] Manual workflow trigger works.
- [ ] SEO tags and sitemap contain valid production URLs.
- [ ] Mobile/tablet/desktop and direct-link refresh tests pass.
- [ ] No secrets are exposed in the built `dist/`.

## 16. Cursor implementation prompt

> Implement this repository following `spec.md` phase by phase. Start with Astro static + TypeScript strict + Tailwind, then build the responsive layout and WordPress REST API client. Do not introduce SSR, a Node.js production server, or runtime API routes. Use build-time data fetching and static routes with `getStaticPaths()`. Keep CMS credentials and deployment secrets server-side. Create and test the GitHub Actions build workflow first; do not enable deployment or destructive sync until the hosting directory and SFTP access are verified. Explain each phase and run lint/typecheck/build tests before proceeding.

## 17. Decisions to confirm before production

1. Domain and WordPress location: `/cms` vs `cms.example.com`.
2. Shared Hosting provider and whether SFTP/SSH is available.
3. Deployment target directory and existing files that must never be touched.
4. Webhook relay hosting option (WordPress plugin + trusted relay / managed service).
5. Site branding, pages, navigation and design references.
6. Whether news updates must appear immediately or a short build delay is acceptable.
