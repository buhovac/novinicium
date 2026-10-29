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
    /** Stable, unique anchor (App\Actions\Blog\ArticleOutline). */
    id: string;
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

export type TableCellNode = {
    type: 'tableHeader' | 'tableCell';
    colspan: number;
    rowspan: number;
    content: BlockNode[];
};
export type TableRowNode = { type: 'tableRow'; content: TableCellNode[] };
export type TableNode = {
    type: 'table';
    /** From a "Table caption" block placed directly above the table. */
    caption: string | null;
    content: TableRowNode[];
};

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

/** Custom block: Image (App\Filament\Resources\Posts\Blocks\ImageBlock). */
export type ImageWidth = 'standard' | 'wide' | 'full';
export type ImageBlockNode = {
    type: 'image';
    src: string;
    /** Empty only when decorative. */
    alt: string;
    decorative: boolean;
    caption: string | null;
    credit: string | null;
    width: ImageWidth;
};

/** Custom block: Code (CodeBlock); native code blocks map to it too. */
export type CodeNode = {
    type: 'code';
    language: string;
    label: string;
    code: string;
};

/** Custom block: Statistics (App\Filament\Resources\Posts\Blocks\StatisticsBlock). */
export type StatisticsLayout = 'single' | 'two' | 'three';
export type StatisticItem = {
    value: string;
    unit: string | null;
    label: string;
    explanation: string | null;
    source: string | null;
};
export type StatisticsNode = {
    type: 'statistics';
    layout: StatisticsLayout;
    items: StatisticItem[];
};

/** Custom block: Image comparison (App\Filament\Resources\Posts\Blocks\ImageComparisonBlock). */
export type ComparisonImage = {
    src: string;
    /** Always non-empty: both alts are required. */
    alt: string;
    label: string | null;
};
export type ImageComparisonNode = {
    type: 'imageComparison';
    first: ComparisonImage;
    second: ComparisonImage;
    caption: string | null;
};

/** Custom block: Chart (App\Filament\Resources\Posts\Blocks\ChartBlock). */
export type ChartType = 'bar' | 'line' | 'pie' | 'doughnut';
export type ChartSeries = {
    name: string;
    /** One number per category, in category order. */
    values: number[];
    /** The same values formatted with the unit, e.g. "43.3 million". */
    display: string[];
};
export type ChartNode = {
    type: 'chart';
    chartType: ChartType;
    title: string;
    categoryHeading: string;
    categories: string[];
    /** Pie and doughnut always have exactly one series. */
    series: ChartSeries[];
    unit: string | null;
    source: string | null;
    caption: string | null;
};

/** Custom block: Quiz (App\Filament\Resources\Posts\Blocks\QuizBlock). */
export type QuizQuestionNode = {
    question: string;
    /** Plain text, 2–6 options. */
    options: string[];
    /** Public by design: a self-check, not an exam. */
    correctIndex: number;
    explanation: string;
};
export type QuizNode = {
    type: 'quiz';
    title: string | null;
    questions: QuizQuestionNode[];
};
/** Per-question state, kept in the browser only. */
export type QuizQuestionStatus = 'open' | 'needsAnswer' | 'checked';

export type BlockNode =
    | ParagraphNode
    | HeadingNode
    | BulletListNode
    | OrderedListNode
    | BlockquoteNode
    | HorizontalRuleNode
    | TableNode
    | GridNode
    | CalloutNode
    | ImageBlockNode
    | CodeNode
    | StatisticsNode
    | ImageComparisonNode
    | ChartNode
    | QuizNode;

/** Table of contents entry (App\Actions\Blog\ArticleOutline::tableOfContents). */
export type TocEntry = {
    id: string;
    text: string;
    level: 2 | 3;
    children: TocEntry[];
};
