import { cache } from "react";
import { notFound } from "next/navigation";
import { getTranslations } from "next-intl/server";
import { getItem, ApiError } from "@/lib/api";
import AvailabilityAndQuote from "@/components/AvailabilityAndQuote";
import RatingStars from "@/components/RatingStars";
import ItemGallery from "@/components/ItemGallery";
import ItemTabs from "@/components/ItemTabs";
import ItemCard from "@/components/ItemCard";
import Breadcrumbs from "@/components/Breadcrumbs";
import JsonLd from "@/components/JsonLd";
import {
  absoluteUrl,
  breadcrumbJsonLd,
  excerpt,
  formatFcfa,
  pageMetadata,
  productJsonLd,
} from "@/lib/seo";
import type { Item } from "@/lib/types";

// One API call shared by the page and its metadata.
const loadItem = cache((locale: string, slug: string) => getItem(locale, slug));

function seoTexts(item: Item, t: Awaited<ReturnType<typeof getTranslations<"seo">>>) {
  const name = item.name.trim();
  const forSale = Boolean(item.sale_price || item.sale_price_on_request);
  const price = formatFcfa(item.rental_price_per_day);
  const generated = t("itemDescription", { name, price });
  const shortTail = t("itemDescriptionTail", { price });
  // Some names already say "Location & Achat ... à Lomé": no need to repeat it.
  const nameSaysItAll = /locat|lom[ée]|rental|achat|vente/i.test(name);
  // A short description from the admin is completed with the price and the city.
  const own = excerpt(item.description);
  const description =
    own.length >= 90 ? own : own ? excerpt(`${own.replace(/[.!…\s]+$/, "")}. ${shortTail}`) : generated;

  return {
    title: nameSaysItAll ? name : t(forSale ? "itemTitleBoth" : "itemTitleRental", { name }),
    description,
  };
}

export async function generateMetadata({ params }: PageProps<"/[locale]/catalogue/[slug]">) {
  const { locale, slug } = await params;
  const t = await getTranslations({ locale, namespace: "seo" });
  try {
    const { item } = await loadItem(locale, slug);
    const { title, description } = seoTexts(item, t);
    return pageMetadata({
      locale,
      path: `/catalogue/${item.slug}`,
      title,
      description,
      image: item.image_url,
    });
  } catch {
    return {};
  }
}

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
    ({ item, related } = await loadItem(locale, slug));
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      notFound();
    }
    throw err;
  }

  const tSeo = await getTranslations({ locale, namespace: "seo" });
  const { description: seoDescription } = seoTexts(item, tSeo);
  const trail = [
    { name: tNav("home"), url: absoluteUrl(locale) },
    { name: tCatalogue("title"), url: absoluteUrl(locale, "/catalogue") },
    ...(item.category
      ? [
          {
            name: item.category.name,
            url: absoluteUrl(locale, `/catalogue?category=${item.category.slug}`),
          },
        ]
      : []),
    { name: item.name, url: absoluteUrl(locale, `/catalogue/${item.slug}`) },
  ];

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <JsonLd data={[productJsonLd(locale, item, seoDescription), breadcrumbJsonLd(trail)]} />
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
          <h1 className="heading-page">{item.name}</h1>

          {item.rating && (
            <div className="mt-2">
              <RatingStars rating={Number(item.rating)} count={item.rating_count} size="md" />
            </div>
          )}

          <div className="mt-2">
            <AvailabilityAndQuote item={item} locale={locale} />
          </div>
        </div>
      </div>

      <ItemTabs item={item} />

      {related.length > 0 && (
        <div className="mt-16">
          <h2 className="mb-6 heading-section">{t("youMayAlsoLike")}</h2>
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
