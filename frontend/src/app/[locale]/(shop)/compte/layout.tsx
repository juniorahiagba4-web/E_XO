import { getTranslations } from "next-intl/server";
import { PRIVATE_PAGE } from "@/lib/seo";

// Personal page (one visitor's cart, account...): kept out of search engines.
export async function generateMetadata({ params }: LayoutProps<"/[locale]/compte">) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "account" });
  return { title: t("title"), ...PRIVATE_PAGE };
}

export default function Layout({ children }: { children: React.ReactNode }) {
  return children;
}
