import { getTranslations } from "next-intl/server";
import type { Category } from "@/lib/types";

export default async function CategoryHeroBanner({
  locale,
  category,
}: {
  locale: string;
  category: Category;
}) {
  const t = await getTranslations({ locale, namespace: "catalogue" });

  return (
    <div
      className="relative mb-8 flex min-h-[220px] items-end overflow-hidden rounded-2xl bg-brand-navy bg-cover bg-center"
      style={category.image_url ? { backgroundImage: `url(${category.image_url})` } : undefined}
    >
      <div className="absolute inset-0 bg-gradient-to-t from-brand-navy/90 via-brand-navy/50 to-brand-navy/10" />
      <div className="relative flex flex-col items-start gap-3 p-8">
        <span className="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
          {t("collectionBadge")}
        </span>
        <h2 className="font-display text-4xl font-black text-white">
          {category.name}
        </h2>
        {category.description && (
          <p className="max-w-lg text-slate-100">{category.description}</p>
        )}
      </div>
    </div>
  );
}
