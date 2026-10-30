import { getTranslations } from "next-intl/server";
import ReviewForm from "@/components/ReviewForm";

export async function generateMetadata({
  params,
}: PageProps<"/[locale]/avis/[reference]">) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "reviewForm" });
  // Private link sent to a single customer: keep it out of search engines.
  return { title: t("title"), robots: { index: false, follow: false } };
}

export default async function ReviewPage({
  params,
  searchParams,
}: PageProps<"/[locale]/avis/[reference]">) {
  const { reference } = await params;
  const { t } = await searchParams;

  return (
    <div className="mx-auto max-w-2xl px-4 py-12">
      <ReviewForm reference={reference} reviewToken={typeof t === "string" ? t : ""} />
    </div>
  );
}
