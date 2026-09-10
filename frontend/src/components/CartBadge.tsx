"use client";

import { Link } from "@/i18n/navigation";
import { useCart } from "@/lib/cart-context";

export default function CartBadge() {
  const cart = useCart();
  const count = cart.lines.reduce((sum, l) => sum + l.quantity, 0);

  if (count === 0) return null;

  return (
    <Link
      href="/devis"
      className="relative flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white"
    >
      {count}
    </Link>
  );
}
