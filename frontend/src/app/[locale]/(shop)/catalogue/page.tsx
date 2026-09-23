import { getTranslations } from "next-intl/server";
import CatalogueBrowser from "@/components/CatalogueBrowser";
import Breadcrumbs from "@/components/Breadcrumbs";

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
      <h1 className="mb-8 text-3xl font-bold text-brand-navy">{t("title")}</h1>

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
