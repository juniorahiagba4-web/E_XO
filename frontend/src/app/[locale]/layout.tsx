import type { Metadata } from "next";
import { NextIntlClientProvider, hasLocale } from "next-intl";
import { notFound } from "next/navigation";
import { Geist_Mono } from "next/font/google";
import { routing } from "@/i18n/routing";
import { CartProvider } from "@/lib/cart-context";
import { AuthProvider } from "@/lib/auth-context";
import { AuthGateProvider } from "@/lib/auth-gate-context";
import { FavoritesProvider } from "@/lib/favorites-context";
import { COMPANY_NAME } from "@/lib/config";
import "../globals.css";

// Helvetica itself isn't a licensable web font, so the whole site uses the
// standard Helvetica Neue / Arial system-font stack (defined in globals.css
// as --font-sans / --font-display) instead of a next/font Google import —
// only the monospace reference-code font still comes from next/font.
const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: `${COMPANY_NAME} - Location de mobilier événementiel`,
  description:
    "Location et vente de chaises, tables, nappes, glacières pour vos événements au Togo et dans la sous-région.",
};

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export default async function LocaleLayout({
  children,
  params,
}: LayoutProps<"/[locale]">) {
  const { locale } = await params;

  if (!hasLocale(routing.locales, locale)) {
    notFound();
  }

  return (
    <html
      lang={locale}
      className={`${geistMono.variable} h-full antialiased`}
      suppressHydrationWarning
    >
      <body className="flex min-h-full flex-col bg-white text-brand-navy font-sans">
        <NextIntlClientProvider>
          <AuthProvider>
            <AuthGateProvider>
              <FavoritesProvider>
                <CartProvider>{children}</CartProvider>
              </FavoritesProvider>
            </AuthGateProvider>
          </AuthProvider>
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
