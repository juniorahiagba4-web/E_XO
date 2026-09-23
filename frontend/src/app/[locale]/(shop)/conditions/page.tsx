import { getTranslations } from "next-intl/server";

type LegalSection = { heading: string; body: string };

export default async function TermsOfServicePage({
  params,
}: PageProps<"/[locale]/conditions">) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "legal.terms" });
  const sections = t.raw("sections") as LegalSection[];

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="mb-2 text-3xl font-bold text-brand-navy">{t("title")}</h1>
      <p className="mb-10 text-sm text-slate-400">{t("updated")}</p>

      <div className="flex flex-col gap-8">
        {sections.map((section) => (
          <div key={section.heading}>
            <h2 className="mb-2 text-lg font-semibold text-brand-navy">{section.heading}</h2>
            <p className="text-slate-500">{section.body}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
