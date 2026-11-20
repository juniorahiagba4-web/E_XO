import { getTranslations } from "next-intl/server";
import CatalogueBrowser from "@/components/CatalogueBrowser";
import Breadcrumbs from "@/components/Breadcrumbs";
import { getCategories } from "@/lib/api";
import { pageMetadata } from "@/lib/seo";

export async function generateMetadata({ params, searchParams }: PageProps<"/[locale]/catalogue">) {
  const { locale } = await params;
  const { category } = await searchParams;
  const t = await getTranslations({ locale, namespace: "seo" });

  // A category ("Chaises", "Glacières"...) is its own page for Google. Search,
  // sort and price filters are not: they point back to the catalogue.
  const slug = typeof category === "string" ? category : undefined;
  const current = slug
    ? (await getCategories(locale).catch(() => [])).find((c) => c.slug === slug)
    : undefined;

  if (current) {
    return pageMetadata({
      locale,
      path: `/catalogue?category=${encodeURIComponent(current.slug)}`,
      title: t("categoryTitle", { category: current.name }),
      description: current.description?.trim() || t("categoryDescription", { category: current.name }),
      image: current.image_url,
    });
  }

  return pageMetadata({
    locale,
    path: "/catalogue",
    title: t("catalogueTitle"),
    description: t("catalogueDescription"),
  });
}

export default async function CataloguePage({
  params,
  searchParams,
}: PageProps<"/[locale]/catalogue">) {
  const { locale } = await params;
  const { category, search, sort, min_price, max_price } = await searchParams;
  const t = await getTranslations({ locale, namespace: "catalogue" });
  const tNav = await getTranslations({ locale, namespace: "nav" });

  return (
    <div className="mx-auto max-w-6xl px-4 py-12">
      <Breadcrumbs items={[{ label: tNav("home"), href: "/" }, { label: t("title") }]} />
      <h1 className="mb-8 heading-page">{t("title")}</h1>

      <CatalogueBrowser
        locale={locale}
        basePath="/catalogue"
        category={typeof category === "string" ? category : undefined}
        search={typeof search === "string" ? search : undefined}
        sort={typeof sort === "string" ? sort : undefined}
        minPrice={typeof min_price === "string" ? min_price : undefined}
        maxPrice={typeof max_price === "string" ? max_price : undefined}
      />
    </div>
  );
}
