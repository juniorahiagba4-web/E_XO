"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import { useRouter, usePathname } from "@/i18n/navigation";
import { useSearchParams } from "next/navigation";

export default function CatalogueFilters({ category }: { category?: string }) {
  const t = useTranslations("catalogue");
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const [search, setSearch] = useState(searchParams.get("search") ?? "");
  const [minPrice, setMinPrice] = useState(searchParams.get("min_price") ?? "");
  const [maxPrice, setMaxPrice] = useState(searchParams.get("max_price") ?? "");
  const sort = searchParams.get("sort") ?? "";

  function applyParams(next: Record<string, string | undefined>) {
    const params = new URLSearchParams(searchParams.toString());
    for (const [key, value] of Object.entries(next)) {
      if (value) {
        params.set(key, value);
      } else {
        params.delete(key);
      }
    }
    router.push(`${pathname}?${params.toString()}`);
  }

  function handleSearchSubmit(e: React.FormEvent) {
    e.preventDefault();
    applyParams({ search: search.trim() || undefined });
  }

  function handlePriceSubmit(e: React.FormEvent) {
    e.preventDefault();
    applyParams({ min_price: minPrice || undefined, max_price: maxPrice || undefined });
  }

  return (
    <div className="mb-8 flex flex-col gap-4">
      <form onSubmit={handleSearchSubmit} className="flex max-w-md gap-2">
        <input
          type="search"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder={t("searchPlaceholder")}
          className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-gold focus:outline-none"
        />
        <button
          type="submit"
          aria-label={t("searchButton")}
          className="flex shrink-0 items-center justify-center rounded-lg bg-brand-navy px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-navy-light"
        >
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
            <circle cx="11" cy="11" r="7" />
            <path strokeLinecap="round" d="m20 20-3.5-3.5" />
          </svg>
        </button>
      </form>

      <div className="flex flex-wrap items-end gap-4">
        <form onSubmit={handlePriceSubmit} className="flex items-end gap-2">
          <label className="flex flex-col gap-1 text-xs text-slate-500">
            {t("minPrice")}
            <input
              type="number"
              min={0}
              value={minPrice}
              onChange={(e) => setMinPrice(e.target.value)}
              className="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
            />
          </label>
          <label className="flex flex-col gap-1 text-xs text-slate-500">
            {t("maxPrice")}
            <input
              type="number"
              min={0}
              value={maxPrice}
              onChange={(e) => setMaxPrice(e.target.value)}
              className="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
            />
          </label>
          <button
            type="submit"
            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:border-brand-navy"
          >
            {t("applyPrice")}
          </button>
        </form>

        <label className="flex flex-col gap-1 text-xs text-slate-500">
          {t("sortLabel")}
          <select
            value={sort}
            onChange={(e) => applyParams({ sort: e.target.value || undefined })}
            className="rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
          >
            <option value="">{t("sortDefault")}</option>
            <option value="price_asc">{t("sortPriceAsc")}</option>
            <option value="price_desc">{t("sortPriceDesc")}</option>
            <option value="popular">{t("sortPopular")}</option>
            <option value="newest">{t("sortNewest")}</option>
          </select>
        </label>

        {category && (
          <button
            type="button"
            onClick={() => applyParams({ category: undefined })}
            className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-500 hover:border-brand-navy hover:text-brand-navy"
          >
            {t("clearCategory")}
          </button>
        )}
      </div>
    </div>
  );
}
