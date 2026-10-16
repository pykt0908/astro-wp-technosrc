import type { Post, Category, PaginatedPosts, WP_RawPost, WP_RawCategory } from '../types/wordpress';
import { sanitizeWordPressHtml, stripHtml } from './sanitize';

const WP_API_BASE_URL = process.env.WP_API_BASE_URL || 'https://example.com/cms/wp-json/wp/v2';
const FETCH_TIMEOUT_MS = 10000;
const IS_CI = process.env.CI === 'true';

// Sample fallback posts for local development if CMS is not yet configured or unreachable
const LOCAL_MOCK_POSTS: Post[] = [
  {
    id: 1,
    slug: 'technosrc-announces-cloud-integration',
    titleHtml: 'Technosrc ประกาศเปิดตัวระบบบูรณาการคลาวด์รุ่นใหม่',
    titlePlain: 'Technosrc ประกาศเปิดตัวระบบบูรณาการคลาวด์รุ่นใหม่',
    excerptHtml: '<p>เพิ่มประสิทธิภาพการทำงานและรองรับการประมวลผลข้อมูลปริมาณมหาศาลด้วยโครงสร้างพื้นฐานระดับเอ็นเตอร์ไพรส์</p>',
    excerptPlain: 'เพิ่มประสิทธิภาพการทำงานและรองรับการประมวลผลข้อมูลปริมาณมหาศาลด้วยโครงสร้างพื้นฐานระดับเอ็นเตอร์ไพรส์',
    contentHtml: `
      <p>Technosrc มีความยินดีที่จะประกาศการเปิดตัวโซลูชันระบบบูรณาการคลาวด์รุ่นใหม่ล่าสุด ที่ถูกออกแบบมาเพื่อเพิ่มความคล่องตัวในการทำงานขององค์กรทุกขนาด</p>
      <h2>จุดเด่นของระบบ</h2>
      <ul>
        <li>ความเร็วและประสิทธิภาพการประมวลผลที่สูงขึ้นกว่า 40%</li>
        <li>รองรับการเชื่อมต่อกับระบบเดิมอย่างไร้รอยต่อผ่าน API อัจฉริยะ</li>
        <li>มาตรฐานความปลอดภัยระดับสากล พร้อมการสำรองข้อมูลอัตโนมัติ</li>
      </ul>
      <p>ทีมวิศวกรของเราได้พัฒนาเทคโนโลยีนี้โดยคำนึงถึงความต้องการของลูกค้าในยุคดิจิทัลเป็นหลัก เพื่อให้สามารถขยายขีดความสามารถทางธุรกิจได้อย่างมั่นคงและปลอดภัย</p>
    `,
    publishedAt: new Date(Date.now() - 86400000 * 2).toISOString(),
    modifiedAt: new Date(Date.now() - 86400000 * 2).toISOString(),
    coverUrl: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=1200&auto=format&fit=crop',
    coverAlt: 'Cloud Infrastructure and Data Centers',
    categoryIds: [1],
    categoryNames: ['ข่าวประชาสัมพันธ์'],
  },
  {
    id: 2,
    slug: 'annual-cybersecurity-trends-2026',
    titleHtml: 'แนวโน้มความปลอดภัยทางไซเบอร์สำหรับองค์กรในปี 2026',
    titlePlain: 'แนวโน้มความปลอดภัยทางไซเบอร์สำหรับองค์กรในปี 2026',
    excerptHtml: '<p>เจาะลึกภัยคุกคามรูปแบบใหม่และกลยุทธ์ Zero Trust ที่ทุกองค์กรควรนำมาปรับใช้เพื่อความปลอดภัยสูงสุด</p>',
    excerptPlain: 'เจาะลึกภัยคุกคามรูปแบบใหม่และกลยุทธ์ Zero Trust ที่ทุกองค์กรควรนำมาปรับใช้เพื่อความปลอดภัยสูงสุด',
    contentHtml: `
      <p>ในยุคที่เทคโนโลยี AI ก้าวหน้าอย่างรวดเร็ว ภัยคุกคามทางไซเบอร์ก็มีความซับซ้อนมากยิ่งขึ้น การรักษาความปลอดภัยแบบดั้งเดิมจึงอาจไม่เพียงพออีกต่อไป</p>
      <h2>แนวทางปฏิบัติที่สำคัญ</h2>
      <p>การนำแนวคิด <strong>Zero Trust Architecture</strong> เข้ามาใช้จะช่วยให้องค์กรสามารถตรวจสอบสิทธิ์การเข้าถึงอย่างเข้มงวดในทุกระดับ ไม่ว่าจะเป็นภายในหรือภายนอกเครือข่าย</p>
    `,
    publishedAt: new Date(Date.now() - 86400000 * 5).toISOString(),
    modifiedAt: new Date(Date.now() - 86400000 * 5).toISOString(),
    coverUrl: 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=1200&auto=format&fit=crop',
    coverAlt: 'Cybersecurity Network Monitoring',
    categoryIds: [2],
    categoryNames: ['เทคโนโลยี'],
  },
  {
    id: 3,
    slug: 'smart-iot-solutions-for-industry',
    titleHtml: 'นวัตกรรม Smart IoT ยกระดับอุตสาหกรรมการผลิต',
    titlePlain: 'นวัตกรรม Smart IoT ยกระดับอุตสาหกรรมการผลิต',
    excerptHtml: '<p>การผสานเซ็นเซอร์อัจฉริยะและการวิเคราะห์ข้อมูลเชิงลึกแบบเรียลไทม์เพื่อลดต้นทุนการบำรุงรักษา</p>',
    excerptPlain: 'การผสานเซ็นเซอร์อัจฉริยะและการวิเคราะห์ข้อมูลเชิงลึกแบบเรียลไทม์เพื่อลดต้นทุนการบำรุงรักษา',
    contentHtml: `
      <p>ระบบเซ็นเซอร์อัจฉริยะ IoT ช่วยให้โรงงานสามารถตรวจสอบประสิทธิภาพของเครื่องจักรได้ตลอด 24 ชั่วโมง ช่วยลดเวลา Downtime และวางแผนซ่อมบำรุงล่วงหน้าได้อย่างแม่นยำ</p>
    `,
    publishedAt: new Date(Date.now() - 86400000 * 10).toISOString(),
    modifiedAt: new Date(Date.now() - 86400000 * 10).toISOString(),
    coverUrl: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=1200&auto=format&fit=crop',
    coverAlt: 'Smart Industry IoT Sensor System',
    categoryIds: [2],
    categoryNames: ['เทคโนโลยี'],
  },
];

/**
 * Transforms raw WordPress REST API post item into clean Post domain model
 */
function mapRawPostToPost(raw: WP_RawPost): Post {
  const media = raw._embedded?.['wp:featuredmedia']?.[0];
  const coverUrl = media?.source_url;
  const coverAlt = media?.alt_text || stripHtml(raw.title.rendered);

  const termCategories = raw._embedded?.['wp:term']?.[0] || [];
  const categoryNames = termCategories.map((term) => term.name);

  return {
    id: raw.id,
    slug: raw.slug,
    titleHtml: raw.title.rendered,
    titlePlain: stripHtml(raw.title.rendered),
    excerptHtml: sanitizeWordPressHtml(raw.excerpt.rendered),
    excerptPlain: stripHtml(raw.excerpt.rendered),
    contentHtml: sanitizeWordPressHtml(raw.content.rendered),
    publishedAt: raw.date,
    modifiedAt: raw.modified,
    coverUrl,
    coverAlt,
    categoryIds: raw.categories || [],
    categoryNames,
  };
}

/**
 * Helper to fetch with timeout and standard headers
 */
async function wpFetch(endpoint: string): Promise<Response> {
  const url = `${WP_API_BASE_URL.replace(/\/$/, '')}/${endpoint.replace(/^\//, '')}`;
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);

  try {
    const res = await fetch(url, {
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
        'User-Agent': 'Astro-Headless-Client/1.0',
      },
    });
    return res;
  } finally {
    clearTimeout(timer);
  }
}

/**
 * Get paginated list of published posts
 */
export async function getPublishedPosts(page = 1, perPage = 9): Promise<PaginatedPosts> {
  try {
    const res = await wpFetch(`posts?status=publish&_embed=1&page=${page}&per_page=${perPage}&orderby=date&order=desc`);

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
    return {
      posts,
      totalPosts: totalPosts || posts.length,
      totalPages: totalPages || 1,
      currentPage: page,
    };
  } catch (error) {
    console.warn(`[WordPress API] Unable to fetch posts at ${WP_API_BASE_URL}:`, error);

    if (IS_CI) {
      throw new Error(`[WordPress CI Error] Build failed because CMS API is unreachable: ${error}`);
    }

    // In local dev without live CMS, return mock sample posts
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
 * Fetch all published posts across all pages (used for getStaticPaths and detail routes)
 */
export async function getAllPublishedPosts(): Promise<Post[]> {
  try {
    const firstPageRes = await wpFetch('posts?status=publish&_embed=1&page=1&per_page=100&orderby=date&order=desc');

    if (!firstPageRes.ok) {
      throw new Error(`WordPress API returned status ${firstPageRes.status}: ${firstPageRes.statusText}`);
    }

    const totalPages = parseInt(firstPageRes.headers.get('x-wp-totalpages') || '1', 10);
    const firstPageData: WP_RawPost[] = await firstPageRes.json();
    let allRawPosts: WP_RawPost[] = [...firstPageData];

    if (totalPages > 1) {
      for (let p = 2; p <= totalPages; p++) {
        const pageRes = await wpFetch(`posts?status=publish&_embed=1&page=${p}&per_page=100&orderby=date&order=desc`);
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
  try {
    const res = await wpFetch(`posts?slug=${encodeURIComponent(slug)}&status=publish&_embed=1`);

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
  try {
    const res = await wpFetch('categories?per_page=100&hide_empty=true');
    if (!res.ok) {
      return [];
    }
    const rawCategories: WP_RawCategory[] = await res.json();
    return rawCategories.map((c) => ({
      id: c.id,
      name: c.name,
      slug: c.slug,
      count: c.count,
    }));
  } catch (error) {
    console.warn('[WordPress API] Failed to fetch categories:', error);
    return [
      { id: 1, name: 'ข่าวประชาสัมพันธ์', slug: 'news', count: 1 },
      { id: 2, name: 'เทคโนโลยี', slug: 'technology', count: 2 },
    ];
  }
}
