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
  coverAlt?: string;
  categoryIds: number[];
  categoryNames?: string[];
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
