import type { AppliedPromo, CartLine } from "./cart-context";

/**
 * Client-side discount preview only — the authoritative amount is always
 * recomputed server-side when the reservation is created (see
 * ReservationService::resolveDiscount on the backend), so this never needs
 * to be exact to the centime, just a fair approximation to show the user.
 */
export function computeDiscountPreview(
  promo: AppliedPromo | null,
  lines: CartLine[],
  lineTotal: (line: CartLine) => number,
): number {
  if (!promo) return 0;

  const { promotion } = promo;
  const applicableSubtotal = lines.reduce((sum, line) => {
    const applies = promotion.item
      ? promotion.item.id === line.item.id
      : promotion.category
        ? promotion.category.id === line.item.category?.id
        : true;
    return applies ? sum + lineTotal(line) : sum;
  }, 0);

  if (applicableSubtotal <= 0) return 0;

  return promotion.discount_type === "percent"
    ? Math.round(applicableSubtotal * (Number(promotion.discount_value) / 100))
    : Math.min(Number(promotion.discount_value), applicableSubtotal);
}
