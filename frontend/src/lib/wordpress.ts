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
  Personnel,
  PersonnelCategory,
  SarReport,
  SarYear,
  EducationMisItem,
  EducationMisYear,
  StudentStatRow,
  PersonnelStatRow,
  CurriculumStatRow,
  AdmissionsGraduatesRow,
  AchievementStatRow,
  EmploymentStatRow,
  PartnerEnterpriseRow,
  OrganizationData,
  OrgLeader,
  OrgDivision,
  OrgJob,
  OrgSettings,
  OrgPersonnel,
  OpenHouseInfo,
  OpenHouseCategory,
  OpenHouseActivity,
} from '../types/wordpress';
export type {
  Personnel,
  PersonnelCategory,
  SarReport,
  SarYear,
  EducationMisItem,
  EducationMisYear,
  StudentStatRow,
  PersonnelStatRow,
  CurriculumStatRow,
  AdmissionsGraduatesRow,
  AchievementStatRow,
  EmploymentStatRow,
  PartnerEnterpriseRow,
  OrganizationData,
  OrgLeader,
  OrgDivision,
  OrgJob,
  OrgSettings,
  OrgPersonnel,
  OpenHouseInfo,
  OpenHouseCategory,
  OpenHouseActivity,
};
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
  // In development mode, bypass cache so WordPress updates show up immediately
  if (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.DEV) {
    return null;
  }
  const item = apiCache.get(key);
  if (item && item.expiresAt > Date.now()) {
    return item.data as T;
  }
  return null;
}

function setInCache<T>(key: string, data: T): void {
  if (typeof import.meta !== 'undefined' && import.meta.env && import.meta.env.DEV) {
    return;
  }
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
  siteName: 'Sriracha Technological College',
  siteTagline: 'Institutional Excellence & Innovation',
  logoUrl: '/logo/LOGO_SERIRACHA_COLOR_FULL.png',
  faviconUrl: '/favicon.svg',
  contact: {
    email: 'technosriracha@gmail.com',
    phone: '038-352440, 038-351785',
    address: '12/19 หมู่ที่ 2 ตำบลทุ่งสุขลา อำเภอศรีราชา จังหวัดชลบุรี 20230',
    officeHours: 'จันทร์ - ศุกร์ 08:30 - 16:30 น.',
  },
  social: {
    facebook: 'https://facebook.com',
    line: '@seriracha',
    linkedin: '',
    github: '',
  },
  footerCopyright: `© ${new Date().getFullYear()} วิทยาลัยเทคโนโลยีศรีราชา (Sriracha Technological College). สงวนลิขสิทธิ์ทุกประการ.`,
  vtr: {
    enabled: true,
    youtubeUrl: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    youtubeId: 'dQw4w9WgXcQ',
    embedUrl: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
  },
};

/**
 * Extract 11-char YouTube Video ID from any YouTube URL format
 */
export function extractYouTubeId(url?: string): string {
  if (!url) return '';
  const trimmed = url.trim();
  if (/^[a-zA-Z0-9_-]{11}$/.test(trimmed)) {
    return trimmed;
  }
  const match = trimmed.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i);
  return match ? match[1] : '';
}

/**
 * Helper to identify posts that are uncategorized (e.g. default 'uncategorized' slug/name or 'hello-world')
 */
export function isPostUncategorized(rawOrPost: any): boolean {
  if (!rawOrPost) return true;
  if (rawOrPost.slug === 'hello-world') return true;

  const catNames: string[] =
    rawOrPost.categoryNames ||
    rawOrPost._embedded?.['wp:term']?.[0]?.map((t: any) => t.name) ||
    [];

  const catSlugs: string[] =
    rawOrPost.category_slugs ||
    rawOrPost._embedded?.['wp:term']?.[0]?.map((t: any) => t.slug) ||
    [];

  const validNames = catNames.filter((n) => {
    if (typeof n !== 'string') return false;
    const lower = n.trim().toLowerCase();
    return lower !== 'uncategorized' && lower !== 'ไม่มีหมวดหมู่';
  });

  const validSlugs = catSlugs.filter((s) => {
    if (typeof s !== 'string') return false;
    const lower = s.trim().toLowerCase();
    return lower !== 'uncategorized';
  });

  if (catSlugs.length > 0 && validSlugs.length === 0) {
    return true;
  }

  if (catNames.length > 0 && validNames.length === 0) {
    return true;
  }

  // If raw has categories (array of term objects with slug/name)
  if (Array.isArray(rawOrPost.categories) && rawOrPost.categories.length > 0) {
    const hasValidTerm = rawOrPost.categories.some((c: any) => {
      if (typeof c === 'object' && c !== null) {
        const slug = (c.slug || '').toLowerCase();
        const name = (c.name || '').toLowerCase();
        return slug !== 'uncategorized' && name !== 'uncategorized' && name !== 'ไม่มีหมวดหมู่';
      }
      return true;
    });
    if (!hasValidTerm) return true;
  }

  // If no category names or slugs exist at all
  if (catNames.length === 0 && catSlugs.length === 0 && (!rawOrPost.categories || rawOrPost.categories.length === 0)) {
    return true;
  }

  return false;
}

/**
 * Transforms raw WordPress REST API post item into clean Post domain model
 */
function mapRawPostToPost(raw: any): Post {
  const media = raw._embedded?.['wp:featuredmedia']?.[0];
  const coverUrl = raw.coverUrl || media?.source_url || raw.meta?.seo_og_image_url;
  const coverAlt = raw.coverAlt || media?.alt_text || stripHtml(raw.title?.rendered || raw.title || '');

  const termCategories = (raw._embedded?.['wp:term']?.[0] || []).filter((term: any) => {
    const slug = (term.slug || '').toLowerCase();
    const name = (term.name || '').toLowerCase();
    return slug !== 'uncategorized' && name !== 'uncategorized' && name !== 'ไม่มีหมวดหมู่';
  });
  const rawCatNames = raw.categoryNames || termCategories.map((term: any) => term.name);
  const categoryNames = (Array.isArray(rawCatNames) ? rawCatNames : []).filter((name: string) => {
    if (typeof name !== 'string') return false;
    const lower = name.toLowerCase().trim();
    return lower !== 'uncategorized' && lower !== 'ไม่มีหมวดหมู่';
  });

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
        const posts = data.posts.filter((p: any) => !isPostUncategorized(p)).map(mapRawPostToPost);
        const result: PaginatedPosts = {
          posts,
          totalPosts: data.totalPosts || posts.length,
          totalPages: data.totalPages || Math.max(1, Math.ceil(posts.length / perPage)),
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

    const posts = rawPosts.filter((p: any) => !isPostUncategorized(p)).map(mapRawPostToPost);
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

    const filteredMocks = LOCAL_MOCK_POSTS.filter((p) => !isPostUncategorized(p));
    const startIndex = (page - 1) * perPage;
    const paginated = filteredMocks.slice(startIndex, startIndex + perPage);
    const totalPages = Math.max(1, Math.ceil(filteredMocks.length / perPage));

    return {
      posts: paginated,
      totalPosts: filteredMocks.length,
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

    return allRawPosts.filter((p: any) => !isPostUncategorized(p)).map(mapRawPostToPost);
  } catch (error) {
    console.warn(`[WordPress API] Unable to fetch all posts at ${WP_API_BASE_URL}:`, error);

    if (IS_CI) {
      throw new Error(`[WordPress CI Error] CMS outage during build: ${error}`);
    }

    return LOCAL_MOCK_POSTS.filter((p) => !isPostUncategorized(p));
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
    if (posts && posts.length > 0 && !isPostUncategorized(posts[0])) {
      return mapRawPostToPost(posts[0]);
    }
    return null;
  } catch (error) {
    console.warn(`[WordPress API] Unable to fetch post with slug ${slug}:`, error);

    if (IS_CI) {
      throw new Error(`[WordPress CI Error] CMS outage during build: ${error}`);
    }

    const mock = LOCAL_MOCK_POSTS.find((p) => p.slug === slug && !isPostUncategorized(p));
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
    const data: any = await res.json();
    if (data && data.vtr) {
      const ytUrl = data.vtr.youtube_url || data.vtr.youtubeUrl || '';
      const ytId = data.vtr.youtube_id || data.vtr.youtubeId || extractYouTubeId(ytUrl);
      data.vtr = {
        enabled: data.vtr.enabled !== false,
        youtubeUrl: ytUrl,
        youtubeId: ytId,
        embedUrl: data.vtr.embed_url || data.vtr.embedUrl || (ytId ? `https://www.youtube-nocookie.com/embed/${ytId}` : ''),
      };
    }
    return data as SiteSettings;
  } catch (error) {
    console.warn('[WordPress API] Unable to fetch site settings:', error);
    return LOCAL_MOCK_SITE_SETTINGS;
  }
}

/**
 * Fetch college events from custom project-core REST API endpoint
 */
export async function getCollegeEvents(category?: string, academicYear?: string): Promise<CollegeEvent[]> {
  const cacheKey = `college_events_${category || 'all'}_${academicYear || 'all'}`;
  const cached = getFromCache<CollegeEvent[]>(cacheKey);
  if (cached) return cached;

  const params = new URLSearchParams();
  if (category && category !== 'all') params.set('category', category);
  if (academicYear && academicYear !== 'all') params.set('academic_year', academicYear);
  const query = params.toString() ? `?${params.toString()}` : '';
  const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/events${query}`;

  try {
    const res = await fetchWithTimeout(url);
    if (!res.ok) {
      throw new Error(`Custom Events API returned ${res.status}`);
    }
    const data = await res.json();
    const rawEvents = Array.isArray(data.events) ? data.events : (Array.isArray(data) ? data : []);
    const result: CollegeEvent[] = rawEvents.map((ev: any) => {
      const cleanContent = (ev.content || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
      const rawExcerpt = (ev.excerpt || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
      const finalExcerpt = cleanContent.length === 0 ? '' : rawExcerpt;
      return {
        id: ev.id,
        title: ev.title || '',
        slug: ev.slug || '',
        content: cleanContent.length === 0 ? '' : (ev.content || ''),
        excerpt: finalExcerpt,
        academicYear: String(ev.academic_year || ev.academicYear || ''),
        startDate: ev.start_date || ev.startDate || '',
        endDate: ev.end_date || ev.endDate || '',
        time: ev.time || '',
        location: ev.location || '',
        note: ev.note || ev.event_note || '',
        organizer: ev.organizer || '',
        link: ev.link || '',
        attachmentUrl: ev.attachment_url || ev.attachmentUrl || '',
        color: ev.color || 'blue',
        isFeatured: !!ev.is_featured,
        thumbnail: ev.thumbnail || null,
        categories: Array.isArray(ev.categories) ? ev.categories : [],
      };
    });

    setInCache(cacheKey, result);
    return result;
  } catch (error) {
    console.warn('[WordPress API] Unable to fetch college events:', error);
    return [];
  }
}

/**
 * Fetch all available academic years
 */
export async function getCollegeAcademicYears(): Promise<string[]> {
  const cacheKey = 'college_academic_years';
  const cached = getFromCache<string[]>(cacheKey);
  if (cached) return cached;

  try {
    const res = await fetchWithTimeout(`${PROJECT_CORE_API_URL.replace(/\/$/, '')}/academic-years`);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        const years = data.map((item: any) => String(item.name || item.slug)).filter(Boolean);
        if (years.length > 0) {
          setInCache(cacheKey, years);
          return years;
        }
      }
    }
  } catch (err) {
    // fallback
  }

  return [];
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
    if (Array.isArray(rawCats)) {
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
    // Return empty array
  }

  return [];
}

/**
 * ============================================================================
 * CURRICULUMS & PROGRAMS (หลักสูตร ปวช. / ปวส. และสาขาวิชา)
 * ============================================================================
 */

export interface CurriculumTaxonomyItem {
  id: number;
  name: string;
  slug: string;
  count?: number;
}

export interface CurriculumMeta {
  degreeTh: string;
  degreeEn?: string;
  duration?: string;
  requirements?: string;
  badge: string;
  tuitionFee: string;
  studyPlanUrl: string;
  documentCoverUrl?: string;
  highlights: string[];
  careers: string[];
}

export interface CurriculumGalleryImage {
  id: number;
  url: string;
  thumbUrl: string;
  alt?: string;
}

export interface CurriculumItem {
  id: number;
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  menuOrder: number;
  featuredImage: string | null;
  gallery?: CurriculumGalleryImage[];
  galleryCount?: number;
  level: CurriculumTaxonomyItem | null;
  department: CurriculumTaxonomyItem | null;
  meta: CurriculumMeta;
}


/**
 * Fetch curriculums from WordPress REST API
 */
export async function getCurriculums(options: {
  level?: string;
  department?: string;
  perPage?: number;
} = {}): Promise<CurriculumItem[]> {
  const cacheKey = `curriculums_${options.level || 'all'}_${options.department || 'all'}_${options.perPage || 'all'}`;
  const cached = getFromCache<CurriculumItem[]>(cacheKey);
  if (cached) return cached;

  try {
    const params = new URLSearchParams();
    if (options.level && options.level !== 'all') params.append('level', options.level);
    if (options.department && options.department !== 'all') params.append('department', options.department);
    if (options.perPage) params.append('per_page', String(options.perPage));

    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/curriculums?${params.toString()}`;
    const res = await fetchWithTimeout(url);

    if (res.ok) {
      const data = await res.json();
      if (data && Array.isArray(data.curriculums)) {
        let results = data.curriculums;
        if (options.level && options.level !== 'all') {
          results = results.filter((c: CurriculumItem) => c.level?.slug === options.level);
        }
        if (options.department && options.department !== 'all') {
          results = results.filter((c: CurriculumItem) => c.department?.slug === options.department);
        }
        if (options.perPage) {
          results = results.slice(0, options.perPage);
        }
        setInCache(cacheKey, results);
        return results;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch curriculums:', err);
  }

  return [];
}

/**
 * Fetch single curriculum by slug from WordPress REST API
 */
export async function getCurriculumBySlug(slug: string): Promise<CurriculumItem | null> {
  const cacheKey = `curriculum_single_${slug}`;
  const cached = getFromCache<CurriculumItem>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/curriculums/${slug}`;
    const res = await fetchWithTimeout(url);

    if (res.ok) {
      const item = await res.json();
      if (item && item.id) {
        setInCache(cacheKey, item);
        return item;
      }
    }
  } catch (err) {
    console.warn(`[WordPress API] Unable to fetch curriculum with slug ${slug}:`, err);
  }

  return null;
}

/**
 * Fetch curriculum levels (ปวช., ปวส.) from WordPress REST API
 */
export async function getCurriculumLevels(): Promise<CurriculumTaxonomyItem[]> {
  const cacheKey = 'curriculum_levels';
  const cached = getFromCache<CurriculumTaxonomyItem[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/curriculum-levels`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        setInCache(cacheKey, data);
        return data;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch curriculum levels:', err);
  }

  return [];
}

/**
 * Fetch curriculum departments (กลุ่มสาขาวิชา) from WordPress REST API
 */
export async function getCurriculumDepartments(): Promise<CurriculumTaxonomyItem[]> {
  const cacheKey = 'curriculum_departments';
  const cached = getFromCache<CurriculumTaxonomyItem[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/curriculum-departments`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        setInCache(cacheKey, data);
        return data;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch curriculum departments:', err);
  }

  return [];
}

/**
 * Fetch all personnel from WordPress REST API (ordered by menu_order)
 */
export async function getPersonnel(categorySlug?: string): Promise<Personnel[]> {
  const cacheKey = `personnel_${categorySlug || 'all'}`;
  const cached = getFromCache<Personnel[]>(cacheKey);
  if (cached) return cached;

  try {
    const params = new URLSearchParams();
    if (categorySlug && categorySlug !== 'all') {
      params.append('category', categorySlug);
    }
    const queryString = params.toString() ? `?${params.toString()}` : '';
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/personnel${queryString}`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        setInCache(cacheKey, data);
        return data;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch personnel:', err);
  }

  return [];
}

/**
 * Fetch personnel categories from WordPress REST API
 */
export async function getPersonnelCategories(): Promise<PersonnelCategory[]> {
  const cacheKey = 'personnel_categories';
  const cached = getFromCache<PersonnelCategory[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/personnel-categories`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        setInCache(cacheKey, data);
        return data;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch personnel categories:', err);
  }

  return [];
}

const LOCAL_MOCK_SAR_YEARS: SarYear[] = [];

const LOCAL_MOCK_SAR_REPORTS: SarReport[] = [];

/**
 * Fetch SAR reports with optional filtering by year
 */
export async function getSarReports(options?: { year?: string; search?: string }): Promise<SarReport[]> {
  const yearParam = options?.year && options.year !== 'all' ? options.year : '';
  const searchParam = options?.search || '';
  const cacheKey = `sar_reports_${yearParam || 'all'}_${searchParam}`;
  const cached = getFromCache<SarReport[]>(cacheKey);
  if (cached) return cached;

  try {
    const params = new URLSearchParams();
    if (yearParam) params.set('year', yearParam);
    if (searchParam) params.set('search', searchParam);
    params.set('per_page', '100');

    const queryString = params.toString() ? `?${params.toString()}` : '';
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/sar-reports${queryString}`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (data && Array.isArray(data.reports)) {
        const sorted = data.reports.sort((a: SarReport, b: SarReport) => {
          const yearA = parseInt(a.year?.name?.match(/\d{4}/)?.[0] || a.year?.slug || '0', 10);
          const yearB = parseInt(b.year?.name?.match(/\d{4}/)?.[0] || b.year?.slug || '0', 10);
          if (yearB !== yearA) return yearB - yearA;
          if ((a.order ?? 0) !== (b.order ?? 0)) return (a.order ?? 0) - (b.order ?? 0);
          return new Date(b.publishedAt || 0).getTime() - new Date(a.publishedAt || 0).getTime();
        });
        setInCache(cacheKey, sorted);
        return sorted;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch SAR reports:', err);
  }

  // Fallback to local mock (clean empty array)
  let results = [...LOCAL_MOCK_SAR_REPORTS];
  if (yearParam) {
    results = results.filter((r) => r.year?.slug === yearParam || r.year?.name === yearParam);
  }
  if (searchParam) {
    const s = searchParam.toLowerCase();
    results = results.filter((r) => r.title.toLowerCase().includes(s) || r.description.toLowerCase().includes(s));
  }
  results.sort((a, b) => {
    const yearA = parseInt(a.year?.name?.match(/\d{4}/)?.[0] || a.year?.slug || '0', 10);
    const yearB = parseInt(b.year?.name?.match(/\d{4}/)?.[0] || b.year?.slug || '0', 10);
    if (yearB !== yearA) return yearB - yearA;
    if ((a.order ?? 0) !== (b.order ?? 0)) return (a.order ?? 0) - (b.order ?? 0);
    return new Date(b.publishedAt || 0).getTime() - new Date(a.publishedAt || 0).getTime();
  });
  return results;
}

/**
 * Fetch SAR years list
 */
export async function getSarYears(): Promise<SarYear[]> {
  const cacheKey = 'sar_years';
  const cached = getFromCache<SarYear[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/sar-years`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        setInCache(cacheKey, data);
        return data;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch SAR years:', err);
  }

  return LOCAL_MOCK_SAR_YEARS;
}

// Fallback Mock Data for Educational MIS
export const LOCAL_MOCK_MIS_YEARS: EducationMisYear[] = [
  { id: 81, name: 'ปีการศึกษา 2568', slug: '2568', count: 1 },
  { id: 82, name: 'ปีการศึกษา 2567', slug: '2567', count: 1 },
  { id: 83, name: 'ปีการศึกษา 2566', slug: '2566', count: 0 },
];

export const LOCAL_MOCK_EDUCATION_MIS: EducationMisItem[] = [
  {
    id: 36,
    title: 'ข้อมูลสารสนเทศทางการศึกษา ประจำปีการศึกษา 2568',
    slug: 'education-mis-2568',
    description: 'สรุปสารสนเทศทางการศึกษา วิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2568 รวบรวมข้อมูลสถิตินักเรียนนักศึกษา บุคลากร หลักสูตร ผลสัมฤทธิ์ทางการเรียน ผลการทดสอบ V-NET การมีงานทำ และความร่วมมือกับสถานประกอบการตามมาตรฐานการอาชีวศึกษา',
    academicYear: { id: 81, name: 'ปีการศึกษา 2568', slug: '2568' },
    pdfUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    overview: {
      totalStudents: 1850,
      totalPersonnel: 85,
      programsCount: 14,
      employmentRate: '96.8',
      avgGpax: '3.28',
      partnerCount: 54,
    },
    students: {
      total: 1850,
      pvchTotal: 1120,
      pvsTotal: 730,
      byDepartment: [
        { level: 'ปวช.', department: 'ช่างยนต์', male: 210, female: 12, total: 222 },
        { level: 'ปวช.', department: 'ช่างไฟฟ้ากำลัง', male: 185, female: 15, total: 200 },
        { level: 'ปวช.', department: 'ช่างอิเล็กทรอนิกส์', male: 140, female: 18, total: 158 },
        { level: 'ปวช.', department: 'เทคโนโลยีสารสนเทศ', male: 110, female: 65, total: 175 },
        { level: 'ปวช.', department: 'การบัญชี', male: 25, female: 165, total: 190 },
        { level: 'ปวช.', department: 'การตลาด', male: 35, female: 140, total: 175 },
        { level: 'ปวส.', department: 'เทคนิคเครื่องกล (ช่างยนต์)', male: 145, female: 5, total: 150 },
        { level: 'ปวส.', department: 'ไฟฟ้า', male: 130, female: 10, total: 140 },
        { level: 'ปวส.', department: 'เทคโนโลยีสารสนเทศ', male: 85, female: 55, total: 140 },
        { level: 'ปวส.', department: 'การบัญชี', male: 20, female: 135, total: 155 },
        { level: 'ปวส.', department: 'การจัดการโลจิสติกส์', male: 45, female: 100, total: 145 },
      ],
    },
    personnel: {
      total: 85,
      executives: 5,
      teachers: 58,
      support: 22,
      breakdown: [
        { category: 'ฝ่ายบริหาร', position: 'ผู้อำนวยการ และรองผู้อำนวยการ', count: 5, notes: 'ปริญญาเอก 2, ปริญญาโท 3' },
        { category: 'แผนกวิชาช่างยนต์', position: 'ครูผู้สอนประจำแผนกวิชา', count: 12, notes: 'ผู้เชี่ยวชาญยานยนต์ไฟฟ้า (EV) 4 คน' },
        { category: 'แผนกวิชาช่างไฟฟ้ากำลัง', position: 'ครูผู้สอนประจำแผนกวิชา', count: 10, notes: 'ผู้ทดสอบมาตรฐานฝีมือแรงงาน 5 คน' },
        { category: 'แผนกวิชาช่างอิเล็กทรอนิกส์', position: 'ครูผู้สอนประจำแผนกวิชา', count: 8, notes: 'IoT และหุ่นยนต์อุตสาหกรรม' },
        { category: 'แผนกวิชาเทคโนโลยีสารสนเทศ', position: 'ครูผู้สอนประจำแผนกวิชา', count: 9, notes: 'Cloud & Software Development' },
        { category: 'แผนกวิชาการบัญชี', position: 'ครูผู้สอนประจำแผนกวิชา', count: 8, notes: 'ERP & Digital Accounting' },
        { category: 'แผนกวิชาการตลาดและโลจิสติกส์', position: 'ครูผู้สอนประจำแผนกวิชา', count: 7, notes: 'E-Commerce & Supply Chain' },
        { category: 'แผนกวิชาสามัญสัมพันธ์', position: 'ครูผู้สอนหมวดวิชาสามัญ (ภาษา/วิทย์/คณิต)', count: 4, notes: 'ครูต่างชาติ 2 คน' },
        { category: 'บุคลากรทางการศึกษา', position: 'เจ้าหน้าที่สนับสนุนการปฏิบัติงาน/ธุรการ', count: 22, notes: 'ฝ่ายทะเบียน, การเงิน, พัสดุ, อาคารสถานที่' },
      ],
    },
    curriculum: {
      levelsCount: 2,
      departmentsCount: 8,
      programsCount: 14,
      list: [
        { level: 'ปวช.', department: 'ช่างยนต์', specialties: 'สาขางานยานยนต์, สาขางานยานยนต์ไฟฟ้า (EV)', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวช.', department: 'ช่างไฟฟ้ากำลัง', specialties: 'สาขางานไฟฟ้ากำลัง, สาขางานระบบควบคุมอัตโนมัติ', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวช.', department: 'ช่างอิเล็กทรอนิกส์', specialties: 'สาขางานอิเล็กทรอนิกส์, สาขางานอุปกรณ์การสื่อสาร', study_mode: 'ปกติ' },
        { level: 'ปวช.', department: 'เทคโนโลยีสารสนเทศ', specialties: 'สาขางานนักพัฒนาซอฟต์แวร์, สาขางานคอมพิวเตอร์กราฟิก', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวช.', department: 'การบัญชี', specialties: 'สาขางานการบัญชี', study_mode: 'ปกติ' },
        { level: 'ปวช.', department: 'การตลาด', specialties: 'สาขางานการตลาดดิจิทัล', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวส.', department: 'เทคนิคเครื่องกล', specialties: 'สาขางานเทคนิคยานยนต์, สาขางานยานยนต์ไฟฟ้าอัจฉริยะ', study_mode: 'ทวิภาคี 100%' },
        { level: 'ปวส.', department: 'ไฟฟ้า', specialties: 'สาขางานระบบควบคุมและระบบอัตโนมัติอุตสาหกรรม', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวส.', department: 'เทคโนโลยีสารสนเทศ', specialties: 'สาขางานเทคโนโลยีสารสนเทศและการสื่อสาร, Cloud Platform', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวส.', department: 'การบัญชี', specialties: 'สาขางานการบัญชีและระบบสารสนเทศทางการเงิน', study_mode: 'ปกติ' },
        { level: 'ปวส.', department: 'การจัดการโลจิสติกส์และซัพพลายเชน', specialties: 'สาขางานการจัดการคลังสินค้าและการขนส่งต่อเนื่อง', study_mode: 'ปกติ และ ทวิภาคี' },
      ],
    },
    admissionsGraduates: [
      { year: '2568', intake_pvch: 480, intake_pvs: 390, intake_total: 870, grad_pvch: 395, grad_pvs: 340, grad_total: 735, grad_rate: '95.8%' },
      { year: '2567', intake_pvch: 465, intake_pvs: 380, intake_total: 845, grad_pvch: 380, grad_pvs: 330, grad_total: 710, grad_rate: '94.2%' },
      { year: '2566', intake_pvch: 450, intake_pvs: 360, intake_total: 810, grad_pvch: 370, grad_pvs: 315, grad_total: 685, grad_rate: '93.6%' },
    ],
    achievements: {
      gpaxAvg: '3.28',
      gpaxPvch: '3.21',
      gpaxPvs: '3.36',
      vnetPassRate: '89.4',
      vocationalStandardPassRate: '96.2',
      list: [
        { department: 'ช่างยนต์', gpax: '3.15', vnet_score: '72.4%', standard_pass_rate: '98.5%' },
        { department: 'ช่างไฟฟ้ากำลัง', gpax: '3.20', vnet_score: '74.2%', standard_pass_rate: '97.2%' },
        { department: 'ช่างอิเล็กทรอนิกส์', gpax: '3.18', vnet_score: '71.8%', standard_pass_rate: '95.0%' },
        { department: 'เทคโนโลยีสารสนเทศ', gpax: '3.38', vnet_score: '82.5%', standard_pass_rate: '96.8%' },
        { department: 'การบัญชี', gpax: '3.42', vnet_score: '84.1%', standard_pass_rate: '99.0%' },
        { department: 'การตลาดและโลจิสติกส์', gpax: '3.25', vnet_score: '76.3%', standard_pass_rate: '94.5%' },
      ],
    },
    employment: {
      totalRate: '96.8',
      fieldRate: '62.5',
      otherRate: '14.3',
      higherEdRate: '15.0',
      freelanceRate: '5.0',
      unemployedRate: '3.2',
      data: [
        { category: 'มีงานทำตรงสาขาวิชาที่สำเร็จการศึกษา', count: 459, percent: '62.5%', avg_salary: '16,500 - 24,000' },
        { category: 'มีงานทำในสาขาวิชาอื่น / ภาคอุตสาหกรรม', count: 105, percent: '14.3%', avg_salary: '15,000 - 18,500' },
        { category: 'ศึกษาต่อในระดับปริญญาตรี / สถาบันอุดมศึกษา', count: 110, percent: '15.0%', avg_salary: '—' },
        { category: 'ประกอบอาชีพอิสระ / ผู้ประกอบการใหม่ (Startup)', count: 37, percent: '5.0%', avg_salary: '18,000 - 35,000' },
        { category: 'อยู่ระหว่างรอการเกณฑ์ทหาร / เตรียมตัวศึกษาต่อ', count: 24, percent: '3.2%', avg_salary: '—' },
      ],
    },
    partners: {
      mouCount: 54,
      dualVocationalCount: 42,
      internStudentsCount: 640,
      internAllowanceAvg: 10500,
      enterprises: [
        { logo_url: '/images/partners/toyota.svg', name: 'บริษัท โตโยต้า มอเตอร์ ประเทศไทย จำกัด', industry: 'ยานยนต์และชิ้นส่วน', cooperation_type: 'ทวิภาคี (DVE) & มอบทุนการศึกษา', departments: 'ช่างยนต์, ช่างไฟฟ้า' },
        { logo_url: '/images/partners/honda.svg', name: 'บริษัท ฮอนด้า ออโตโมบิล (ประเทศไทย) จำกัด', industry: 'ยานยนต์อัจฉริยะ', cooperation_type: 'ทวิภาคี & รับเข้าทำงานทันที', departments: 'ช่างยนต์, ช่างอิเล็กทรอนิกส์' },
        { logo_url: '/images/partners/delta.svg', name: 'บริษัท เดลต้า อีเลคโทรนิคส์ (ประเทศไทย) จำกัด (มหาชน)', industry: 'อิเล็กทรอนิกส์และพลังงานหมุนเวียน', cooperation_type: 'ศูนย์เรียนรู้ & ฝึกอาชีพทวิภาคี', departments: 'ช่างอิเล็กทรอนิกส์, ช่างไฟฟ้ากำลัง' },
        { logo_url: '/images/partners/mitsubishi.svg', name: 'บริษัท มิตซูบิชิ มอเตอร์ส (ประเทศไทย) จำกัด', industry: 'ยานยนต์และระบบอัตโนมัติ', cooperation_type: 'ทวิภาคีพรีเมียม (EEC Model)', departments: 'เทคนิคเครื่องกล, แมคคาทรอนิกส์' },
        { logo_url: '/images/partners/cpall.svg', name: 'บมจ. ซีพี ออลล์ (CP ALL PCL)', industry: 'ค้าปลีกและเทคโนโลยีสารสนเทศ', cooperation_type: 'ทวิภาคี & ทุนเรียนฟรีมีรายได้', departments: 'การตลาด, เทคโนโลยีสารสนเทศ' },
        { logo_url: '/images/partners/kerry.svg', name: 'บริษัท เคอรี่ เอ็กซ์เพรส (ประเทศไทย) จำกัด (มหาชน)', industry: 'การจัดการคลังสินค้าและโลจิสติกส์', cooperation_type: 'ฝึกงานและระบบบริหารจัดส่ง', departments: 'การจัดการโลจิสติกส์' },
        { logo_url: '/images/partners/wha.svg', name: 'บริษัท ดับบลิวเอชเอ อินดัสเตรียล ดีเวลลอปเมนท์ จำกัด (มหาชน)', industry: 'นิคมอุตสาหกรรมและสาธารณูปโภค', cooperation_type: 'MOU พัฒนากำลังคนเขต EEC', departments: 'ทุกสาขาวิชาช่างและบริหาร' },
        { logo_url: '/images/partners/bgrimm.svg', name: 'บริษัท บี.กริม เพาเวอร์ จำกัด (มหาชน)', industry: 'พลังงานสะอาดและวิศวกรรมไฟฟ้า', cooperation_type: 'ฝึกอาชีพช่างเทคนิคโรงไฟฟ้า', departments: 'ช่างไฟฟ้ากำลัง, ช่างยนต์' },
      ],
    },
    publishedAt: '2026-10-18T00:00:00+07:00',
  },
  {
    id: 37,
    title: 'ข้อมูลสารสนเทศทางการศึกษา ประจำปีการศึกษา 2567',
    slug: 'education-mis-2567',
    description: 'สารสนเทศทางการศึกษา วิทยาลัยเทคโนโลยีศรีราชา ประจำปีการศึกษา 2567',
    academicYear: { id: 82, name: 'ปีการศึกษา 2567', slug: '2567' },
    pdfUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    overview: {
      totalStudents: 1780,
      totalPersonnel: 82,
      programsCount: 13,
      employmentRate: '95.4',
      avgGpax: '3.22',
      partnerCount: 48,
    },
    students: {
      total: 1780,
      pvchTotal: 1080,
      pvsTotal: 700,
      byDepartment: [
        { level: 'ปวช.', department: 'ช่างยนต์', male: 200, female: 10, total: 210 },
        { level: 'ปวช.', department: 'ช่างไฟฟ้ากำลัง', male: 180, female: 12, total: 192 },
        { level: 'ปวช.', department: 'ช่างอิเล็กทรอนิกส์', male: 135, female: 15, total: 150 },
        { level: 'ปวช.', department: 'เทคโนโลยีสารสนเทศ', male: 105, female: 60, total: 165 },
        { level: 'ปวช.', department: 'การบัญชี', male: 22, female: 160, total: 182 },
        { level: 'ปวช.', department: 'การตลาด', male: 32, female: 135, total: 167 },
        { level: 'ปวส.', department: 'เทคนิคเครื่องกล (ช่างยนต์)', male: 140, female: 4, total: 144 },
        { level: 'ปวส.', department: 'ไฟฟ้า', male: 125, female: 8, total: 133 },
        { level: 'ปวส.', department: 'เทคโนโลยีสารสนเทศ', male: 80, female: 50, total: 130 },
        { level: 'ปวส.', department: 'การบัญชี', male: 18, female: 130, total: 148 },
        { level: 'ปวส.', department: 'การจัดการโลจิสติกส์', male: 42, female: 95, total: 137 },
      ],
    },
    personnel: {
      total: 82,
      executives: 5,
      teachers: 56,
      support: 21,
      breakdown: [
        { category: 'ฝ่ายบริหาร', position: 'ผู้อำนวยการ และรองผู้อำนวยการ', count: 5, notes: 'ปริญญาเอก 2, ปริญญาโท 3' },
        { category: 'ครูผู้สอนประจำแผนกวิชา', position: 'ครูประจำการสายผู้สอน', count: 56, notes: 'วุฒิปริญญาโท 24 คน, ปริญญาตรี 32 คน' },
        { category: 'บุคลากรทางการศึกษา', position: 'เจ้าหน้าที่สนับสนุน', count: 21, notes: 'ทุกฝ่ายงานสนับสนุน' },
      ],
    },
    curriculum: {
      levelsCount: 2,
      departmentsCount: 8,
      programsCount: 13,
      list: [
        { level: 'ปวช.', department: 'ช่างยนต์', specialties: 'สาขางานยานยนต์', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวช.', department: 'ช่างไฟฟ้ากำลัง', specialties: 'สาขางานไฟฟ้ากำลัง', study_mode: 'ปกติ และ ทวิภาคี' },
        { level: 'ปวช.', department: 'ช่างอิเล็กทรอนิกส์', specialties: 'สาขางานอิเล็กทรอนิกส์', study_mode: 'ปกติ' },
        { level: 'ปวช.', department: 'เทคโนโลยีสารสนเทศ', specialties: 'สาขางานคอมพิวเตอร์และสารสนเทศ', study_mode: 'ปกติ' },
        { level: 'ปวช.', department: 'การบัญชี', specialties: 'สาขางานการบัญชี', study_mode: 'ปกติ' },
        { level: 'ปวช.', department: 'การตลาด', specialties: 'สาขางานการตลาด', study_mode: 'ปกติ' },
      ],
    },
    admissionsGraduates: [
      { year: '2567', intake_pvch: 465, intake_pvs: 380, intake_total: 845, grad_pvch: 380, grad_pvs: 330, grad_total: 710, grad_rate: '94.2%' },
      { year: '2566', intake_pvch: 450, intake_pvs: 360, intake_total: 810, grad_pvch: 370, grad_pvs: 315, grad_total: 685, grad_rate: '93.6%' },
    ],
    achievements: {
      gpaxAvg: '3.22',
      gpaxPvch: '3.16',
      gpaxPvs: '3.30',
      vnetPassRate: '87.2',
      vocationalStandardPassRate: '95.1',
      list: [
        { department: 'ช่างยนต์', gpax: '3.10', vnet_score: '70.5%', standard_pass_rate: '97.0%' },
        { department: 'ช่างไฟฟ้ากำลัง', gpax: '3.15', vnet_score: '72.0%', standard_pass_rate: '96.0%' },
        { department: 'เทคโนโลยีสารสนเทศ', gpax: '3.32', vnet_score: '80.0%', standard_pass_rate: '95.5%' },
        { department: 'การบัญชี', gpax: '3.38', vnet_score: '82.5%', standard_pass_rate: '98.0%' },
      ],
    },
    employment: {
      totalRate: '95.4',
      fieldRate: '60.2',
      otherRate: '15.2',
      higherEdRate: '15.5',
      freelanceRate: '4.5',
      unemployedRate: '4.6',
      data: [
        { category: 'มีงานทำตรงสาขาวิชา', count: 427, percent: '60.2%', avg_salary: '15,500 - 22,000' },
        { category: 'มีงานทำในสาขาอื่น', count: 108, percent: '15.2%', avg_salary: '14,500 - 17,500' },
        { category: 'ศึกษาต่อระดับปริญญาตรี', count: 110, percent: '15.5%', avg_salary: '—' },
        { category: 'ประกอบอาชีพอิสระ', count: 32, percent: '4.5%', avg_salary: '16,000 - 30,000' },
        { category: 'อื่นๆ', count: 33, percent: '4.6%', avg_salary: '—' },
      ],
    },
    partners: {
      mouCount: 48,
      dualVocationalCount: 36,
      internStudentsCount: 590,
      internAllowanceAvg: 9800,
      enterprises: [
        { name: 'บริษัท โตโยต้า มอเตอร์ ประเทศไทย จำกัด', industry: 'ยานยนต์', cooperation_type: 'ทวิภาคี', departments: 'ช่างยนต์' },
        { name: 'บริษัท ฮอนด้า ออโตโมบิล (ประเทศไทย) จำกัด', industry: 'ยานยนต์', cooperation_type: 'ทวิภาคี', departments: 'ช่างยนต์' },
        { name: 'บริษัท เดลต้า อีเลคโทรนิคส์ จำกัด (มหาชน)', industry: 'อิเล็กทรอนิกส์', cooperation_type: 'ฝึกงาน', departments: 'ช่างอิเล็กทรอนิกส์' },
      ],
    },
    publishedAt: '2025-10-18T00:00:00+07:00',
  },
];

/**
 * Fetch Educational MIS list
 */
export async function getEducationMisList(options?: { year?: string; search?: string }): Promise<EducationMisItem[]> {
  const yearParam = options?.year?.trim();
  const searchParam = options?.search?.trim();
  const cacheKey = `education_mis_list_${yearParam || 'all'}_${searchParam || 'none'}`;
  const cached = getFromCache<EducationMisItem[]>(cacheKey);
  if (cached) return cached;

  try {
    const params = new URLSearchParams();
    if (yearParam && yearParam !== 'all') params.set('year', yearParam);
    if (searchParam) params.set('search', searchParam);

    const queryString = params.toString();
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/education-mis${queryString ? `?${queryString}` : ''}`;

    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (data && Array.isArray(data.items) && data.items.length > 0) {
        setInCache(cacheKey, data.items);
        return data.items;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch Educational MIS items from WordPress:', err);
  }

  // Filter fallback mock data
  let results = [...LOCAL_MOCK_EDUCATION_MIS];
  if (yearParam && yearParam !== 'all') {
    results = results.filter((r) => r.academicYear?.slug === yearParam || r.academicYear?.name === yearParam);
  }
  if (searchParam) {
    const s = searchParam.toLowerCase();
    results = results.filter((r) => r.title.toLowerCase().includes(s) || (r.description && r.description.toLowerCase().includes(s)));
  }
  return results;
}

/**
 * Fetch Educational MIS academic years
 */
export async function getEducationMisYears(): Promise<EducationMisYear[]> {
  const cacheKey = 'education_mis_years';
  const cached = getFromCache<EducationMisYear[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/education-mis-years`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        setInCache(cacheKey, data);
        return data;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch Educational MIS years:', err);
  }

  return LOCAL_MOCK_MIS_YEARS;
}

/**
 * Fetch a single Educational MIS item for a specific year or latest
 */
export async function getEducationMis(year?: string): Promise<EducationMisItem | null> {
  const list = await getEducationMisList({ year });
  if (list && list.length > 0) {
    return list[0];
  }
  return null;
}

// ==========================================
// Organization Structure (โครงสร้างการบริหาร)
// ==========================================

const LOCAL_MOCK_ORGANIZATION: OrganizationData = {
  settings: {
    page_title: 'โครงสร้างการบริหาร',
    page_description: 'รู้จักผู้บริหารและหน่วยงานที่ร่วมขับเคลื่อนวิทยาลัยเทคโนโลยีศรีราชา',
    academic_year: '2569',
    updated_at: '2026-10-18',
    pdf_url: '',
    committee_title: 'คณะกรรมการสถานศึกษา',
    committee_desc: 'ร่วมกำกับ ดูแล และส่งเสริมมาตรฐานคุณภาพการจัดการศึกษา',
    committee_link: '',
  },
  leaders: [
    {
      id: 'mock_1',
      position: 'ผู้รับใบอนุญาต',
      type: 'executive',
      sub: 'ผู้บริหารวิทยาลัย',
      remark: '',
      status: 'active',
      personnel: { name: 'ชื่อ–นามสกุล', photo: '' },
    },
    {
      id: 'mock_2',
      position: 'ผู้จัดการ',
      type: 'executive',
      sub: 'ผู้บริหารวิทยาลัย',
      remark: '',
      status: 'active',
      personnel: { name: 'ชื่อ–นามสกุล', photo: '' },
    },
    {
      id: 'mock_3',
      position: 'ผู้อำนวยการวิทยาลัย',
      type: 'executive',
      sub: 'ผู้อำนวยการ',
      remark: '',
      status: 'active',
      personnel: { name: 'ชื่อ–นามสกุล', photo: '' },
    },
  ],
  committee: {
    id: 'mock_comm',
    position: 'คณะกรรมการสถานศึกษา',
    type: 'committee',
    sub: 'ร่วมกำกับ ดูแล และส่งเสริมมาตรฐานคุณภาพการจัดการศึกษา',
    remark: '',
    status: 'active',
    personnel: { name: 'คณะกรรมการสถานศึกษา', photo: '' },
  },
  divisions: [
    {
      id: 'mock_div_1',
      slug: 'resource-management',
      title: 'ฝ่ายบริหารทรัพยากร',
      color: '#278bb4',
      order: 1,
      description: 'งานบริหารงานทั่วไป งานบุคคล การเงิน อาคารสถานที่ และทะเบียน',
      head: {
        position: 'ฝ่ายบริหารทรัพยากร',
        sub: 'ผู้บริหารฝ่าย',
        personnel: { name: 'ชื่อ–นามสกุล', photo: '' },
      },
      jobs: [
        { id: 'job_1_1', title: 'งานบริหารงานทั่วไป', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_1_2', title: 'งานบริหารและพัฒนาทรัพยากรบุคคล', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_1_3', title: 'งานการเงิน งานบัญชี และพัสดุ', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_1_4', title: 'งานอาคารสถานที่', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_1_5', title: 'งานทะเบียน', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
      ],
    },
    {
      id: 'mock_div_2',
      slug: 'strategic-planning',
      title: 'ฝ่ายยุทธศาสตร์และแผนงาน',
      color: '#7372b3',
      order: 2,
      description: 'งานยุทธศาสตร์ แผนงาน ประกันคุณภาพ ศูนย์ข้อมูล และส่งเสริมธุรกิจ',
      head: {
        position: 'ฝ่ายยุทธศาสตร์และแผนงาน',
        sub: 'ผู้บริหารฝ่าย',
        personnel: { name: 'ชื่อ–นามสกุล', photo: '' },
      },
      jobs: [
        { id: 'job_2_1', title: 'งานพัฒนายุทธศาสตร์ แผนงานและงบประมาณ', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_2_2', title: 'งานมาตรฐานและการประกันคุณภาพการศึกษา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_2_3', title: 'งานศูนย์สารสนเทศและสื่อสารองค์กร', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_2_4', title: 'งานส่งเสริมการวิจัย นวัตกรรม และสิ่งประดิษฐ์', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_2_5', title: 'งานส่งเสริมธุรกิจและการเป็นผู้ประกอบการ', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_2_6', title: 'งานติดตามและประเมินผลการจัดการศึกษา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
      ],
    },
    {
      id: 'mock_div_3',
      slug: 'student-affairs',
      title: 'ฝ่ายกิจการนักเรียน นักศึกษา',
      color: '#348a7a',
      order: 3,
      description: 'งานกิจกรรม ครูที่ปรึกษา ปกครอง สวัสดิการ และโครงการพิเศษ',
      head: {
        position: 'ฝ่ายกิจการนักเรียน นักศึกษา',
        sub: 'ผู้บริหารฝ่าย',
        personnel: { name: 'ชื่อ–นามสกุล', photo: '' },
      },
      jobs: [
        { id: 'job_3_1', title: 'งานกิจกรรมนักเรียน นักศึกษา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_3_2', title: 'งานครูที่ปรึกษาและการแนะแนว', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_3_3', title: 'งานปกครองและความปลอดภัยนักเรียน นักศึกษา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_3_4', title: 'งานสวัสดิการนักเรียน นักศึกษา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_3_5', title: 'งานโครงการพิเศษและการบริการ', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
      ],
    },
    {
      id: 'mock_div_4',
      slug: 'academic-affairs',
      title: 'ฝ่ายวิชาการ',
      color: '#bd9052',
      order: 4,
      description: 'แผนกวิชา หลักสูตร วัดผล ทวิภาคี วิทยบริการ และการทดสอบ',
      head: {
        position: 'ฝ่ายวิชาการ',
        sub: 'ผู้บริหารฝ่าย',
        personnel: { name: 'ชื่อ–นามสกุล', photo: '' },
      },
      jobs: [
        { id: 'job_4_1', title: 'แผนกวิชา / สาขาวิชา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_4_2', title: 'งานพัฒนาหลักสูตรและการจัดการเรียนรู้', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_4_3', title: 'งานวัดผลและประเมินผล', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_4_4', title: 'งานอาชีวศึกษาระบบทวิภาคีและความร่วมมือ', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_4_5', title: 'งานวิทยบริการและเทคโนโลยีการศึกษา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
        { id: 'job_4_6', title: 'งานการจัดการศึกษาและทดสอบทางการศึกษา', personnel: { name: 'ชื่อ–นามสกุล', photo: '' } },
      ],
    },
  ],
};

/**
 * Fetch Organization Structure Data from WordPress Headless REST API
 */
export async function getOrganizationData(): Promise<OrganizationData> {
  const cacheKey = 'org_structure_data';
  const cached = getFromCache<OrganizationData>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/organization`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (data && data.divisions && Array.isArray(data.divisions)) {
        setInCache(cacheKey, data);
        return data as OrganizationData;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch organization data:', err);
  }

  return LOCAL_MOCK_ORGANIZATION;
}

/**
 * Download Portal Data Types & API
 */
export interface DownloadCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  count?: number;
  order: number;
}

export interface DownloadItem {
  id: number;
  title: string;
  slug?: string;
  description?: string;
  category?: DownloadCategory;
  sourceType: 'file' | 'link' | string;
  fileUrl?: string;
  externalUrl?: string;
  fileType: 'pdf' | 'docx' | 'doc' | 'xlsx' | 'xls' | 'pptx' | 'ppt' | 'zip' | 'rar' | 'image' | 'video' | 'link' | string;
  fileSize?: string;
  version?: string;
  coverUrl?: string;
  order: number;
  publishedAt?: string;
}

export const FALLBACK_DOWNLOAD_CATEGORIES: DownloadCategory[] = [
  { id: 1, name: 'แบบฟอร์มคำร้องทั่วไป', slug: 'forms', description: 'แบบฟอร์มคำร้องสำหรับนักเรียน นักศึกษา และผู้ปกครอง', count: 3, order: 1 },
  { id: 2, name: 'ระเบียบปฏิบัติและข้อบังคับ', slug: 'regulations', description: 'ระเบียบวิทยาลัย ข้อบังคับ และประกาศสำคัญ', count: 2, order: 2 },
  { id: 3, name: 'คู่มือนักศึกษาและหลักสูตร', slug: 'handbook', description: 'คู่มือการเรียน แผนการเรียน และแนวปฏิบัติการศึกษา', count: 2, order: 3 },
  { id: 4, name: 'เอกสารสำหรับครูและบุคลากร', slug: 'staff', description: 'แบบฟอร์มฝ่ายบุคคล แบบฟอร์มขออนุมัติ และเทมเพลตงานวิทยาลัย', count: 3, order: 4 },
];

export const FALLBACK_DOWNLOADS: DownloadItem[] = [
  {
    id: 101,
    title: 'แบบฟอร์มคำร้องทั่วไป (วทศ.01)',
    slug: 'form-general-request-01',
    description: 'แบบฟอร์มสำหรับนักเรียนนักศึกษาใช้ในการยื่นคำร้องขอเอกสารทางการศึกษา ขอหนังสือรับรอง หรือคำร้องทั่วไป',
    category: FALLBACK_DOWNLOAD_CATEGORIES[0],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'pdf',
    fileSize: '245 KB',
    version: 'แบบฟอร์ม วทศ.01',
    order: 1,
    publishedAt: '2026-10-15T08:30:00Z',
  },
  {
    id: 102,
    title: 'แบบฟอร์มขอผ่อนผันค่าธรรมเนียมการศึกษา',
    slug: 'form-tuition-deferral',
    description: 'แบบฟอร์มสำหรับผู้ปกครองและนักศึกษาที่มีความจำเป็นต้องขอผ่อนผันการชำระเงินค่าเล่าเรียน',
    category: FALLBACK_DOWNLOAD_CATEGORIES[0],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'pdf',
    fileSize: '198 KB',
    version: 'แบบฟอร์ม วทศ.02',
    order: 2,
    publishedAt: '2026-10-12T09:00:00Z',
  },
  {
    id: 103,
    title: 'แบบคำร้องขอลงทะเบียนเรียนล่าช้า / เพิ่ม-ถอนรายวิชา',
    slug: 'form-course-add-drop',
    description: 'แบบฟอร์มขอเพิ่มหรือถอนรายวิชา และคำร้องขอลงทะเบียนเรียนเกินกำหนดเวลาที่แผนกทะเบียนกำหนด',
    category: FALLBACK_DOWNLOAD_CATEGORIES[0],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'docx',
    fileSize: '142 KB',
    version: 'แบบฟอร์ม วทศ.03',
    order: 3,
    publishedAt: '2026-10-10T14:20:00Z',
  },
  {
    id: 104,
    title: 'ระเบียบวิทยาลัยว่าด้วยเครื่องแบบ วินัย และความประพฤตินักศึกษา',
    slug: 'regulations-uniform-discipline',
    description: 'ข้อบังคับและแนวปฏิบัติว่าด้วยการแต่งกาย ระเบียบวินัย และการตัดคะแนนความประพฤติของนักเรียนนักศึกษา',
    category: FALLBACK_DOWNLOAD_CATEGORIES[1],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'pdf',
    fileSize: '1.2 MB',
    version: 'ฉบับปรับปรุง 2568',
    order: 1,
    publishedAt: '2026-09-25T11:00:00Z',
  },
  {
    id: 105,
    title: 'แนวปฏิบัติและระเบียบการฝึกประสบการณ์ทักษะวิชาชีพในสถานประกอบการ',
    slug: 'regulations-internship-guidelines',
    description: 'คู่มือและข้อปฏิบัติตนสำหรับนักศึกษาระดับ ปวช. และ ปวส. ในระหว่างการฝึกงานในโรงงานและสถานประกอบการ',
    category: FALLBACK_DOWNLOAD_CATEGORIES[1],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'pdf',
    fileSize: '890 KB',
    version: 'ฉบับปี 2568',
    order: 2,
    publishedAt: '2026-09-20T10:15:00Z',
  },
  {
    id: 106,
    title: 'คู่มือนักศึกษาใหม่และผู้ปกครอง ประจำปีการศึกษา',
    slug: 'handbook-new-students',
    description: 'ข้อมูลสำคัญสำหรับนักศึกษาเข้าใหม่ แนะนำอาคารสถานที่ อาจารย์ที่ปรึกษา และระบบบริการการศึกษา',
    category: FALLBACK_DOWNLOAD_CATEGORIES[2],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'pdf',
    fileSize: '4.5 MB',
    version: 'Student Handbook',
    order: 1,
    publishedAt: '2026-08-30T09:30:00Z',
  },
  {
    id: 107,
    title: 'ตารางและแผนการเรียนโครงสร้างหลักสูตร ปวช. และ ปวส.',
    slug: 'curriculum-plan-sheet',
    description: 'ตารางสรุปโครงสร้างหน่วยกิต รายวิชาบังคับ และวิชาเลือก เพื่อการวางแผนการศึกษาตลอดหลักสูตร',
    category: FALLBACK_DOWNLOAD_CATEGORIES[2],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'xlsx',
    fileSize: '320 KB',
    version: 'ไฟล์ Excel สำหรับวางแผน',
    order: 2,
    publishedAt: '2026-08-20T13:45:00Z',
  },
  {
    id: 108,
    title: 'แบบฟอร์มขออนุมัติไปราชการและอบรมสัมมนาบุคลากร',
    slug: 'staff-travel-training-form',
    description: 'แบบฟอร์มสำหรับคณาจารย์และบุคลากรใช้ขออนุมัติเข้ารับการอบรม สัมมนาทางวิชาการ หรือปฏิบัติราชการภายนอก',
    category: FALLBACK_DOWNLOAD_CATEGORIES[3],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'docx',
    fileSize: '115 KB',
    version: 'แบบฟอร์ม ทส.05',
    order: 1,
    publishedAt: '2026-10-05T15:00:00Z',
  },
  {
    id: 109,
    title: 'เทมเพลตสไลด์นำเสนองานทางการของวิทยาลัย (PowerPoint)',
    slug: 'staff-presentation-slide-template',
    description: 'ไฟล์เทมเพลตสไลด์บรรยาย Presentation ธีมสีฟ้า-ขาว ตราสัญลักษณ์ทางการ สำหรับใช้ในงานวิชาการและพิธีการ',
    category: FALLBACK_DOWNLOAD_CATEGORIES[3],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'pptx',
    fileSize: '3.8 MB',
    version: 'S-TECH Official Template 16:9',
    order: 2,
    publishedAt: '2026-09-18T16:20:00Z',
  },
  {
    id: 110,
    title: 'ชุดตราสัญลักษณ์และโลโก้วิทยาลัยความละเอียดสูง (Press & Media Kit)',
    slug: 'staff-college-logo-press-kit',
    description: 'รวมไฟล์ตราสัญลักษณ์วิทยาลัยเทคโนโลยีศรีราชาครบทุกเฉดสี ทั้งแบบสีทอง สีเงิน และสีขาว สำหรับสื่อสิ่งพิมพ์และกราฟิก',
    category: FALLBACK_DOWNLOAD_CATEGORIES[3],
    sourceType: 'file',
    fileUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    externalUrl: '',
    fileType: 'zip',
    fileSize: '8.2 MB',
    version: 'Logo Pack (PNG, SVG, AI)',
    order: 3,
    publishedAt: '2026-09-01T10:00:00Z',
  },
];

/**
 * Fetch all Download categories
 */
export async function getDownloadCategories(): Promise<DownloadCategory[]> {
  const cacheKey = 'download_categories';
  const cached = getFromCache<DownloadCategory[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/download-categories`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        setInCache(cacheKey, data);
        return data as DownloadCategory[];
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch download categories:', err);
  }

  return FALLBACK_DOWNLOAD_CATEGORIES;
}

/**
 * Fetch Downloads list with category filter and search
 */
export async function getDownloads(category?: string, search?: string): Promise<DownloadItem[]> {
  const cacheKey = `downloads_${category || 'all'}_${search || ''}`;
  const cached = getFromCache<DownloadItem[]>(cacheKey);
  if (cached) return cached;

  try {
    const params = new URLSearchParams();
    if (category && category !== 'all') params.set('category', category);
    if (search) params.set('search', search);

    const queryStr = params.toString() ? `?${params.toString()}` : '';
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/downloads${queryStr}`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        setInCache(cacheKey, data);
        return data as DownloadItem[];
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch downloads:', err);
  }

  // Filter fallback data if parameters provided
  let filtered = [...FALLBACK_DOWNLOADS];
  if (category && category !== 'all') {
    filtered = filtered.filter(item => item.category?.slug === category);
  }
  if (search) {
    const q = search.toLowerCase();
    filtered = filtered.filter(item =>
      item.title.toLowerCase().includes(q) ||
      (item.description && item.description.toLowerCase().includes(q)) ||
      (item.version && item.version.toLowerCase().includes(q))
    );
  }

  return filtered;
}

// ==========================================
// 12. STUDENT HANDBOOK & REGULATIONS (CPT)
// ==========================================

export interface HandbookCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  count?: number;
  order?: number;
}

export interface StudentHandbook {
  id: number;
  title: string;
  slug: string;
  content: string;
  excerpt?: string;
  category?: HandbookCategory;
  badge?: string;
  effectiveDate?: string;
  pdfUrl?: string;
  coverUrl?: string;
  order?: number;
  publishedAt?: string;
  updatedAt?: string;
}

/**
 * Fetch all handbook categories from WordPress REST API
 */
export async function getHandbookCategories(): Promise<HandbookCategory[]> {
  const cacheKey = 'wp:handbook:categories';
  const cached = getFromCache<HandbookCategory[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/handbook-categories`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        setInCache(cacheKey, data);
        return data as HandbookCategory[];
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch handbook categories:', err);
  }

  return [];
}

/**
 * Fetch student handbooks list from WordPress REST API
 */
export async function getStudentHandbooks(category?: string, search?: string): Promise<StudentHandbook[]> {
  const cacheKey = `wp:handbook:list:${category || 'all'}:${search || 'all'}`;
  const cached = getFromCache<StudentHandbook[]>(cacheKey);
  if (cached) return cached;

  try {
    const params = new URLSearchParams();
    if (category && category !== 'all') params.set('category', category);
    if (search) params.set('search', search);

    const queryStr = params.toString() ? `?${params.toString()}` : '';
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/student-handbooks${queryStr}`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data)) {
        setInCache(cacheKey, data);
        return data as StudentHandbook[];
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch student handbooks:', err);
  }

  return [];
}

/**
 * Fetch a single student handbook by slug from WordPress REST API
 */
export async function getStudentHandbookBySlug(slug: string): Promise<StudentHandbook | null> {
  const cacheKey = `wp:handbook:single:${slug}`;
  const cached = getFromCache<StudentHandbook>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/student-handbooks/${encodeURIComponent(slug)}`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (data && data.id) {
        setInCache(cacheKey, data);
        return data as StudentHandbook;
      }
    }
  } catch (err) {
    console.warn(`[WordPress API] Unable to fetch handbook '${slug}':`, err);
  }

  // Fallback: check all handbooks if single endpoint missed
  const all = await getStudentHandbooks();
  const found = all.find(item => item.slug === slug);
  return found || null;
}

// -------------------------------------------------------------
// Open House (วันวิชาการ) API Functions & Fallbacks
// -------------------------------------------------------------

const FALLBACK_OPENHOUSE_INFO: OpenHouseInfo = {
  title: 'งานสัปดาห์วันวิชาการและเปิดบ้าน Open House 2026',
  tagline: 'จุดประกายความคิด สร้างสรรค์นวัตกรรมสู่อาชีพแห่งอนาคต วิทยาลัยเทคโนโลยีศรีราชา',
  logoUrl: '',
  bannerUrl: '',
  dates: '15 - 16 มกราคม 2569 เวลา 08:30 - 16:30 น.',
  venue: 'วิทยาลัยเทคโนโลยีศรีราชา อ.ศรีราชา จ.ชลบุรี',
  description: 'ขอเชิญนักเรียน นักศึกษา ครู อาจารย์ และผู้สนใจ เข้าร่วมงานวันวิชาการและการแข่งขันทักษะวิชาการ-วิชาชีพ ชมนิทรรศการผลงานสิ่งประดิษฐ์และนวัตกรรม พร้อมร่วมกิจกรรมเวิร์กช็อปจากทุกสาขาวิชา',
  contactName: 'ฝ่ายวิชาการและงานประชาสัมพันธ์',
  contactPhone: '038-352440',
  contactLine: '@sriracha',
  registrationUrl: 'https://forms.gle/openhouse-technosrc',
  livestreamUrl: '',
};

const FALLBACK_OPENHOUSE_CATEGORIES: OpenHouseCategory[] = [
  { id: 1, name: 'การแข่งขันทักษะวิชาการ', slug: 'academic-competition', count: 3 },
  { id: 2, name: 'การแข่งขันทักษะวิชาชีพ', slug: 'vocational-competition', count: 3 },
  { id: 3, name: 'นิทรรศการผลงานและนวัตกรรม', slug: 'exhibition', count: 2 },
  { id: 4, name: 'กิจกรรมเวิร์กช็อปและเปิดบ้านสาขาวิชา', slug: 'workshop', count: 2 },
];

const FALLBACK_OPENHOUSE_ACTIVITIES: OpenHouseActivity[] = [
  {
    id: 1,
    title: 'การแข่งขันทักษะการเขียนโปรแกรมคอมพิวเตอร์และเว็บแอปพลิเคชัน',
    slug: 'coding-competition',
    content: '<p>การแข่งขันพัฒนาเว็บแอปพลิเคชันแก้ปัญหาชุมชนหรือวิทยาลัย สำหรับนักเรียนระดับมัธยมศึกษาตอนปลายและ ปวช. ทีมละ 2 คน</p>',
    excerpt: 'การแข่งขันพัฒนาเว็บแอปพลิเคชันแก้ปัญหาชุมชนหรือวิทยาลัย ทีมละ 2 คน แข่งขัน 3 ชั่วโมง',
    coverUrl: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&auto=format&fit=crop&q=80',
    category: { id: 2, name: 'การแข่งขันทักษะวิชาชีพ', slug: 'vocational-competition' },
    venue: 'อาคารปฏิบัติการเทคโนโลยีสารสนเทศ ชั้น 3 ห้อง Lab 302',
    dateTime: '15 มกราคม 2569 เวลา 09:00 - 12:00 น.',
    rulesSummary: '1. ทีมละ 2 คน สังกัดสถานศึกษาเดียวกัน\n2. อุปกรณ์: วิทยาลัยจัดเตรียมเครื่องคอมพิวเตอร์และเครื่องมือให้\n3. การตัดสินของคณะกรรมการผู้ทรงคุณวุฒิถือเป็นที่สิ้นสุด',
    rulesPdfUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    hasRulesPdf: true,
    registrationUrl: 'https://forms.gle/coding-competition-2026',
    hasRegistration: true,
    photosUrl: 'https://drive.google.com/',
    hasPhotos: true,
    certificateUrl: '',
    hasCertificate: false,
    contact: 'อ.วิชัย เทคโนโลยี โทร. 081-234-5678',
    order: 1,
  },
  {
    id: 2,
    title: 'การแข่งขันทักษะหุ่นยนต์และระบบควบคุมอัตโนมัติ (Robotics Challenge)',
    slug: 'robotics-competition',
    content: '<p>การแข่งขันประดิษฐ์และบังคับหุ่นยนต์เดินตามเส้นและภารกิจกู้ภัยชิงถ้วยเกียรติยศ</p>',
    excerpt: 'ประดิษฐ์และบังคับหุ่นยนต์เดินตามเส้นและภารกิจกู้ภัย ชิงรางวัลและเกียรติบัตร',
    coverUrl: 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800&auto=format&fit=crop&q=80',
    category: { id: 2, name: 'การแข่งขันทักษะวิชาชีพ', slug: 'vocational-competition' },
    venue: 'อาคารช่างอุตสาหกรรม ลานประลองหุ่นยนต์ ชั้น 1',
    dateTime: '15 มกราคม 2569 เวลา 13:00 - 16:30 น.',
    rulesSummary: '1. ทีมละไม่เกิน 3 คน\n2. ขนาดหุ่นยนต์ไม่เกิน 20x20x20 ซม. น้ำหนักไม่เกิน 1.5 กก.\n3. ใช้ระบบแบตเตอรี่ไม่เกิน 12V',
    rulesPdfUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    hasRulesPdf: true,
    registrationUrl: 'https://forms.gle/robotics-2026',
    hasRegistration: true,
    photosUrl: '',
    hasPhotos: false,
    certificateUrl: '',
    hasCertificate: false,
    contact: 'อ.เกรียงไกร เมคคาทรอนิกส์ โทร. 089-876-5432',
    order: 2,
  },
  {
    id: 3,
    title: 'การแข่งขันทักษะภาษาอังกฤษเพื่อการสื่อสารในสถานประกอบการ (English for Work)',
    slug: 'english-speech-contest',
    content: '<p>การแข่งขันนำเสนอและตอบคำถามภาษาอังกฤษเชิงธุรกิจ Business Presentation & Impromptu Speech</p>',
    excerpt: 'แข่งขันนำเสนอไอเดียธุรกิจและตอบคำถามภาษาอังกฤษระดับนานาชาติ',
    coverUrl: 'https://images.unsplash.com/photo-1577495508048-b635879837f1?w=800&auto=format&fit=crop&q=80',
    category: { id: 1, name: 'การแข่งขันทักษะวิชาการ', slug: 'academic-competition' },
    venue: 'ห้องประชุมสัมมนาวิชาการ อาคาร 1 ชั้น 4',
    dateTime: '16 มกราคม 2569 เวลา 09:00 - 12:00 น.',
    rulesSummary: '1. แข่งขันประเภทเดี่ยว (1 คนต่อสถาบัน)\n2. เวลาการนำเสนอไม่เกิน 5 นาที และตอบคำถามกรรมการ 3 นาที',
    rulesPdfUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    hasRulesPdf: true,
    registrationUrl: 'https://forms.gle/english-contest-2026',
    hasRegistration: true,
    photosUrl: '',
    hasPhotos: false,
    certificateUrl: 'https://drive.google.com/',
    hasCertificate: true,
    contact: 'อ.พรทิพย์ ภาษาต่างประเทศ โทร. 086-555-4321',
    order: 3,
  },
  {
    id: 4,
    title: 'นิทรรศการผลงานสิ่งประดิษฐ์และนวัตกรรมคนรุ่นใหม่ (Techno Innovation Expo)',
    slug: 'innovation-expo',
    content: '<p>จัดแสดงผลงานสิ่งประดิษฐ์จากโครงงานนักศึกษาทุกสาขาวิชา พร้อมเปิดให้ผู้เข้าชมร่วมโหวตรางวัล Popular Vote</p>',
    excerpt: 'ชมสุดยอดผลงานสิ่งประดิษฐ์และนวัตกรรม พร้อมโหวตรางวัล Popular Vote',
    coverUrl: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=800&auto=format&fit=crop&q=80',
    category: { id: 3, name: 'นิทรรศการผลงานและนวัตกรรม', slug: 'exhibition' },
    venue: 'ลานกิจกรรมโดมอเนกประสงค์ ชั้น 1',
    dateTime: '15 - 16 มกราคม 2569 ตลอดทั้งวัน (08:30 - 16:30 น.)',
    rulesSummary: 'เปิดให้ผู้ร่วมงานทุกคนเข้าชมนิทรรศการและร่วมสนุกกับบูธกิจกรรมฟรี ไม่มีค่าใช้จ่าย',
    rulesPdfUrl: '',
    hasRulesPdf: false,
    registrationUrl: '',
    hasRegistration: false,
    photosUrl: '',
    hasPhotos: false,
    certificateUrl: '',
    hasCertificate: false,
    contact: 'งานวิจัยและพัฒนานวัตกรรม โทร. 038-352440 ต่อ 112',
    order: 4,
  },
  {
    id: 5,
    title: 'เวิร์กช็อปสร้างสื่อสร้างสรรค์และคอนเทนต์ครีเอเตอร์ (AI & Digital Media Workshop)',
    slug: 'ai-digital-media-workshop',
    content: '<p>เรียนรู้เทคนิคการใช้ Generative AI ในการผลิตสื่อ กราฟิก และตัดต่อวิดีโอสั้นแบบมืออาชีพ</p>',
    excerpt: 'เวิร์กช็อป 3 ชั่วโมง ลงมือทำจริงกับการใช้ AI ช่วยผลิตคอนเทนต์ยุคใหม่',
    coverUrl: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&auto=format&fit=crop&q=80',
    category: { id: 4, name: 'กิจกรรมเวิร์กช็อปและเปิดบ้านสาขาวิชา', slug: 'workshop' },
    venue: 'ห้องดิจิทัลสตูดิโอ อาคาร 2 ชั้น 2',
    dateTime: '16 มกราคม 2569 รอบเช้า 09:30 น. และ รอบบ่าย 13:30 น.',
    rulesSummary: 'รับจำนวนจำกัดรอบละ 30 ที่นั่ง ฟรีอุปกรณ์และเอกสารประกอบการฝึกอบรม',
    rulesPdfUrl: '',
    hasRulesPdf: false,
    registrationUrl: 'https://forms.gle/ai-workshop-2026',
    hasRegistration: true,
    photosUrl: '',
    hasPhotos: false,
    certificateUrl: '',
    hasCertificate: false,
    contact: 'สาขาวิชาคอมพิวเตอร์กราฟิก โทร. 082-999-1122',
    order: 5,
  },
  {
    id: 6,
    title: 'การแข่งขันทักษะการเขียนแบบวิศวกรรมด้วยคอมพิวเตอร์ (AutoCAD / SolidWorks)',
    slug: 'cad-cam-competition',
    content: '<p>การแข่งขันเขียนแบบ 2D และ 3D Modeling ชิ้นส่วนเครื่องกลตามโจทย์ที่กำหนด</p>',
    excerpt: 'ทดสอบทักษะการออกแบบและเขียนแบบ 3D Modeling ระดับมาตรฐานสากล',
    coverUrl: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800&auto=format&fit=crop&q=80',
    category: { id: 2, name: 'การแข่งขันทักษะวิชาชีพ', slug: 'vocational-competition' },
    venue: 'ห้องปฏิบัติการ CAD/CAM อาคารช่างกล ชั้น 2',
    dateTime: '15 มกราคม 2569 เวลา 09:30 - 12:30 น.',
    rulesSummary: '1. ประเภทเดี่ยว ผู้เข้าแข่งขันต้องมีความรู้การอ่านแบบวิศวกรรม\n2. วิทยาลัยจัดเตรียมเครื่องคอมพิวเตอร์พร้อมโปรแกรม',
    rulesPdfUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    hasRulesPdf: true,
    registrationUrl: 'https://forms.gle/cad-competition-2026',
    hasRegistration: true,
    photosUrl: '',
    hasPhotos: false,
    certificateUrl: '',
    hasCertificate: false,
    contact: 'สาขาวิชาเขียนแบบเครื่องกล โทร. 083-444-5566',
    order: 6,
  },
  {
    id: 7,
    title: 'การแข่งขันทักษะแผนธุรกิจสตาร์ทอัพรุ่นใหม่ (Young Business Innovation Pitching)',
    slug: 'business-pitching',
    content: '<p>การประกวดนำเสนอแผนธุรกิจนวัตกรรมและสตาร์ทอัพ ชิงเงินรางวัลทุนตั้งต้นธุรกิจ</p>',
    excerpt: 'ประชันไอเดียธุรกิจนวัตกรรม Pitching 5 นาที ต่อหน้าคณะกรรมการนักลงทุน',
    coverUrl: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop&q=80',
    category: { id: 1, name: 'การแข่งขันทักษะวิชาการ', slug: 'academic-competition' },
    venue: 'ห้องประชุม Auditorium อาคารอำนวยการ ชั้น 3',
    dateTime: '16 มกราคม 2569 เวลา 13:00 - 16:30 น.',
    rulesSummary: '1. ทีมละ 2-3 คน\n2. นำเสนอสไลด์ Pitch Deck 5 นาที และตอบคำถามกรรมการ 3 นาที',
    rulesPdfUrl: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    hasRulesPdf: true,
    registrationUrl: 'https://forms.gle/pitching-2026',
    hasRegistration: true,
    photosUrl: '',
    hasPhotos: false,
    certificateUrl: '',
    hasCertificate: false,
    contact: 'สาขาวิชาการตลาดและการจัดการ โทร. 085-111-2233',
    order: 7,
  },
  {
    id: 8,
    title: 'เวิร์กช็อปซ่อมบำรุงและวินิจฉัยยานยนต์ไฟฟ้าสมัยใหม่ (EV Diagnosis Workshop)',
    slug: 'ev-workshop',
    content: '<p>สัมผัสเทคโนโลยียานยนต์ไฟฟ้า EV การตรวจเช็กระบบความปลอดภัยและระบบขับเคลื่อนมอเตอร์ไฟฟ้า</p>',
    excerpt: 'เวิร์กช็อปทดลองปฏิบัติการตรวจสอบระบบความปลอดภัยยานยนต์ไฟฟ้าจริง',
    coverUrl: 'https://images.unsplash.com/photo-1581092335397-9583fe92d232?w=800&auto=format&fit=crop&q=80',
    category: { id: 4, name: 'กิจกรรมเวิร์กช็อปและเปิดบ้านสาขาวิชา', slug: 'workshop' },
    venue: 'ศูนย์ฝึกอบรมเทคโนโลยียานยนต์ไฟฟ้า EV Center',
    dateTime: '15 มกราคม 2569 รอบบ่าย 13:30 - 16:00 น.',
    rulesSummary: 'เปิดรับนักเรียน ม.ต้น และ ม.ปลาย ที่สนใจเทคโนโลยียานยนต์ไฟฟ้า ไม่จำกัดสายการเรียน',
    rulesPdfUrl: '',
    hasRulesPdf: false,
    registrationUrl: 'https://forms.gle/ev-workshop-2026',
    hasRegistration: true,
    photosUrl: '',
    hasPhotos: false,
    certificateUrl: '',
    hasCertificate: false,
    contact: 'สาขาวิชาช่างยนต์/ยานยนต์ไฟฟ้า โทร. 087-777-8899',
    order: 8,
  },
];

/**
 * Fetch Open House event info
 */
export async function getOpenHouseInfo(): Promise<OpenHouseInfo> {
  const cacheKey = 'wp:openhouse:info';
  const cached = getFromCache<OpenHouseInfo>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/openhouse/info`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (data && data.title) {
        setInCache(cacheKey, data);
        return data as OpenHouseInfo;
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch openhouse info:', err);
  }

  return FALLBACK_OPENHOUSE_INFO;
}

/**
 * Fetch Open House categories
 */
export async function getOpenHouseCategories(): Promise<OpenHouseCategory[]> {
  const cacheKey = 'wp:openhouse:categories';
  const cached = getFromCache<OpenHouseCategory[]>(cacheKey);
  if (cached) return cached;

  try {
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/openhouse/categories`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        setInCache(cacheKey, data);
        return data as OpenHouseCategory[];
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch openhouse categories:', err);
  }

  return FALLBACK_OPENHOUSE_CATEGORIES;
}

/**
 * Fetch Open House activities
 */
export async function getOpenHouseActivities(category?: string, search?: string): Promise<OpenHouseActivity[]> {
  const cacheKey = `wp:openhouse:activities:${category || 'all'}:${search || 'all'}`;
  const cached = getFromCache<OpenHouseActivity[]>(cacheKey);
  if (cached) return cached;

  try {
    const params = new URLSearchParams();
    if (category && category !== 'all') params.set('category', category);
    if (search) params.set('search', search);

    const queryStr = params.toString() ? `?${params.toString()}` : '';
    const url = `${PROJECT_CORE_API_URL.replace(/\/$/, '')}/openhouse/activities${queryStr}`;
    const res = await fetchWithTimeout(url);
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        setInCache(cacheKey, data);
        return data as OpenHouseActivity[];
      }
    }
  } catch (err) {
    console.warn('[WordPress API] Unable to fetch openhouse activities:', err);
  }

  // Filter fallback data if WordPress returned empty
  let results = [...FALLBACK_OPENHOUSE_ACTIVITIES];
  if (category && category !== 'all') {
    results = results.filter(item => item.category?.slug === category);
  }
  if (search) {
    const q = search.toLowerCase();
    results = results.filter(item => 
      item.title.toLowerCase().includes(q) || 
      item.venue.toLowerCase().includes(q) || 
      item.rulesSummary.toLowerCase().includes(q)
    );
  }
  return results;
}







