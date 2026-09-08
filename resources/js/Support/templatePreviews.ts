const previewPaths: Record<string, string> = {
    'romantic-floral': '/images/template-previews/romantic-floral.webp',
    'editorial-luxury': '/images/template-previews/editorial-luxury.webp',
    'modern-cinematic': '/images/template-previews/modern-cinematic.webp',
    'dolce-vita': '/images/template-previews/dolce-vita.webp',
    'blossom-oud': '/images/template-previews/blossom-oud.webp',
    'sacred-garden': '/images/template-previews/sacred-garden.webp',
};

export const templatePreviewUrl = (componentKey: string): string | null => previewPaths[componentKey] ?? null;
