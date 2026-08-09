export type PageMetadata = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type PaginatedData<T> = {
  items: T[];
  pagination: PageMetadata;
};

export function getNextPageParam<T>(page: PaginatedData<T>): number | undefined {
  return page.pagination.current_page < page.pagination.last_page
    ? page.pagination.current_page + 1
    : undefined;
}
