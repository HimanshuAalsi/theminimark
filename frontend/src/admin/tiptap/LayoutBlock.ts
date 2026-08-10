import { Node, mergeAttributes, type CommandProps, type RawCommands } from '@tiptap/core'

/** 12-column layout row — each column spans 12/cols (max 4 columns). */
export const LayoutBlock = Node.create({
  name: 'layoutBlock',
  group: 'block',
  content: 'layoutColumn{1,4}',
  defining: true,

  addAttributes() {
    return {
      cols: {
        default: 2,
        parseHTML: (el) => parseInt(el.getAttribute('data-cols') || '2', 10),
        renderHTML: (attrs) => ({ 'data-cols': String(attrs.cols) }),
      },
    }
  },

  parseHTML() {
    return [{ tag: 'div[data-type="layout-block"]' }]
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'div',
      mergeAttributes(HTMLAttributes, {
        'data-type': 'layout-block',
        class: 'blog-layout',
        'data-cols': String(HTMLAttributes['data-cols'] ?? 2),
      }),
      0,
    ]
  },

  addCommands() {
    return {
      insertLayoutBlock:
        (cols: number) =>
        ({ chain }: CommandProps) => {
          const n = Math.min(4, Math.max(1, cols))
          const columns = Array.from({ length: n }, () => ({
            type: 'layoutColumn',
            content: [{ type: 'paragraph' }],
          }))
          return chain()
            .insertContent({
              type: this.name,
              attrs: { cols: n },
              content: columns,
            })
            .run()
        },
    } as Partial<RawCommands>
  },
})

export const LayoutColumn = Node.create({
  name: 'layoutColumn',
  content: 'block+',
  defining: true,

  parseHTML() {
    return [{ tag: 'div[data-type="layout-column"]' }]
  },

  renderHTML() {
    return ['div', { 'data-type': 'layout-column', class: 'blog-layout__col' }, 0]
  },
})
