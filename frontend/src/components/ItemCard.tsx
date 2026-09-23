"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCart } from "@/lib/cart-context";
import { useAuthGate } from "@/lib/auth-gate-context";
import RatingStars from "./RatingStars";
import FavoriteHeart from "./FavoriteHeart";
import type { Item } from "@/lib/types";

export default function ItemCard({ item }: { item: Item }) {
  const t = useTranslations("catalogue");
  const cart = useCart();
  const { guard } = useAuthGate();
  const [added, setAdded] = useState<"rental" | "purchase" | null>(null);
  const [notAvailable, setNotAvailable] = useState(false);

  const canBuyNow = Boolean(item.sale_price) && (item.purchasable_quantity ?? 0) > 0;
  const outOfRentalStock = item.total_stock <= 0;

  function flash(mode: "rental" | "purchase") {
    setAdded(mode);
    window.setTimeout(() => setAdded(null), 1500);
  }

  function quickAdd() {
    if (outOfRentalStock) return;
    guard(() => {
      cart.addLine(item, 1, "rental");
      flash("rental");
    });
  }

  function quickBuy() {
    if (!canBuyNow) {
      setNotAvailable(true);
      window.setTimeout(() => setNotAvailable(false), 2500);
      return;
    }
    guard(() => {
      cart.addLine(item, 1, "purchase");
      flash("purchase");
    });
  }

  return (
    <div className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 transition hover:shadow-md">
      <Link href={`/catalogue/${item.slug}`} className="relative block">
        <div className="aspect-square w-full bg-slate-100">
          {item.image_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={item.image_url}
              alt={item.name}
              className="h-full w-full object-cover transition group-hover:scale-105"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-slate-300">
              {item.name.charAt(0)}
            </div>
          )}
        </div>
        <div className="absolute right-2 top-2">
          <FavoriteHeart item={item} />
        </div>
      </Link>
      <div className="flex flex-1 flex-col gap-1.5 p-4">
        <Link href={`/catalogue/${item.slug}`} className="font-medium text-brand-navy hover:underline">
          {item.name}
        </Link>

        {item.rating && (
          <RatingStars rating={Number(item.rating)} count={item.rating_count} />
        )}

        <div className="flex flex-wrap items-baseline gap-x-2 text-sm">
          <span className="text-slate-700">
            {item.rental_price_per_day} {t("perDay")}
          </span>
          {item.sale_price && (
            <span className="text-slate-400">· {t("orBuy")} {item.sale_price}</span>
          )}
        </div>

        <span className={`text-xs ${outOfRentalStock ? "text-red-500" : "text-emerald-600"}`}>
          {outOfRentalStock ? t("outOfStock") : t("inStock", { count: item.total_stock })}
        </span>

        <div className="mt-2 flex gap-2">
          <button
            type="button"
            onClick={quickAdd}
            disabled={outOfRentalStock}
            className="flex-1 rounded-full border border-brand-navy px-3 py-1.5 text-xs font-medium text-brand-navy transition hover:bg-brand-navy hover:text-white disabled:pointer-events-none disabled:opacity-40"
          >
            {added === "rental" ? t("added") : t("addToCart")}
          </button>
          <button
            type="button"
            onClick={quickBuy}
            className="flex-1 rounded-full bg-brand-navy px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-navy-light"
          >
            {added === "purchase" ? t("added") : t("quickBuy")}
          </button>
        </div>
        {notAvailable && (
          <p className="text-xs text-red-500">{t("notAvailableForSale")}</p>
        )}
      </div>
    </div>
  );
}
