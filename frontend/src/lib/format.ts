/**
 * Formats a price (as returned by the API, e.g. "2000.00") as a whole
 * number with thousands separators — XOF has no subunit in everyday use,
 * so cents are never shown.
 */
export function formatPrice(value: string | number | null | undefined): string {
  return Math.round(Number(value ?? 0)).toLocaleString();
}
