import { getTranslations } from "next-intl/server";
import { getCategories, getItems } from "@/lib/api";
import CategorySidebar from "./CategorySidebar";
import CategoryHeroBanner from "./CategoryHeroBanner";
import CatalogueFilters from "./CatalogueFilters";
import ItemCard from "./ItemCard";

export default async function CatalogueBrowser({
  locale,
  basePath,
  category,
  search,
  sort,
  minPrice,
  maxPrice,
}: {
  locale: string;
  basePath: "/" | "/catalogue";
  category?: string;
  search?: string;
  sort?: string;
  minPrice?: string;
  maxPrice?: string;
}) {
  const t = await getTranslations({ locale, namespace: "catalogue" });
  const categories = await getCategories(locale).catch(() => []);
  const activeCategory = category ? categories.find((c) => c.slug === category) : undefined;

  let items: Awaited<ReturnType<typeof getItems>> = [];
  let loadError = false;
  try {
    items = await getItems(locale, {
      category,
      search,
      sort: sort as never,
      min_price: minPrice,
      max_price: maxPrice,
    });
  } catch {
    loadError = true;
  }

  return (
    <div>
      {activeCategory && <CategoryHeroBanner locale={locale} category={activeCategory} />}

      <div className="flex flex-col gap-8 lg:flex-row">
        <CategorySidebar locale={locale} basePath={basePath} activeCategory={category} categories={categories} />

        <div className="min-w-0 flex-1">
          <CatalogueFilters category={category} />

          {loadError && (
            <p className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
              {t("loadError")}
            </p>
          )}

          {!loadError && items.length === 0 && <p className="text-slate-500">{t("empty")}</p>}

          <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 xl:grid-cols-4">
            {items.map((item) => (
              <ItemCard key={item.id} item={item} />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
