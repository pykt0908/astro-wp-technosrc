export interface WP_FeaturedMedia {
  id: number;
  source_url: string;
  alt_text?: string;
  media_details?: {
    width?: number;
    height?: number;
  };
}

export interface WP_Embedded {
  'wp:featuredmedia'?: WP_FeaturedMedia[];
  'wp:term'?: Array<Array<{
    id: number;
    name: string;
    slug: string;
    taxonomy: string;
  }>>;
}

export interface WP_RawPost {
  id: number;
  date: string;
  modified: string;
  slug: string;
  status: string;
  type: string;
  link: string;
  title: {
    rendered: string;
  };
  content: {
    rendered: string;
    protected: boolean;
  };
  excerpt: {
    rendered: string;
    protected: boolean;
  };
  featured_media: number;
  categories: number[];
  tags: number[];
  _embedded?: WP_Embedded;
  meta?: {
    seo_meta_title?: string;
    seo_meta_description?: string;
    seo_og_image_url?: string;
  };
}

export interface WP_RawCategory {
  id: number;
  count: number;
  description: string;
  link: string;
  name: string;
  slug: string;
  taxonomy: string;
  parent: number;
}

export interface PostGalleryImage {
  id: number;
  url: string;
  thumbUrl?: string;
  alt?: string;
}

export interface Post {
  id: number;
  slug: string;
  titleHtml: string;
  titlePlain: string;
  excerptHtml: string;
  excerptPlain: string;
  contentHtml: string;
  publishedAt: string;
  modifiedAt: string;
  coverUrl?: string;
  coverThumb?: string;
  coverAlt?: string;
  categoryIds: number[];
  categoryNames?: string[];
  categories?: Array<{ id: number; name: string; slug: string }>;
  views?: number;
  gallery?: PostGalleryImage[];
  galleryCount?: number;
  authorName?: string;
  authorRole?: string;
  authorAvatar?: string;
  seoMetaTitle?: string;
  seoMetaDescription?: string;
  seoOgImageUrl?: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  count: number;
}

export interface PaginatedPosts {
  posts: Post[];
  totalPosts: number;
  totalPages: number;
  currentPage: number;
}

export interface Banner {
  id: number;
  title: string;
  desktopImageUrl: string;
  mobileImageUrl: string;
  targetUrl: string;
  menuOrder: number;
}

export interface SiteSettings {
  siteName: string;
  siteTagline: string;
  logoUrl: string;
  faviconUrl: string;
  contact: {
    email: string;
    phone: string;
    address: string;
    officeHours: string;
  };
  social: {
    facebook: string;
    line: string;
    linkedin: string;
    github: string;
  };
  footerCopyright: string;
}
