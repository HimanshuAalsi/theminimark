import Image from '@tiptap/extension-image'
import { mergeAttributes } from '@tiptap/core'

export type BlogImageSize = 'sm' | 'md' | 'lg' | 'full'

export const ResizableImage = Image.extend({
  name: 'image',

  addAttributes() {
    return {
      ...this.parent?.(),
      size: {
        default: 'lg',
        parseHTML: (el) => (el.getAttribute('data-size') as BlogImageSize) || 'lg',
        renderHTML: (attrs) => ({
          'data-size': attrs.size as string,
          class: `blog-img blog-img--${attrs.size as string}`,
        }),
      },
      alt: {
        default: null,
        parseHTML: (el) => el.getAttribute('alt'),
        renderHTML: (attrs) => (attrs.alt ? { alt: attrs.alt } : {}),
      },
    }
  },

  renderHTML({ HTMLAttributes }) {
    return ['img', mergeAttributes(HTMLAttributes, { class: `blog-img blog-img--${HTMLAttributes['data-size'] || 'lg'}` })]
  },
})
