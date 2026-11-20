import { getTranslations } from "next-intl/server";
import { PRIVATE_PAGE } from "@/lib/seo";

// Personal page (one visitor's cart, account...): kept out of search engines.
export async function generateMetadata({ params }: LayoutProps<"/[locale]/connexion">) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "auth" });
  return { title: t("loginTitle"), ...PRIVATE_PAGE };
}

export default function Layout({ children }: { children: React.ReactNode }) {
  return children;
}
