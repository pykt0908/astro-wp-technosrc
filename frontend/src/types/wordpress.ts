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
  vtr?: {
    enabled: boolean;
    youtubeUrl: string;
    youtubeId?: string;
    embedUrl?: string;
  };
}

export interface CollegeEventCategory {
  id: number;
  name: string;
  slug: string;
  color?: string;
  count?: number;
}

export interface CollegeEvent {
  id: number;
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  academicYear: string;
  startDate: string;
  endDate: string;
  time: string;
  location: string;
  note?: string;
  organizer: string;
  link: string;
  attachmentUrl: string;
  color: 'blue' | 'emerald' | 'amber' | 'rose' | 'purple' | string;
  isFeatured: boolean;
  thumbnail?: string | null;
  categories: CollegeEventCategory[];
}

export interface PersonnelCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  count?: number;
  order?: number;
}

export interface Personnel {
  id: number;
  name: string;
  slug: string;
  order: number;
  dept_order?: number;
  nickname?: string;
  position: string;
  department: string;
  education?: string;
  email?: string;
  phone?: string;
  bio?: string;
  image?: {
    url: string;
    alt?: string;
    width?: number;
    height?: number;
  } | null;
  categories: PersonnelCategory[];
}

export interface SarYear {
  id: number;
  name: string;
  slug: string;
  count?: number;
}

export interface SarReport {
  id: number;
  title: string;
  slug: string;
  reportNo?: string;
  description: string;
  content?: string;
  year: SarYear | null;
  fileUrl: string;
  fileSize?: string;
  fileType?: string;
  externalUrl?: string;
  coverUrl?: string;
  order: number;
  publishedAt: string;
  modifiedAt?: string;
}

export interface EducationMisYear {
  id: number;
  name: string;
  slug: string;
  count?: number;
}

export interface StudentStatRow {
  level: string;
  department: string;
  male: number;
  female: number;
  total: number;
}

export interface PersonnelStatRow {
  category: string;
  position: string;
  count: number;
  notes?: string;
}

export interface CurriculumStatRow {
  level: string;
  department: string;
  specialties: string;
  study_mode: string;
}

export interface AdmissionsGraduatesRow {
  year: string;
  intake_pvch: number;
  intake_pvs: number;
  intake_total: number;
  grad_pvch: number;
  grad_pvs: number;
  grad_total: number;
  grad_rate: string;
}

export interface AchievementStatRow {
  department: string;
  gpax: string;
  vnet_score: string;
  standard_pass_rate: string;
}

export interface EmploymentStatRow {
  category: string;
  count: number;
  percent: string;
  avg_salary: string;
}

export interface PartnerEnterpriseRow {
  logo_url?: string;
  name: string;
  industry: string;
  cooperation_type: string;
  departments: string;
}

export interface EducationMisItem {
  id: number;
  title: string;
  slug: string;
  description?: string;
  academicYear: EducationMisYear;
  pdfUrl: string;
  overview: {
    totalStudents: number;
    totalPersonnel: number;
    programsCount: number;
    employmentRate: string;
    avgGpax: string;
    partnerCount: number;
  };
  students: {
    total: number;
    pvchTotal: number;
    pvsTotal: number;
    byDepartment: StudentStatRow[];
  };
  personnel: {
    total: number;
    executives: number;
    teachers: number;
    support: number;
    breakdown: PersonnelStatRow[];
  };
  curriculum: {
    levelsCount: number;
    departmentsCount: number;
    programsCount: number;
    list: CurriculumStatRow[];
  };
  admissionsGraduates: AdmissionsGraduatesRow[];
  achievements: {
    gpaxAvg: string;
    gpaxPvch: string;
    gpaxPvs: string;
    vnetPassRate: string;
    vocationalStandardPassRate: string;
    list: AchievementStatRow[];
  };
  employment: {
    totalRate: string;
    fieldRate: string;
    otherRate: string;
    higherEdRate: string;
    freelanceRate: string;
    unemployedRate: string;
    data: EmploymentStatRow[];
  };
  partners: {
    mouCount: number;
    dualVocationalCount: number;
    internStudentsCount: number;
    internAllowanceAvg: number;
    enterprises: PartnerEnterpriseRow[];
  };
  publishedAt: string;
  modifiedAt?: string;
}

export interface OrgPersonnel {
  id?: number | string;
  name: string;
  photo?: string;
  position?: string;
  sub?: string;
}

export interface OrgLeader {
  id: number | string;
  position: string;
  type: 'executive' | 'committee' | string;
  sub?: string;
  remark?: string;
  status?: string;
  personnel: OrgPersonnel;
}

export interface OrgJob {
  id: number | string;
  title: string;
  code?: string;
  order?: number;
  position_title?: string;
  remark?: string;
  personnel: OrgPersonnel;
}

export interface OrgDivision {
  id: number | string;
  slug: string;
  title: string;
  color: string;
  order: number;
  description?: string;
  head: {
    id?: number | string;
    position: string;
    sub?: string;
    remark?: string;
    personnel: OrgPersonnel;
  };
  jobs: OrgJob[];
}

export interface OrgSettings {
  page_title: string;
  page_description: string;
  academic_year: string;
  updated_at: string;
  pdf_url?: string;
  committee_title: string;
  committee_desc: string;
  committee_link?: string;
}

export interface OrganizationData {
  settings: OrgSettings;
  leaders: OrgLeader[];
  committee: OrgLeader;
  divisions: OrgDivision[];
}

