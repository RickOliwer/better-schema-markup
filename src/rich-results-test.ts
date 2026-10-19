const RICH_RESULTS_TEST = "https://search.google.com/test/rich-results";

/** Link to Google's Rich Results Test for a public URL. */
export function richResultsTestUrl(pageUrl: string): string {
  return `${RICH_RESULTS_TEST}?${new URLSearchParams({ url: pageUrl }).toString()}`;
}

/**
 * Google can only test a page it can load, so the link is offered for
 * published posts with an http(s) URL. Values come from the editor store and
 * are checked before use.
 */
export function testablePostUrl(status: unknown, link: unknown): string | null {
  if (status !== "publish" || typeof link !== "string") {
    return null;
  }

  return /^https?:\/\//.test(link) ? link : null;
}
