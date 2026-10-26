/**
 * Formats a price (as returned by the API, e.g. "2000.00") as a whole
 * number with thousands separators — FCFA has no subunit in everyday use,
 * so cents are never shown.
 *
 * The separator is built by hand instead of relying on `toLocaleString()`:
 * without an explicit locale, Node (server render) groups with "," while the
 * browser groups with a narrow no-break space, which made React report a
 * hydration mismatch on every page that shows a price. A no-break space is
 * also the correct French separator and keeps "2 000 FCFA" on one line.
 */
const THIN_SPACE = "\u00a0";

export function formatPrice(value: string | number | null | undefined): string {
  const amount = Math.round(Number(value ?? 0));
  if (!Number.isFinite(amount)) return "0";
  return String(amount).replace(/\B(?=(\d{3})+(?!\d))/g, THIN_SPACE);
}
