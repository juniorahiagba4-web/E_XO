"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { Link } from "@/i18n/navigation";
import { useCart } from "@/lib/cart-context";
import { useAuthGate } from "@/lib/auth-gate-context";
import RatingStars from "./RatingStars";
import FavoriteHeart from "./FavoriteHeart";
import type { Item, OrderType } from "@/lib/types";

export default function ItemCard({ item }: { item: Item }) {
  const t = useTranslations("catalogue");
  const cart = useCart();
  const { guard } = useAuthGate();
  const [mode, setMode] = useState<OrderType>("rental");
  const [added, setAdded] = useState(false);
  const [notAvailable, setNotAvailable] = useState(false);

  const canBuyNow = Boolean(item.sale_price) && (item.purchasable_quantity ?? 0) > 0;
  const hasSalePrice = Boolean(item.sale_price);
  const outOfRentalStock = item.total_stock <= 0;

  function flash() {
    setAdded(true);
    window.setTimeout(() => setAdded(false), 1500);
  }

  function handlePrimaryAction() {
    if (mode === "rental") {
      if (outOfRentalStock) return;
      guard(() => {
        cart.addLine(item, 1, "rental");
        flash();
      });
      return;
    }

    if (!canBuyNow) {
      setNotAvailable(true);
      window.setTimeout(() => setNotAvailable(false), 2500);
      return;
    }
    guard(() => {
      cart.addLine(item, 1, "purchase");
      flash();
    });
  }

  const primaryDisabled = mode === "rental" && outOfRentalStock;

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

        {hasSalePrice && (
          <div className="mt-2 inline-flex self-start rounded-full border border-slate-200 p-0.5 text-xs">
            <button
              type="button"
              onClick={() => setMode("rental")}
              className={`rounded-full px-2.5 py-1 font-medium transition ${
                mode === "rental" ? "bg-brand-navy text-white" : "text-slate-500"
              }`}
            >
              {t("modeRental")}
            </button>
            <button
              type="button"
              onClick={() => setMode("purchase")}
              className={`rounded-full px-2.5 py-1 font-medium transition ${
                mode === "purchase" ? "bg-brand-navy text-white" : "text-slate-500"
              }`}
            >
              {t("modePurchase")}
            </button>
          </div>
        )}

        <div className={`flex gap-2 ${hasSalePrice ? "mt-1" : "mt-2"}`}>
          <button
            type="button"
            onClick={() => cart.openCart()}
            aria-label={t("openCart")}
            title={t("openCart")}
            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-brand-navy transition hover:border-brand-navy"
          >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
              <path strokeLinecap="round" strokeLinejoin="round" d="M3 4h2l1.5 12.5A2 2 0 0 0 8.5 18h9a2 2 0 0 0 2-1.7L21 8H6" />
              <circle cx="9.5" cy="21" r="1.3" fill="currentColor" stroke="none" />
              <circle cx="17.5" cy="21" r="1.3" fill="currentColor" stroke="none" />
            </svg>
          </button>
          <button
            type="button"
            onClick={handlePrimaryAction}
            disabled={primaryDisabled}
            className="flex-1 rounded-full bg-brand-navy px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-navy-light disabled:pointer-events-none disabled:opacity-40"
          >
            {added ? t("added") : mode === "rental" ? t("rent") : t("quickBuy")}
          </button>
        </div>
        {notAvailable && (
          <p className="text-xs text-red-500">{t("notAvailableForSale")}</p>
        )}
      </div>
    </div>
  );
}
