import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import type { Category } from "@/lib/types";

export default async function CategorySidebar({
  locale,
  basePath,
  activeCategory,
  categories,
}: {
  locale: string;
  basePath: "/" | "/catalogue";
  activeCategory?: string;
  categories: Category[];
}) {
  const t = await getTranslations({ locale, namespace: "catalogue" });

  return (
    <nav
      aria-label={t("allCategories")}
      className="flex gap-2 overflow-x-auto pb-2 lg:sticky lg:top-24 lg:w-56 lg:shrink-0 lg:flex-col lg:self-start lg:overflow-visible lg:pb-0"
    >
      <Link
        href={{ pathname: basePath, query: {} }}
        className={`shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition ${
          !activeCategory ? "bg-brand-navy text-white" : "text-slate-600 hover:bg-slate-100"
        }`}
      >
        {t("allCategories")}
      </Link>
      {categories.map((cat) => (
        <Link
          key={cat.id}
          href={{ pathname: basePath, query: { category: cat.slug } }}
          className={`shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition ${
            activeCategory === cat.slug ? "bg-brand-navy text-white" : "text-slate-600 hover:bg-slate-100"
          }`}
        >
          {cat.name}
        </Link>
      ))}
    </nav>
  );
}
