import { notFound } from "next/navigation";
import { getTranslations } from "next-intl/server";
import { getItem, ApiError } from "@/lib/api";
import AvailabilityAndQuote from "@/components/AvailabilityAndQuote";
import RatingStars from "@/components/RatingStars";
import ItemGallery from "@/components/ItemGallery";
import ItemTabs from "@/components/ItemTabs";
import ItemCard from "@/components/ItemCard";
import Breadcrumbs from "@/components/Breadcrumbs";

export default async function ProductPage({
  params,
}: PageProps<"/[locale]/catalogue/[slug]">) {
  const { locale, slug } = await params;
  const t = await getTranslations({ locale, namespace: "product" });
  const tNav = await getTranslations({ locale, namespace: "nav" });
  const tCatalogue = await getTranslations({ locale, namespace: "catalogue" });

  let item;
  let related;
  try {
    ({ item, related } = await getItem(locale, slug));
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      notFound();
    }
    throw err;
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <Breadcrumbs
        items={[
          { label: tNav("home"), href: "/" },
          { label: tCatalogue("title"), href: "/catalogue" },
          ...(item.category
            ? [{ label: item.category.name, href: `/catalogue?category=${item.category.slug}` }]
            : []),
          { label: item.name },
        ]}
      />

      <div className="mt-6 grid gap-10 sm:grid-cols-2 sm:items-start">
        <div className="sm:sticky sm:top-24">
          <ItemGallery item={item} />
        </div>

        <div>
          <h1 className="text-3xl font-bold text-brand-navy">{item.name}</h1>

          {item.rating && (
            <div className="mt-2">
              <RatingStars rating={Number(item.rating)} count={item.rating_count} size="md" />
            </div>
          )}

          <p className="mt-2 text-xl text-brand-gold-dark">{item.rental_price_per_day} / jour</p>
          {item.sale_price && (
            <p className="mt-1 text-slate-600">{t("orBuyFor")} {item.sale_price} XOF</p>
          )}

          <p className={`mt-2 text-sm font-medium ${item.total_stock > 0 ? "text-emerald-600" : "text-red-500"}`}>
            {item.total_stock > 0
              ? t("stockAvailable", { count: item.total_stock })
              : t("stockUnavailable")}
          </p>

          <div className="mt-6">
            <AvailabilityAndQuote item={item} locale={locale} />
          </div>
        </div>
      </div>

      <ItemTabs item={item} />

      {related.length > 0 && (
        <div className="mt-16">
          <h2 className="mb-6 text-xl font-semibold text-brand-navy">{t("youMayAlsoLike")}</h2>
          <div className="grid grid-cols-2 gap-6 sm:grid-cols-4">
            {related.map((r) => (
              <ItemCard key={r.id} item={r} />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
