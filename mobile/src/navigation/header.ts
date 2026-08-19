export type HeaderTitleOptions = {
  headerTitle?: unknown;
  title?: string;
};

export function resolveHeaderTitle(
  options: HeaderTitleOptions,
  fallback: string,
): string {
  return typeof options.headerTitle === "string"
    ? options.headerTitle
    : (options.title ?? fallback);
}
