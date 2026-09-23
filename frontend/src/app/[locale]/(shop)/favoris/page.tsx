"use client";

import { useEffect, useState } from "react";
import { useLocale, useTranslations } from "next-intl";
import { useRouter } from "@/i18n/navigation";
import { useAuth } from "@/lib/auth-context";
import { getMyFavorites, ApiError } from "@/lib/api";
import ItemCard from "@/components/ItemCard";
import type { Item } from "@/lib/types";

export default function FavoritesPage() {
  const t = useTranslations("favorites");
  const locale = useLocale();
  const router = useRouter();
  const { user, token, loading } = useAuth();

  const [items, setItems] = useState<Item[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (loading) return;
    if (!user || !token) {
      router.push("/connexion?redirect=/favoris");
      return;
    }
    getMyFavorites(locale, token)
      .then(setItems)
      .catch((err) => setError(err instanceof ApiError ? err.message : t("error")));
  }, [loading, user, token, locale, router, t]);

  if (loading || !user) {
    return <div className="mx-auto max-w-5xl px-4 py-16 text-slate-500">{t("loading")}</div>;
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <h1 className="mb-8 text-3xl font-bold text-brand-navy">{t("title")}</h1>

      {error && <p className="text-sm text-red-600">{error}</p>}

      {items && items.length === 0 && <p className="text-slate-500">{t("empty")}</p>}

      {items && items.length > 0 && (
        <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
          {items.map((item) => (
            <ItemCard key={item.id} item={item} />
          ))}
        </div>
      )}
    </div>
  );
}
