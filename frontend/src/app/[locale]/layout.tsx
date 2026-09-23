import type { Metadata } from "next";
import { NextIntlClientProvider, hasLocale } from "next-intl";
import { notFound } from "next/navigation";
import { Archivo_Black, Geist_Mono, Inter } from "next/font/google";
import { routing } from "@/i18n/routing";
import { CartProvider } from "@/lib/cart-context";
import { AuthProvider } from "@/lib/auth-context";
import { AuthGateProvider } from "@/lib/auth-gate-context";
import { FavoritesProvider } from "@/lib/favorites-context";
import { COMPANY_NAME } from "@/lib/config";
import "../globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
});

const archivoBlack = Archivo_Black({
  variable: "--font-archivo-black",
  weight: "400",
  subsets: ["latin"],
});

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
      className={`${inter.variable} ${archivoBlack.variable} ${geistMono.variable} h-full antialiased`}
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
