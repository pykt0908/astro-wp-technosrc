import type {
  Post,
  Category,
  PaginatedPosts,
  WP_RawPost,
  WP_RawCategory,
  Banner,
  SiteSettings,
  CollegeEvent,
  CollegeEventCategory,
} from '../types/wordpress';
import { sanitizeWordPressHtml, stripHtml } from './sanitize';

// Base API endpoints
// Standard WP REST API: /wp-json/wp/v2
// Custom Project Core API: /wp-json/project-core/v1
const WP_API_BASE_URL =
  (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.WP_API_BASE_URL) ||
  process.env.WP_API_BASE_URL ||
  'http://localhost:8080/wp-json/wp/v2';
const PROJECT_CORE_API_URL = WP_API_BASE_URL.replace(/\/wp\/v2\/?$/, '/project-core/v1');
const FETCH_TIMEOUT_MS = 6000;
const IS_CI = (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.CI === 'true') || process.env.CI === 'true';

// In-memory cache to avoid repeated network calls during fast dev navigation
const apiCache = new Map<string, { data: any; expiresAt: number }>();
const CACHE_TTL_MS = 20000; // 20s TTL

function getFromCache<T>(key: string): T | null {
  const item = apiCache.get(key);
  if (item && item.expiresAt > Date.now()) {
    return item.data as T;
  }
  return null;
}

function setInCache<T>(key: string, data: T): void {
  apiCache.set(key, { data, expiresAt: Date.now() + CACHE_TTL_MS });
}

// Fallback Mock Data for Local Development
// Fallback Mock Data matching institution and user mockup
const LOCAL_MOCK_POSTS: Post[] = [
  {
    id: 22,
    slug: 'meeting-dean-september-24-2026',
    titleHtml: 'วันพฤหัสบดีที่ 24 กันยายน 2569 เวลา 15.30 น. ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ เป็นประธานการประชุม',
    titlePlain: 'วันพฤหัสบดีที่ 24 กันยายน 2569 เวลา 15.30 น. ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ เป็นประธานการประชุม',
    excerptHtml: '<p>วันพฤหัสบดีที่ 24 กันยายน 2569 เวลา 15.30 น. ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ เป็นประธานการประชุม ประธานสาขาวิชาและประธานหลักสูตรฯ เพื่อร่วมประชุมหารือและเตรียมความพร้อมในการเปิดภาคเรียน...</p>',
    excerptPlain: 'วันพฤหัสบดีที่ 24 กันยายน 2569 เวลา 15.30 น. ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ เป็นประธานการประชุม ประธานสาขาวิชาและประธานหลักสูตรฯ เพื่อร่วมประชุมหารือและเตรียมความพร้อมในการเปิดภาคเรียน...',
    contentHtml: '<p>วันพฤหัสบดีที่ 24 กันยายน 2569 เวลา 15.30 น. ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ เป็นประธานการประชุม ประธานสาขาวิชาและประธานหลักสูตรฯ เพื่อร่วมประชุมหารือและเตรียมความพร้อมในการเปิดภาคเรียน</p>',
    publishedAt: '2026-09-24T15:30:00+07:00',
    modifiedAt: '2026-09-24T15:30:00+07:00',
    coverUrl: 'https://images.unsplash.com/photo-1577495508048-b635879837f1?q=80&w=1200&auto=format&fit=crop',
    coverAlt: 'การประชุมวิชาการและการบริหารวิทยาลัย',
    categoryIds: [3],
    categoryNames: ['ข่าวประชาสัมพันธ์'],
    views: 36,
    galleryCount: 3,
    authorName: 'Admin',
    authorRole: 'ผู้ดูแลระบบ',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop',
    gallery: [
      { id: 1, url: 'https://images.unsplash.com/photo-1577495508048-b635879837f1?q=80&w=1200&auto=format&fit=crop' },
      { id: 2, url: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=1200&auto=format&fit=crop' },
      { id: 3, url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1200&auto=format&fit=crop' },
    ],
  },
  {
    id: 23,
    slug: 'chinese-language-study-tour-2026',
    titleHtml: 'CN ✈️ คณะครุศาสตร์ร่วมส่งนักศึกษาสาขาวิชาภาษาจีน เดินทางศึกษาทักษะรายวิชาภาษาจีน ณ สาร...',
    titlePlain: 'CN ✈️ คณะครุศาสตร์ร่วมส่งนักศึกษาสาขาวิชาภาษาจีน เดินทางศึกษาทักษะรายวิชาภาษาจีน ณ สาร...',
    excerptHtml: '<p>CN ✈️ คณะครุศาสตร์ร่วมส่งนักศึกษาสาขาวิชาภาษาจีน เดินทางศึกษาทักษะรายวิชาภาษาจีน ณ สาธารณรัฐประชาชนจีน เพื่อเพิ่มพูนทักษะทางวิชาการและเปิดโลกทัศน์...</p>',
    excerptPlain: 'CN ✈️ คณะครุศาสตร์ร่วมส่งนักศึกษาสาขาวิชาภาษาจีน เดินทางศึกษาทักษะรายวิชาภาษาจีน ณ สาธารณรัฐประชาชนจีน เพื่อเพิ่มพูนทักษะทางวิชาการและเปิดโลกทัศน์...',
    contentHtml: '<p>CN ✈️ คณะครุศาสตร์ร่วมส่งนักศึกษาสาขาวิชาภาษาจีน เดินทางศึกษาทักษะรายวิชาภาษาจีน ณ สาธารณรัฐประชาชนจีน</p>',
    publishedAt: '2026-09-24T10:00:00+07:00',
    modifiedAt: '2026-09-24T10:00:00+07:00',
    coverUrl: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=800&auto=format&fit=crop',
    coverAlt: 'นักศึกษาสาขาวิชาภาษาจีนเดินทางศึกษาดูงาน',
    categoryIds: [3],
    categoryNames: ['ข่าวประชาสัมพันธ์'],
    views: 11,
    galleryCount: 3,
    authorName: 'Admin',
    authorRole: 'ผู้ดูแลระบบ',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop',
    gallery: [
      { id: 4, url: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=1200&auto=format&fit=crop' },
      { id: 5, url: 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?q=80&w=1200&auto=format&fit=crop' },
    ],
  },
  {
    id: 24,
    slug: 'quality-assurance-assessment-2026',
    titleHtml: '📣 คณะครุศาสตร์ ต้อนรับคณะผู้ตรวจประเมินคุณภาพการศึกษาภายใน ระดับคณะ ตามเกณ...',
    titlePlain: '📣 คณะครุศาสตร์ ต้อนรับคณะผู้ตรวจประเมินคุณภาพการศึกษาภายใน ระดับคณะ ตามเกณ...',
    excerptHtml: '<p>📣 คณะครุศาสตร์ ต้อนรับคณะผู้ตรวจประเมินคุณภาพการศึกษาภายใน ระดับคณะ ตามเกณฑ์ EdPEx ประจำปีการศึกษา 2569 เพื่อพัฒนามาตรฐานสู่ความเป็นเลิศ...</p>',
    excerptPlain: '📣 คณะครุศาสตร์ ต้อนรับคณะผู้ตรวจประเมินคุณภาพการศึกษาภายใน ระดับคณะ ตามเกณฑ์ EdPEx ประจำปีการศึกษา 2569 เพื่อพัฒนามาตรฐานสู่ความเป็นเลิศ...',
    contentHtml: '<p>📣 คณะครุศาสตร์ ต้อนรับคณะผู้ตรวจประเมินคุณภาพการศึกษาภายใน ระดับคณะ ตามเกณฑ์ EdPEx ประจำปีการศึกษา 2569</p>',
    publishedAt: '2026-09-24T09:00:00+07:00',
    modifiedAt: '2026-09-24T09:00:00+07:00',
    coverUrl: 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?q=80&w=800&auto=format&fit=crop',
    coverAlt: 'คณะผู้ตรวจประเมินคุณภาพการศึกษาภายใน',
    categoryIds: [3],
    categoryNames: ['ข่าวประชาสัมพันธ์'],
    views: 10,
    galleryCount: 3,
    authorName: 'Admin',
    authorRole: 'ผู้ดูแลระบบ',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop',
    gallery: [
      { id: 6, url: 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?q=80&w=1200&auto=format&fit=crop' },
    ],
  },
  {
    id: 25,
    slug: 'faculty-tshirt-preorder-2026',
    titleHtml: 'เสื้อสวยๆ มาแล้วจ้า',
    titlePlain: 'เสื้อสวยๆ มาแล้วจ้า',
    excerptHtml: '<p>เสื้อสวยๆ มาแล้วจ้า ✅ เปิดรับ Pre-order เสื้อชวนครุศาสตร์ คืนถิ่น 86 ปี 👕 ราคาตัวละ 300 บาท ✅ ช่องทางการสั่งซื้อติดต่อฝ่ายกิจการนักศึกษา...</p>',
    excerptPlain: 'เสื้อสวยๆ มาแล้วจ้า ✅ เปิดรับ Pre-order เสื้อชวนครุศาสตร์ คืนถิ่น 86 ปี 👕 ราคาตัวละ 300 บาท ✅ ช่องทางการสั่งซื้อติดต่อฝ่ายกิจการนักศึกษา...',
    contentHtml: '<p>เสื้อสวยๆ มาแล้วจ้า ✅ เปิดรับ Pre-order เสื้อชวนครุศาสตร์ คืนถิ่น 86 ปี 👕 ราคาตัวละ 300 บาท</p>',
    publishedAt: '2026-09-23T14:00:00+07:00',
    modifiedAt: '2026-09-23T14:00:00+07:00',
    coverUrl: 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?q=80&w=800&auto=format&fit=crop',
    coverAlt: 'เสื้อโครงการคืนถิ่น',
    categoryIds: [3],
    categoryNames: ['ข่าวประชาสัมพันธ์'],
    views: 5,
    galleryCount: 3,
    authorName: 'Admin',
    authorRole: 'ผู้ดูแลระบบ',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop',
    gallery: [
      { id: 7, url: 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?q=80&w=800&auto=format&fit=crop' },
    ],
  },
  {
    id: 26,
    slug: 'executive-meeting-september-15-2026',
    titleHtml: 'วันที่ 15 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ พร้อม...',
    titlePlain: 'วันที่ 15 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ พร้อม...',
    excerptHtml: '<p>วันที่ 15 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ พร้อมด้วยผู้บริหาร อาจารย์คณะ ร่วมประชุมพัฒนาหลักสูตรนวัตกรรม...</p>',
    excerptPlain: 'วันที่ 15 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ พร้อมด้วยผู้บริหาร อาจารย์คณะ ร่วมประชุมพัฒนาหลักสูตรนวัตกรรม...',
    contentHtml: '<p>วันที่ 15 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ พร้อมด้วยผู้บริหาร อาจารย์คณะ</p>',
    publishedAt: '2026-09-15T11:00:00+07:00',
    modifiedAt: '2026-09-15T11:00:00+07:00',
    coverUrl: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=800&auto=format&fit=crop',
    coverAlt: 'การประชุมพัฒนาหลักสูตร',
    categoryIds: [4],
    categoryNames: ['วิชาการ'],
    views: 3,
    galleryCount: 3,
    authorName: 'Admin',
    authorRole: 'ผู้ดูแลระบบ',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop',
    gallery: [
      { id: 8, url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=800&auto=format&fit=crop' },
    ],
  },
  {
    id: 27,
    slug: 'university-collaboration-september-14-2026',
    titleHtml: 'วันที่ 14 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ มหาว...',
    titlePlain: 'วันที่ 14 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ มหาว...',
    excerptHtml: '<p>วันที่ 14 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ มหาวิทยาลัยราชภัฏเพื่อส่งเสริมศักยภาพและพัฒนาองค์ความรู้ร่วมกัน...</p>',
    excerptPlain: 'วันที่ 14 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ มหาวิทยาลัยราชภัฏเพื่อส่งเสริมศักยภาพและพัฒนาองค์ความรู้ร่วมกัน...',
    contentHtml: '<p>วันที่ 14 กันยายน 2569 ผู้ช่วยศาสตราจารย์ ดร.ลินดา นาคโปย คณบดีคณะครุศาสตร์ มหาวิทยาลัยราชภัฏเพื่อส่งเสริมศักยภาพและพัฒนาองค์ความรู้ร่วมกัน</p>',
    publishedAt: '2026-09-14T13:30:00+07:00',
    modifiedAt: '2026-09-14T13:30:00+07:00',
    coverUrl: 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop',
    coverAlt: 'ความร่วมมือทางวิชาการ',
    categoryIds: [3],
    categoryNames: ['ข่าวประชาสัมพันธ์'],
    views: 6,
    galleryCount: 3,
    authorName: 'Admin',
    authorRole: 'ผู้ดูแลระบบ',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop',
    gallery: [
      { id: 9, url: 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop' },
    ],
  },
];

const LOCAL_MOCK_BANNERS: Banner[] = [
  {
    id: 101,
    title: 'ยินดีต้อนรับสู่วิทยาลัยเทคโนโลยีศรีราชา มุ่งสู่ความเป็นเลิศด้านวิชาชีพและเทคโนโลยี',
    desktopImageUrl: 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1709&h=650&fit=crop',
    mobileImageUrl: 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=800&h=650&fit=crop',
    targetUrl: '/about',
    menuOrder: 1,
  },
  {
    id: 102,
    title: 'เปิดรับสมัครนักศึกษาใหม่ ประจำปีการศึกษา 2569 - สมัครเรียนออนไลน์ได้แล้ววันนี้',
    desktopImageUrl: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=1709&h=650&fit=crop',
    mobileImageUrl: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=800&h=650&fit=crop',
    targetUrl: 'https://admission.technosrc.ac.th',
    menuOrder: 2,
  },
  {
    id: 103,
    title: 'ศูนย์การเรียนรู้เทคโนโลยีสารสนเทศและนวัตกรรมแห่งอนาคต',
    desktopImageUrl: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1709&h=650&fit=crop',
    mobileImageUrl: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=800&h=650&fit=crop',
    targetUrl: '/news',
    menuOrder: 3,
  },
];

const LOCAL_MOCK_SITE_SETTINGS: SiteSettings = {
  siteName: 'SERIRACHA TECHNOLOGY COLLEGE',
  siteTagline: 'Institutional Excellence & Innovation',
  logoUrl: '/logo/LOGO_SERIRACHA_COLOR_FULL.png',
  faviconUrl: '/favicon.svg',
  contact: {
    email: 'contact@seriracha.ac.th',
    phone: '065-465-0306',
    address: 'ตำบลทุ่งสุขลา อำเภอศรีราชา จังหวัดชลบุรี',
    officeHours: 'จันทร์ - ศุกร์ 08:30 - 17:00 น.',
  },
  social: {
    facebook: 'https://facebook.com',
    line: '@seriracha',
    linkedin: '',
    github: '',
  },
  footerCopyright: `© ${new Date().getFullYear()} วิทยาลัยเทคโนโลยีศรีราชา (SERIRACHA TECHNOLOGY COLLEGE). สงวนลิขสิทธิ์ทุกประการ.`,
};

/**
 * Transforms raw WordPress REST API post item into clean Post domain model
 */
function mapRawPostToPost(raw: any): Post {
  const media = raw._embedded?.['wp:featuredmedia']?.[0];
  const coverUrl = raw.coverUrl || media?.source_url || raw.meta?.seo_og_image_url;
  const coverAlt = raw.coverAlt || media?.alt_text || stripHtml(raw.title?.rendered || raw.title || '');

  const termCategories = raw._embedded?.['wp:term']?.[0] || [];
  const categoryNames = raw.categoryNames || termCategories.map((term: any) => term.name);

  return {
    id: raw.id,
    slug: raw.slug,
    titleHtml: raw.titleHtml || raw.title?.rendered || raw.title || '',
    titlePlain: raw.titlePlain || stripHtml(raw.title?.rendered || raw.title || ''),
    excerptHtml: sanitizeWordPressHtml(raw.excerptHtml || raw.excerpt?.rendered || raw.excerpt || ''),
    excerptPlain: raw.excerptPlain || stripHtml(raw.excerpt?.rendered || raw.excerpt || ''),
    contentHtml: sanitizeWordPressHtml(raw.contentHtml || raw.content?.rendered || raw.content || ''),
    publishedAt: raw.publishedAt || raw.date,
    modifiedAt: raw.modifiedAt || raw.modified,
    coverUrl,
    coverThumb: raw.coverThumb || coverUrl,
    coverAlt,
    categoryIds: raw.categoryIds || raw.categories || [],
    categoryNames,
    categories: raw.categories,
    views: raw.views ?? (raw.post_views ? Number(raw.post_views) : 12),
    gallery: raw.gallery || raw.post_gallery || [],
    galleryCount: raw.galleryCount ?? (raw.gallery ? raw.gallery.length : (raw.post_gallery_count ? Number(raw.post_gallery_count) : 0)),
    authorName: raw.authorName || 'Admin',
    authorRole: raw.authorRole || 'ผู้ดูแลระบบ',
    authorAvatar: raw.authorAvatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=120&auto=format&fit=crop',
    seoMetaTitle: raw.seoMetaTitle || raw.meta?.seo_meta_title,
    seoMetaDescription: raw.seoMetaDescription || raw.meta?.seo_meta_description,
    seoOgImageUrl: raw.seoOgImageUrl || raw.meta?.seo_og_image_url,
  };
}

/**
 * Helper to fetch with timeout and standard headers
 */
async function fetchWithTimeout(url: string): Promise<Response> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);

  try {
    const res = await fetch(url, {
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
        'User-Agent': 'Technosrc-Astro-Client/1.0',
      },
    });
    return res;
  } finally {
    clearTimeout(timer);
  }
}

/**
 * Get paginated list of published posts with category filtering support
 */
export async function getPublishedPosts(page = 1, perPage = 20, category = 'all'): Promise<PaginatedPosts> {
  const cacheKey = `posts_p${page}_pp${perPage}_cat${category}`;
  const cached = getFromCache<PaginatedPosts>(cacheKey);
  if (cached) return cached;

  // 1. Try custom project-core REST API endpoint first (returns gallery, views, formatted categories)
  const categoryParam = category && category !== 'all' ? `&category=${encodeURIComponent(category)}` : '';
  const projectCoreUrl = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/posts?page=${page}&per_page=${perPage}${categoryParam}`;

  try {
    const res = await fetchWithTimeout(projectCoreUrl);
    if (res.ok) {
      const data = await res.json();
      if (data && Array.isArray(data.posts) && data.posts.length > 0) {
        const posts = data.posts.map(mapRawPostToPost);
        const result: PaginatedPosts = {
          posts,
          totalPosts: data.totalPosts || posts.length,
          totalPages: data.totalPages || 1,
          currentPage: page,
        };
        setInCache(cacheKey, result);
        return result;
      }
    }
  } catch (err) {
    // Continue to standard WP fallback
  }

  // 2. Standard WordPress REST API fallback
  const url = `${WP_API_BASE_URL.replace(/\/$/, '')}/posts?status=publish&_embed=1&page=${page}&per_page=${perPage}&orderby=date&order=desc`;

  try {
    const res = await fetchWithTimeout(url);

    if (!res.ok) {
      if (res.status === 400 && page > 1) {
        return { posts: [], totalPosts: 0, totalPages: 0, currentPage: page };
      }
      throw new Error(`WordPress API returned status ${res.status}: ${res.statusText}`);
    }

    const totalPosts = parseInt(res.headers.get('x-wp-total') || '0', 10);
    const totalPages = parseInt(res.headers.get('x-wp-totalpages') || '1', 10);
    const rawPosts: WP_RawPost[] = await res.json();

    const posts = rawPosts.map(mapRawPostToPost);
    const result: PaginatedPosts = {
      posts,
      totalPosts: totalPosts || posts.length,
      totalPages: totalPages || 1,
      currentPage: page,
    };
    setInCache(cacheKey, result);
    return result;
  } catch (error) {
    console.warn(`[WordPress API] Unable to fetch posts at ${WP_API_BASE_URL}:`, error);

    if (IS_CI) {
      throw new Error(`[WordPress CI Error] Build failed because CMS API is unreachable: ${error}`);
    }

    const startIndex = (page - 1) * perPage;
    const paginated = LOCAL_MOCK_POSTS.slice(startIndex, startIndex + perPage);
    const totalPages = Math.max(1, Math.ceil(LOCAL_MOCK_POSTS.length / perPage));

    return {
      posts: paginated,
      totalPosts: LOCAL_MOCK_POSTS.length,
      totalPages,
      currentPage: page,
    };
  }
}

/**
 * Fetch all published posts across all pages (used for getStaticPaths)
 */
export async function getAllPublishedPosts(): Promise<Post[]> {
  const url = `${WP_API_BASE_URL.replace(/\/$/, '')}/posts?status=publish&_embed=1&page=1&per_page=100&orderby=date&order=desc`;

  try {
    const firstPageRes = await fetchWithTimeout(url);

    if (!firstPageRes.ok) {
      throw new Error(`WordPress API returned status ${firstPageRes.status}: ${firstPageRes.statusText}`);
    }

    const totalPages = parseInt(firstPageRes.headers.get('x-wp-totalpages') || '1', 10);
    const firstPageData: WP_RawPost[] = await firstPageRes.json();
    let allRawPosts: WP_RawPost[] = [...firstPageData];

    if (totalPages > 1) {
      for (let p = 2; p <= totalPages; p++) {
        const pageUrl = `${WP_API_BASE_URL.replace(/\/$/, '')}/posts?status=publish&_embed=1&page=${p}&per_page=100&orderby=date&order=desc`;
        const pageRes = await fetchWithTimeout(pageUrl);
        if (pageRes.ok) {
          const pageData: WP_RawPost[] = await pageRes.json();
          allRawPosts = allRawPosts.concat(pageData);
        }
      }
    }

    return allRawPosts.map(mapRawPostToPost);
  } catch (error) {
    console.warn(`[WordPress API] Unable to fetch all posts at ${WP_API_BASE_URL}:`, error);

    if (IS_CI) {
      throw new Error(`[WordPress CI Error] CMS outage during build: ${error}`);
    }

    return LOCAL_MOCK_POSTS;
  }
}

/**
 * Fetch a single post by its slug
 */
export async function getPostBySlug(slug: string): Promise<Post | null> {
  const url = `${WP_API_BASE_URL.replace(/\/$/, '')}/posts?slug=${encodeURIComponent(slug)}&status=publish&_embed=1`;

  try {
    const res = await fetchWithTimeout(url);

    if (!res.ok) {
      throw new Error(`WordPress API returned ${res.status}`);
    }

    const posts: WP_RawPost[] = await res.json();
    if (posts && posts.length > 0) {
      return mapRawPostToPost(posts[0]);
    }
    return null;
  } catch (error) {
    console.warn(`[WordPress API] Unable to fetch post with slug ${slug}:`, error);

    if (IS_CI) {
      throw new Error(`[WordPress CI Error] CMS outage during build: ${error}`);
    }

    const mock = LOCAL_MOCK_POSTS.find((p) => p.slug === slug);
    return mock || null;
  }
}

/**
 * Fetch categories
 */
export async function getCategories(): Promise<Category[]> {
  const cacheKey = 'categories_all';
  const cached = getFromCache<Category[]>(cacheKey);
  if (cached) return cached;

  // 1. Try custom project-core REST API endpoint first
  try {
    const res = await fetchWithTimeout(`${PROJECT_CORE_API_URL.replace(/\/$/, '')}/categories`);
    if (res.ok) {
      const cats: Category[] = await res.json();
      if (Array.isArray(cats) && cats.length > 0) {
        setInCache(cacheKey, cats);
        return cats;
      }
    }
  } catch (err) {
    // Continue to standard WP fallback
  }

  // 2. Standard WordPress REST API fallback
  const url = `${WP_API_BASE_URL.replace(/\/$/, '')}/categories?per_page=100&hide_empty=false`;

  try {
    const res = await fetchWithTimeout(url);
    if (!res.ok) {
      return [];
    }
    const rawCategories: WP_RawCategory[] = await res.json();
    const result = rawCategories
      .filter((c) => c.slug !== 'uncategorized')
      .map((c) => ({
        id: c.id,
        name: c.name,
        slug: c.slug,
        count: c.count,
      }));
    setInCache(cacheKey, result);
    return result;
  } catch (error) {
    console.warn('[WordPress API] Failed to fetch categories:', error);
    return [
      { id: 3, name: 'ข่าวประชาสัมพันธ์', slug: 'pr-news', count: 5 },
      { id: 4, name: 'วิชาการ', slug: 'academics', count: 1 },
      { id: 5, name: 'กิจกรรมนักศึกษา', slug: 'student-activities', count: 0 },
    ];
  }
}

/**
 * Fetch active banners from custom project-core REST API endpoint
 */
export async function getBanners(position?: string): Promise<Banner[]> {
  const cacheKey = `banners_${position || 'all'}`;
  const cached = getFromCache<Banner[]>(cacheKey);
  if (cached) return cached;

  const query = position ? `?position=${encodeURIComponent(position)}` : '';
  const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/banners${query}`;

  try {
    const res = await fetchWithTimeout(url);
    if (!res.ok) {
      throw new Error(`Custom Banners API returned ${res.status}`);
    }
    const rawData = await res.json();
    const result: Banner[] = (Array.isArray(rawData) ? rawData : []).map((b: any) => ({
      id: b.id,
      title: b.title || '',
      desktopImageUrl: b.desktopImageUrl || b.desktop_image_url || '',
      mobileImageUrl: b.mobileImageUrl || b.mobile_image_url || b.desktopImageUrl || b.desktop_image_url || '',
      targetUrl: b.targetUrl || b.target_url || '',
      menuOrder: b.menuOrder ?? b.menu_order ?? 0,
    }));
    setInCache(cacheKey, result);
    return result;
  } catch (error) {
    console.warn('[WordPress API] Unable to fetch banners:', error);
    return LOCAL_MOCK_BANNERS;
  }
}

/**
 * Fetch global site settings from custom project-core REST API endpoint
 */
export async function getSiteSettings(): Promise<SiteSettings> {
  const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/site-settings`;

  try {
    const res = await fetchWithTimeout(url);
    if (!res.ok) {
      throw new Error(`Custom Site Settings API returned ${res.status}`);
    }
    const data: SiteSettings = await res.json();
    return data;
  } catch (error) {
    console.warn('[WordPress API] Unable to fetch site settings:', error);
    return LOCAL_MOCK_SITE_SETTINGS;
  }
}

const LOCAL_MOCK_EVENTS: CollegeEvent[] = [
  {
    id: 29,
    title: 'พิธีไหว้ครู ประจำปีการศึกษา 2569',
    slug: 'wai-kru-ceremony-2569',
    content: '<p>ขอเชิญชวนคณะผู้บริหาร ครู อาจารย์ บุคลากรทางการศึกษา และนักเรียนนักศึกษาทุกระดับชั้น เข้าร่วมพิธีไหว้ครู ประจำปีการศึกษา 2569 เพื่อร่วมแสดงความกตัญญูกตเวทิตาต่อครูอาจารย์ผู้ประสิทธิ์ประสาทวิชาความรู้ ณ หอประชุมใหญ่</p>',
    excerpt: 'ขอเชิญชวนคณะครู บุคลากร และนักเรียนนักศึกษาทุกระดับชั้น เข้าร่วมพิธีไหว้ครู ประจำปีการศึกษา 2569 เพื่อแสดงความกตัญญูกตเวทิตา',
    startDate: '2026-10-22',
    endDate: '2026-10-22',
    time: '08:30 - 12:00 น.',
    location: 'หอประชุมวิทยาลัยเทคโนโลยีแหลมฉบัง',
    organizer: 'ฝ่ายพัฒนากิจกรรมนักเรียน นักศึกษา',
    link: '',
    attachmentUrl: '',
    color: 'blue',
    isFeatured: true,
    thumbnail: null,
    categories: [{ id: 8, name: 'กิจกรรมนักศึกษา', slug: 'student-activities' }],
  },
  {
    id: 30,
    title: 'วันปิยมหาราช (วันหยุดราชการ)',
    slug: 'chulalongkorn-day-2026',
    content: '<p>วิทยาลัยหยุดทำการเนื่องในวันคล้ายวันสวรรคตพระบาทสมเด็จพระจุลจอมเกล้าเจ้าอยู่หัว (วันปิยมหาราช)</p>',
    excerpt: 'วิทยาลัยหยุดทำการเนื่องในวันคล้ายวันสวรรคตพระบาทสมเด็จพระจุลจอมเกล้าเจ้าอยู่หัว (วันปิยมหาราช)',
    startDate: '2026-10-23',
    endDate: '2026-10-23',
    time: 'ตลอดวัน',
    location: 'วิทยาลัยเทคโนโลยีแหลมฉบัง',
    organizer: 'ฝ่ายบริหารทั่วไป',
    color: 'rose',
    isFeatured: false,
    thumbnail: null,
    categories: [{ id: 10, name: 'วันสำคัญและวันหยุด', slug: 'holidays' }],
  },
  {
    id: 31,
    title: 'สอบวัดผลกลางภาคเรียนที่ 2/2569',
    slug: 'midterm-exam-term-2-2569',
    content: '<p>กำหนดการสอบวัดผลกลางภาคเรียนที่ 2 ประจำปีการศึกษา 2569 ระดับ ปวช. และ ปวส. ทุกสาขาวิชา ขอให้นักเรียนนักศึกษาเตรียมตัวและตรวจสอบตารางสอบให้เรียบร้อยก่อนวันสอบ</p>',
    excerpt: 'กำหนดการสอบวัดผลกลางภาคเรียนที่ 2 ประจำปีการศึกษา 2569 ระดับ ปวช. และ ปวส. ทุกสาขาวิชา',
    startDate: '2026-11-03',
    endDate: '2026-11-07',
    time: '08:30 - 16:30 น.',
    location: 'อาคารเรียน 1-3',
    organizer: 'งานวัดผลและประเมินผล',
    color: 'amber',
    isFeatured: true,
    thumbnail: null,
    categories: [{ id: 7, name: 'กำหนดการศึกษา', slug: 'academic-schedule' }],
  },
  {
    id: 32,
    title: 'การแข่งขันทักษะวิชาชีพและทักษะพื้นฐาน ระดับภาค',
    slug: 'vocational-skills-competition-2026',
    content: '<p>นำทีมนักศึกษาตัวแทนวิทยาลัยเข้าร่วมการแข่งขันทักษะวิชาชีพ สาขาวิชาช่างยนต์ ช่างไฟฟ้ากำลัง เทคโนโลยีสารสนเทศ และการบัญชี ระดับภาคตะวันออก ประจำปีการศึกษา 2569</p>',
    excerpt: 'ตัวแทนนักศึกษาเข้าร่วมการแข่งขันทักษะวิชาชีพ สาขาวิชาช่างยนต์ ไฟฟ้า และเทคโนโลยีสารสนเทศ',
    startDate: '2026-11-18',
    endDate: '2026-11-20',
    time: '09:00 - 17:00 น.',
    location: 'ศูนย์ประชุมและนิทรรศการ',
    organizer: 'คณะกรรมการ อวท.',
    color: 'purple',
    isFeatured: true,
    thumbnail: null,
    categories: [{ id: 9, name: 'วิชาการและอบรม', slug: 'academic-training' }],
  },
  {
    id: 33,
    title: 'โครงการส่งเสริมคุณธรรมและจริยธรรมนักศึกษาใหม่',
    slug: 'ethics-workshop-freshmen-2026',
    content: '<p>กิจกรรมส่งเสริมคุณธรรม จริยธรรม และการพัฒนาภาวะผู้นำ สำหรับนักศึกษาใหม่ เสริมสร้างทักษะการอยู่ร่วมกันในสังคมอย่างมีความสุข</p>',
    excerpt: 'กิจกรรมอบรมเชิงปฏิบัติการพัฒนาภาวะผู้นำ จริยธรรม และการปรับตัวในการเรียนวิชาชีพ',
    startDate: '2026-11-28',
    endDate: '2026-11-28',
    time: '08:30 - 15:30 น.',
    location: 'อาคารอเนกประสงค์',
    organizer: 'แผนกงานครูที่ปรึกษา',
    color: 'emerald',
    isFeatured: false,
    thumbnail: null,
    categories: [{ id: 8, name: 'กิจกรรมนักศึกษา', slug: 'student-activities' }],
  },
];

/**
 * Fetch college events from custom project-core REST API endpoint
 */
export async function getCollegeEvents(category?: string): Promise<CollegeEvent[]> {
  const cacheKey = `college_events_${category || 'all'}`;
  const cached = getFromCache<CollegeEvent[]>(cacheKey);
  if (cached) return cached;

  const query = category && category !== 'all' ? `?category=${encodeURIComponent(category)}` : '';
  const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/events${query}`;

  try {
    const res = await fetchWithTimeout(url);
    if (!res.ok) {
      throw new Error(`Custom Events API returned ${res.status}`);
    }
    const data = await res.json();
    const rawEvents = Array.isArray(data.events) ? data.events : (Array.isArray(data) ? data : []);
    const result: CollegeEvent[] = rawEvents.map((ev: any) => ({
      id: ev.id,
      title: ev.title || '',
      slug: ev.slug || '',
      content: ev.content || '',
      excerpt: ev.excerpt || '',
      startDate: ev.start_date || ev.startDate || '',
      endDate: ev.end_date || ev.endDate || '',
      time: ev.time || '',
      location: ev.location || '',
      organizer: ev.organizer || '',
      link: ev.link || '',
      attachmentUrl: ev.attachment_url || ev.attachmentUrl || '',
      color: ev.color || 'blue',
      isFeatured: !!ev.is_featured,
      thumbnail: ev.thumbnail || null,
      categories: Array.isArray(ev.categories) ? ev.categories : [],
    }));

    if (result.length > 0) {
      setInCache(cacheKey, result);
      return result;
    }
    return LOCAL_MOCK_EVENTS;
  } catch (error) {
    console.warn('[WordPress API] Unable to fetch college events:', error);
    return LOCAL_MOCK_EVENTS;
  }
}

/**
 * Fetch event categories from custom project-core REST API endpoint
 */
export async function getCollegeEventCategories(): Promise<CollegeEventCategory[]> {
  const cacheKey = 'college_event_categories';
  const cached = getFromCache<CollegeEventCategory[]>(cacheKey);
  if (cached) return cached;

  const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/event-categories`;

  try {
    const res = await fetchWithTimeout(url);
    if (!res.ok) {
      throw new Error(`Custom Event Categories API returned ${res.status}`);
    }
    const rawCats = await res.json();
    if (Array.isArray(rawCats) && rawCats.length > 0) {
      const cats: CollegeEventCategory[] = rawCats.map((c: any) => ({
        id: c.id,
        name: c.name,
        slug: c.slug,
        color: c.color || 'blue',
        count: c.count,
      }));
      setInCache(cacheKey, cats);
      return cats;
    }
  } catch (err) {
    // Return fallback
  }

  return [
    { id: 7, name: 'กำหนดการศึกษา', slug: 'academic-schedule', color: 'amber', count: 1 },
    { id: 8, name: 'กิจกรรมนักศึกษา', slug: 'student-activities', color: 'blue', count: 2 },
    { id: 9, name: 'วิชาการและอบรม', slug: 'academic-training', color: 'purple', count: 1 },
    { id: 10, name: 'วันสำคัญและวันหยุด', slug: 'holidays', color: 'rose', count: 1 },
  ];
}
