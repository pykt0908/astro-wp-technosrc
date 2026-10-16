import sanitizeHtml from 'sanitize-html';

const defaultAllowedTags = [
  ...sanitizeHtml.defaults.allowedTags,
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  'img',
  'figure',
  'figcaption',
  'picture',
  'source',
  'video',
  'audio',
  'iframe',
  'table',
  'thead',
  'tbody',
  'tr',
  'th',
  'td',
  'pre',
  'code',
  'blockquote',
  'mark',
  'del',
  'ins',
];

const defaultAllowedAttributes = {
  ...sanitizeHtml.defaults.allowedAttributes,
  '*': ['class', 'id', 'aria-*', 'role', 'title', 'lang', 'dir'],
  a: ['href', 'name', 'target', 'rel'],
  img: ['src', 'srcset', 'sizes', 'alt', 'title', 'width', 'height', 'loading'],
  iframe: ['src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen'],
  video: ['src', 'poster', 'controls', 'width', 'height', 'autoplay', 'muted', 'loop'],
  audio: ['src', 'controls', 'autoplay', 'muted', 'loop'],
  source: ['src', 'srcset', 'type', 'media'],
  table: ['border', 'cellpadding', 'cellspacing'],
};

/**
 * Sanitizes rich HTML content from WordPress REST API to prevent XSS attacks.
 */
export function sanitizeWordPressHtml(dirtyHtml: string): string {
  if (!dirtyHtml) return '';
  return sanitizeHtml(dirtyHtml, {
    allowedTags: defaultAllowedTags,
    allowedAttributes: defaultAllowedAttributes,
    allowedSchemes: ['http', 'https', 'mailto', 'tel'],
    transformTags: {
      a: (tagName, attribs) => {
        // Enforce rel="noopener noreferrer" on external links
        if (attribs.target === '_blank') {
          return {
            tagName: 'a',
            attribs: {
              ...attribs,
              rel: 'noopener noreferrer',
            },
          };
        }
        return { tagName, attribs };
      },
      img: (_tagName, attribs) => {
        // Add lazy loading by default
        return {
          tagName: 'img',
          attribs: {
            loading: 'lazy',
            decoding: 'async',
            ...attribs,
          },
        };
      },
    },
  });
}

/**
 * Strips all HTML tags and returns clean plain text. Useful for meta tags, titles, and excerpts.
 */
export function stripHtml(html: string): string {
  if (!html) return '';
  return sanitizeHtml(html, {
    allowedTags: [],
    allowedAttributes: {},
  })
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/\s+/g, ' ')
    .trim();
}
