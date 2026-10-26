/**
 * Blog body node model produced by App\Actions\Blog\BuildPostContent.
 * The server whitelists every node and attribute, so the renderer can map
 * these 1:1 to elements without v-html.
 */

export type ContentMark =
    | { type: 'bold' }
    | { type: 'italic' }
    | { type: 'code' }
    | { type: 'link'; href: string; newTab: boolean };

export type TextNode = { type: 'text'; text: string; marks: ContentMark[] };
export type HardBreakNode = { type: 'hardBreak' };
export type ImageNode = { type: 'image'; src: string; alt: string };

export type InlineNode = TextNode | HardBreakNode | ImageNode;

export type ParagraphNode = { type: 'paragraph'; content: InlineNode[] };
export type HeadingNode = {
    type: 'heading';
    level: 2 | 3;
    content: InlineNode[];
};
export type ListItemNode = { type: 'listItem'; content: BlockNode[] };
export type BulletListNode = { type: 'bulletList'; content: ListItemNode[] };
export type OrderedListNode = {
    type: 'orderedList';
    start: number;
    content: ListItemNode[];
};
export type BlockquoteNode = { type: 'blockquote'; content: BlockNode[] };
export type HorizontalRuleNode = { type: 'horizontalRule' };
export type CodeBlockNode = {
    type: 'codeBlock';
    language: string | null;
    text: string;
};

export type TableCellNode = {
    type: 'tableHeader' | 'tableCell';
    colspan: number;
    rowspan: number;
    content: BlockNode[];
};
export type TableRowNode = { type: 'tableRow'; content: TableCellNode[] };
export type TableNode = { type: 'table'; content: TableRowNode[] };

export type GridColumnNode = {
    type: 'gridColumn';
    span: number;
    content: BlockNode[];
};
export type GridNode = {
    type: 'grid';
    columns: number;
    fromBreakpoint: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    content: GridColumnNode[];
};

/** Custom block: Callout (App\Filament\Resources\Posts\Blocks\CalloutBlock). */
export type CalloutVariant =
    'information' | 'definition' | 'important' | 'warning' | 'example';
export type CalloutNode = {
    type: 'callout';
    variant: CalloutVariant;
    label: string;
    title: string;
    paragraphs: string[];
};

export type BlockNode =
    | ParagraphNode
    | HeadingNode
    | BulletListNode
    | OrderedListNode
    | BlockquoteNode
    | HorizontalRuleNode
    | CodeBlockNode
    | TableNode
    | GridNode
    | CalloutNode;
